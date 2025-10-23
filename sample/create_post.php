<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php'); 
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    json_response(['error' => 'Authentication required'], 401);
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$input = get_json_input();
$symbol = $input['symbol'] ?? '';
$title = $input['title'] ?? '';
$content = $input['content'] ?? ''; // Assuming you have this column

if (empty($symbol) || empty($title) || empty($content)) {
    json_response(['error' => 'Missing symbol, title, or content'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    $stock = $stmt->fetch();

    if (!$stock) {
        json_response(['error' => 'Stock not found'], 404);
    }

    $stmt = $pdo->prepare("INSERT INTO threads (stock_id, author_username, title, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$stock['id'], $username, $title, $content]);
    $new_post_id = $pdo->lastInsertId();

    json_response(['status' => 'success', 'message' => 'Post created!', 'new_post_id' => $new_post_id]);

} catch (Exception $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

