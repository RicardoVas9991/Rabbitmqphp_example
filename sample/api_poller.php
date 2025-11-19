#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$api_key = 'ALPHAVANTAGE_API_KEY';

$connection = new AMQPStreamConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS, RABBIT_VHOST);
$channel = $connection->channel();

$queue = 'priceQueue';
$channel->queue_declare($queue, false, true, false, false);

$tickers = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA'];
$symbol = $tickers[date('G') % count($tickers)];

echo "Fetching $symbol...\n";

$url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$api_key";
$json = @file_get_contents($url);
$data = json_decode($json, true);

if (isset($data['Global Quote']['05. price'])) {
    $price = $data['Global Quote']['05. price'];
    
    $payload = [
        'type' => 'update_price',
        'symbol' => $symbol,
        'price' => $price
    ];

    $msg = new AMQPMessage(json_encode($payload));
    $channel->basic_publish($msg, '', $queue);
    echo "Sent $symbol @ $price\n";
} else {
    echo "Failed to fetch price.\n";
}

$channel->close();
$connection->close();
?>
