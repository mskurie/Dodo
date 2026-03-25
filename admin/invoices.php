<?php
// admin/invoices.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$conn = getConnection();

// Получаем оплаченные заказы как счета
$invoices = $conn->query("
    SELECT o.*, c.full_name as client_name, t.table_number
    FROM orders o
    JOIN clients c ON o.client_id = c.id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.payment_status = 'paid'
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Счета</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; }
        .navbar a { color: white; text-decoration: none; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #333; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .btn { display: inline-block; padding: 5px 10px; background: #e31837; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div><strong>Dodo Pizza</strong> | Счета</div>
        <div><a href="dashboard.php">← Назад</a></div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Оплаченные заказы</h2>
            <table>
                <tr>
                    <th>№ заказа</th>
                    <th>Дата</th>
                    <th>Клиент</th>
                    <th>Стол</th>
                    <th>Сумма</th>
                    <th>Способ оплаты</th>
                </tr>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?php echo $inv['order_number']; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($inv['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($inv['client_name']); ?></td>
                    <td><?php echo $inv['table_number'] ?? '-'; ?></td>
                    <td><?php echo number_format($inv['final_amount'], 2); ?> ₽</td>
                    <td><?php echo $inv['payment_method']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>