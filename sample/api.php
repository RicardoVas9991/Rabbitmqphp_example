<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$clientRequestPayload = json_decode(file_get_contents('php://input'), true);
if (!$clientRequestPayload) {
    echo json_encode(['returnCode' => 1, 'message' => 'Invalid JSON']);
    exit;
}

class AuthenticationServiceClient {
    private $amqpConnection;
    private $amqpChannel;
    private $temporaryReplyQueue;
    private $serviceResponse;
    private $uniqueRequestIdentifier;
    private $targetServiceQueue;

    public function __construct() {
        $parsedIniConfig = parse_ini_file("testRabbitMQ.ini", true);
        $serverConfig = $parsedIniConfig['testServer'];

        $this->targetServiceQueue = $serverConfig['QUEUE'];

        $this->amqpConnection = new AMQPStreamConnection(
            $serverConfig['BROKER_HOST'],
            $serverConfig['BROKER_PORT'],
            $serverConfig['USER'],
            $serverConfig['PASSWORD'],
            $serverConfig['VHOST']
        );
        $this->amqpChannel = $this->amqpConnection->channel();
        
        list($this->temporaryReplyQueue, ,) = $this->amqpChannel->queue_declare("", false, false, true, false);
        $this->amqpChannel->basic_consume($this->temporaryReplyQueue, '', false, true, false, false, array($this, 'handleServiceResponse'));
    }

    public function handleServiceResponse($amqpResponseMessage) {
        if ($amqpResponseMessage->get('correlation_id') == $this->uniqueRequestIdentifier) {
            $this->serviceResponse = $amqpResponseMessage->body;
        }
    }

    public function sendAuthRequest($authDataPayload) {
        $this->serviceResponse = null;
        $this->uniqueRequestIdentifier = uniqid();

        $outgoingAmqpMessage = new AMQPMessage(
            json_encode($authDataPayload),
            array(
                'correlation_id' => $this->uniqueRequestIdentifier,
                'reply_to'       => $this->temporaryReplyQueue
            )
        );

        $this->amqpChannel->basic_publish($outgoingAmqpMessage, '', $this->targetServiceQueue);
        
        while (!$this->serviceResponse) {
            $this->amqpChannel->wait();
        }
        return $this->serviceResponse;
    }
}

try {
    $authClient = new AuthenticationServiceClient();
    echo $authClient->sendAuthRequest($clientRequestPayload);
} catch (Exception $applicationException) {
    echo json_encode(['returnCode' => 500, 'message' => "RabbitMQ Error: " . $applicationException->getMessage()]);
}
?>
