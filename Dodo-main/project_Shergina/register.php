<?php
// register.php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } else {
        $conn = getConnection();
        $check = $conn->prepare("SELECT id FROM staff WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = 'Пользователь уже существует';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'waiter';
            $stmt = $conn->prepare("INSERT INTO staff (username, email, password_hash, full_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssss", $username, $email, $hash, $full_name, $phone, $role);

            if ($stmt->execute()) {
                $success = 'Регистрация успешна!';
                $_POST = [];
            } else {
                $error = 'Ошибка: ' . $conn->error;
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
    <title>Регистрация · Додо Пицца</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card { background: white; border-radius: 40px; padding: 50px 40px; max-width: 500px; width: 100%; box-shadow: 0 30px 60px rgba(0,0,0,0.3); }
        .auth-logo { text-align: center; margin-bottom: 30px; }
        .auth-logo .dodo { font-size: 2.5rem; font-weight: 800; color: #E31E24; }
        .auth-title { font-size: 1.8rem; font-weight: 700; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control { width: 100%; padding: 14px 20px; border: 2px solid #E0E0E0; border-radius: 60px; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: #E31E24; }
        .btn { width: 100%; padding: 16px; background: #E31E24; color: white; border: none; border-radius: 60px; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #C8102E; }
        .alert { padding: 15px 20px; border-radius: 60px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #FFEBEE; color: #C62828; }
        .alert-success { background: #E8F5E9; color: #2E7D32; }
        .auth-links { text-align: center; margin-top: 25px; }
        .auth-links a { color: #E31E24; text-decoration: none; }
        .back-link { display: inline-block; margin-top: 15px; color: #757575; text-decoration: none; }
        small { color: #757575; font-size: 0.85rem; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo"><span class="dodo">ДОДО</span></div>
        <h1 class="auth-title">Регистрация сотрудника</h1>

        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?> <a href="login.php">Войти</a></div><?php endif; ?>

        <form method="post">
            <div class="form-group"><label>Логин *</label><input type="text" name="username" class="form-control" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label>Полное имя</label><input type="text" name="full_name" class="form-control"></div>
            <div class="form-group"><label>Телефон</label><input type="text" name="phone" class="form-control"></div>
            <div class="form-group"><label>Пароль *</label><input type="password" name="password" class="form-control" required><small>минимум 6 символов</small></div>
            <div class="form-group"><label>Подтверждение *</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>

        <div class="auth-links"><a href="login.php">Уже есть аккаунт?</a> · <a href="index.php">На главную</a></div>
    </div>
</body>
</html>
