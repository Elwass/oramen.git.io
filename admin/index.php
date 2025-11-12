<?php
require_once __DIR__ . '/../config.php';
require_login();

$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['', 'Baru', 'Sedang Dibuat', 'Selesai', 'Dibayar'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$sql = "SELECT o.id, o.status, o.created_at, t.table_number, COALESCE(SUM(oi.quantity * oi.price), 0) AS total
        FROM orders o
        JOIN tables t ON t.id = o.table_id
        LEFT JOIN order_items oi ON oi.order_id = o.id";

$params = [];
$types = '';

if ($statusFilter) {
    $sql .= ' WHERE o.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
} else {
    $sql .= " WHERE o.status <> 'Dibatalkan'";
}

$sql .= ' GROUP BY o.id, o.status, o.created_at, t.table_number ORDER BY o.created_at DESC';

$stmt = $mysqli->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Dashboard Pesanan</h1>
    <form class="d-flex" method="get">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <?php foreach (['Baru','Sedang Dibuat','Selesai','Dibayar'] as $status): ?>
                <option value="<?php echo esc_html($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo esc_html($status); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Meja</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$orders): ?>
            <tr><td colspan="6" class="text-center">Belum ada pesanan.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <tr class="order-row status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                    <td>#<?php echo esc_html($order['id']); ?></td>
                    <td>Meja <?php echo esc_html($order['table_number']); ?></td>
                    <td><span class="badge bg-status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>"><?php echo esc_html($order['status']); ?></span></td>
                    <td><?php echo esc_html(date('d/m/Y H:i', strtotime($order['created_at']))); ?></td>
                    <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="/admin/orders/detail.php?id=<?php echo (int) $order['id']; ?>">Detail</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
