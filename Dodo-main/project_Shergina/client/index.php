<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';
require_once '../includes/cart_functions.php';
require_once '../includes/client_auth.php';

$clientAuth = new ClientAuth();
$currentClient = $clientAuth->getCurrentClient();
$conn = getConnection();

$menu = $conn->query("
    SELECT d.*, c.name as category_name
    FROM dishes d
    JOIN dish_categories c ON d.category_id = c.id
    WHERE d.is_available = 1
    ORDER BY c.sort_order, d.name
")->fetch_all(MYSQLI_ASSOC);

$menu_by_category = [];
foreach ($menu as $dish) {
    $menu_by_category[$dish['category_name']][] = $dish;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $dish_id = intval($_POST['dish_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    addToCart($dish_id, $quantity);
    header('Location: index.php?added=' . $dish_id);
    exit();
}

$cart_count = getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додо Пицца · Доставка еды</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #F8F8F8; }
        .navbar { background-color: #E31E24; color: white; padding: 1rem 0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { font-size: 1.8rem; font-weight: 800; color: white; text-decoration: none; }
        .logo span { font-weight: 300; font-size: 1rem; margin-left: 5px; opacity: 0.8; }
        .nav-links { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .nav-link { color: white; text-decoration: none; padding: 8px 16px; border-radius: 40px; transition: background 0.2s; }
        .nav-link:hover { background: rgba(255,255,255,0.2); }
        .cart-link { background: white; color: #E31E24; padding: 10px 20px; border-radius: 40px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .cart-link:hover { background: #f0f0f0; }
        .cart-count { background: #E31E24; color: white; border-radius: 50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; }
        .hero { background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%); color: white; padding: 80px 0; text-align: center; border-radius: 0 0 40px 40px; margin-bottom: 60px; }
        .hero h1 { font-size: 3.5rem; font-weight: 800; margin-bottom: 15px; }
        .hero p { font-size: 1.2rem; opacity: 0.9; max-width: 700px; margin: 0 auto; }
        .category { margin: 50px 0; }
        .category-title { font-size: 2rem; font-weight: 700; margin-bottom: 30px; color: #1A1A1A; display: flex; align-items: center; gap: 10px; }
        .category-title:before { content: ''; width: 6px; height: 28px; background: #E31E24; border-radius: 3px; }
        .dishes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .dish-card { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #E0E0E0; transition: transform 0.3s, box-shadow 0.3s; }
        .dish-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
        .dish-image { width: 100%; height: 200px; object-fit: cover; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 4rem; }
        .dish-content { padding: 20px; }
        .dish-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; color: #1A1A1A; }
        .dish-description { color: #757575; font-size: 0.9rem; margin-bottom: 15px; line-height: 1.5; }
        .dish-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .dish-price { font-size: 1.4rem; font-weight: 800; color: #E31E24; }
        .add-to-cart-form { display: flex; gap: 8px; }
        .quantity-input { width: 55px; padding: 8px; border: 1px solid #E0E0E0; border-radius: 8px; text-align: center; font-weight: 600; }
        .add-btn { background: #E31E24; color: white; border: none; padding: 10px 18px; border-radius: 40px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .add-btn:hover { background: #C8102E; }
        .footer { background: white; padding: 40px 0; margin-top: 60px; text-align: center; color: #757575; border-top: 1px solid #E0E0E0; }
        .user-greeting { background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 40px; font-weight: 500; }
        @media (max-width: 768px) { .navbar .container { flex-direction: column; gap: 15px; } .hero h1 { font-size: 2.5rem; } .dishes-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <?php if ($currentClient): ?>
                    <span class="user-greeting">👤 <?php echo htmlspecialchars($currentClient['name']); ?></span>
                    <a href="reservations.php" class="nav-link">📅 Бронь</a>
                    <a href="my_orders.php" class="nav-link">📋 Мои заказы</a>
                    <a href="logout.php" class="nav-link">Выйти</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Вход</a>
                    <a href="register.php" class="nav-link">Регистрация</a>
                <?php endif; ?>
                <a href="cart.php" class="cart-link">
                    🛒 Корзина
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h1>ДОДО ПИЦЦА</h1>
            <p>Свежая, горячая пицца с доставкой до двери</p>
        </div>
    </div>

    <div class="container booking-btn-container" style="text-align: center; margin: -30px auto 40px;">
        <a href="reservations.php" style="display: inline-block; padding: 14px 35px; background: white; color: #E31E24; text-decoration: none; border: 2px solid #E31E24; font-weight: 700; border-radius: 40px; transition: all 0.2s;">
            📅 Забронировать стол
        </a>
    </div>

    <div class="container">
        <?php foreach ($menu_by_category as $category => $dishes): ?>
            <div class="category">
                <h2 class="category-title"><?php echo $category; ?></h2>
                <div class="dishes-grid">
                    <?php foreach ($dishes as $dish): ?>
                        <div class="dish-card">
                            <?php if (!empty($dish['image'])): ?>
                                <img src="../uploads/dishes/<?php echo $dish['image']; ?>"
                                     alt="<?php echo $dish['name']; ?>" class="dish-image">
                            <?php else: ?>
                                <div class="dish-image">🍕</div>
                            <?php endif; ?>
                            <div class="dish-content">
                                <h3 class="dish-name"><?php echo $dish['name']; ?></h3>
                                <p class="dish-description"><?php echo $dish['description']; ?></p>
                                <div class="dish-footer">
                                    <span class="dish-price"><?php echo number_format($dish['price'], 0); ?> ₽</span>
                                    <form method="post" class="add-to-cart-form">
                                        <input type="hidden" name="dish_id" value="<?php echo $dish['id']; ?>">
                                        <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="10">
                                        <button type="submit" name="add_to_cart" class="add-btn">В корзину</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца</p>
            <p style="font-size: 0.9rem; margin-top: 10px;">Доставка еды | Бронирование столов</p>
        </div>
    </div>

    <!-- Секретная кнопка -->
    <a href="secret.php" style="opacity: 0.03; font-size: 4px; position: fixed; bottom: 2px; right: 3px; color: #E31E24; text-decoration: none; z-index: 9999;">🍕</a>
</body>
</html>