#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('mysqlconnect.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 1. Load Configuration
$ini_file = "testRabbitMQ.ini";
if (!file_exists($ini_file)) {
    die("Error: Config file not found.\n");
}
$params = parse_ini_file($ini_file, true);
$config = $params['tradeServer'];

// 2. Connect
try {
    $connection = new AMQPStreamConnection(
        $config['BROKER_HOST'], $config['BROKER_PORT'], 
        $config['USER'], $config['PASSWORD'], $config['VHOST']
    );
    $channel = $connection->channel();
} catch (\Exception $e) {
    echo "Connection Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 3. Setup Queue
$queue = $config['QUEUE'];
$channel->queue_declare($queue, false, true, false, false);
echo "Trade Worker connected to '$queue'..." . PHP_EOL;

// 4. Trade Logic Function
function processTrade($request) {
    global $mydb; 
    
    $user_id  = $request['user_id'];
    $symbol   = $request['symbol'];
    $quantity = (int)$request['quantity'];
    $type     = $request['type']; 

    echo "Processing $type: $quantity x $symbol for User $user_id\n";

    $stmt = $mydb->prepare("INSERT INTO transactions (user_id, symbol, quantity, type, timestamp) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssis", $user_id, $symbol, $quantity, $type);
    
    if ($stmt->execute()) {
        return ['status' => 'success', 'message' => "Trade executed: $type $quantity $symbol"];
    } else {
        return ['status' => 'error', 'message' => "DB Error: " . $stmt->error];
    }
}

// 5. Callback Handler
$callback = function(AMQPMessage $msg) use ($channel) {
    $data = json_decode($msg->body, true);
    if ($data) {
        $result = processTrade($data);
        
        $responseMsg = new AMQPMessage(
            json_encode($result),
            ['correlation_id' => $msg->get('correlation_id')]
        );

        $channel->basic_publish($responseMsg, '', $msg->get('reply_to'));
        echo "Reply sent.\n";
    }
    $msg->ack();
};

$channel->basic_consume($queue, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
