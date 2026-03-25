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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'waiter';
    
    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните обязательные поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } else {
        // Проверка на существующего пользователя
        $check = $conn->prepare("SELECT id FROM staff WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO staff (username, email, password_hash, full_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssss", $username, $email, $password_hash, $full_name, $phone, $role);
            
            if ($stmt->execute()) {
                $message = 'Сотрудник успешно добавлен';
            } else {
                $error = 'Ошибка: ' . $conn->error;
            }
        }
    }
}

// Получаем список сотрудников для выпадающего списка ролей
$roles = ['admin' => 'Администратор', 'manager' => 'Менеджер', 'waiter' => 'Официант'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление сотрудника · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
    <style>
        .add-form {
            max-width: 600px;
            margin: 0 auto;
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
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23757575' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
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
            text-decoration: none;
        }
        
        .btn:hover {
            background: #C8102E;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
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
        
        .button-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
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
            <h1>Добавление нового сотрудника</h1>
            <p>Создание учетной записи для персонала</p>
        </div>
    </div>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" class="add-form">
                <div class="form-group">
                    <label>Логин *</label>
                    <input type="text" name="username" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Полное имя</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="+7 (999) 123-45-67">
                </div>
                
                <div class="form-group">
                    <label>Роль *</label>
                    <select name="role" class="form-control" required>
                        <?php foreach ($roles as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($_POST['role'] ?? 'waiter') == $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Пароль *</label>
                    <input type="password" name="password" class="form-control" required>
                    <small style="color: #757575;">Минимум 6 символов</small>
                </div>
                
                <div class="form-group">
                    <label>Подтверждение пароля *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <div class="button-group">
                    <a href="staff.php" class="btn btn-secondary">← Назад</a>
                    <button type="submit" class="btn">➕ Добавить сотрудника</button>
                </div>
            </form>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца · Управление сотрудниками</p>
        </div>
    </div>
</body>
</html>
