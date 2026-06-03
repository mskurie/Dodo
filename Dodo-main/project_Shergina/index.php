<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();
$conn = getConnection();
$today = date('Y-m-d');

$free_tables = $conn->query("SELECT COUNT(*) as cnt FROM tables WHERE is_active = 1 AND status = 'free'")->fetch_assoc()['cnt'];
$today_reservations = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE DATE(reservation_time) = '$today'")->fetch_assoc()['cnt'];
$today_revenue = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = '$today' AND payment_status = 'paid'")->fetch_assoc()['total'];
$dishes_count = $conn->query("SELECT COUNT(*) as cnt FROM dishes WHERE is_available = 1")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додо Пицца · Система управления</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #F8F8F8; }
        .navbar { background-color: #E31E24; color: white; padding: 1rem 0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { font-size: 1.8rem; font-weight: 800; color: white; text-decoration: none; }
        .logo span { font-weight: 300; font-size: 1rem; margin-left: 5px; opacity: 0.8; }
        .nav-links { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 8px 20px; background: white; color: #E31E24; border: none; border-radius: 40px; font-weight: 600; text-decoration: none; transition: transform 0.2s; }
        .btn:hover { transform: scale(1.05); }
        .btn-outline { background: transparent; color: white; border: 2px solid white; }
        .btn-outline:hover { background: white; color: #E31E24; }
        .logout-btn { background-color: #1A1A1A; color: white; padding: 6px 16px; border-radius: 40px; text-decoration: none; font-weight: 600; }
        .logout-btn:hover { background-color: #000; }
        .user-info { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.15); padding: 5px 15px 5px 20px; border-radius: 40px; }
        .user-name { font-weight: 600; }
        .user-role { font-size: 0.8rem; opacity: 0.7; }
        .hero { background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%); color: white; padding: 80px 0; text-align: center; border-radius: 0 0 40px 40px; margin-bottom: 60px; }
        .hero h1 { font-size: 3.5rem; font-weight: 800; margin-bottom: 20px; }
        .hero p { font-size: 1.2rem; opacity: 0.9; max-width: 700px; margin: 0 auto 30px; }
        .hero-buttons { display: flex; gap: 20px; justify-content: center; margin-top: 30px; flex-wrap: wrap; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin: 40px 0; }
        .stat-card { background: white; border-radius: 24px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #E0E0E0; text-align: center; }
        .stat-icon { font-size: 2.5rem; margin-bottom: 15px; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #E31E24; }
        .stat-label { color: #757575; font-size: 0.9rem; text-transform: uppercase; }
        .features-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin: 40px 0; }
        .feature-card { background: white; border-radius: 24px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #E0E0E0; text-align: center; transition: transform 0.3s; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(227,30,36,0.1); }
        .feature-icon { font-size: 3rem; margin-bottom: 20px; }
        .feature-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 15px; }
        .feature-text { color: #757575; line-height: 1.5; font-size: 0.95rem; }
        .section-title { font-size: 2rem; font-weight: 700; margin: 60px 0 30px; color: #1A1A1A; display: flex; align-items: center; gap: 10px; }
        .section-title:before { content: ''; width: 8px; height: 32px; background: #E31E24; border-radius: 4px; }
        .footer { background: white; padding: 40px 0; margin-top: 60px; text-align: center; color: #757575; border-top: 1px solid #E0E0E0; }
        @media (max-width: 1024px) { .stats-grid, .features-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .stats-grid, .features-grid { grid-template-columns: 1fr; } .hero h1 { font-size: 2.5rem; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <?php if ($auth->isLoggedIn()): ?>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Пользователь'); ?></span>
                        <span class="user-role"><?php echo $_SESSION['role'] ?? 'staff'; ?></span>
                        <a href="admin/dashboard.php" class="btn">Панель</a>
                        <a href="logout.php" class="logout-btn">Выйти</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn">Вход для персонала</a>
                    <a href="client/index.php" class="btn btn-outline">🍕 Заказать пиццу</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="hero">
        <div class="container">
            <h1>ДОДО ПИЦЦА</h1>
            <p>Профессиональная система управления рестораном</p>
            <?php if (!$auth->isLoggedIn()): ?>
                <div class="hero-buttons">
                    <a href="login.php" class="btn">Войти в систему</a>
                    <a href="client/index.php" class="btn btn-outline">🍕 Заказать еду</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">🪑</div><div class="stat-value"><?php echo $free_tables; ?></div><div class="stat-label">Свободных столов</div></div>
            <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-value"><?php echo $today_reservations; ?></div><div class="stat-label">Бронирований сегодня</div></div>
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?php echo number_format($today_revenue, 0); ?> ₽</div><div class="stat-label">Выручка сегодня</div></div>
            <div class="stat-card"><div class="stat-icon">🍕</div><div class="stat-value"><?php echo $dishes_count; ?></div><div class="stat-label">Блюд в меню</div></div>
        </div>
        <h2 class="section-title">Возможности системы</h2>
        <div class="features-grid">
            <div class="feature-card"><div class="feature-icon">🪑</div><h3 class="feature-title">Управление столами</h3><p class="feature-text">Бронирование столов, отслеживание занятости</p></div>
            <div class="feature-card"><div class="feature-icon">📋</div><h3 class="feature-title">Заказы</h3><p class="feature-text">Создание заказов, добавление блюд</p></div>
            <div class="feature-card"><div class="feature-icon">🧾</div><h3 class="feature-title">Счета</h3><p class="feature-text">Генерация счетов, учёт оплат</p></div>
            <div class="feature-card"><div class="feature-icon">📊</div><h3 class="feature-title">Аналитика</h3><p class="feature-text">Отчёты по продажам</p></div>
        </div>
    </div>
    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца</p>
        </div>
    </div>
</body>
</html>
