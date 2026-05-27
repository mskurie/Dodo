 <?php
session_start();
require_once '../config/database.php';

$conn = getConnection();

echo "<h1>🔧 Исправление статусов столов</h1>";

// Проверяем структуру таблицы
$columns = $conn->query("SHOW COLUMNS FROM tables");
$has_status = false;
$status_type = '';

while ($col = $columns->fetch_assoc()) {
    if ($col['Field'] == 'status') {
        $has_status = true;
        $status_type = $col['Type'];
        echo "<p>Поле status существует. Тип: $status_type</p>";
    }
}

// Если нет поля status, добавляем его
if (!$has_status) {
    $conn->query("ALTER TABLE tables ADD COLUMN status VARCHAR(20) DEFAULT 'free'");
    echo "<p style='color:green;'>✅ Добавлено поле status</p>";
}

// Устанавливаем статус 'free' для всех столов
$conn->query("UPDATE tables SET status = 'free' WHERE status IS NULL OR status = ''");
$conn->query("UPDATE tables SET is_active = 1 WHERE is_active IS NULL");

$updated = $conn->affected_rows;
echo "<p style='color:green;'>✅ Обновлено столов: $updated</p>";

// Проверяем результат
$free = $conn->query("SELECT COUNT(*) as cnt FROM tables WHERE status = 'free'")->fetch_assoc()['cnt'];
$total = $conn->query("SELECT COUNT(*) as cnt FROM tables")->fetch_assoc()['cnt'];

echo "<h3>Результат:</h3>";
echo "<p>Всего столов: $total</p>";
echo "<p>Свободных столов: $free</p>";

// Показываем все столы
$tables = $conn->query("SELECT id, table_number, status FROM tables");
echo "<h3>Список столов:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Номер</th><th>Статус</th></tr>";
while ($table = $tables->fetch_assoc()) {
    $color = $table['status'] == 'free' ? 'green' : 'orange';
    echo "<tr>";
    echo "<td>{$table['id']}</td>";
    echo "<td>{$table['table_number']}</td>";
    echo "<td style='color:$color; font-weight:bold;'>{$table['status']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='dashboard.php'>➡️ Вернуться в панель управления</a></p>";
?>
