<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 1. Standard API Headers
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); 

// 2. Get Symbol
$symbol = strtoupper(trim($_GET['symbol'] ?? ''));
if ($symbol === '') {
    echo json_encode(["ok" => false, "error" => "No symbol provided"]);
    exit;
}

// 3. RPC Client Class 
class QuoteRpcClient {
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $target_queue;

    public function __construct() {
        $ini_file = __DIR__ . '/testRabbitMQ.ini';
        if (!file_exists($ini_file)) {
            throw new Exception("Config file not found");
        }
        $params = parse_ini_file($ini_file, true);
        
        $config = $params['testServer']; 

        $this->target_queue = $config['QUEUE'];

        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'],
            $config['BROKER_PORT'],
            $config['USER'],
            $config['PASSWORD'],
            $config['VHOST']
        );
        $this->channel = $this->connection->channel();
        
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "", false, false, true, false
        );

        $this->channel->basic_consume(
            $this->callback_queue, '', false, true, false, false, 
            array($this, 'onResponse')
        );
    }

    public function onResponse($rep) {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function getQuote($symbol) {
        $this->response = null;
        $this->corr_id = uniqid();

        $request = [
            'type' => 'search', 
            'query' => $symbol
        ];

        $msg = new AMQPMessage(
            json_encode($request),
            array(
                'correlation_id' => $this->corr_id,
                'reply_to'       => $this->callback_queue
            )
        );

        $this->channel->basic_publish($msg, '', $this->target_queue);
        
        // Wait for response
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}

// 4. Execute
try {
    $client = new QuoteRpcClient();
    echo $client->getQuote($symbol);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
?>
