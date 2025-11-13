<?php
require_once __DIR__ . '/../config.php';
session_destroy();
header('Location: ' . url_for('admin/login.php'));
exit;
