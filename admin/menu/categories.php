<?php
require_once __DIR__ . '/../../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        if (!empty($_POST['id'])) {
            $id = (int) $_POST['id'];
            $stmt = $mysqli->prepare('UPDATE menu_categories SET name = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
        } else {
            $stmt = $mysqli->prepare('INSERT INTO menu_categories (name, created_at) VALUES (?, NOW())');
            $stmt->bind_param('s', $name);
            $stmt->execute();
        }
    }
    header('Location: /admin/menu/categories.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        $stmt = $mysqli->prepare('DELETE FROM menu_categories WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
    header('Location: /admin/menu/categories.php');
    exit;
}

$result = $mysqli->query('SELECT * FROM menu_categories ORDER BY name');
$categories = $result ? result_fetch_all_assoc($result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h1 class="h4 mb-0">Kategori Menu</h1>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="id" id="category-id" value="">
                    <div class="col-12">
                        <label for="category-name" class="form-label">Nama Kategori</label>
                        <input type="text" name="name" id="category-name" class="form-control" required>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-secondary" onclick="resetCategoryForm()">Bersihkan</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">Daftar Kategori</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($categories as $category): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo esc_html($category['name']); ?></span>
                            <span>
                                <button class="btn btn-sm btn-outline-primary me-2" onclick='editCategory(<?php echo htmlspecialchars(json_encode($category), ENT_QUOTES, "UTF-8"); ?>)'>Edit</button>
                                <a class="btn btn-sm btn-outline-danger" href="?delete=<?php echo (int) $category['id']; ?>" onclick="return confirm('Hapus kategori ini?');">Hapus</a>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>
function editCategory(category) {
    document.getElementById('category-id').value = category.id;
    document.getElementById('category-name').value = category.name;
}
function resetCategoryForm() {
    document.getElementById('category-id').value = '';
    document.getElementById('category-name').value = '';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
