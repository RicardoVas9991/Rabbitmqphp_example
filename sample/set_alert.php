<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');
session_start();

$alertConfiguration = get_json_input();
$activeUserId = $alertConfiguration['user_id'] ?? $_SESSION['user_id'] ?? 0;
$targetStockSymbol = $alertConfiguration['symbol'] ?? '';
$targetPriceThreshold = $alertConfiguration['target_price'] ?? 0;
$alertCondition = $alertConfiguration['condition'] ?? 'ABOVE';

if (!$activeUserId || !$targetStockSymbol) {
    json_response(['error' => 'Invalid input'], 400);
}

$stockIdLookup = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
$stockIdLookup->bind_param("s", $targetStockSymbol);
$stockIdLookup->execute();

$stockLookupResult = $stockIdLookup->get_result();
$stockRecord = $stockLookupResult->fetch_assoc();
$stockIdLookup->close();

if (!$stockRecord) {
    json_response(['error' => 'Stock not found'], 404);
}

$alertInsertionStmt = $mydb->prepare("INSERT INTO price_alerts (user_id, stock_id, target_price, `condition`) VALUES (?, ?, ?, ?)");
$alertInsertionStmt->bind_param("iids", $activeUserId, $stockRecord['id'], $targetPriceThreshold, $alertCondition);

if ($alertInsertionStmt->execute()) {
    json_response(['status' => 'success', 'message' => 'Alert set']);
} else {
    json_response(['error' => 'DB Error: ' . $alertInsertionStmt->error], 500);
}
$alertInsertionStmt->close();
?>
