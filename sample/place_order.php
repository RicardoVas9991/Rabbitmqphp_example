<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$orderJsonPayload = json_decode(file_get_contents('php://input'), true);

if (!$orderJsonPayload) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

class OrderSubmissionClient {
    private $brokerConnection;
    private $orderChannel;
    private $executionReplyQueue;
    private $executionResponse;
    private $orderCorrelationId;
    private $targetTradeQueue;

    public function __construct() {
        $parsedIniConfig = parse_ini_file("testRabbitMQ.ini", true);
        $tradeConfig = $parsedIniConfig['tradeServer']; 

        $this->targetTradeQueue = $tradeConfig['QUEUE'];

        $this->brokerConnection = new AMQPStreamConnection(
            $tradeConfig['BROKER_HOST'], $tradeConfig['BROKER_PORT'], 
            $tradeConfig['USER'], $tradeConfig['PASSWORD'], $tradeConfig['VHOST']
        );
        $this->orderChannel = $this->brokerConnection->channel();
        list($this->executionReplyQueue, ,) = $this->orderChannel->queue_declare("", false, false, true, false);
        $this->orderChannel->basic_consume($this->executionReplyQueue, '', false, true, false, false, [$this, 'handleExecutionReport']);
    }

    public function handleExecutionReport($amqpReportMessage) {
        if ($amqpReportMessage->get('correlation_id') == $this->orderCorrelationId) {
            $this->executionResponse = $amqpReportMessage->body;
        }
    }

    public function submitOrderToExchange($orderDetails) {
        $this->executionResponse = null;
        $this->orderCorrelationId = uniqid();

        $amqpOrderMessage = new AMQPMessage(
            json_encode($orderDetails),
            ['correlation_id' => $this->orderCorrelationId, 'reply_to' => $this->executionReplyQueue]
        );

        $this->orderChannel->basic_publish($amqpOrderMessage, '', $this->targetTradeQueue);
        
        while (!$this->executionResponse) {
            $this->orderChannel->wait();
        }
        return $this->executionResponse;
    }
}

try {
    $tradingClient = new OrderSubmissionClient();
    echo $tradingClient->submitOrderToExchange($orderJsonPayload);
} catch (Exception $applicationException) {
    echo json_encode(['status' => 'error', 'message' => $applicationException->getMessage()]);
}
?>
