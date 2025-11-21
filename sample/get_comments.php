<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');

$targetThreadId = (int)($_GET['thread_id'] ?? 0);

if ($targetThreadId <= 0) {
    json_response(['error' => 'A valid positive Thread ID is required.'], 400);
}

try {
    $commentFetchQuery = $mydb->prepare("SELECT id, author_username, body, created_at FROM comments WHERE thread_id = ? ORDER BY created_at ASC");
    $commentFetchQuery->bind_param("i", $targetThreadId);
    $commentFetchQuery->execute();
    
    $commentResultSet = $commentFetchQuery->get_result();
    $threadComments = $commentResultSet->fetch_all(MYSQLI_ASSOC);
    $commentFetchQuery->close();

    json_response($threadComments);

} catch (Exception $databaseException) {
    json_response(['error' => 'Comment retrieval failed: ' . $databaseException->getMessage()], 500);
}
?>
