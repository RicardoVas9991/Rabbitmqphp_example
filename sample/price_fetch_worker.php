#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('mysqlconnect.php');
require_once('config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$queue = 'priceQueue';

try {
    $connection = new AMQPStreamConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS, RABBIT_VHOST);
    $channel = $connection->channel();
} catch (Exception $e) {
    exit(1);
}

$channel->queue_declare($queue, false, true, false, false);
echo "Price Worker listening on $queue...\n";

$callback = function(AMQPMessage $msg) {
    global $mydb;
    
    echo "Received: " . $msg->body . "\n";
    $data = json_decode($msg->body, true);
    
    if ($data && isset($data['symbol'], $data['price'])) {
        $symbol = $data['symbol'];
        $price = $data['price'];

        $stmt = $mydb->prepare("SELECT id FROM stocks WHERE symbol = ?");
        $stmt->bind_param("s", $symbol);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            $stock_id = $row['id'];
            $stmt = $mydb->prepare("INSERT INTO stock_prices (stock_id, current_price) VALUES (?, ?) ON DUPLICATE KEY UPDATE current_price = ?");
            $stmt->bind_param("idd", $stock_id, $price, $price);
            $stmt->execute();
            $stmt->close();
            echo "Updated DB: $symbol -> $price\n";
        } else {
            echo "Stock $symbol not found in DB.\n";
        }
    }
    $msg->ack();
};

$channel->basic_consume($queue, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
?>
