#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function requestProcessor($request)
{
    echo "Broker received request:" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return ["returnCode" => 99, "message" => "Invalid request: Missing type"];
    }

    // Forward all database-related operations to the DB server
    switch (strtolower($request['type'])) {
        case "register":
        case "login":
        case "validate_session":
        case "update_user":
            // Forward to the database server queue
            $dbClient = new rabbitMQClient("testRabbitMQ.ini", "dbServer");
            $response = $dbClient->send_request($request);
            return $response;

        default:
            return ["returnCode" => 98, "message" => "Unknown request type"];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "brokerServer");

echo "Broker Server listening..." . PHP_EOL;
$server->process_requests('requestProcessor');
echo "Broker Server shutting down..." . PHP_EOL;
?>

