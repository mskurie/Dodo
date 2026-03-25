<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = $auth->getUser();
$conn = getConnection();
$today = date('Y-m-d');

// Статистика - исправленные запросы с проверками
// Свободные столы
$free_tables_result = $conn->query("SELECT COUNT(*) as cnt FROM tables WHERE is_active = 1 AND status = 'free'");
$free_tables = $free_tables_result ? $free_tables_result->fetch_assoc()['cnt'] : 0;

// Бронирования сегодня (только подтвержденные и ожидающие)
$today_reservations_result = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE DATE(reservation_time) = '$today' AND status IN ('confirmed', 'pending')");
$today_reservations = $today_reservations_result ? $today_reservations_result->fetch_assoc()['cnt'] : 0;

// Выручка сегодня (только оплаченные заказы)
$today_revenue_result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = '$today' AND payment_status = 'paid'");
$today_revenue = $today_revenue_result ? $today_revenue_result->fetch_assoc()['total'] : 0;

// Активные заказы (не завершенные и не отмененные)
$active_orders_result = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today' AND order_status NOT IN ('completed', 'cancelled')");
$active_orders = $active_orders_result ? $active_orders_result->fetch_assoc()['cnt'] : 0;

// Всего столов в системе (для отображения)
$total_tables_result = $conn->query("SELECT COUNT(*) as cnt FROM tables WHERE is_active = 1");
$total_tables = $total_tables_result ? $total_tables_result->fetch_assoc()['cnt'] : 0;

// Проверка наличия данных в таблицах
$tables_exist = $conn->query("SELECT 1 FROM tables LIMIT 1")->num_rows > 0;
$reservations_exist = $conn->query("SELECT 1 FROM reservations LIMIT 1")->num_rows > 0;
$orders_exist = $conn->query("SELECT 1 FROM orders LIMIT 1")->num_rows > 0;

