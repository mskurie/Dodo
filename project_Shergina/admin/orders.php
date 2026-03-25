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

$orders = $conn->query("
    SELECT o.*, c.full_name as client_name, c.phone
    FROM orders o
    JOIN clients c ON o.client_id = c.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
    <nav class="dodo-navbar">
        <div class="container navbar-container">
            <a href="dashboard.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="navbar-menu">
                <a href="dashboard.php" class="navbar-link">Главная</a>
                <a href="reservations.php" class="navbar-link">Бронирования</a>
                <a href="orders.php" class="navbar-link active">Заказы</a>
                <a href="menu.php" class="navbar-link">Меню</a>
                <a href="reports.php" class="navbar-link">Отчеты</a>
                <?php if ($auth->isAdmin()): ?>
                <a href="staff.php" class="navbar-link">Сотрудники</a>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                <span class="user-role"><?php echo $user['role']; ?></span>
                <a href="../logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>Управление заказами</h1>
            <p>Отслеживание статусов и истории заказов</p>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="card-title" style="margin-bottom: 0;">Список заказов</h2>
                <a href="#" class="btn btn-sm">➕ Новый заказ</a>
            </div>

            <?php if (empty($orders)): ?>
                <p style="color: #757575; text-align: center; padding: 40px;">Нет заказов</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="dodo-table">
                        <thead>
                            <tr>
                                <th>№ заказа</th>
                                <th>Дата</th>
                                <th>Клиент</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Оплата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong><?php echo $o['order_number']; ?></strong></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($o['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($o['client_name']); ?></td>
                                <td><?php echo number_format($o['final_amount'], 0); ?> ₽</td>
                                <td>
                                    <span class="status-badge <?php echo $o['order_status']; ?>">
                                        <?php echo $o['order_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $o['payment_status']; ?>">
                                        <?php echo $o['payment_status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца · Панель управления</p>
        </div>
    </div>
</body>
</html>
