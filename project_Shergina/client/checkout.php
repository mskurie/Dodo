<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Простая проверка авторизации
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'] ?? 'Клиент';
$client_email = $_SESSION['client_email'] ?? '';

$conn = getConnection();

// Получаем первый доступный стол
$table_result = $conn->query("SELECT id FROM tables WHERE is_active = 1 LIMIT 1");
if ($table_result && $table_result->num_rows > 0) {
    $table = $table_result->fetch_assoc();
    $table_id = $table['id'];
} else {
    $table_id = 1;
}

// Получаем корзину из сессии
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_items = [];
$total = 0;

if (!empty($cart)) {
    $ids = implode(',', array_keys($cart));
    $result = $conn->query("SELECT * FROM dishes WHERE id IN ($ids)");
    while ($dish = $result->fetch_assoc()) {
        $quantity = $cart[$dish['id']];
        $subtotal = $dish['price'] * $quantity;
        $total += $subtotal;
        $cart_items[] = [
            'id' => $dish['id'],
            'name' => $dish['name'],
            'price' => $dish['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$message = '';
$order_number = '';
$show_modal = false;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    
    if (empty($address)) {
        $message = '<div style="color:red; text-align:center; padding:10px;">Укажите адрес доставки</div>';
    } else {
        try {
            // Начинаем транзакцию
            $conn->begin_transaction();
            
            // Генерируем номер заказа
            $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Проверяем уникальность номера
            $check = $conn->query("SELECT id FROM orders WHERE order_number = '$order_number'");
            while ($check && $check->num_rows > 0) {
                $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
                $check = $conn->query("SELECT id FROM orders WHERE order_number = '$order_number'");
            }
            
            // Вставляем заказ
            $sql = "INSERT INTO orders (order_number, client_id, table_id, total_amount, final_amount, order_status, payment_status, created_at) 
                    VALUES ('$order_number', $client_id, $table_id, $total, $total, 'new', 'paid', NOW())";
            
            if ($conn->query($sql)) {
                $order_id = $conn->insert_id;
                
                // Вставляем позиции
                foreach ($cart_items as $item) {
                    $notes = "Адрес: $address";
                    $sql2 = "INSERT INTO order_items (order_id, dish_id, quantity, unit_price, notes) 
                             VALUES ($order_id, {$item['id']}, {$item['quantity']}, {$item['price']}, '$notes')";
                    $conn->query($sql2);
                }
                
                // Подтверждаем транзакцию
                $conn->commit();
                
                // Очищаем корзину
                unset($_SESSION['cart']);
                
                // Показываем модальное окно
                $show_modal = true;
            } else {
                throw new Exception("Ошибка: " . $conn->error);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div style="color:red; text-align:center; padding:10px;">Ошибка: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа · Додо Пицца</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            color: #E31E24;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #E31E24;
        }
        input[readonly] {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        .btn {
            background: #E31E24;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background: #C8102E;
        }
        .order-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .order-total {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            color: #E31E24;
        }
        .message {
            margin-bottom: 20px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #E31E24;
        }
        
        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 40px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: popIn 0.5s;
            border: 5px solid #E31E24;
        }
        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-pizza {
            width: 120px;
            height: 120px;
            background: linear-gradient(145deg, #F4A460 0%, #E3A857 100%);
            border-radius: 50%;
            margin: 0 auto 30px;
            position: relative;
            animation: spin 10s linear infinite;
            border: 5px solid #C94F1E;
        }
        .modal-pizza::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            background: #C94F1E;
            border-radius: 50%;
            box-shadow: 
                30px -20px 0 #C94F1E,
                -30px 20px 0 #C94F1E,
                20px 30px 0 #C94F1E,
                -20px -30px 0 #C94F1E;
        }
        .modal-pizza::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: #FFD700;
            border-radius: 50%;
            box-shadow: 
                25px -15px 0 #FFD700,
                -25px 15px 0 #FFD700;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .modal h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #E31E24;
            margin-bottom: 15px;
        }
        .order-number-box {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 50px;
            margin: 20px 0;
        }
        .order-number-box p {
            font-size: 1.8rem;
            font-weight: 700;
            color: #E31E24;
        }
        .pizza-btn {
            width: 150px;
            height: 150px;
            background: linear-gradient(145deg, #F4A460 0%, #E3A857 100%);
            border: 8px solid #C94F1E;
            border-radius: 50%;
            margin: 20px auto 0;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
            animation: bounce 2s infinite;
        }
        .pizza-btn:hover {
            transform: scale(1.1);
        }
        .pizza-btn span {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: 800;
            font-size: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .cheese {
            position: absolute;
            width: 15px;
            height: 15px;
            background: #FFD700;
            border-radius: 50%;
            opacity: 0.6;
        }
        .cheese:nth-child(1) { top: 30px; left: 40px; }
        .cheese:nth-child(2) { top: 50px; right: 40px; }
        .cheese:nth-child(3) { bottom: 40px; left: 50px; }
        .cheese:nth-child(4) { bottom: 30px; right: 30px; }
        .cheese:nth-child(5) { top: 80px; left: 80px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍕 Оформление заказа</h1>
        
        <?php echo $message; ?>
        
        <div class="order-summary">
            <h3 style="margin-bottom: 15px;">Ваш заказ:</h3>
            <?php foreach ($cart_items as $item): ?>
                <div class="order-item">
                    <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                    <span><?php echo number_format($item['subtotal'], 0); ?> ₽</span>
                </div>
            <?php endforeach; ?>
            <div class="order-total">
                Итого: <?php echo number_format($total, 0); ?> ₽
            </div>
        </div>
        
        <form method="post" id="orderForm">
            <div class="form-group">
                <label>Имя</label>
                <input type="text" value="<?php echo htmlspecialchars($client_name); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($client_email); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Адрес доставки *</label>
                <textarea name="address" required placeholder="Улица, дом, квартира"></textarea>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">✅ Подтвердить заказ</button>
        </form>
        
        <a href="cart.php" class="back-link">← Вернуться в корзину</a>
    </div>
    
    <!-- Модальное окно -->
    <div id="successModal" class="modal <?php echo $show_modal ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-pizza"></div>
            <h2>Спасибо за заказ!</h2>
            <div class="order-number-box">
                <p><?php echo $order_number; ?></p>
            </div>
            <p style="margin-bottom: 20px; color: #666;">Ваша пицца уже готовится!</p>
            <div class="pizza-btn" onclick="window.location.href='index.php'">
                <span>OK</span>
                <div class="cheese"></div>
                <div class="cheese"></div>
                <div class="cheese"></div>
                <div class="cheese"></div>
                <div class="cheese"></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('orderForm')?.addEventListener('submit', function(e) {
            const address = document.querySelector('textarea[name="address"]').value.trim();
            if (!address) {
                e.preventDefault();
                alert('Укажите адрес доставки');
                return false;
            }
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Оформляем...';
            return true;
        });
        
        window.onclick = function(event) {
            var modal = document.getElementById('successModal');
            if (event.target == modal) {
                window.location.href = 'index.php';
            }
        }
        
        <?php if ($show_modal): ?>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>