<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/client_auth.php';


$clientAuth = new ClientAuth();

// Проверяем авторизацию
if (!$clientAuth->isLoggedIn()) {
    header('Location: login.php?redirect=reservations');
    exit();
}

$currentClient = $clientAuth->getCurrentClient();
$conn = getConnection();

$message = '';
$error = '';

// Получаем список залов
$halls = $conn->query("SELECT * FROM halls WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Получаем список столов (для AJAX или первоначальной загрузки)
$tables = $conn->query("
    SELECT t.*, h.name as hall_name 
    FROM tables t
    JOIN halls h ON t.hall_id = h.id
    WHERE t.is_active = 1
    ORDER BY h.name, t.table_number
")->fetch_all(MYSQLI_ASSOC);

// Обработка создания бронирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    $table_id = intval($_POST['table_id']);
    $guests = intval($_POST['guests']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $duration = intval($_POST['duration'] ?? 120);
    $notes = trim($_POST['notes'] ?? '');
    
    $reservation_time = $date . ' ' . $time . ':00';
    $end_time = date('Y-m-d H:i:s', strtotime($reservation_time . ' + ' . $duration . ' minutes'));
    
    // Проверяем, свободен ли стол
    $check = $conn->prepare("
        SELECT id FROM reservations 
        WHERE table_id = ? 
        AND (
            (reservation_time BETWEEN ? AND ?)
            OR (DATE_ADD(reservation_time, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?)
            OR (? BETWEEN reservation_time AND DATE_ADD(reservation_time, INTERVAL duration_minutes MINUTE))
        )
        AND status IN ('confirmed', 'pending')
    ");
    $check->bind_param("isssss", $table_id, $reservation_time, $end_time, $reservation_time, $end_time, $reservation_time);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error = 'Этот стол уже забронирован на выбранное время';
    } else {
        // Создаем бронирование
        $stmt = $conn->prepare("
            INSERT INTO reservations (
                client_id, table_id, guests_count, reservation_time, 
                duration_minutes, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        $stmt->bind_param("iiisss", 
            $currentClient['id'], 
            $table_id, 
            $guests, 
            $reservation_time, 
            $duration, 
            $notes
        );
        
        if ($stmt->execute()) {
            $message = 'Бронирование успешно создано!';
        } else {
            $error = 'Ошибка при создании бронирования: ' . $conn->error;
        }
    }
}

// Получаем бронирования текущего клиента
$my_reservations = $conn->prepare("
    SELECT r.*, t.table_number, h.name as hall_name 
    FROM reservations r
    JOIN tables t ON r.table_id = t.id
    JOIN halls h ON t.hall_id = h.id
    WHERE r.client_id = ?
    ORDER BY r.reservation_time DESC
");
$my_reservations->bind_param("i", $currentClient['id']);
$my_reservations->execute();
$my_reservations = $my_reservations->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование столов · Додо Пицца</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #F8F8F8;
        }
        
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
            gap: 20px;
            align-items: center;
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
        
        .hero {
            background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .reservation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 40px 0;
        }
        
        .card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #E0E0E0;
        }
        
        .card-title {
            font-size: 1.5rem;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1A1A1A;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #E0E0E0;
            border-radius: 60px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #E31E24;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23757575' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
        }
        
        textarea.form-control {
            border-radius: 20px;
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background: #E31E24;
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            background: #C8102E;
        }
        
        .btn-outline {
            background: white;
            color: #E31E24;
            border: 2px solid #E31E24;
        }
        
        .btn-outline:hover {
            background: #E31E24;
            color: white;
        }
        
        .alert {
            padding: 15px 25px;
            border-radius: 60px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        
        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #F8F8F8;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #E31E24;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #E0E0E0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-badge.confirmed {
            background: #28A745;
            color: white;
        }
        
        .status-badge.pending {
            background: #FFC107;
            color: #1A1A1A;
        }
        
        .status-badge.cancelled {
            background: #DC3545;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #757575;
        }
        
        .footer {
            background: white;
            padding: 40px 0;
            margin-top: 60px;
            text-align: center;
            color: #757575;
            border-top: 1px solid #E0E0E0;
        }
        
        .time-duration {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .reservation-grid {
                grid-template-columns: 1fr;
            }
            
            .time-duration {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">ДОДО<span>пицца</span></a>
            <div class="nav-links">
                <span style="color:white;">👤 <?php echo htmlspecialchars($currentClient['name']); ?></span>
                <a href="index.php" class="nav-link">Меню</a>
                <a href="reservations.php" class="nav-link active">Бронирование</a>
                <a href="logout.php" class="nav-link">Выйти</a>
                <a href="cart.php" class="cart-link">
                    🛒 Корзина
                    <?php 
                    require_once '../includes/cart_functions.php';
                    $count = getCartCount(); 
                    if ($count > 0): ?>
                        <span class="cart-count"><?php echo $count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h1>Бронирование столов</h1>
            <p>Забронируйте столик в нашем ресторане</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="reservation-grid">
            <!-- Форма бронирования -->
            <div class="card">
                <h2 class="card-title">Новое бронирование</h2>
                
                <form method="post" id="reservation-form">
                    <div class="form-group">
                        <label>Выберите стол *</label>
                        <select name="table_id" class="form-control" required>
                            <option value="">-- Выберите стол --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo $table['id']; ?>">
                                    <?php echo $table['hall_name']; ?> · Стол <?php echo $table['table_number']; ?> 
                                    (<?php echo $table['capacity']; ?> мест)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Количество гостей *</label>
                        <input type="number" name="guests" class="form-control" min="1" max="20" value="2" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Дата *</label>
                        <input type="date" name="date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    
                    <div class="time-duration">
                        <div class="form-group">
                            <label>Время *</label>
                            <input type="time" name="time" class="form-control" 
                                   value="19:00" min="10:00" max="23:00" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Длительность</label>
                            <select name="duration" class="form-control">
                                <option value="60">1 час</option>
                                <option value="90">1.5 часа</option>
                                <option value="120" selected>2 часа</option>
                                <option value="150">2.5 часа</option>
                                <option value="180">3 часа</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Особые пожелания</label>
                        <textarea name="notes" class="form-control" 
                                  placeholder="Особые пожелания, аллергии, праздник..."></textarea>
                    </div>
                    
                    <button type="submit" name="create_reservation" class="btn">
                        ✅ Забронировать стол
                    </button>
                </form>
            </div>
            
            <!-- Мои бронирования -->
            <div class="card">
                <h2 class="card-title">Мои бронирования</h2>
                
                <?php if (empty($my_reservations)): ?>
                    <div class="empty-state">
                        <p style="font-size: 3rem; margin-bottom: 20px;">📅</p>
                        <p>У вас пока нет бронирований</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Зал/Стол</th>
                                    <th>Гостей</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_reservations as $res): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($res['reservation_time'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($res['reservation_time'])); ?></td>
                                    <td><?php echo $res['hall_name']; ?>/<?php echo $res['table_number']; ?></td>
                                    <td><?php echo $res['guests_count']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $res['status']; ?>">
                                            <?php 
                                            switch($res['status']) {
                                                case 'confirmed': echo 'Подтверждено'; break;
                                                case 'pending': echo 'Ожидает'; break;
                                                case 'cancelled': echo 'Отменено'; break;
                                                default: echo $res['status'];
                                            }
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px;">
                    <p style="color: #757575; font-size: 0.9rem;">
                        ⏰ При опоздании более чем на 15 минут бронь может быть отменена
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Додо Пицца</p>
        </div>
    </div>

    <script>
        // Валидация даты и времени
        document.getElementById('reservation-form').addEventListener('submit', function(e) {
            const date = document.querySelector('input[name="date"]').value;
            const time = document.querySelector('input[name="time"]').value;
            
            if (!date || !time) {
                e.preventDefault();
                alert('Выберите дату и время');
                return;
            }
            
            const selectedDate = new Date(date + 'T' + time);
            const now = new Date();
            
            if (selectedDate < now) {
                e.preventDefault();
                alert('Нельзя забронировать стол на прошедшее время');
            }
        });
    </script>
</body>
</html>
