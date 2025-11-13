<?php
require_once __DIR__ . '/../../config.php';
require_login();
require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';

$result = $mysqli->query('SELECT table_number FROM tables ORDER BY table_number');
$tables = $result ? result_fetch_all_assoc($result) : [];

$qrDir = __DIR__ . '/../../uploads/qr/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0755, true);
}

foreach ($tables as $table) {
    $qrFileName = 'table_' . preg_replace('/[^A-Za-z0-9]/', '_', $table['table_number']) . '.png';
    $qrPath = $qrDir . $qrFileName;
    $orderUrl = absolute_url('order.php?table=' . urlencode($table['table_number']));
    QRcode::png($orderUrl, $qrPath, QR_ECLEVEL_L, 6);
}

header('Location: ' . url_for('admin/tables/index.php'));
exit;
