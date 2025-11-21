<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');

$requestedSymbol = $_GET['symbol'] ?? '';

if (empty($requestedSymbol)) {
    json_response(['error' => 'A stock symbol parameter is required.'], 400);
}

try {
    $stockLookupQuery = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
    $stockLookupQuery->bind_param("s", $requestedSymbol);
    $stockLookupQuery->execute();
    
    $stockResult = $stockLookupQuery->get_result();
    $stockRecord = $stockResult->fetch_assoc();
    $stockLookupQuery->close();

    if (!$stockRecord) {
        json_response(['error' => "Stock symbol '$requestedSymbol' not found in database."], 404);
    }
    
    $internalStockId = $stockRecord['id'];
    
    $threadFetchQuery = $mydb->prepare("SELECT id, title, author_username, created_at FROM threads WHERE stock_id = ? ORDER BY created_at DESC");
    $threadFetchQuery->bind_param("i", $internalStockId);
    $threadFetchQuery->execute();
    
    $threadResultSet = $threadFetchQuery->get_result();
    $discussionThreads = $threadResultSet->fetch_all(MYSQLI_ASSOC);
    $threadFetchQuery->close();

    json_response($discussionThreads);

} catch (Exception $databaseException) {
    json_response(['error' => 'Message board retrieval failed: ' . $databaseException->getMessage()], 500);
}
?>
