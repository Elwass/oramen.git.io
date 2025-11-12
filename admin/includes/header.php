<?php
require_once __DIR__ . '/../../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ramen 1 Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/admin/index.php">Ramen 1 Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/admin/index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/menu/index.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/menu/categories.php">Kategori</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/tables/index.php">Meja & QR</a></li>
            </ul>
            <div class="d-flex">
                <span class="navbar-text me-3">👤 <?php echo esc_html($_SESSION['username'] ?? 'Admin'); ?></span>
                <a class="btn btn-outline-light" href="/admin/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
<div class="container-fluid py-4">
