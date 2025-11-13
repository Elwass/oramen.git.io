<?php
// qr.php - dynamic QR code renderer for tables/orders
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/phpqrcode/qrlib.php';

$tableId = isset($_GET['table_id']) ? (int) $_GET['table_id'] : 0;
$tableNumber = trim($_GET['table'] ?? '');

$foundNumber = null;
if ($tableId > 0) {
    $stmt = $mysqli->prepare('SELECT table_number FROM tables WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $tableId);
        if ($stmt->execute()) {
            $row = stmt_fetch_assoc($stmt);
            if ($row) {
                $foundNumber = $row['table_number'];
            }
        }
        $stmt->close();
    }
}

if ($foundNumber === null && $tableNumber !== '') {
    $stmt = $mysqli->prepare('SELECT table_number FROM tables WHERE table_number = ?');
    if ($stmt) {
        $stmt->bind_param('s', $tableNumber);
        if ($stmt->execute()) {
            $row = stmt_fetch_assoc($stmt);
            if ($row) {
                $foundNumber = $row['table_number'];
            }
        }
        $stmt->close();
    }
}

if ($foundNumber === null) {
    http_response_code(404);
    header('Content-Type: image/png');
    $img = imagecreatetruecolor(400, 400);
    $white = imagecolorallocate($img, 255, 255, 255);
    $grey = imagecolorallocate($img, 180, 180, 180);
    imagefill($img, 0, 0, $white);
    imagestring($img, 5, 40, 180, 'QR tidak ditemukan', $grey);
    imagepng($img);
    imagedestroy($img);
    exit;
}

$orderUrl = absolute_url('order.php?table=' . rawurlencode((string) $foundNumber));

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
QRcode::png($orderUrl, '', QR_ECLEVEL_L, 8, 2);
exit;
