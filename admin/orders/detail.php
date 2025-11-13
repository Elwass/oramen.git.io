<?php
require_once __DIR__ . '/../../config.php';
require_login();

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: /admin/index.php');
    exit;
}

$stmt = $mysqli->prepare("SELECT o.id, o.status, o.created_at, t.table_number, COALESCE(SUM(oi.quantity * oi.price), 0) AS total
                         FROM orders o
                         JOIN tables t ON t.id = o.table_id
                         LEFT JOIN order_items oi ON oi.order_id = o.id
                         WHERE o.id = ?
                         GROUP BY o.id, o.status, o.created_at, t.table_number LIMIT 1");
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

include __DIR__ . '/../includes/header.php';
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Pesanan #<?php echo esc_html($order['id']); ?> • Meja <?php echo esc_html($order['table_number']); ?></h2>
                <span class="badge bg-status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">Status: <?php echo esc_html($order['status']); ?></span>
            </div>
            <div class="card-body">
                <?php if (!$orderItems): ?>
                    <p class="text-muted">Belum ada item pada pesanan ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Menu</th>
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
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h6 mb-0">Ringkasan</h3>
            </div>
            <div class="card-body">
                <p class="d-flex justify-content-between"><span>Tanggal</span> <strong><?php echo esc_html(date('d/m/Y H:i', strtotime($order['created_at']))); ?></strong></p>
                <p class="d-flex justify-content-between"><span>Total</span> <strong>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></strong></p>
                <div class="d-grid gap-2">
                    <?php if ($order['status'] === 'Baru'): ?>
                        <form method="post" action="/admin/orders/update_status.php" class="d-grid gap-2">
                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                            <input type="hidden" name="status" value="Sedang Dibuat">
                            <button type="submit" class="btn btn-warning">Tandai Sedang Dibuat</button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array($order['status'], ['Baru','Sedang Dibuat'], true)): ?>
                        <form method="post" action="/admin/orders/update_status.php" class="d-grid gap-2">
                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                            <input type="hidden" name="status" value="Selesai">
                            <button type="submit" class="btn btn-success">Tandai Selesai</button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array($order['status'], ['Selesai'], true)): ?>
                        <form method="post" action="/admin/orders/update_status.php" class="d-grid gap-2">
                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                            <input type="hidden" name="status" value="Dibayar">
                            <button type="submit" class="btn btn-primary">Proses Pembayaran</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($order['status'] === 'Dibayar'): ?>
                        <a class="btn btn-outline-secondary" href="/admin/orders/receipt.php?id=<?php echo (int) $order['id']; ?>" target="_blank">Cetak Struk</a>
                    <?php endif; ?>
                    <a class="btn btn-light" href="/admin/index.php">← Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
