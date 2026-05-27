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

// Получаем список бронирований
$reservations = $conn->query("
    SELECT r.*, c.full_name as client_name, c.phone, t.table_number, h.name as hall_name
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN tables t ON r.table_id = t.id
    JOIN halls h ON t.hall_id = h.id
    ORDER BY r.reservation_time DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирования · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
    <!-- Навигация -->
    <nav class="dodo-navbar">
        <div class="container navbar-container">
            <a href="dashboard.php" class="logo">ДОДО<span>пицца</span></a>

            <div class="navbar-menu">
                <a href="dashboard.php" class="navbar-link">Главная</a>
                <a href="reservations.php" class="navbar-link active">Бронирования</a>
                <a href="orders.php" class="navbar-link">Заказы</a>
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

    <!-- Шапка страницы -->
    <div class="page-header">
        <div class="container">
            <h1>Управление бронированиями</h1>
            <p>Просмотр и управление бронями столов</p>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="card-title" style="margin-bottom: 0;">Список бронирований</h2>
                <a href="#" class="btn btn-sm">➕ Добавить бронь</a>
            </div>

            <?php if (empty($reservations)): ?>
                <p style="color: #757575; text-align: center; padding: 40px;">Нет бронирований</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="dodo-table">
                        <thead>
                            <tr>
                                <th>Дата и время</th>
                                <th>Клиент</th>
                                <th>Телефон</th>
                                <th>Зал / Стол</th>
                                <th>Гостей</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td><strong><?php echo date('d.m.Y H:i', strtotime($r['reservation_time'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['client_name']); ?></td>
                                <td><?php echo $r['phone']; ?></td>
                                <td><?php echo $r['hall_name'] . ' / ' . $r['table_number']; ?></td>
                                <td><?php echo $r['guests_count']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $r['status']; ?>">
                                        <?php echo $r['status']; ?>
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

    <!-- Футер -->
    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца · Панель управления</p>
        </div>
    </div>
</body>
</html>
