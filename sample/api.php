<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Standard CORS and JSON headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle Pre-flight check
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['returnCode' => 1, 'message' => 'Invalid JSON']);
    exit;
}

class AuthRpcClient {
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $target_queue;

    public function __construct() {
        // --- READ INI FILE ---
        // We use the same 'testServer' section as the worker
        $params = parse_ini_file("testRabbitMQ.ini", true);
        $config = $params['testServer'];

        $this->target_queue = $config['QUEUE']; // Matches the worker's queue

        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'],
            $config['BROKER_PORT'],
            $config['USER'],
            $config['PASSWORD'],
            $config['VHOST']
        );
        $this->channel = $this->connection->channel();
        
        // Create temporary callback queue
        list($this->callback_queue, ,) = $this->channel->queue_declare("", false, false, true, false);
        $this->channel->basic_consume($this->callback_queue, '', false, true, false, false, array($this, 'onResponse'));
    }

    public function onResponse($rep) {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function sendRequest($data) {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            json_encode($data),
            array(
                'correlation_id' => $this->corr_id,
                'reply_to'       => $this->callback_queue
            )
        );

        $this->channel->basic_publish($msg, '', $this->target_queue);
        
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}

// Execute
try {
    $client = new AuthRpcClient();
    // $input contains 'type' (login/register), 'username', 'password', etc.
    echo $client->sendRequest($input);
} catch (Exception $e) {
    echo json_encode(['returnCode' => 500, 'message' => "RabbitMQ Error: " . $e->getMessage()]);
}
?>

