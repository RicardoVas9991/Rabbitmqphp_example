<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php'); 

$symbol = $_GET['symbol'] ?? '';
if (empty($symbol)) {
    json_response(['error' => 'No symbol provided'], 400);
}

try {
    // 1. Find stock ID
    $stmt = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
    $stmt->bind_param("s", $symbol);
    $stmt->execute();
    $res = $stmt->get_result();
    $stock = $res->fetch_assoc();

    if (!$stock) {
        json_response(['error' => 'Stock not found'], 404);
    }
    
    // 2. Get posts
    $stmt = $mydb->prepare("SELECT id, title, author_username, created_at FROM threads WHERE stock_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $stock['id']);
    $stmt->execute();
    
    $res = $stmt->get_result();
    $posts = $res->fetch_all(MYSQLI_ASSOC);

    json_response($posts);

} catch (Exception $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>
