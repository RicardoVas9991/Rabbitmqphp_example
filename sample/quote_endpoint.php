<?php
require_once __DIR__ . '/vendor/autoload.php';
// require_once('rabbitMQLib.inc'); 

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header("Content-Type: application/json");

$symbol = $_GET['symbol'] ?? '';
if (!$symbol) {
    echo json_encode(['error' => 'No symbol provided']);
    exit;
}

class QuoteClient {
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $target_queue;

    public function __construct() {
        // --- READ INI FILE ---
        $params = parse_ini_file("testRabbitMQ.ini", true);
        $config = $params['testServer']; // Use the same section as the worker

        $this->target_queue = $config['QUEUE']; // Send to the worker's queue

        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'],
            $config['BROKER_PORT'],
            $config['USER'],
            $config['PASSWORD'],
            $config['VHOST']
        );
        $this->channel = $this->connection->channel();
        
        list($this->callback_queue, ,) = $this->channel->queue_declare("", false, false, true, false);
        $this->channel->basic_consume($this->callback_queue, '', false, true, false, false, array($this, 'onResponse'));
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
            'type' => 'get_quote', // Ensure your worker handles this
            'symbol' => $symbol
        ];

        $msg = new AMQPMessage(
            json_encode($request),
            array('correlation_id' => $this->corr_id, 'reply_to' => $this->callback_queue)
        );

        $this->channel->basic_publish($msg, '', $this->target_queue);
        
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}

try {
    $client = new QuoteClient();
    echo $client->getQuote($symbol);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
