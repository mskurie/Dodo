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

// Простая статистика
$today = date('Y-m-d');

// Заказы сегодня
$orders_today = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['cnt'];

// Выручка сегодня
$revenue_today = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = '$today' AND payment_status = 'paid'")->fetch_assoc()['total'];

// Всего заказов
$total_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];

// Всего выручка
$total_revenue = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['total'];

// Популярные блюда
$popular_dishes = $conn->query("
    SELECT d.name, SUM(oi.quantity) as total_quantity
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    GROUP BY d.id
    ORDER BY total_quantity DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты · Додо Пицца</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        
        .navbar {
            background: #E31E24;
            color: white;
            padding: 15px 0;
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
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #E31E24, #C8102E);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        h1 { font-size: 2rem; margin-bottom: 10px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #E31E24;
            margin: 10px 0;
        }
        
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h2 { margin-bottom: 20px; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f8f8;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #E31E24;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .footer {
            background: white;
            padding: 20px 0;
            margin-top: 40px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="logo">ДОДО пицца</a>
            <div class="nav-links">
                <a href="dashboard.php">Главная</a>
                <a href="reports.php" style="font-weight: bold;">Отчеты</a>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="../logout.php">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>Отчеты и статистика</h1>
            <p>Основные показатели работы ресторана</p>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div>Заказов сегодня</div>
                <div class="stat-value"><?php echo $orders_today; ?></div>
            </div>
            <div class="stat-card">
                <div>Выручка сегодня</div>
                <div class="stat-value"><?php echo number_format($revenue_today, 0); ?> ₽</div>
            </div>
            <div class="stat-card">
                <div>Всего заказов</div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
            </div>
            <div class="stat-card">
                <div>Общая выручка</div>
                <div class="stat-value"><?php echo number_format($total_revenue, 0); ?> ₽</div>
            </div>
        </div>

        <div class="card">
            <h2>Популярные блюда</h2>
            <table>
                <tr>
                    <th>Блюдо</th>
                    <th>Продано</th>
                </tr>
                <?php foreach ($popular_dishes as $dish): ?>
                <tr>
                    <td><?php echo htmlspecialchars($dish['name']); ?></td>
                    <td><?php echo $dish['total_quantity']; ?> шт.</td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца</p>
        </div>
    </div>
</body>
</html>