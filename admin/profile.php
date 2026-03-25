 <?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = $auth->getUser();
$conn = getConnection();

// Получаем полную информацию о сотруднике
$stmt = $conn->prepare("
    SELECT id, username, email, full_name, phone, role, is_active, created_at, last_login, avatar 
    FROM staff WHERE id = ?
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

$message = '';
$error = '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($full_name) || empty($email)) {
            $error = 'Имя и Email обязательны для заполнения';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный email';
        } else {
            // Проверяем, не занят ли email другим пользователем
            $check = $conn->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $user['id']);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'Этот email уже используется другим сотрудником';
            } else {
                $update = $conn->prepare("UPDATE staff SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $update->bind_param("sssi", $full_name, $email, $phone, $user['id']);
                
                if ($update->execute()) {
                    // Обновляем сессию
                    $_SESSION['full_name'] = $full_name;
                    $message = 'Профиль успешно обновлен';
                    // Обновляем данные сотрудника
                    $employee['full_name'] = $full_name;
                    $employee['email'] = $email;
                    $employee['phone'] = $phone;
                } else {
                    $error = 'Ошибка при обновлении профиля';
                }
            }
        }
    }
    
    // Обработка смены пароля
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        // Получаем текущий хеш пароля
        $pass_stmt = $conn->prepare("SELECT password_hash FROM staff WHERE id = ?");
        $pass_stmt->bind_param("i", $user['id']);
        $pass_stmt->execute();
        $current_hash = $pass_stmt->get_result()->fetch_assoc()['password_hash'];
        
        if (!password_verify($current, $current_hash)) {
            $error = 'Текущий пароль неверен';
        } elseif (strlen($new) < 6) {
            $error = 'Новый пароль должен быть не менее 6 символов';
        } elseif ($new !== $confirm) {
            $error = 'Новый пароль и подтверждение не совпадают';
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE staff SET password_hash = ? WHERE id = ?");
            $update->bind_param("si", $new_hash, $user['id']);
            
            if ($update->execute()) {
                $message = 'Пароль успешно изменен';
            } else {
                $error = 'Ошибка при смене пароля';
            }
        }
    }
    
    // Обработка загрузки аватара
    if (isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2 MB
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Разрешены только JPG, PNG, GIF и WEBP форматы';
            } elseif ($file['size'] > $max_size) {
                $error = 'Максимальный размер файла 2 МБ';
            } else {
                $upload_dir = '../uploads/avatars/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Удаляем старый аватар
                if (!empty($employee['avatar']) && file_exists($upload_dir . $employee['avatar'])) {
                    unlink($upload_dir . $employee['avatar']);
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $update = $conn->prepare("UPDATE staff SET avatar = ? WHERE id = ?");
                    $update->bind_param("si", $filename, $user['id']);
                    
                    if ($update->execute()) {
                        $employee['avatar'] = $filename;
                        $message = 'Аватар успешно загружен';
                    } else {
                        $error = 'Ошибка при сохранении аватара';
                    }
                } else {
                    $error = 'Ошибка при загрузке файла';
                }
            }
        }
    }
    
    // Удаление аватара
    if (isset($_POST['delete_avatar'])) {
        if (!empty($employee['avatar'])) {
            $upload_dir = '../uploads/avatars/';
            if (file_exists($upload_dir . $employee['avatar'])) {
                unlink($upload_dir . $employee['avatar']);
            }
            
            $update = $conn->prepare("UPDATE staff SET avatar = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            
            if ($update->execute()) {
                $employee['avatar'] = null;
                $message = 'Аватар удален';
            } else {
                $error = 'Ошибка при удалении аватара';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #F8F8F8;
        }
        
        .navbar {
            background-color: #E31E24;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }
        
        .logo span {
            font-weight: 300;
            font-size: 1rem;
            margin-left: 5px;
            opacity: 0.8;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 40px;
            transition: background 0.2s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.25);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.1);
            padding: 5px 15px 5px 20px;
            border-radius: 40px;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .user-role {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .logout-btn {
            background: #1A1A1A;
            color: white;
            padding: 6px 16px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .logout-btn:hover {
            background: #000;
        }
        
        .page-header {
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin: 40px 0;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid #E0E0E0;
            text-align: center;
        }
        
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #E31E24;
            background: linear-gradient(135deg, #E31E24, #C8102E);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
        }
        
        .avatar-upload {
            margin-top: 15px;
        }
        
        .avatar-upload label {
            display: inline-block;
            padding: 8px 16px;
            background: #E31E24;
            color: white;
            border-radius: 40px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        
        .avatar-upload label:hover {
            background: #C8102E;
        }
        
        .avatar-upload input {
            display: none;
        }
        
        .avatar-delete {
            margin-top: 10px;
        }
        
        .avatar-delete button {
            background: none;
            border: none;
            color: #DC3545;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
        }
        
        .avatar-delete button:hover {
            color: #C82333;
        }
        
        .employee-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .employee-role {
            display: inline-block;
            padding: 4px 12px;
            background: #E31E24;
            color: white;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .employee-meta {
            text-align: left;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #E0E0E0;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .meta-label {
            color: #757575;
        }
        
        .meta-value {
            font-weight: 600;
        }
        
        .profile-content {
            background: white;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid #E0E0E0;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #E0E0E0;
            padding-bottom: 10px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: #757575;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #E31E24;
        }
        
        .tab.active {
            color: #E31E24;
            border-bottom: 2px solid #E31E24;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1A1A1A;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E0E0E0;
            border-radius: 60px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #E31E24;
        }
        
        .form-control[readonly] {
            background: #F8F8F8;
            cursor: not-allowed;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #E31E24;
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #C8102E;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 60px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        
        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        
        .info-box {
            background: #F8F8F8;
            padding: 15px;
            border-radius: 20px;
            margin-bottom: 20px;
        }
        
        .footer {
            background: white;
            padding: 40px 0;
            margin-top: 60px;
            text-align: center;
            color: #757575;
            border-top: 1px solid #E0E0E0;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar .container {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">Главная</a>
                <a href="reservations.php" class="nav-link">Бронирования</a>
                <a href="orders.php" class="nav-link">Заказы</a>
                <a href="menu.php" class="nav-link">Меню</a>
                <a href="reports.php" class="nav-link">Отчеты</a>
                <?php if ($auth->isAdmin()): ?>
                <a href="staff.php" class="nav-link">Сотрудники</a>
                <?php endif; ?>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($employee['full_name'] ?: $employee['username']); ?></span>
                    <span class="user-role"><?php echo $employee['role']; ?></span>
                    <a href="../logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>Личный кабинет</h1>
            <p>Управление личной информацией и настройками</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Боковая панель с аватаром -->
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <?php if (!empty($employee['avatar']) && file_exists('../uploads/avatars/' . $employee['avatar'])): ?>
                        <img src="../uploads/avatars/<?php echo $employee['avatar']; ?>" alt="Avatar" class="avatar">
                    <?php else: ?>
                        <div class="avatar">
                            <?php echo strtoupper(substr($employee['full_name'] ?: $employee['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="avatar-upload">
                    <form method="post" enctype="multipart/form-data">
                        <label for="avatar">📷 Загрузить фото</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" onchange="this.form.submit()">
                        <input type="hidden" name="upload_avatar" value="1">
                    </form>
                </div>
                
                <?php if (!empty($employee['avatar'])): ?>
                <div class="avatar-delete">
                    <form method="post">
                        <button type="submit" name="delete_avatar" onclick="return confirm('Удалить фото?')">Удалить фото</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="employee-name"><?php echo htmlspecialchars($employee['full_name'] ?: 'Не указано'); ?></div>
                <div class="employee-role">
                    <?php 
                    switch($employee['role']) {
                        case 'admin': echo 'Администратор'; break;
                        case 'manager': echo 'Менеджер'; break;
                        case 'waiter': echo 'Официант'; break;
                        default: echo $employee['role'];
                    }
                    ?>
                </div>
                
                <div class="employee-meta">
                    <div class="meta-item">
                        <span class="meta-label">Логин:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($employee['username']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Дата регистрации:</span>
                        <span class="meta-value"><?php echo date('d.m.Y', strtotime($employee['created_at'])); ?></span>
                    </div>
                    <?php if ($employee['last_login']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Последний вход:</span>
                        <span class="meta-value"><?php echo date('d.m.Y H:i', strtotime($employee['last_login'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Основной контент -->
            <div class="profile-content">
                <div class="tabs">
                    <div class="tab active" onclick="showTab('profile')">👤 Личные данные</div>
                    <div class="tab" onclick="showTab('password')">🔐 Безопасность</div>
                </div>
                
                <!-- Вкладка с личными данными -->
                <div id="profile-tab" class="tab-content active">
                    <h2 style="margin-bottom: 20px;">Редактирование профиля</h2>
                    
                    <form method="post">
                        <div class="form-group">
                            <label>Логин (нельзя изменить)</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['username']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Полное имя *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>"
                                   placeholder="+7 (999) 123-45-67">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn">Сохранить изменения</button>
                    </form>
                </div>
                
                <!-- Вкладка с безопасностью -->
                <div id="password-tab" class="tab-content">
                    <h2 style="margin-bottom: 20px;">Смена пароля</h2>
                    
                    <div class="info-box">
                        <strong>⚠️ Важно:</strong> Пароль должен содержать минимум 6 символов
                    </div>
                    
                    <form method="post">
                        <div class="form-group">
                            <label>Текущий пароль *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Новый пароль *</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label>Подтверждение нового пароля *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn">Изменить пароль</button>
                    </form>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 1px solid #E0E0E0;">
                    
                    <h3 style="margin-bottom: 15px;">Активность аккаунта</h3>
                    <div class="info-box">
                        <p><strong>Статус:</strong> 
                            <span style="color: #28A745;">● Активен</span>
                        </p>
                        <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y', strtotime($employee['created_at'])); ?></p>
                        <?php if ($employee['last_login']): ?>
                        <p><strong>Последний вход:</strong> <?php echo date('d.m.Y H:i', strtotime($employee['last_login'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца · Личный кабинет сотрудника</p>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Убираем активный класс у всех табов
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Показываем выбранную вкладку
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Подсвечиваем нажатый таб
            event.target.classList.add('active');
        }
        
        // Предпросмотр аватара
        document.getElementById('avatar')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                // Форма отправится автоматически
            }
        });
    </script>
</body>
</html>
