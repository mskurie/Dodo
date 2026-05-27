<?php
session_start();
require_once '../config/database.php';
require_once '../includes/client_auth.php';

$clientAuth = new ClientAuth();

if (!$clientAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentClient = $clientAuth->getCurrentClient();
$conn = getConnection();

// Получаем все заказы клиента
$orders = $conn->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
    FROM orders o
    WHERE o.client_id = " . $currentClient['id'] . "
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Статистика заказов
$total_orders = count($orders);
$total_spent = 0;
$completed_orders = 0;

foreach ($orders as $order) {
    $total_spent += $order['final_amount'];
    if ($order['order_status'] == 'completed' || $order['payment_status'] == 'paid') {
        $completed_orders++;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #F8F8F8; }
        
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
            flex-wrap: wrap;
            gap: 15px;
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
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
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
        
        .cart-link {
            background: white;
            color: #E31E24;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #E0E0E0;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #E31E24;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #757575;
            font-size: 0.9rem;
        }
        
        .card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #E0E0E0;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title:before {
            content: '';
            width: 6px;
            height: 24px;
            background: #E31E24;
            border-radius: 4px;
        }
        
        .order-card {
            border-left: 4px solid #E31E24;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .order-card:hover {
            transform: translateX(5px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #E31E24;
        }
        
        .order-date {
            color: #757575;
            font-size: 0.9rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-new { background: #17A2B8; color: white; }
        .status-preparing { background: #FFC107; color: #1A1A1A; }
        .status-ready { background: #28A745; color: white; }
        .status-paid { background: #28A745; color: white; }
        .status-completed { background: #6C757D; color: white; }
        .status-cancelled { background: #DC3545; color: white; }
        
        .order-items {
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #E0E0E0;
            border-bottom: 1px solid #E0E0E0;
        }
        
        .order-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            flex: 2;
        }
        
        .item-quantity {
            width: 80px;
            text-align: center;
            font-weight: 500;
        }
        
        .item-price {
            width: 100px;
            text-align: right;
            font-weight: 600;
            color: #E31E24;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #E0E0E0;
        }
        
        .order-total span:last-child {
            color: #E31E24;
            font-size: 1.3rem;
        }
        
        .empty-orders {
            text-align: center;
            padding: 60px;
        }
        
        .empty-orders-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: #E31E24;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #C8102E;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-item-row {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .item-name {
                flex: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <span class="user-greeting" style="color:white; background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 40px;">👤 <?php echo htmlspecialchars($currentClient['name']); ?></span>
                <a href="index.php" class="nav-link">Меню</a>
                <a href="reservations.php" class="nav-link">Бронирование</a>
                <a href="my_orders.php" class="nav-link active">Мои заказы</a>
                <a href="logout.php" class="nav-link">Выйти</a>
                <a href="cart.php" class="cart-link">
                    🛒 Корзина
                    <?php 
                    require_once '../includes/cart_functions.php';
                    $count = getCartCount(); 
                    if ($count > 0): ?>
                        <span class="cart-count" style="background: #E31E24; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem;"><?php echo $count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>Мои заказы</h1>
            <p>История ваших заказов</p>
        </div>
    </div>

    <div class="container">
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-label">Всего заказов</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_spent, 0); ?> ₽</div>
                <div class="stat-label">Потрачено всего</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $completed_orders; ?></div>
                <div class="stat-label">Выполнено</div>
            </div>
        </div>

        <!-- Список заказов -->
        <div class="card">
            <h2 class="card-title">История заказов</h2>
            
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <div class="empty-orders-icon">📦</div>
                    <h3>У вас пока нет заказов</h3>
                    <p style="color: #757575; margin: 15px 0;">Сделайте первый заказ и получите пиццу с бесплатной доставкой!</p>
                    <a href="index.php" class="btn">🍕 Перейти в меню</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php 
                    // Получаем детали заказа с правильными ценами
                    $items = $conn->query("
                        SELECT oi.*, d.name 
                        FROM order_items oi
                        JOIN dishes d ON oi.dish_id = d.id
                        WHERE oi.order_id = " . $order['id']
                    )->fetch_all(MYSQLI_ASSOC);
                    
                    // Вычисляем общую сумму позиций
                    $items_total = 0;
                    foreach ($items as $item) {
                        // Используем unit_price * quantity для вычисления суммы
                        $item_total = $item['unit_price'] * $item['quantity'];
                        $items_total += $item_total;
                    }
                    ?>
                    <div class="order-card card" style="padding: 20px;">
                        <div class="order-header">
                            <div>
                                <span class="order-number">Заказ #<?php echo $order['order_number']; ?></span>
                                <div class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                            </div>
                            <div>
                                <span class="order-status status-<?php echo $order['order_status']; ?>">
                                    <?php 
                                    switch($order['order_status']) {
                                        case 'new': echo '🆕 Новый'; break;
                                        case 'preparing': echo '👨‍🍳 Готовится'; break;
                                        case 'ready': echo '✅ Готов'; break;
                                        case 'served': echo '🍽️ Подано'; break;
                                        case 'paid': echo '💳 Оплачено'; break;
                                        case 'completed': echo '✔️ Выполнен'; break;
                                        case 'cancelled': echo '❌ Отменён'; break;
                                        default: echo $order['order_status'];
                                    }
                                    ?>
                                </span>
                                <span class="order-date" style="margin-left: 10px;">
                                    <?php if ($order['payment_status'] == 'paid'): ?>
                                        💳 Оплачено
                                    <?php else: ?>
                                        ⏳ Ожидает оплаты
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <div style="font-weight: 600; margin-bottom: 10px;">Состав заказа:</div>
                            <?php foreach ($items as $item): ?>
                                <?php 
                                $item_total = $item['unit_price'] * $item['quantity'];
                                ?>
                                <div class="order-item-row">
                                    <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                    <span class="item-price"><?php echo number_format($item_total, 0); ?> ₽</span>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Показываем промежуточный итог если нужно -->
                            <?php if ($items_total != $order['final_amount']): ?>
                                <div class="order-total" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #E0E0E0;">
                                    <span>Промежуточный итог:</span>
                                    <span><?php echo number_format($items_total, 0); ?> ₽</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-total">
                            <span>Итого:</span>
                            <span><?php echo number_format($order['final_amount'], 0); ?> ₽</span>
                        </div>
                        
                        <?php if ($order['payment_status'] != 'paid' && $order['order_status'] != 'cancelled'): ?>
                            <div style="margin-top: 15px; text-align: right;">
                                <a href="checkout.php?order_id=<?php echo $order['id']; ?>" class="btn" style="padding: 8px 20px; font-size: 0.9rem;">💳 Оплатить</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца</p>
            <p style="font-size: 0.9rem; margin-top: 10px;">Доставка еды | Бронирование столов</p>
        </div>
    </div>
</body>
</html>