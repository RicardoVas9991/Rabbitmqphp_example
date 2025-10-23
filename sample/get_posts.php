<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php'); 

$symbol = $_GET['symbol'] ?? '';
if (empty($symbol)) {
    json_response(['error' => 'No symbol provided'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    $stock = $stmt->fetch();

    if (!$stock) {
        json_response(['error' => 'Stock not found'], 404);
    }
    
    $stmt = $pdo->prepare("SELECT id, title, author_username, created_at FROM threads WHERE stock_id = ? ORDER BY created_at DESC");
    $stmt->execute([$stock['id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response($posts);

} catch (Exception $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

