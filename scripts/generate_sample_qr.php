<?php
// CLI helper to preseed QR PNG files for default tables.
declare(strict_types=1);
require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';

$baseUrl = 'http://localhost/project-ramen1/order.php?table=';
$qrDir = __DIR__ . '/../uploads/qr';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0775, true);
}

$tables = range(1, 10);
foreach ($tables as $tableNumber) {
    $target = sprintf('%s/table_%d.png', $qrDir, $tableNumber);
    $url = $baseUrl . rawurlencode((string) $tableNumber);
    QRcode::png($url, $target, QR_ECLEVEL_L, 6);
    echo "Generated QR for table {$tableNumber}: {$target}\n";
}
