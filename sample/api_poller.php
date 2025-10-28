#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php'; 
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$api_key = 'X06XHO4GPPMMFGJJ';
$rabbitmq_host = '100.114.135.58'; 
$rabbitmq_port = 5672;
$rabbitmq_user = 'test';
$rabbitmq_pass = 'test';
$queue_name = 'price_updates';
$date = date('Y-m-d H:i:s');

$all_stocks_to_track = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA'];

$current_hour = (int)date('G'); 
$stock_to_fetch = $all_stocks_to_track[$current_hour % count($all_stocks_to_track)];
$stocks_for_this_run = [$stock_to_fetch];

try {
    $connection = new AMQPStreamConnection(
        $rabbitmq_host, 
        $rabbitmq_port, 
        $rabbitmq_user, 
        $rabbitmq_pass
    );
    $channel = $connection->channel();
    $channel->queue_declare($queue_name, false, true, false, false);

    echo "[$date] Connected to RabbitMQ. Fetching " . implode(',', $stocks_for_this_run) . "\n";

    foreach ($stocks_for_this_run as $symbol) {
        $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$api_key";
        
        $response_json = @file_get_contents($url);
        $data = json_decode($response_json, true);

        if (empty($data) || isset($data['Note']) || !isset($data['Global Quote']['05. price'])) {
            $error_msg = $data['Note'] ?? 'Invalid response';
            echo "Failed to fetch price for $symbol: $error_msg\n";
            if(isset($data['Note'])) break; 
            continue;
        }

        $price = $data['Global Quote']['05. price'];
        
        $payload = json_encode([
            'symbol' => $symbol,
            'price' => $price
        ]);

        $msg = new AMQPMessage($payload, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($msg, '', $queue_name);

        echo "Published price for $symbol: $price\n";
    }

    $channel->close();
    $connection->close();
    echo "[$date] All prices published.\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

