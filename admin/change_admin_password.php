 <?php
// change_admin_password_secure.php - Смена пароля с подтверждением
require_once 'config/database.php';

// Функция для безопасного вывода
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_login = trim($_POST['admin'] ?? 'admin');
    $new_password = $_POST['admin123'] ?? '';
    $confirm_password = $_POST['admin123'] ?? '';
    
    if (empty($new_password)) {
        $error = 'Введите новый пароль';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($new_password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $conn = getConnection();
        
        // Проверяем существование пользователя
        $check = $conn->prepare("SELECT id, username, full_name, role FROM staff WHERE username = ?");
        $check->bind_param("s", $admin_login);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Пользователь '{$admin_login}' не найден";
        } else {
            $user = $result->fetch_assoc();
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update = $conn->prepare("UPDATE staff SET password_hash = ? WHERE username = ?");
            $update->bind_param("ss", $new_hash, $admin_login);
            
            if ($update->execute()) {
                $message = "Пароль для пользователя <strong>{$admin_login}</strong> успешно изменен!";
            } else {
                $error = "Ошибка при обновлении: " . $conn->error;
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
    <title>Смена пароля администратора</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5a67d8;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #e7f3ff;
            color: #004085;
            border: 1px solid #b8daff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Смена пароля администратора</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
            <div class="login-link">
                <a href="login.php">➡️ Перейти на страницу входа</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
            <form method="post">
                <div class="form-group">
                    <label>Логин администратора:</label>
                    <input type="text" name="username" value="admin" readonly 
                           style="background: #f5f5f5; cursor: not-allowed;">
                    <small style="color: #666;">Логин администратора по умолчанию: admin</small>
                </div>
                
                <div class="form-group">
                    <label>Новый пароль:</label>
                    <input type="password" name="new_password" required minlength="6"
                           placeholder="Введите новый пароль">
                </div>
                
                <div class="form-group">
                    <label>Подтвердите пароль:</label>
                    <input type="password" name="confirm_password" required
                           placeholder="Введите пароль еще раз">
                </div>
                
                <button type="submit">🔑 Сменить пароль</button>
            </form>
            
            <div class="info">
                <strong>ℹ️ Информация:</strong><br>
                • Этот скрипт изменяет пароль ТОЛЬКО для пользователя <strong>admin</strong><br>
                • Все остальные данные пользователя сохраняются<br>
                • После смены пароля вы сможете войти с новым паролем
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
