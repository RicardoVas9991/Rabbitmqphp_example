<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? $_SESSION['user_id'] ?? 0;
$symbol = $input['symbol'] ?? '';
$target_price = $input['target_price'] ?? 0;
$condition = $input['condition'] ?? 'ABOVE';

if (!$user_id || !$symbol) {
    json_response(['error' => 'Invalid input'], 400);
}

$stmt = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
$stmt->bind_param("s", $symbol);
$stmt->execute();
$res = $stmt->get_result();
$stock = $res->fetch_assoc();
$stmt->close();

if (!$stock) {
    json_response(['error' => 'Stock not found'], 404);
}

$stmt = $mydb->prepare("INSERT INTO price_alerts (user_id, stock_id, target_price, `condition`) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iids", $user_id, $stock['id'], $target_price, $condition);

if ($stmt->execute()) {
    json_response(['status' => 'success', 'message' => 'Alert set']);
} else {
    json_response(['error' => 'DB Error: ' . $stmt->error], 500);
}
?>
