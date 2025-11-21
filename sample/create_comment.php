<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    json_response(['error' => 'Authentication required'], 401);
}
$activeUserId = $_SESSION['user_id'];
$activeUsername = $_SESSION['username'];

$commentPayload = get_json_input();
$targetThreadId = (int)($commentPayload['thread_id'] ?? 0);
$commentBodyText = $commentPayload['body'] ?? '';

if ($targetThreadId <= 0 || empty($commentBodyText)) {
    json_response(['error' => 'Missing thread ID or comment body'], 400);
}

try {
    $commentInsertionStmt = $mydb->prepare("INSERT INTO comments (thread_id, author_username, body) VALUES (?, ?, ?)");
    $commentInsertionStmt->bind_param("iss", $targetThreadId, $activeUsername, $commentBodyText);
    $commentInsertionStmt->execute();
    
    $generatedCommentId = $mydb->insert_id;
    $commentInsertionStmt->close();

    json_response(['status' => 'success', 'message' => 'Comment posted!', 'new_comment_id' => $generatedCommentId]);

} catch (Exception $databaseException) {
    if (str_contains($databaseException->getMessage(), 'foreign key constraint fails')) {
         json_response(['error' => 'The thread you are replying to does not exist.'], 404);
    }
    json_response(['error' => 'Database error: ' . $databaseException->getMessage()], 500);
}
?>
