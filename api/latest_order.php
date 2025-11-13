<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$stmt = $mysqli->query("SELECT id, created_at FROM orders ORDER BY created_at DESC LIMIT 1");
$order = $stmt->fetch_assoc();

echo json_encode([
    'latest_id' => $order['id'] ?? null,
    'created_at' => $order['created_at'] ?? null
]);
