<?php
require_once __DIR__ . '/../../config.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /admin/tables/index.php');
    exit;
}

$stmt = $mysqli->prepare('SELECT table_number FROM tables WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();

if (!$table) {
    header('Location: /admin/tables/index.php');
    exit;
}

require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';
$qrDir = __DIR__ . '/../../uploads/qr/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0755, true);
}

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$qrFileName = 'table_' . preg_replace('/[^A-Za-z0-9]/', '_', $table['table_number']) . '.png';
$qrPath = $qrDir . $qrFileName;
$orderUrl = $baseUrl . '/order.php?table=' . urlencode($table['table_number']);
if (!file_exists($qrPath)) {
    QRcode::png($orderUrl, $qrPath, QR_ECLEVEL_L, 6);
}
$qrWebPath = '/uploads/qr/' . $qrFileName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Meja <?php echo esc_html($table['table_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { text-align: center; padding: 40px; }
        .qr-card { border: 2px solid #222; display: inline-block; padding: 24px 32px; border-radius: 12px; }
        h1 { font-size: 32px; margin-bottom: 8px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="qr-card">
        <h1>Ramen 1</h1>
        <h2>Meja <?php echo esc_html($table['table_number']); ?></h2>
        <img src="<?php echo esc_html($qrWebPath); ?>" alt="QR Meja" style="width:260px;">
        <p class="mt-3">Scan untuk memesan langsung dari meja.</p>
        <p class="small text-muted mb-0"><?php echo esc_html($orderUrl); ?></p>
    </div>
    <div class="mt-4 print-btn">
        <button class="btn btn-primary" onclick="window.print()">Cetak QR</button>
        <a class="btn btn-secondary" href="/admin/tables/index.php">Kembali</a>
    </div>
</body>
</html>
