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
$cart = getCartItems($conn);
$cart_count = getCartCount();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $dish_id => $quantity) {
            updateCartQuantity(intval($dish_id), intval($quantity));
        }
        header('Location: cart.php?updated=1');
        exit();
    }

    if (isset($_POST['remove_item'])) {
        removeFromCart(intval($_POST['dish_id']));
        header('Location: cart.php?removed=1');
        exit();
    }

    if (isset($_POST['clear_cart'])) {
        clearCart();
        header('Location: cart.php?cleared=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина — Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .navbar {
            background: #ffffff;
            padding: 16px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #E31E24;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-link {
            color: #1a1a1a;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-link:hover {
            background: #f5f5f5;
            color: #E31E24;
        }

        .cart-link {
            background: #E31E24;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .cart-link:hover {
            background: #c4181f;
        }

        .cart-count {
            background: #ffffff;
            color: #E31E24;
            border-radius: 50%;
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0 6px;
        }

        .user-greeting {
            color: #1a1a1a;
            font-weight: 500;
            padding: 8px 16px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .page-header {
            padding: 40px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 40px;
            padding: 40px 0;
        }

        .cart-items {
            background: #ffffff;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto auto auto;
            align-items: center;
            gap: 24px;
            padding: 24px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        .item-price {
            color: #1a1a1a;
            font-weight: 600;
            font-size: 1rem;
        }

        .item-quantity {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .item-quantity input {
            width: 60px;
            padding: 10px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            background: #ffffff;
            color: #1a1a1a;
            text-align: center;
            font-weight: 500;
            font-size: 1rem;
        }

        .item-quantity input:focus {
            outline: none;
            border-color: #E31E24;
        }

        .item-subtotal {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1a1a1a;
            min-width: 100px;
            text-align: right;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 8px;
            transition: color 0.2s;
            line-height: 1;
        }

        .remove-btn:hover {
            color: #E31E24;
        }

        .cart-summary {
            background: #f8f8f8;
            border-radius: 16px;
            padding: 24px;
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .summary-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e5e5;
            color: #1a1a1a;
            font-size: 0.95rem;
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 20px 0;
            padding-top: 20px;
            border-top: 2px solid #e5e5e5;
            color: #1a1a1a;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #E31E24;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #c4181f;
        }

        .btn-block {
            width: 100%;
            display: block;
        }

        .btn-outline {
            background: transparent;
            color: #E31E24;
            border: 2px solid #E31E24;
        }

        .btn-outline:hover {
            background: #E31E24;
            color: #ffffff;
        }

        .btn-sm {
            padding: 10px 20px;
            font-size: 0.95rem;
        }

        .alert {
            padding: 14px 20px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .empty-cart {
            text-align: center;
            padding: 80px 40px;
            background: #f8f8f8;
            border-radius: 16px;
        }

        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 24px;
            color: #ccc;
        }

        .empty-cart h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1a1a1a;
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 32px;
            font-size: 1.1rem;
        }

        .footer {
            background: #f8f8f8;
            padding: 40px 0;
            margin-top: 60px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e5e5e5;
        }

        @media (max-width: 900px) {
            .cart-container {
                grid-template-columns: 1fr;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 12px;
            }

            .item-quantity,
            .item-subtotal {
                grid-column: span 2;
            }

            .item-subtotal {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                🍕 Додо Пицца
            </a>
            <div class="nav-links">
                <?php if ($currentClient): ?>
                    <span class="user-greeting">👤 <?php echo htmlspecialchars($currentClient['name']); ?></span>
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

    <div class="page-header">
        <div class="container">
            <h1>🛒 Корзина</h1>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert">✓ Корзина обновлена</div>
        <?php endif; ?>

        <?php if (isset($_GET['removed'])): ?>
            <div class="alert">✓ Товар удалён</div>
        <?php endif; ?>

        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert">✓ Корзина очищена</div>
        <?php endif; ?>

        <?php if (empty($cart['items'])): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Ваша корзина пуста</h2>
                <p>Добавьте что-нибудь вкусное из нашего меню</p>
                <a href="index.php" class="btn">Перейти в меню</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <form method="post" id="cart-form">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../uploads/dishes/<?php echo $item['image']; ?>"
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        🍕
                                    <?php endif; ?>
                                </div>

                                <div class="item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="item-price"><?php echo number_format($item['price'], 0); ?> ₽</div>
                                </div>

                                <div class="item-quantity">
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]"
                                           value="<?php echo $item['quantity']; ?>"
                                           min="1" max="10">
                                </div>

                                <div class="item-subtotal">
                                    <?php echo number_format($item['subtotal'], 0); ?> ₽
                                </div>

                                <button type="submit" name="remove_item" value="<?php echo $item['id']; ?>"
                                        class="remove-btn" title="Удалить">×</button>
                            </div>
                        <?php endforeach; ?>

                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" name="update_cart" class="btn btn-sm">
                                Обновить
                            </button>
                            <button type="submit" name="clear_cart" class="btn btn-sm btn-outline">
                                Очистить
                            </button>
                        </div>
                    </form>
                </div>

                <div class="cart-summary">
                    <h2 class="summary-title">Ваш заказ</h2>

                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="summary-row">
                            <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                            <span><?php echo number_format($item['subtotal'], 0); ?> ₽</span>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-total">
                        <span>Итого</span>
                        <span><?php echo number_format($cart['total'], 0); ?> ₽</span>
                    </div>

                    <?php if ($currentClient): ?>
                        <a href="checkout.php" class="btn btn-block">Оформить заказ</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-block">Войти для оформления</a>
                        <p style="text-align: center; margin-top: 16px; color: #666; font-size: 0.95rem;">
                            Уже есть аккаунт? <a href="login.php" style="color: #E31E24;">Войти</a>
                        </p>
                    <?php endif; ?>

                    <a href="index.php" class="btn btn-outline btn-block" style="margin-top: 12px;">
                        Продолжить выбор
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
