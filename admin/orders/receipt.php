<?php
require_once __DIR__ . '/../../config.php';
require_login();

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: /admin/index.php');
    exit;
}

$stmt = $mysqli->prepare("SELECT o.id, o.status, o.created_at, o.updated_at, t.table_number
                         FROM orders o
                         JOIN tables t ON t.id = o.table_id
                         WHERE o.id = ? LIMIT 1");
$stmt->bind_param('i', $orderId);
$order = null;
if ($stmt->execute()) {
    $order = stmt_fetch_assoc($stmt);
}

if (!$order) {
    header('Location: /admin/index.php');
    exit;
}

$stmtItems = $mysqli->prepare('SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON mi.id = oi.menu_item_id WHERE oi.order_id = ?');
$stmtItems->bind_param('i', $orderId);
$orderItems = [];
if ($stmtItems->execute()) {
    $orderItems = stmt_fetch_all_assoc($stmtItems);
}
$total = 0;
foreach ($orderItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran #<?php echo esc_html($order['id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f9f9f9; }
        .receipt { max-width: 600px; margin: 40px auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 15px 45px rgba(0,0,0,0.1); }
        .receipt-header { text-align: center; margin-bottom: 24px; }
        .receipt-header h1 { margin: 0; font-size: 24px; }
        @media print {
            body { background: #fff; }
            .receipt { box-shadow: none; margin: 0; }
            .print-hide { display: none !important; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="receipt-header">
        <h1>Ramen 1</h1>
        <p>Struk Pembayaran • #<?php echo esc_html($order['id']); ?></p>
        <p>Meja <?php echo esc_html($order['table_number']); ?> • <?php echo esc_html(date('d/m/Y H:i', strtotime($order['created_at']))); ?></p>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?php echo esc_html($item['name']); ?></td>
                <td><?php echo (int) $item['quantity']; ?></td>
                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total</th>
                <th>Rp <?php echo number_format($total, 0, ',', '.'); ?></th>
            </tr>
        </tfoot>
    </table>
    <p class="text-center">Status: <strong><?php echo esc_html($order['status']); ?></strong></p>
    <div class="text-center print-hide">
        <button class="btn btn-primary" onclick="window.print()">Cetak</button>
        <a class="btn btn-secondary" href="/admin/orders/detail.php?id=<?php echo (int) $order['id']; ?>">Kembali</a>
    </div>
</div>
</body>
</html>
