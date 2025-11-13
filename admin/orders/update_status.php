<?php
require_once __DIR__ . '/../../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php');
    exit;
}

$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = $_POST['status'] ?? '';
$allowed = ['Baru','Sedang Dibuat','Selesai','Dibayar'];

if ($orderId <= 0 || !in_array($status, $allowed, true)) {
    header('Location: /admin/index.php');
    exit;
}

$stmt = $mysqli->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
$stmt->bind_param('si', $status, $orderId);
$stmt->execute();

if ($status === 'Dibayar') {
    header('Location: /admin/orders/receipt.php?id=' . $orderId);
} else {
    header('Location: /admin/orders/detail.php?id=' . $orderId);
}
exit;
