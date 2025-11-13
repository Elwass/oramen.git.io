<?php
require_once __DIR__ . '/../../config.php';
require_login();

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($itemId > 0) {
    $stmt = $mysqli->prepare('DELETE FROM menu_items WHERE id = ?');
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
}

header('Location: /admin/menu/index.php');
exit;