// Последние бронирования
$recent_reservations = [];
if ($reservations_exist) {
    $recent_reservations = $conn->query("
        SELECT r.*, c.full_name as client_name, t.table_number, h.name as hall_name
        FROM reservations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN tables t ON r.table_id = t.id
        LEFT JOIN halls h ON t.hall_id = h.id
        ORDER BY r.reservation_time DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}

// Последние заказы
$recent_orders = [];
if ($orders_exist) {
    $recent_orders = $conn->query("
        SELECT o.*, c.full_name as client_name
        FROM orders o
        LEFT JOIN clients c ON o.client_id = c.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
    <style>
        /* Стили для информационных сообщений */
        .info-message {
            background: #E3F2FD;
            color: #0D47A1;
            padding: 12px 20px;
            border-radius: 40px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            border: 1px solid #BBDEFB;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .warning-message {
            background: #FFF3E0;
            color: #E65100;
            padding: 12px 20px;
            border-radius: 40px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            border: 1px solid #FFE0B2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-icon {
            font-size: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin: 40px 0;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #E0E0E0;
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(227,30,36,0.1);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #E31E24;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #757575;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-small {
            font-size: 0.8rem;
            color: #999;
            margin-top: 5px;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            border: 1px solid #E0E0E0;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title:before {
            content: '';
            width: 6px;
            height: 28px;
            background: #E31E24;
            border-radius: 4px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #F8F8F8;
            border-radius: 20px;
            text-decoration: none;
            color: #1A1A1A;
            font-weight: 600;
            transition: all 0.2s;
            border: 1px solid #E0E0E0;
        }

        .action-btn:hover {
            background: #E31E24;
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(227,30,36,0.2);
            border-color: #E31E24;
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 16px;
        }

        .dodo-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .dodo-table th {
            background: #F8F8F8;
            color: #1A1A1A;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 14px 12px;
            text-align: left;
            border-bottom: 2px solid #E31E24;
        }

        .dodo-table td {
            padding: 12px;
            border-bottom: 1px solid #E0E0E0;
        }

        .dodo-table tr:hover {
            background: rgba(227,30,36,0.02);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .status-badge.confirmed,
        .status-badge.paid,
        .status-badge.completed {
            background: #28A745;
            color: white;
        }

        .status-badge.pending,
        .status-badge.in_progress,
        .status-badge.preparing {
            background: #FFC107;
            color: #1A1A1A;
        }

        .status-badge.cancelled {
            background: #DC3545;
            color: white;
        }

        .status-badge.new {
            background: #17A2B8;
            color: white;
        }

        .footer {
            background: white;
            padding: 40px 0;
            margin-top: 60px;
            text-align: center;
            color: #757575;
            border-top: 1px solid #E0E0E0;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <nav class="dodo-navbar">
        <div class="container navbar-container">
            <a href="dashboard.php" class="logo">ДОДО<span>пицца</span></a>

            <div class="navbar-menu">
                <a href="dashboard.php" class="navbar-link active">Главная</a>
                <a href="reservations.php" class="navbar-link">Бронирования</a>
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
                <a href="profile.php" class="nav-link" style="color: white; text-decoration: none; padding: 5px 10px; background: rgba(255,255,255,0.2); border-radius: 40px;">👤 Профиль</a>
                <a href="../logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>
    </nav>

    <!-- Шапка страницы -->
    <div class="page-header">
        <div class="container">
            <h1>Добро пожаловать, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Управляйте рестораном в реальном времени</p>
        </div>
    </div>

    <div class="container">
        <!-- Информационные сообщения о состоянии базы данных -->
        <?php if (!$tables_exist): ?>
            <div class="warning-message">
                <span class="message-icon">⚠️</span>
                <span>В системе нет столов. Добавьте столы в разделе "Бронирования".</span>
            </div>
        <?php endif; ?>

        <?php if (!$reservations_exist): ?>
            <div class="info-message">
                <span class="message-icon">ℹ️</span>
                <span>Нет бронирований. Они появятся, когда клиенты начнут бронировать столы.</span>
            </div>
        <?php endif; ?>

        <?php if (!$orders_exist): ?>
            <div class="info-message">
                <span class="message-icon">ℹ️</span>
                <span>Нет заказов. Они появятся, когда клиенты начнут оформлять заказы.</span>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🪑</div>
                <div class="stat-value"><?php echo $free_tables; ?> / <?php echo $total_tables; ?></div>
                <div class="stat-label">Свободных столов</div>
                <div class="stat-small">всего столов: <?php echo $total_tables; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo $today_reservations; ?></div>
                <div class="stat-label">Бронирований сегодня</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?php echo number_format($today_revenue, 0); ?> ₽</div>
                <div class="stat-label">Выручка сегодня</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🍕</div>
                <div class="stat-value"><?php echo $active_orders; ?></div>
                <div class="stat-label">Активных заказов</div>
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="card">
            <h2 class="card-title">Быстрые действия</h2>
            <div class="quick-actions">
                <a href="reservations.php" class="action-btn">
                    <span class="action-icon">📅</span>
                    Бронирования
                </a>
                <a href="orders.php" class="action-btn">
                    <span class="action-icon">🍕</span>
                    Заказы
                </a>
                <a href="menu.php" class="action-btn">
                    <span class="action-icon">📖</span>
                    Меню
                </a>
                <a href="reports.php" class="action-btn">
                    <span class="action-icon">📊</span>
                    Отчеты
                </a>
                <?php if ($auth->isAdmin()): ?>
                <a href="staff.php" class="action-btn">
                    <span class="action-icon">👥</span>
                    Сотрудники
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Последние бронирования -->
        <div class="card">
            <h2 class="card-title">Последние бронирования</h2>
            <?php if (!empty($recent_reservations)): ?>
                <div class="table-responsive">
                    <table class="dodo-table">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Клиент</th>
                                <th>Зал / Стол</th>
                                <th>Гостей</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reservations as $r): ?>
                            <tr>
                                <td><?php echo date('d.m H:i', strtotime($r['reservation_time'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($r['client_name'] ?? 'Не указано'); ?></strong></td>
                                <td><?php echo ($r['hall_name'] ?? 'Неизвестно') . ' / ' . ($r['table_number'] ?? '—'); ?></td>
                                <td><?php echo $r['guests_count'] ?? 0; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $r['status'] ?? 'pending'; ?>">
                                        <?php echo $r['status'] ?? 'pending'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #757575; text-align: center; padding: 20px;">Нет бронирований</p>
            <?php endif; ?>
        </div>

        <!-- Последние заказы -->
        <div class="card">
            <h2 class="card-title">Последние заказы</h2>
            <?php if (!empty($recent_orders)): ?>
                <div class="table-responsive">
                    <table class="dodo-table">
                        <thead>
                            <tr>
                                <th>№ заказа</th>
                                <th>Время</th>
                                <th>Клиент</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $o): ?>
                            <tr>
                                <td><strong><?php echo $o['order_number'] ?? '—'; ?></strong></td>
                                <td><?php echo isset($o['created_at']) ? date('d.m H:i', strtotime($o['created_at'])) : '—'; ?></td>
                                <td><?php echo htmlspecialchars($o['client_name'] ?? 'Не указано'); ?></td>
                                <td><?php echo isset($o['final_amount']) ? number_format($o['final_amount'], 0) : 0; ?> ₽</td>
                                <td>
                                    <span class="status-badge <?php echo $o['order_status'] ?? 'new'; ?>">
                                        <?php echo $o['order_status'] ?? 'new'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #757575; text-align: center; padding: 20px;">Нет заказов</p>
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