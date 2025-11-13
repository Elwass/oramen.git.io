<?php
require_once __DIR__ . '/../../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table_number'])) {
    $tableNumber = trim($_POST['table_number']);
    if ($tableNumber !== '') {
        $stmt = $mysqli->prepare('INSERT INTO tables (table_number, created_at) VALUES (?, NOW())');
        $stmt->bind_param('s', $tableNumber);
        $stmt->execute();
    }
    header('Location: ' . url_for('admin/tables/index.php'));
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        $stmt = $mysqli->prepare('DELETE FROM tables WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
    header('Location: ' . url_for('admin/tables/index.php'));
    exit;
}

$result = $mysqli->query('SELECT * FROM tables ORDER BY table_number');
$tables = $result ? result_fetch_all_assoc($result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Tambah Meja</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="table_number" class="form-label">Nomor Meja</label>
                        <input type="text" name="table_number" id="table_number" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Daftar Meja</h2>
                <form method="post" action="generate_all.php">
                    <button type="submit" class="btn btn-outline-secondary">Generate QR Semua Meja</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nomor Meja</th>
                                <th>QR Code</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td>Meja <?php echo esc_html($table['table_number']); ?></td>
                                <td>
                                    <?php
                                        $qrImage = url_for('qr.php?table=' . rawurlencode($table['table_number']));
                                        $orderUrl = absolute_url('order.php?table=' . urlencode($table['table_number']));
                                    ?>
                                    <a href="<?php echo esc_html($qrImage); ?>" target="_blank">
                                        <img src="<?php echo esc_html($qrImage); ?>" alt="QR Meja <?php echo esc_html($table['table_number']); ?>" style="width:120px;">
                                    </a>
                                    <p class="small mb-0"><a href="<?php echo esc_html($orderUrl); ?>" target="_blank">Preview Menu</a></p>
                                </td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="print.php?id=<?php echo (int) $table['id']; ?>" target="_blank">Print</a>
                                    <a class="btn btn-sm btn-outline-danger" href="?delete=<?php echo (int) $table['id']; ?>" onclick="return confirm('Hapus meja ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
