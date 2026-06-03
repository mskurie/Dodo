<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../includes/client_auth.php';

$clientAuth = new ClientAuth();

// Если пользователь подтвердил выход
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $clientAuth->logout();
    header('Location: index.php');
    exit();
}

// Если пользователь уже не авторизован
if (!$clientAuth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$currentClient = $clientAuth->getCurrentClient();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выход · Додо Пицца</title>
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
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .logout-card {
            background: white;
            border-radius: 40px;
            padding: 50px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            color: #E31E24;
            margin-bottom: 20px;
        }
        
        .logo span {
            font-weight: 300;
            font-size: 1rem;
            color: #757575;
            display: block;
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            border: 4px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1A1A1A;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-size: 1.2rem;
            color: #E31E24;
            font-weight: 600;
            margin-bottom: 30px;
        }
        
        .message {
            color: #757575;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #E31E24;
            color: white;
            box-shadow: 0 4px 12px rgba(227,30,36,0.3);
        }
        
        .btn-primary:hover {
            background: #C8102E;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(227,30,36,0.4);
        }
        
        .btn-secondary {
            background: #F8F8F8;
            color: #757575;
            border: 2px solid #E0E0E0;
        }
        
        .btn-secondary:hover {
            background: #E0E0E0;
            color: #1A1A1A;
        }
        
        .auth-links {
            border-top: 1px solid #E0E0E0;
            padding-top: 30px;
        }
        
        .auth-links h3 {
            font-size: 1.1rem;
            color: #1A1A1A;
            margin-bottom: 15px;
        }
        
        .auth-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .auth-btn {
            padding: 12px 25px;
            border-radius: 60px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
        }
        
        .auth-btn-login {
            background: #E31E24;
            color: white;
        }
        
        .auth-btn-login:hover {
            background: #C8102E;
            transform: translateY(-2px);
        }
        
        .auth-btn-register {
            background: #1A1A1A;
            color: white;
        }
        
        .auth-btn-register:hover {
            background: #333;
            transform: translateY(-2px);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #757575;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #E31E24;
        }
        
        .pizza-animation {
            font-size: 3rem;
            animation: spin 10s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="pizza-animation">🍕</div>
        
        <div class="avatar">
            <?php echo strtoupper(substr($currentClient['name'], 0, 1)); ?>
        </div>
        
        <h1>До свидания,</h1>
        <div class="user-name"><?php echo htmlspecialchars($currentClient['name']); ?>!</div>
        
        <div class="message">
            Спасибо, что были с нами!<br>
            Ждем вас снова в Додо Пицца
        </div>
        
        <div class="button-group">
            <a href="?confirm=yes" class="btn btn-primary">✅ Подтвердить выход</a>
            <a href="index.php" class="btn btn-secondary">↩️ Остаться</a>
        </div>
        
        <div class="auth-links">
            <h3>Вход и регистрация</h3>
            <div class="auth-buttons">
                <a href="login.php" class="auth-btn auth-btn-login">🔑 Войти</a>
                <a href="register.php" class="auth-btn auth-btn-register">📝 Регистрация</a>
            </div>
        </div>
        
        <a href="index.php" class="back-link">← На главную</a>
    </div>
</body>
</html>
