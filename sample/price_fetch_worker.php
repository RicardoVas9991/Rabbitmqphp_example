#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('mysqlconnect.php'); 

require_once('vendor/autoload.php'); 
use PhpAmqpLib\Connection\AMQPStreamConnection;

echo "Ensuring queue 'price_update_queue' exists...\n";
try {
    $ini_settings = parse_ini_file("testRabbitMQ.ini", true)["priceServer"];

    $connection = new AMQPStreamConnection(
        $ini_settings['BROKER_HOST'],
        $ini_settings['BROKER_PORT'],
        $ini_settings['USER'],
        $ini_settings['PASSWORD'],
        $ini_settings['VHOST']
    );
    $channel = $connection->channel();
    
    $channel->queue_declare(
        $ini_settings['QUEUE'], 
        false, 
        true, 
        false,
        false 
    );

    $channel->exchange_declare(
        $ini_settings['EXCHANGE'],
        'direct', 
        false,
        true, 
        false
    );

    echo "Queue '{$ini_settings['QUEUE']}' is ready.\n";
    
    $channel->close();
    $connection->close();
} catch (Exception $e) {
    echo "Fatal Error: Could not set up RabbitMQ queue: " . $e->getMessage() . "\n";
    exit(1); 
}

global $pdo; 

function doPriceUpdate($request, $pdo)
{
    echo "Processing price update for: " . $request['symbol'] . "\n";
    try {
        $sql = "INSERT INTO stock_prices (stock_id, current_price)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                    current_price = VALUES(current_price)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $request['stock_id'],
            $request['price']
        ]);
        
        return ['status' => 'ok', 'message' => 'Price updated'];

    } catch (Exception $e) {
        echo "DB Error: " . $e->getMessage() . "\n";
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function requestProcessor($request)
{
    global $pdo; 
    echo "Received request" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return ["status" => "error", "message" => "No request type provided"];
    }

    switch (strtolower($request['type'])) {
        case "update_price":
            return doPriceUpdate($request, $pdo);
        
        default:
            return ["status" => "error", "message" => "Unknown request type for price worker"];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "priceServer");

echo "Price update worker active and waiting for requests..." . PHP_EOL;
$server->process_requests('requestProcessor');

echo "Price update worker shutting down." . PHP_EOL;
exit();
?>

