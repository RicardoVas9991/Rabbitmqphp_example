<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header("Content-Type: application/json");

$targetStockSymbol = $_GET['symbol'] ?? '';
if (!$targetStockSymbol) {
    echo json_encode(['error' => 'No symbol provided']);
    exit;
}

class StockQuoteRpcClient {
    private $brokerConnection;
    private $quoteChannel;
    private $temporaryReplyQueue;
    private $rpcResponse;
    private $uniqueCorrelationId;
    private $targetWorkerQueue;

    public function __construct() {
        $rabbitMqConfigFile = "testRabbitMQ.ini";
        $parsedIniConfig = parse_ini_file($rabbitMqConfigFile, true);
        $serverConfig = $parsedIniConfig['testServer'];

        $this->targetWorkerQueue = $serverConfig['QUEUE'];

        $this->brokerConnection = new AMQPStreamConnection(
            $serverConfig['BROKER_HOST'],
            $serverConfig['BROKER_PORT'],
            $serverConfig['USER'],
            $serverConfig['PASSWORD'],
            $serverConfig['VHOST']
        );
        $this->quoteChannel = $this->brokerConnection->channel();
        
        list($this->temporaryReplyQueue, ,) = $this->quoteChannel->queue_declare("", false, false, true, false);
        $this->quoteChannel->basic_consume($this->temporaryReplyQueue, '', false, true, false, false, array($this, 'handleRpcResponse'));
    }

    public function handleRpcResponse($amqpResponseMessage) {
        if ($amqpResponseMessage->get('correlation_id') == $this->uniqueCorrelationId) {
            $this->rpcResponse = $amqpResponseMessage->body;
        }
    }

    public function requestStockQuote($symbolToQuery) {
        $this->rpcResponse = null;
        $this->uniqueCorrelationId = uniqid();

        $quoteRequestPayload = [
            'type' => 'get_quote', 
            'symbol' => $symbolToQuery
        ];

        $outgoingAmqpMessage = new AMQPMessage(
            json_encode($quoteRequestPayload),
            array('correlation_id' => $this->uniqueCorrelationId, 'reply_to' => $this->temporaryReplyQueue)
        );

        $this->quoteChannel->basic_publish($outgoingAmqpMessage, '', $this->targetWorkerQueue);
        
        while (!$this->rpcResponse) {
            $this->quoteChannel->wait();
        }
        return $this->rpcResponse;
    }
}

try {
    $stockQuoteClient = new StockQuoteRpcClient();
    echo $stockQuoteClient->requestStockQuote($targetStockSymbol);
} catch (Exception $applicationException) {
    echo json_encode(['error' => $applicationException->getMessage()]);
}
?>
