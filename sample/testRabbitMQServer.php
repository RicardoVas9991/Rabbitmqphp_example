#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Function that routes database-related requests to the dbServer
function forwardToDB($request)
{
    $dbClient = new rabbitMQClient("testRabbitMQ.ini", "sharedServer");
    return $dbClient->send_request($request);
}

// Main processor
function requestProcessor($request)
{
    echo "Broker received request:" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return ["returnCode" => 1, "message" => "No request type provided"];
    }

    switch (strtolower($request['type'])) {
        case "login":
        case "register":
	case "validate_session":
	
            // Forward these directly to dbServer
            return forwardToDB($request);

        default:
            return ["returnCode" => 98, "message" => "Unknown request type"];
    }
}

// Start the broker server
//$server = new rabbitMQServer("testRabbitMQ.ini", "brokerServer");
//echo "Broker server active and waiting for requests..." . PHP_EOL;
//$server->process_requests('requestProcessor');

//echo "Broker server shutting down." . PHP_EOL;
?>

