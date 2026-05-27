<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/client_auth.php';

$clientAuth = new ClientAuth();

if ($clientAuth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $result = $clientAuth->register($name, $email, $phone, $password);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация · Додо Пицца</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #F8F8F8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background: white;
            border-radius: 24px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
        }

        .logo {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 800;
            color: #E31E24;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .logo span {
            font-weight: 300;
            font-size: 1rem;
            color: #757575;
            margin-left: 5px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #1A1A1A;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1A1A1A;
            font-size: 0.95rem;
        }

        input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #E0E0E0;
            background: #ffffff;
            color: #1A1A1A;
            font-size: 1rem;
            border-radius: 12px;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #E31E24;
            box-shadow: 0 0 0 3px rgba(227, 30, 36, 0.1);
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: #E31E24;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn:hover {
            background: #C8102E;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 30, 36, 0.3);
        }

        .error {
            color: #C62828;
            text-align: center;
            margin-bottom: 20px;
            background: #FFEBEE;
            padding: 15px;
            border: 1px solid #FFCDD2;
            border-radius: 12px;
            font-weight: 500;
        }

        .success {
            color: #2E7D32;
            text-align: center;
            margin-bottom: 20px;
            background: #E8F5E9;
            padding: 15px;
            border: 1px solid #C8E6C9;
            border-radius: 12px;
            font-weight: 500;
        }

        .success a {
            color: #1B5E20;
            text-decoration: underline;
        }

        .links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #E0E0E0;
        }

        .links a {
            color: #E31E24;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .pizza-icon {
            font-size: 4rem;
            display: block;
            text-align: center;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <span class="pizza-icon">🍕</span>
        <div class="logo">ДОДО<span>пицца</span></div>
        <h1>Регистрация</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?> <a href="login.php">Войти</a></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Имя *</label>
                <input type="text" name="name" required placeholder="Иван Иванов">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="name@example.com">
            </div>
            <div class="form-group">
                <label>Телефон *</label>
                <input type="tel" name="phone" required placeholder="+7 (999) 000-00-00">
            </div>
            <div class="form-group">
                <label>Пароль *</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Подтверждение пароля *</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>
        <div class="links">
            <a href="login.php">Уже есть аккаунт? Войти</a><br>
            <a href="index.php">← На главную</a>
        </div>
    </div>
</body>
</html>
