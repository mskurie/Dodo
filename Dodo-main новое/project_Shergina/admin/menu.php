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

$categories = $conn->query("SELECT * FROM dish_categories ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);
$dishes = $conn->query("
    SELECT d.*, c.name as category_name
    FROM dishes d
    JOIN dish_categories c ON d.category_id = c.id
    ORDER BY c.sort_order, d.name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Меню · Додо Пицца</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #F8F8F8; }
        .navbar {
            background-color: #E31E24;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        .page-header h1 { font-size: 2.5rem; font-weight: 800; }
        .card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #E0E0E0;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .menu-item {
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 20px;
            padding: 20px;
            transition: transform 0.2s;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(227,30,36,0.1);
        }
        .menu-item h3 { font-size: 1.2rem; margin-bottom: 10px; }
        .menu-item .price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #E31E24;
            margin-top: 15px;
        }
        .menu-item .category {
            color: #757575;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 10px;
        }
        .status.available { background: #28A745; color: white; }
        .status.unavailable { background: #6C757D; color: white; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #E31E24;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <a href="dashboard.php">← Назад</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>Управление меню</h1>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2 style="margin-bottom: 20px;">Наше меню</h2>

            <?php if (empty($dishes)): ?>
                <p>Меню пусто</p>
            <?php else: ?>
                <div class="menu-grid">
                    <?php foreach ($dishes as $dish): ?>
                        <div class="menu-item">
                            <span class="category"><?php echo $dish['category_name']; ?></span>
                            <h3><?php echo htmlspecialchars($dish['name']); ?></h3>
                            <?php if (!empty($dish['description'])): ?>
                                <p style="color: #757575; font-size: 0.9rem;"><?php echo $dish['description']; ?></p>
                            <?php endif; ?>
                            <div class="price"><?php echo number_format($dish['price'], 0); ?> ₽</div>
                            <div>
                                <span class="status <?php echo $dish['is_available'] ? 'available' : 'unavailable'; ?>">
                                    <?php echo $dish['is_available'] ? 'В наличии' : 'Нет в наличии'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
