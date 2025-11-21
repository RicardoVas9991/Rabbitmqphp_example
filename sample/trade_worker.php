#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('mysqlconnect.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$rabbitMqConfigFile = "testRabbitMQ.ini";
if (!file_exists($rabbitMqConfigFile)) {
    die("Error: Config file not found.\n");
}
$parsedIniConfig = parse_ini_file($rabbitMqConfigFile, true);
$tradeServerConfig = $parsedIniConfig['tradeServer'];

try {
    $tradeBrokerConnection = new AMQPStreamConnection(
        $tradeServerConfig['BROKER_HOST'], $tradeServerConfig['BROKER_PORT'], 
        $tradeServerConfig['USER'], $tradeServerConfig['PASSWORD'], $tradeServerConfig['VHOST']
    );
    $tradeExecutionChannel = $tradeBrokerConnection->channel();
} catch (\Exception $connectionException) {
    echo "Connection Error: " . $connectionException->getMessage() . PHP_EOL;
    exit(1);
}

$tradingQueueName = $tradeServerConfig['QUEUE'];
$tradeExecutionChannel->queue_declare($tradingQueueName, false, true, false, false);
echo "Trade Worker connected to '$tradingQueueName'..." . PHP_EOL;

function executeStockTransaction($tradeDetails) {
    global $mydb; 
    
    $activeUserId  = $tradeDetails['user_id'];
    $stockSymbol   = $tradeDetails['symbol'];
    $shareQuantity = (int)$tradeDetails['quantity'];
    $transactionType = $tradeDetails['type']; 

    echo "Processing $transactionType: $shareQuantity x $stockSymbol for User $activeUserId\n";

    $transactionInsertStmt = $mydb->prepare("INSERT INTO transactions (user_id, symbol, quantity, type, timestamp) VALUES (?, ?, ?, ?, NOW())");
    $transactionInsertStmt->bind_param("ssis", $activeUserId, $stockSymbol, $shareQuantity, $transactionType);
    
    if ($transactionInsertStmt->execute()) {
        return ['status' => 'success', 'message' => "Trade executed: $transactionType $shareQuantity $stockSymbol"];
    } else {
        return ['status' => 'error', 'message' => "DB Error: " . $transactionInsertStmt->error];
    }
}

$incomingTradeHandler = function(AMQPMessage $amqpTradeEnvelope) use ($tradeExecutionChannel) {
    $decodedTradeData = json_decode($amqpTradeEnvelope->body, true);
    if ($decodedTradeData) {
        $executionResult = executeStockTransaction($decodedTradeData);
        
        $tradeResponseMessage = new AMQPMessage(
            json_encode($executionResult),
            ['correlation_id' => $amqpTradeEnvelope->get('correlation_id')]
        );

        $tradeExecutionChannel->basic_publish($tradeResponseMessage, '', $amqpTradeEnvelope->get('reply_to'));
        echo "Reply sent.\n";
    }
    $amqpTradeEnvelope->ack();
};

$tradeExecutionChannel->basic_consume($tradingQueueName, '', false, false, false, false, $incomingTradeHandler);

while ($tradeExecutionChannel->is_consuming()) {
    $tradeExecutionChannel->wait();
}

$tradeExecutionChannel->close();
$tradeBrokerConnection->close();
?>
