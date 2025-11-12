<?php
require_once __DIR__ . '/config.php';

$tableNumber = trim($_GET['table'] ?? '');
if ($tableNumber !== '') {
    $stmt = $mysqli->prepare('SELECT id FROM tables WHERE table_number = ? LIMIT 1');
    $stmt->bind_param('s', $tableNumber);
    $stmt->execute();
    $table = $stmt->get_result()->fetch_assoc();
    $tableId = $table['id'] ?? null;
} else {
    $tableId = null;
}

$categoriesResult = $mysqli->query('SELECT id, name FROM menu_categories ORDER BY name');
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);

$stmtItems = $mysqli->query('SELECT mi.*, mc.name AS category_name FROM menu_items mi JOIN menu_categories mc ON mc.id = mi.category_id ORDER BY mc.name, mi.name');
$items = $stmtItems->fetch_all(MYSQLI_ASSOC);

$menuByCategory = [];
foreach ($items as $item) {
    $menuByCategory[$item['category_name']][] = $item;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Digital Ramen 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/order.css">
</head>
<body class="bg-dark text-light">
    <nav class="navbar navbar-dark bg-black sticky-top">
        <div class="container">
            <span class="navbar-brand">🍜 Ramen 1</span>
            <span class="badge bg-danger">Meja <span id="table-number-display"><?php echo esc_html($tableNumber ?: '-'); ?></span></span>
        </div>
    </nav>
    <main class="container my-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if (!$menuByCategory): ?>
                    <div class="alert alert-warning">Menu belum tersedia. Silakan hubungi staff.</div>
                <?php endif; ?>
                <?php foreach ($menuByCategory as $category => $menuItems): ?>
                    <section class="mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h4 mb-0"><?php echo esc_html($category); ?></h2>
                            <a href="#" class="text-decoration-none text-warning" onclick="scrollToCart();return false;">Lihat Keranjang</a>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($menuItems as $item): ?>
                                <div class="col-md-6">
                                    <div class="card menu-card h-100">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo esc_html($item['image']); ?>" class="card-img-top" alt="<?php echo esc_html($item['name']); ?>">
                                        <?php endif; ?>
                                        <div class="card-body d-flex flex-column">
                                            <h3 class="h5"><?php echo esc_html($item['name']); ?></h3>
                                            <p class="text-muted small flex-grow-1"><?php echo esc_html($item['description']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-warning">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                                <button class="btn btn-outline-warning btn-sm" data-item='<?php echo htmlspecialchars(json_encode([
                                                    'id' => (int) $item['id'],
                                                    'name' => $item['name'],
                                                    'price' => (float) $item['price'],
                                                    'image' => $item['image'],
                                                ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>'>Tambah</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <div class="card sticky-lg-top" style="top: 80px;">
                    <div class="card-header bg-warning text-dark">
                        <h2 class="h5 mb-0">Keranjang Pesanan</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="table-number" class="form-label">Nomor Meja</label>
                            <input type="text" id="table-number" class="form-control" value="<?php echo esc_html($tableNumber); ?>" placeholder="Contoh: 5" required>
                        </div>
                        <ul class="list-group list-group-flush mb-3" id="cart-items"></ul>
                        <p class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span id="cart-total">Rp 0</span>
                        </p>
                        <button class="btn btn-warning w-100" id="submit-order" disabled>Kirim Pesanan</button>
                        <div class="alert alert-success mt-3 d-none" role="alert" id="order-success">Pesanan berhasil dikirim! Mohon tunggu.</div>
                        <div class="alert alert-danger mt-3 d-none" role="alert" id="order-error"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/order.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.__ramenTableId = <?php echo isset($tableId) && $tableId ? (int) $tableId : 'null'; ?>;
        });
    </script>
</body>
</html>
