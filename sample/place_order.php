<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 1. Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// 2. RPC Client
class TradeRpcClient {
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $target_queue;

    public function __construct() {
        $params = parse_ini_file("testRabbitMQ.ini", true);
        $config = $params['tradeServer']; 

        $this->target_queue = $config['QUEUE'];

        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'], $config['BROKER_PORT'], 
            $config['USER'], $config['PASSWORD'], $config['VHOST']
        );
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare("", false, false, true, false);
        $this->channel->basic_consume($this->callback_queue, '', false, true, false, false, [$this, 'onResponse']);
    }

    public function onResponse($rep) {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function sendTrade($data) {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            json_encode($data),
            ['correlation_id' => $this->corr_id, 'reply_to' => $this->callback_queue]
        );

        $this->channel->basic_publish($msg, '', $this->target_queue);
        
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}

// 3. Execute
try {
    $client = new TradeRpcClient();
    echo $client->sendTrade($input);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
