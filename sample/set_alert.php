<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php'); 
session_start();

if (!isset($_SESSION['user_id'])) {
    json_response(['error' => 'Authentication required'], 401);
}
$user_id = $_SESSION['user_id'];

$input = get_json_input();
if (!$input) {
    json_response(['error' => 'Invalid input'], 400);
}

$symbol = $input['symbol'] ?? '';
$target_price = (float)($input['target_price'] ?? 0);
$condition = strtoupper($input['condition'] ?? ''); 

if (empty($symbol) || $target_price <= 0 || !in_array($condition, ['ABOVE', 'BELOW'])) {
    json_response(['error' => 'Invalid data. Check symbol, price, and condition.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    $stock = $stmt->fetch();

    if (!$stock) {
        json_response(['error' => 'Stock not found. Please search for it first.'], 404);
    }
    $stock_id = $stock['id'];

    $stmt = $pdo->prepare("INSERT INTO price_alerts (user_id, stock_id, target_price, `condition`) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $stock_id, $target_price, $condition]);

    json_response(['status' => 'success', 'message' => 'Alert set!']);

} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
         json_response(['error' => 'You already have an identical alert for this stock.'], 409);
    }
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

