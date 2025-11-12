<?php
require_once __DIR__ . '/../../config.php';
require_login();
require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';

$result = $mysqli->query('SELECT table_number FROM tables ORDER BY table_number');
$tables = $result->fetch_all(MYSQLI_ASSOC);

$qrDir = __DIR__ . '/../../uploads/qr/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0755, true);
}

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

foreach ($tables as $table) {
    $qrFileName = 'table_' . preg_replace('/[^A-Za-z0-9]/', '_', $table['table_number']) . '.png';
    $qrPath = $qrDir . $qrFileName;
    $orderUrl = $baseUrl . '/order.php?table=' . urlencode($table['table_number']);
    QRcode::png($orderUrl, $qrPath, QR_ECLEVEL_L, 6);
}

header('Location: /admin/tables/index.php');
exit;
