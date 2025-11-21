<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 1. CORS & JSON Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle Pre-flight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 2. Get Data from Frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['returnCode' => 1, 'message' => 'Invalid JSON input']);
    exit;
}

// 3. RPC Client Class
// This class handles the complex "Send and Wait" logic
class RpcClient {
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    public function __construct() {
        // --- CONFIGURATION (MUST MATCH WORKER) ---
        $host = '100.114.135.58';
        $port = 5672;
        $user = 'test';
        $pass = 'test';
        $vhost = 'testHost'; // Matches your worker

        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        $this->channel = $this->connection->channel();
        
        // Create a temporary, random queue for the response
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",    // Let RabbitMQ name it
            false, // Passive
            false, // Durable
            true,  // Exclusive (delete when connection closes)
            false  // Auto-delete
        );

        // Listen to that temporary queue
        $this->channel->basic_consume(
            $this->callback_queue, 
            '', 
            false, 
            true, 
            false, 
            false, 
            array($this, 'onResponse')
        );
    }

    public function onResponse($rep) {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($request_data) {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            json_encode($request_data),
            array(
                'correlation_id' => $this->corr_id,
                'reply_to'       => $this->callback_queue
            )
        );

        // Publish to 'testQueue' (Matches your worker)
        $this->channel->basic_publish($msg, '', 'testQueue');

        // Wait for the response
        while (!$this->response) {
            $this->channel->wait();
        }
        
        return $this->response;
    }
    
    public function close() {
        $this->channel->close();
        $this->connection->close();
    }
}

// 4. Execute Request
try {
    $client = new RpcClient();
    $response = $client->call($data);
    echo $response; // Send the worker's reply back to the browser
    $client->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "returnCode" => 500, 
        "message" => "RabbitMQ Error: " . $e->getMessage()
    ]);
}
?>
