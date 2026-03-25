<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';
require_once '../includes/client_auth.php';

$clientAuth = new ClientAuth();

if (!$clientAuth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();
$currentClient = $clientAuth->getCurrentClient();

// Получаем информацию о заказе
$order = $conn->query("
    SELECT * FROM orders 
    WHERE id = $order_id AND client_id = {$currentClient['id']}
")->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Получаем позиции заказа
$items = $conn->query("
    SELECT oi.*, d.name 
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    WHERE oi.order_id = $order_id
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client-style.css">
    <style>
        .success-page {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        
        .success-card {
            background: white;
            border-radius: 40px;
            padding: 50px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 0.5s ease infinite alternate;
        }
        
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-10px); }
        }
        
        .success-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #28A745;
            margin-bottom: 15px;
        }
        
        .success-text {
            color: #757575;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .order-number-box {
            background: #F8F8F8;
            padding: 20px;
            border-radius: 20px;
            margin: 30px 0;
        }
        
        .order-number-box h3 {
            font-size: 0.9rem;
            color: #757575;
            margin-bottom: 5px;
        }
        
        .order-number-box p {
            font-size: 1.8rem;
            font-weight: 700;
            color: #E31E24;
        }
        
        .order-details {
            text-align: left;
            margin: 30px 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E0E0E0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #E0E0E0;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .auto-redirect {
            margin-top: 20px;
            color: #757575;
            font-size: 0.9rem;
        }
        
        .countdown {
            font-weight: 600;
            color: #E31E24;
        }
    </style>
</head>
<body>
    <nav class="client-navbar">
        <div class="container navbar-container">
            <a href="index.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <span class="user-greeting">👤 <?php echo htmlspecialchars($currentClient['name']); ?></span>
            </div>
        </div>
    </nav>

    <div class="success-page">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">🍕</div>
                <h1 class="success-title">Спасибо за заказ!</h1>
                <p class="success-text">Ваш заказ успешно оформлен и передан на кухню. Мы начнем готовить сразу после подтверждения.</p>
                
                <div class="order-number-box">
                    <h3>Номер вашего заказа</h3>
                    <p><?php echo $order['order_number']; ?></p>
                </div>
                
                <div class="order-details">
                    <h3 style="margin-bottom: 15px;">Детали заказа</h3>
                    
                    <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                            <span style="font-weight: 600;"><?php echo number_format($item['total_price'], 0); ?> ₽</span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="total-row">
                        <span>Итого</span>
                        <span><?php echo number_format($order['final_amount'], 0); ?> ₽</span>
                    </div>
                </div>
                
                <div class="btn-group">
                    <a href="index.php" class="btn">🍕 В меню</a>
                </div>
                
                <div class="auto-redirect">
                    ⏳ Вы будете перенаправлены в меню через <span class="countdown" id="countdown">10</span> секунд
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца</p>
        </div>
    </div>

    <script>
        // Автоматический редирект через 10 секунд
        let seconds = 10;
        const countdownEl = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            seconds--;
            countdownEl.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = 'index.php';
            }
        }, 1000);
    </script>
</body>
</html>