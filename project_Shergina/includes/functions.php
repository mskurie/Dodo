<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

/**
 * Получить список всех залов
 * @return array
 */
function getHalls() {
    $conn = getConnection();
    $result = $conn->query("SELECT * FROM halls WHERE is_active = 1 ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Получить столы по залу
 * @param int $hall_id
 * @return array
 */
function getTablesByHall($hall_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM tables WHERE hall_id = ? AND is_active = 1 ORDER BY table_number");
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Поиск свободных столов на определённое время
 * @param string $datetime
 * @param int $guests
 * @return array
 */
function getFreeTables($datetime, $guests) {
    $conn = getConnection();
    // Ищем столы, не занятые в указанное время (с учётом длительности брони по умолчанию 2 часа)
    $stmt = $conn->prepare("
        SELECT t.* FROM tables t
        WHERE t.capacity >= ? AND t.is_active = 1
        AND t.id NOT IN (
            SELECT table_id FROM reservations 
            WHERE reservation_time BETWEEN ? AND DATE_ADD(?, INTERVAL 2 HOUR)
            AND status IN ('confirmed', 'active')
        )
    ");
    $stmt->bind_param("iss", $guests, $datetime, $datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Получить всё меню (блюда) с категориями
 * @return array
 */
function getMenu() {
    $conn = getConnection();
    $result = $conn->query("
        SELECT d.*, c.name as category_name 
        FROM dishes d
        JOIN dish_categories c ON d.category_id = c.id
        WHERE d.is_available = 1
        ORDER BY c.sort_order, d.name
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Получить список клиентов
 * @return array
 */
function getClients() {
    $conn = getConnection();
    $result = $conn->query("SELECT * FROM clients ORDER BY full_name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Найти клиента по телефону
 * @param string $phone
 * @return array|null
 */
function findClientByPhone($phone) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM clients WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Создать нового клиента
 * @param string $phone
 * @param string $full_name
 * @param string|null $email
 * @param string|null $notes
 * @return int ID нового клиента
 */
function createClient($phone, $full_name, $email = null, $notes = null) {
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO clients (phone, full_name, email, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $phone, $full_name, $email, $notes);
    $stmt->execute();
    return $conn->insert_id;
}

/**
 * Получить бронирования за определённую дату (или последние 50)
 * @param string|null $date
 * @return array
 */
function getReservations($date = null) {
    $conn = getConnection();
    if ($date) {
        $stmt = $conn->prepare("
            SELECT r.*, c.full_name as client_name, c.phone, t.table_number, h.name as hall_name
            FROM reservations r
            JOIN clients c ON r.client_id = c.id
            JOIN tables t ON r.table_id = t.id
            JOIN halls h ON t.hall_id = h.id
            WHERE DATE(r.reservation_time) = ?
            ORDER BY r.reservation_time
        ");
        $stmt->bind_param("s", $date);
    } else {
        $stmt = $conn->prepare("
            SELECT r.*, c.full_name as client_name, c.phone, t.table_number, h.name as hall_name
            FROM reservations r
            JOIN clients c ON r.client_id = c.id
            JOIN tables t ON r.table_id = t.id
            JOIN halls h ON t.hall_id = h.id
            ORDER BY r.reservation_time DESC
            LIMIT 50
        ");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Получить заказы за определённую дату (или последние 50)
 * @param string|null $date
 * @return array
 */
function getOrders($date = null) {
    $conn = getConnection();
    if ($date) {
        $stmt = $conn->prepare("
            SELECT o.*, c.full_name as client_name, t.table_number
            FROM orders o
            JOIN clients c ON o.client_id = c.id
            LEFT JOIN tables t ON o.table_id = t.id
            WHERE DATE(o.created_at) = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->bind_param("s", $date);
    } else {
        $stmt = $conn->prepare("
            SELECT o.*, c.full_name as client_name, t.table_number
            FROM orders o
            JOIN clients c ON o.client_id = c.id
            LEFT JOIN tables t ON o.table_id = t.id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Генерация уникального номера заказа
 * @return string
 */
function generateOrderNumber() {
    $conn = getConnection();
    $date = date('Ymd');
    $result = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE order_number LIKE 'ORD-$date%'");
    $row = $result->fetch_assoc();
    $num = $row['cnt'] + 1;
    return "ORD-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
}

/**
 * Получить дневную выручку за дату
 * @param string $date
 * @return float
 */
function getDailyRevenue($date) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(final_amount), 0) as total 
        FROM orders 
        WHERE DATE(created_at) = ? AND payment_status = 'paid'
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return (float)$row['total'];
}

/**
 * Получить популярные блюда за дату
 * @param string $date
 * @return array
 */
function getPopularDishes($date) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT d.name, SUM(oi.quantity) as total_quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN dishes d ON oi.dish_id = d.id
        WHERE DATE(o.created_at) = ?
        GROUP BY d.id
        ORDER BY total_quantity DESC
        LIMIT 5
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>