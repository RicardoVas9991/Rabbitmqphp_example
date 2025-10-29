<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);


require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");

$action = $_GET['action'] ?? '';

switch($action){
    case 'search':
        require __DIR__ . '/search.php';
        break;
    case 'quote':
        require __DIR__ . '/quote.php';
        break;
    default:
        echo json_encode(["ok" => false, "error" => "Invalid action"]);
        break;  
}

?>