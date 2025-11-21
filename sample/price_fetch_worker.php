#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('mysqlconnect.php');
require_once('config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$priceIngestionQueueName = 'priceQueue';

try {
    $priceBrokerConnection = new AMQPStreamConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS, RABBIT_VHOST);
    $priceUpdateChannel = $priceBrokerConnection->channel();
} catch (Exception $connectionException) {
    exit(1);
}

$priceUpdateChannel->queue_declare($priceIngestionQueueName, false, true, false, false);
echo "Price Ingestion Engine active on: $priceIngestionQueueName..." . PHP_EOL;

$incomingPriceHandler = function(AMQPMessage $amqpPriceEnvelope) {
    global $mydb;
    
    echo "Received Payload: " . $amqpPriceEnvelope->body . PHP_EOL;
    $decodedPriceData = json_decode($amqpPriceEnvelope->body, true);
    
    if ($decodedPriceData && isset($decodedPriceData['symbol'], $decodedPriceData['price'])) {
        $stockSymbol = $decodedPriceData['symbol'];
        $newMarketPrice = $decodedPriceData['price'];

        $stockLookupStmt = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
        $stockLookupStmt->bind_param("s", $stockSymbol);
        $stockLookupStmt->execute();
        $lookupResult = $stockLookupStmt->get_result();
        $stockRecord = $lookupResult->fetch_assoc();
        $stockLookupStmt->close();

        if ($stockRecord) {
            $internalStockId = $stockRecord['id'];
            $priceUpdateStmt = $mydb->prepare("INSERT INTO stock_prices (stock_id, current_price) VALUES (?, ?) ON DUPLICATE KEY UPDATE current_price = ?");
            $priceUpdateStmt->bind_param("idd", $internalStockId, $newMarketPrice, $newMarketPrice);
            $priceUpdateStmt->execute();
            $priceUpdateStmt->close();
            echo "Database Updated: $stockSymbol -> $newMarketPrice" . PHP_EOL;
        } else {
            echo "Stock symbol $stockSymbol not found in database." . PHP_EOL;
        }
    }
    $amqpPriceEnvelope->ack();
};

$priceUpdateChannel->basic_consume($priceIngestionQueueName, '', false, false, false, false, $incomingPriceHandler);

while ($priceUpdateChannel->is_consuming()) {
    $priceUpdateChannel->wait();
}
?>
