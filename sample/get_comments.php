<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');

$thread_id = (int)($_GET['thread_id'] ?? 0);
if ($thread_id <= 0) {
    json_response(['error' => 'No thread ID provided'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id, author_username, body, created_at FROM comments WHERE thread_id = ? ORDER BY created_at ASC");
    $stmt->execute([$thread_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response($comments);

} catch (Exception $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

