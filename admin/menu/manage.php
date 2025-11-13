<?php
require_once __DIR__ . '/../../config.php';
require_login();

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = null;

$stmtCat = $mysqli->query('SELECT id, name FROM menu_categories ORDER BY name');
$categories = $stmtCat ? result_fetch_all_assoc($stmtCat) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $imagePath = null;

    if ($name === '' || $price <= 0 || $categoryId <= 0) {
        $_SESSION['menu_error'] = 'Nama, harga, dan kategori wajib diisi.';
        $redirect = '/admin/menu/manage.php' . ($itemId ? '?id=' . $itemId : '');
        header('Location: ' . $redirect);
        exit;
    }

    if ($itemId) {
        $stmt = $mysqli->prepare('SELECT image FROM menu_items WHERE id = ?');
        $stmt->bind_param('i', $itemId);
        $existing = null;
        if ($stmt->execute()) {
            $existing = stmt_fetch_assoc($stmt);
        }
        $imagePath = $existing['image'] ?? null;
    }

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '/uploads/';
        if (!is_dir(__DIR__ . '/../../uploads')) {
            mkdir(__DIR__ . '/../../uploads', 0755, true);
        }
        $filename = uniqid('menu_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $target = __DIR__ . '/../../uploads/' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = $uploadDir . $filename;
        }
    }

    if ($itemId) {
        $stmt = $mysqli->prepare('UPDATE menu_items SET name = ?, price = ?, description = ?, category_id = ?, image = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('sdsisi', $name, $price, $description, $categoryId, $imagePath, $itemId);
        $stmt->execute();
    } else {
        $stmt = $mysqli->prepare('INSERT INTO menu_items (name, price, description, category_id, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('sdsis', $name, $price, $description, $categoryId, $imagePath);
        $stmt->execute();
    }

    unset($_SESSION['menu_error']);
    header('Location: /admin/menu/index.php');
    exit;
}

if ($itemId) {
    $stmt = $mysqli->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->bind_param('i', $itemId);
    if ($stmt->execute()) {
        $item = stmt_fetch_assoc($stmt);
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0"><?php echo $item ? 'Edit' : 'Tambah'; ?> Item Menu</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($_SESSION['menu_error'])): ?>
                    <div class="alert alert-danger" role="alert"><?php echo esc_html($_SESSION['menu_error']); ?></div>
                    <?php unset($_SESSION['menu_error']); ?>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Menu</label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo esc_html($item['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Harga</label>
                        <input type="number" name="price" id="price" class="form-control" required min="0" step="1000" value="<?php echo esc_html($item['price'] ?? 0); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['id']; ?>" <?php echo isset($item['category_id']) && (int) $item['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>><?php echo esc_html($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo esc_html($item['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Gambar</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*">
                        <?php if (!empty($item['image'])): ?>
                            <p class="mt-2">Gambar saat ini:</p>
                            <img src="<?php echo esc_html($item['image']); ?>" alt="<?php echo esc_html($item['name']); ?>" class="img-thumbnail" style="max-width: 200px;">
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/menu/index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
