<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error = '';

// Простая капча - генерируем случайное число
$captcha_num1 = rand(1, 10);
$captcha_num2 = rand(1, 10);
$captcha_result = $captcha_num1 + $captcha_num2;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_answer = trim($_POST['captcha_answer'] ?? '');
    $expected_result = intval($_POST['captcha_expected'] ?? 0);
    
    // Простая проверка капчи
    if (empty($user_answer)) {
        $error = 'Пожалуйста, решите пример';
    } elseif (intval($user_answer) !== $expected_result) {
        $error = 'Неправильный ответ! Попробуйте еще раз';
    } else {
        // Капча пройдена
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            header('Location: admin/dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
    
    // Генерируем новый пример для следующей попытки
    $captcha_num1 = rand(1, 10);
    $captcha_num2 = rand(1, 10);
    $captcha_result = $captcha_num1 + $captcha_num2;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход · Додо Пицца</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-card {
            background: white;
            border-radius: 40px;
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        }
        .auth-logo { text-align: center; margin-bottom: 30px; }
        .auth-logo .dodo { font-size: 2.5rem; font-weight: 800; color: #E31E24; }
        .auth-title { font-size: 1.8rem; font-weight: 700; text-align: center; margin-bottom: 30px; color: #1A1A1A; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1A1A1A; }
        .form-control {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #E0E0E0;
            border-radius: 60px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: #E31E24; }
        .btn {
            width: 100%;
            padding: 16px;
            background: #E31E24;
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn:hover { background: #C8102E; }
        .alert {
            padding: 15px 20px;
            background: #FFEBEE;
            color: #C62828;
            border-radius: 60px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            border: 1px solid #FFCDD2;
        }
        .auth-links { text-align: center; margin-top: 25px; }
        .auth-links a { color: #E31E24; text-decoration: none; font-weight: 600; }
        .test-credentials {
            margin-top: 30px;
            padding: 20px;
            background: #F8F8F8;
            border-radius: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
        .test-credentials p { margin: 5px 0; color: #757575; }
        .test-credentials strong { color: #E31E24; }
        .back-link { display: inline-block; margin-top: 15px; color: #757575; text-decoration: none; }
        
        /* Капча стили */
        .captcha-container {
            margin: 20px 0;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 20px;
            text-align: center;
        }
        .captcha-question {
            font-size: 2rem;
            font-weight: 800;
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 15px;
            border: 2px solid #E0E0E0;
            color: #E31E24;
        }
        .captcha-input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #E0E0E0;
            border-radius: 60px;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 600;
        }
        .captcha-input:focus {
            outline: none;
            border-color: #E31E24;
        }
        .captcha-hint {
            font-size: 0.8rem;
            color: #757575;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo"><span class="dodo">ДОДО</span></div>
        <h1 class="auth-title">Вход в систему</h1>

        <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" id="login-form">
            <div class="form-group">
                <label>Логин или Email</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <!-- Простая математическая капча -->
            <div class="captcha-container">
                <div class="captcha-question">
                    <?php echo $captcha_num1; ?> + <?php echo $captcha_num2; ?> = ?
                </div>
                <input type="text" name="captcha_answer" class="captcha-input" 
                       placeholder="Введите ответ" required autocomplete="off">
                <input type="hidden" name="captcha_expected" value="<?php echo $captcha_result; ?>">
                <div class="captcha-hint">
                    🔒 Решите пример, чтобы доказать, что вы не робот
                </div>
            </div>
            
            <button type="submit" class="btn" id="submit-btn">Войти</button>
        </form>

        <div class="auth-links">
            <a href="register.php">Регистрация</a> ·
            <a href="index.php">На главную</a>
        </div>

        <div class="test-credentials">
            <p><strong>🔐 Тестовые данные:</strong></p>
            <p>👑 Админ: <strong>admin</strong> / <strong>admin123</strong></p>
            <p>📊 Менеджер: <strong>manager</strong> / <strong>manager123</strong></p>
            <p>🍕 Официант: <strong>waiter</strong> / <strong>waiter123</strong></p>
        </div>
    </div>

    <script>
        // Простая проверка перед отправкой
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const answer = document.querySelector('input[name="captcha_answer"]').value.trim();
            const expected = document.querySelector('input[name="captcha_expected"]').value;
            
            console.log('Ответ пользователя:', answer);
            console.log('Ожидаемый ответ:', expected);
            
            if (!answer) {
                e.preventDefault();
                alert('Пожалуйста, решите пример');
                return false;
            }
            
            if (parseInt(answer) !== parseInt(expected)) {
                e.preventDefault();
                alert('Неправильный ответ! Попробуйте еще раз');
                return false;
            }
            
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Вход...';
        });
    </script>
</body>
</html>