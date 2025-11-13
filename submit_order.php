<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
    exit;
}

$tableNumber = trim($data['table_number'] ?? '');
$items = $data['items'] ?? [];

if ($tableNumber === '' || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Nomor meja dan item pesanan wajib diisi.']);
    exit;
}

$stmtTable = $mysqli->prepare('SELECT id FROM tables WHERE table_number = ? LIMIT 1');
$stmtTable->bind_param('s', $tableNumber);
$table = null;
if ($stmtTable->execute()) {
    $table = stmt_fetch_assoc($stmtTable);
}

if (!$table) {
    $stmtInsertTable = $mysqli->prepare('INSERT INTO tables (table_number, created_at) VALUES (?, NOW())');
    $stmtInsertTable->bind_param('s', $tableNumber);
    $stmtInsertTable->execute();
    $tableId = $stmtInsertTable->insert_id;
} else {
    $tableId = $table['id'];
}

$stmtOrder = $mysqli->prepare('INSERT INTO orders (table_id, status, created_at, updated_at) VALUES (?, "Baru", NOW(), NOW())');
$stmtOrder->bind_param('i', $tableId);
$stmtOrder->execute();
$orderId = $stmtOrder->insert_id;

$stmtMenu = $mysqli->prepare('SELECT price FROM menu_items WHERE id = ?');
$stmtItem = $mysqli->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)');
$insertedItems = 0;

foreach ($items as $item) {
    $menuId = (int) ($item['menu_item_id'] ?? 0);
    $quantity = (int) ($item['quantity'] ?? 0);
    if ($menuId <= 0 || $quantity <= 0) {
        continue;
    }
    $stmtMenu->bind_param('i', $menuId);
    $menuResult = null;
    if ($stmtMenu->execute()) {
        $menuResult = stmt_fetch_assoc($stmtMenu);
    }
    if (!$menuResult) {
        continue;
    }
    $price = (float) $menuResult['price'];
    $stmtItem->bind_param('iiid', $orderId, $menuId, $quantity, $price);
    $stmtItem->execute();
    $insertedItems++;
}

if ($insertedItems === 0) {
    $stmtDelete = $mysqli->prepare('DELETE FROM orders WHERE id = ?');
    $stmtDelete->bind_param('i', $orderId);
    $stmtDelete->execute();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Item pesanan tidak valid.']);
    exit;
}

http_response_code(201);
echo json_encode(['success' => true, 'order_id' => $orderId, 'table_id' => $tableId]);
