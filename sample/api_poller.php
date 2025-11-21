#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$alphaVantageApiKey = 'ALPHAVANTAGE_API_KEY';

$marketDataConnection = new AMQPStreamConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS, RABBIT_VHOST);
$marketDataChannel = $marketDataConnection->channel();

$priceUpdateQueueName = 'priceQueue';
$marketDataChannel->queue_declare($priceUpdateQueueName, false, true, false, false);

$monitoredStockTickers = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA'];
$targetSymbol = $monitoredStockTickers[date('G') % count($monitoredStockTickers)];

echo "Fetching $targetSymbol...\n";

$alphaVantageEndpoint = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$targetSymbol&apikey=$alphaVantageApiKey";
$rawApiData = @file_get_contents($alphaVantageEndpoint);
$parsedMarketData = json_decode($rawApiData, true);

if (isset($parsedMarketData['Global Quote']['05. price'])) {
    $currentMarketPrice = $parsedMarketData['Global Quote']['05. price'];
    
    $priceUpdatePacket = [
        'type' => 'update_price',
        'symbol' => $targetSymbol,
        'price' => $currentMarketPrice
    ];

    $amqpPriceMessage = new AMQPMessage(json_encode($priceUpdatePacket));
    $marketDataChannel->basic_publish($amqpPriceMessage, '', $priceUpdateQueueName);
    echo "Sent $targetSymbol @ $currentMarketPrice\n";
} else {
    echo "Failed to fetch price.\n";
}

$marketDataChannel->close();
$marketDataConnection->close();
?>
