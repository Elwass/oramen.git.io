<?php
require_once __DIR__ . '/../../config.php';
require_login();

$sql = "SELECT mi.*, mc.name AS category_name FROM menu_items mi JOIN menu_categories mc ON mc.id = mi.category_id ORDER BY mc.name, mi.name";
$result = $mysqli->query($sql);
$items = $result ? result_fetch_all_assoc($result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Manajemen Menu</h1>
        <p class="text-muted mb-0">Kelola item menu Ramen 1.</p>
    </div>
    <a class="btn btn-primary" href="/admin/menu/manage.php">Tambah Item</a>
</div>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>Gambar</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
            <tr><td colspan="6" class="text-center">Belum ada data menu.</td></tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td style="width:120px;">
                        <?php if ($item['image']): ?>
                            <img src="<?php echo esc_html($item['image']); ?>" class="img-fluid rounded" alt="<?php echo esc_html($item['name']); ?>">
                        <?php else: ?>
                            <span class="text-muted">Tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($item['name']); ?></td>
                    <td><?php echo esc_html($item['category_name']); ?></td>
                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    <td><?php echo esc_html($item['description']); ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/admin/menu/manage.php?id=<?php echo (int) $item['id']; ?>">Edit</a>
                        <a class="btn btn-sm btn-outline-danger" href="/admin/menu/delete.php?id=<?php echo (int) $item['id']; ?>" onclick="return confirm('Hapus item ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
