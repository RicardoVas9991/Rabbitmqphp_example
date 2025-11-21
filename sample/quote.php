<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

$targetStockSymbol = strtoupper(trim($_GET['symbol'] ?? ''));
if ($targetStockSymbol === '') {
    echo json_encode(["ok" => false, "error" => "No symbol provided"]);
    exit;
}

class MarketDataRpcClient {
    private $brokerConnection;
    private $marketDataChannel;
    private $temporaryReplyQueue;
    private $marketDataResponse;
    private $requestCorrelationId;
    private $targetServiceQueue;

    public function __construct() {
        $rabbitMqConfigFile = __DIR__ . '/testRabbitMQ.ini';
        if (!file_exists($rabbitMqConfigFile)) {
            throw new Exception("Config file not found");
        }
        $parsedIniConfig = parse_ini_file($rabbitMqConfigFile, true);
        
        $serverConfig = $parsedIniConfig['testServer']; 

        $this->targetServiceQueue = $serverConfig['QUEUE'];

        $this->brokerConnection = new AMQPStreamConnection(
            $serverConfig['BROKER_HOST'],
            $serverConfig['BROKER_PORT'],
            $serverConfig['USER'],
            $serverConfig['PASSWORD'],
            $serverConfig['VHOST']
        );
        $this->marketDataChannel = $this->brokerConnection->channel();
        
        list($this->temporaryReplyQueue, ,) = $this->marketDataChannel->queue_declare(
            "", false, false, true, false
        );

        $this->marketDataChannel->basic_consume(
            $this->temporaryReplyQueue, '', false, true, false, false, 
            array($this, 'handleMarketDataResponse')
        );
    }

    public function handleMarketDataResponse($amqpResponseMessage) {
        if ($amqpResponseMessage->get('correlation_id') == $this->requestCorrelationId) {
            $this->marketDataResponse = $amqpResponseMessage->body;
        }
    }

    public function requestStockQuote($symbolToQuery) {
        $this->marketDataResponse = null;
        $this->requestCorrelationId = uniqid();

        $quoteRequestPayload = [
            'type' => 'search', 
            'query' => $symbolToQuery
        ];

        $outgoingAmqpMessage = new AMQPMessage(
            json_encode($quoteRequestPayload),
            array(
                'correlation_id' => $this->requestCorrelationId,
                'reply_to'       => $this->temporaryReplyQueue
            )
        );

        $this->marketDataChannel->basic_publish($outgoingAmqpMessage, '', $this->targetServiceQueue);
        
        while (!$this->marketDataResponse) {
            $this->marketDataChannel->wait();
        }
        return $this->marketDataResponse;
    }
}

try {
    $marketClient = new MarketDataRpcClient();
    echo $marketClient->requestStockQuote($targetStockSymbol);
} catch (Exception $applicationException) {
    echo json_encode(["ok" => false, "error" => $applicationException->getMessage()]);
}
?>
