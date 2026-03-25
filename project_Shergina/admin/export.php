 <?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$conn = getConnection();

// Получаем параметры
$type = $_GET['type'] ?? 'daily';
$date = $_GET['date'] ?? date('Y-m-d');
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$year = $_GET['year'] ?? date('Y');

// Формируем данные для экспорта
$filename = 'report_' . $type . '_' . date('Y-m-d') . '.csv';
$data = [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Добавляем BOM для UTF-8 в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'daily':
        fputcsv($output, ['Дневной отчет за ' . date('d.m.Y', strtotime($date))]);
        fputcsv($output, []);
        
        // Статистика
        $orders = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$date'")->fetch_assoc()['cnt'];
        $revenue = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = '$date' AND payment_status = 'paid'")->fetch_assoc()['total'];
        
        fputcsv($output, ['Показатель', 'Значение']);
        fputcsv($output, ['Всего заказов', $orders]);
        fputcsv($output, ['Выручка', $revenue . ' ₽']);
        fputcsv($output, []);
        
        // Почасовая статистика
        fputcsv($output, ['Почасовая статистика']);
        fputcsv($output, ['Час', 'Заказов', 'Выручка']);
        
        $hourly = $conn->query("
            SELECT HOUR(created_at) as hour, COUNT(*) as orders, COALESCE(SUM(final_amount), 0) as revenue
            FROM orders WHERE DATE(created_at) = '$date'
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ");
        
        while ($row = $hourly->fetch_assoc()) {
            fputcsv($output, [
                $row['hour'] . ':00 - ' . ($row['hour']+1) . ':00',
                $row['orders'],
                $row['revenue'] . ' ₽'
            ]);
        }
        break;
        
    case 'products':
        fputcsv($output, ['Отчет по популярности блюд']);
        fputcsv($output, []);
        fputcsv($output, ['Категория', 'Блюдо', 'Цена', 'Заказов', 'Продано', 'Выручка']);
        
        $products = $conn->query("
            SELECT c.name as category, d.name, d.price,
                   COUNT(DISTINCT o.id) as order_count,
                   SUM(oi.quantity) as total_quantity,
                   SUM(oi.total_price) as total_revenue
            FROM dishes d
            LEFT JOIN order_items oi ON d.id = oi.dish_id
            LEFT JOIN orders o ON oi.order_id = o.id
            JOIN dish_categories c ON d.category_id = c.id
            GROUP BY d.id
            ORDER BY total_revenue DESC
        ");
        
        while ($row = $products->fetch_assoc()) {
            fputcsv($output, [
                $row['category'],
                $row['name'],
                $row['price'] . ' ₽',
                $row['order_count'],
                $row['total_quantity'] . ' шт.',
                $row['total_revenue'] . ' ₽'
            ]);
        }
        break;
        
    // Добавьте другие типы отчетов по аналогии
}

fclose($output);
?>
