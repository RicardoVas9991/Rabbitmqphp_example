#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Helper to print usage
function printUsage() {
    echo "Usage:\n";
    echo "  php auth_client.php register <username> <password> <email>\n";
    echo "  php auth_client.php login <username> <password>\n";
    echo "  php auth_client.php validate_session <sessionId> <authToken>\n";
    exit(1);
}

// Argument parsing
if ($argc < 2) {
    printUsage();
}

$type = $argv[1];
$request = [];

switch ($type) {
    case "register":
        if ($argc < 5) printUsage();
        $request = [
            "type" => "register",
            "username" => $argv[2],
            "password" => $argv[3],
            "email" => $argv[4],
        ];
        break;
    case "login":
        if ($argc < 4) printUsage();
        $request = [
            "type" => "login",
            "username" => $argv[2],
            "password" => $argv[3],
        ];
        break;
    case "validate_session":
        if ($argc < 4) printUsage();
        $request = [
            "type" => "validate_session",
            "sessionId" => $argv[2],
            "authToken" => $argv[3],
        ];
        break;
    default:
        printUsage();
}

// Create client and send request
$client = new rabbitMQClient("testRabbitMQ.ini", "sharedServer");

try {
    $response = $client->send_request($request);
    echo "Server response:\n";
    print_r($response);
} catch (Exception $e) {
    echo "Error sending request: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
?>
