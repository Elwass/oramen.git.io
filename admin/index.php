<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';

// Pastikan folder QR ada
$qrDir = __DIR__ . '/../../uploads/qr/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0777, true);
}

// Handle form tambah meja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_name = trim($_POST['name'] ?? '');

    if (!empty($table_name)) {
        $stmt = $mysqli->prepare("INSERT INTO tables (name) VALUES (?)");
        $stmt->bind_param("s", $table_name);
        $stmt->execute();

        $newId = $stmt->insert_id;

        // =====================================================
        //  GENERATE QR UNTUK MEJA BARU
        // =====================================================

        $orderUrl = "http://localhost/oramen.git.io/order.php?table={$newId}";

        // QR ENGINE versi CODEX
        $qr = new QRCode([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L,
            'scale'      => 6,
        ]);

        // Bersihkan output buffer agar PNG tidak corrupt
        if (ob_get_length()) {
            ob_clean();
        }

        $pngData = $qr->render($orderUrl); // hasil PNG BINARY VALID

        $filePath = $qrDir . "table_{$newId}.png";
        file_put_contents($filePath, $pngData);

        header("Location: /oramen.git.io/admin/tables/");
        exit;
    }
}

// Ambil daftar meja
$tables = $mysqli->query("SELECT * FROM tables ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daftar Meja - Admin</title>
    <link rel="stylesheet" href="/oramen.git.io/assets/css/admin.css">
</head>
<body>

<div class="wrapper">

    <h2>Daftar Meja</h2>

    <div class="table-list">
        <table class="table">
            <thead>
                <tr>
                    <th>Nomor Meja</th>
                    <th>QR Code</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>

                <?php while ($row = $tables->fetch_assoc()): ?>
                    <tr>
                        <td>Meja <?php echo $row['id']; ?></td>

                        <td>
                            <img src="/oramen.git.io/uploads/qr/table_<?php echo $row['id']; ?>.png"
                                 alt="QR Meja <?php echo $row['id']; ?>"
                                 width="120">

                            <br>
                            <a href="/oramen.git.io/admin/tables/print.php?id=<?php echo $row['id']; ?>">
                                Preview Menu
                            </a>
                        </td>

                        <td>
                            <a href="/oramen.git.io/admin/tables/print.php?id=<?php echo $row['id']; ?>" class="btn">Print</a>
                            <a href="/oramen.git.io/admin/tables/delete.php?id=<?php echo $row['id']; ?>" class="btn-danger">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>

</div>

</body>
</html>
