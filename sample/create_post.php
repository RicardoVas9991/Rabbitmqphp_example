<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    json_response(['error' => 'Authentication required'], 401);
}
$activeUserId = $_SESSION['user_id'];
$activeUsername = $_SESSION['username'];

$jsonRequestPayload = get_json_input();
$targetStockSymbol = $jsonRequestPayload['symbol'] ?? '';
$postTitle = $jsonRequestPayload['title'] ?? '';
$postContent = $jsonRequestPayload['content'] ?? '';

if (empty($targetStockSymbol) || empty($postTitle) || empty($postContent)) {
    json_response(['error' => 'Missing symbol, title, or content'], 400);
}

try {
    $stockLookupStmt = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
    $stockLookupStmt->bind_param("s", $targetStockSymbol);
    $stockLookupStmt->execute();
    
    $stockResult = $stockLookupStmt->get_result();
    $stockRecord = $stockResult->fetch_assoc();
    $stockLookupStmt->close();

    if (!$stockRecord) {
        json_response(['error' => 'Stock not found'], 404);
    }

    $threadInsertionStmt = $mydb->prepare("INSERT INTO threads (stock_id, author_username, title, content) VALUES (?, ?, ?, ?)");
    $threadInsertionStmt->bind_param("isss", $stockRecord['id'], $activeUsername, $postTitle, $postContent);
    $threadInsertionStmt->execute();
    
    $generatedThreadId = $mydb->insert_id;
    $threadInsertionStmt->close();

    json_response(['status' => 'success', 'message' => 'Post created!', 'new_post_id' => $generatedThreadId]);

} catch (Exception $databaseException) {
    json_response(['error' => 'Database error: ' . $databaseException->getMessage()], 500);
}
?>
