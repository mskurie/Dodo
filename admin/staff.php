<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$conn = getConnection();
$message = '';
$error = '';

// Обработка смены пароля сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE staff SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Пароль успешно изменен';
        } else {
            $error = 'Ошибка при смене пароля';
        }
    }
}

// Получаем список сотрудников
$staff = $conn->query("
    SELECT id, username, full_name, email, phone, role, is_active, created_at 
    FROM staff 
    ORDER BY role, full_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление сотрудниками · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
    <style>
        .password-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .password-modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 40px;
            padding: 40px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            animation: modalPop 0.3s;
        }
        
        @keyframes modalPop {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-content h2 {
            color: #E31E24;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .modal-content .form-group {
            margin-bottom: 20px;
        }
        
        .modal-content label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .modal-content input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #E0E0E0;
            border-radius: 60px;
            font-size: 1rem;
        }
        
        .modal-content input:focus {
            outline: none;
            border-color: #E31E24;
        }
        
        .modal-content .btn {
            width: 100%;
            padding: 16px;
            background: #E31E24;
            color: white;
            border: none;
            border-radius: 60px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .modal-content .btn:hover {
            background: #C8102E;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #757575;
        }
        
        .modal-close:hover {
            color: #E31E24;
        }
        
        .action-btn-small {
            padding: 6px 12px;
            background: #FFC107;
            color: #1A1A1A;
            border: none;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0 3px;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn-small:hover {
            background: #E0A800;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .role-admin { background: #E31E24; color: white; }
        .role-manager { background: #FFC107; color: #1A1A1A; }
        .role-waiter { background: #28A745; color: white; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active { background: #28A745; color: white; }
        .status-inactive { background: #6C757D; color: white; }
        
        .footer {
            background: white;
            padding: 40px 0;
            margin-top: 60px;
            text-align: center;
            color: #757575;
            border-top: 1px solid #E0E0E0;
        }
    </style>
</head>
<body>
    <nav class="dodo-navbar">
        <div class="container navbar-container">
            <a href="dashboard.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="navbar-menu">
                <a href="dashboard.php" class="navbar-link">Главная</a>
                <a href="reservations.php" class="navbar-link">Бронирования</a>
                <a href="orders.php" class="navbar-link">Заказы</a>
                <a href="menu.php" class="navbar-link">Меню</a>
                <a href="reports.php" class="navbar-link">Отчеты</a>
                <a href="staff.php" class="navbar-link active">Сотрудники</a>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="user-role">Администратор</span>
                <a href="profile.php" class="nav-link">👤 Профиль</a>
                <a href="../logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>Управление сотрудниками</h1>
            <p>Администрирование персонала и смена паролей</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="card-title" style="margin-bottom: 0;">Список сотрудников</h2>
                <!-- ИСПРАВЛЕННАЯ ССЫЛКА -->
                <a href="add_staff.php" class="btn btn-sm">➕ Добавить сотрудника</a>
            </div>
            
            <?php if (empty($staff)): ?>
                <p>Нет сотрудников</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="dodo-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Логин</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Роль</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $s): ?>
                            <tr>
                                <td><?php echo $s['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($s['full_name'] ?: '-'); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['username']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['phone'] ?: '-'); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $s['role']; ?>">
                                        <?php 
                                        switch($s['role']) {
                                            case 'admin': echo 'Администратор'; break;
                                            case 'manager': echo 'Менеджер'; break;
                                            case 'waiter': echo 'Официант'; break;
                                            default: echo $s['role'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $s['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $s['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button class="action-btn-small" onclick="openPasswordModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['username']); ?>')">
                                            🔑 Сменить пароль
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Модальное окно смены пароля -->
    <div id="passwordModal" class="password-modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePasswordModal()">&times;</span>
            <h2>🔐 Смена пароля</h2>
            
            <form method="post" id="passwordForm">
                <input type="hidden" name="user_id" id="modal_user_id" value="">
                
                <div class="form-group">
                    <label>Сотрудник</label>
                    <input type="text" id="modal_username" class="form-control" readonly style="background: #F8F8F8;">
                </div>
                
                <div class="form-group">
                    <label>Новый пароль</label>
                    <input type="password" name="new_password" required minlength="6">
                    <small style="color: #757575;">Минимум 6 символов</small>
                </div>
                
                <div class="form-group">
                    <label>Подтверждение пароля</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="change_password" class="btn">
                    ✅ Сохранить новый пароль
                </button>
            </form>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца · Управление сотрудниками</p>
        </div>
    </div>

    <script>
        function openPasswordModal(userId, username) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_username').value = username;
            document.getElementById('passwordModal').classList.add('active');
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
        }
        
        // Закрытие по клику вне модального окна
        window.onclick = function(event) {
            var modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>