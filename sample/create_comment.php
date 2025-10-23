<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php'); // $pdo
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    json_response(['error' => 'Authentication required'], 401);
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$input = get_json_input();
$thread_id = (int)($input['thread_id'] ?? 0);
$body = $input['body'] ?? '';

if ($thread_id <= 0 || empty($body)) {
    json_response(['error' => 'Missing thread ID or comment body'], 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (thread_id, author_username, body) VALUES (?, ?, ?)");
    $stmt->execute([$thread_id, $username, $body]);
    $new_comment_id = $pdo->lastInsertId();

    json_response(['status' => 'success', 'message' => 'Comment posted!', 'new_comment_id' => $new_comment_id]);

} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
         json_response(['error' => 'The thread you are replying to does not exist.'], 404);
    }
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

