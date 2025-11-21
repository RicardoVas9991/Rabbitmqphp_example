<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header("Content-Type: application/json");

$query = $_GET['query'] ?? '';
if (!$query) {
    echo json_encode(['error' => 'No search query provided']);
    exit;
}

class SearchRpcClient {
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $target_queue;

    public function __construct() {
        $params = parse_ini_file("testRabbitMQ.ini", true);
        $config = $params['testServer']; 

        $this->target_queue = $config['QUEUE']; 

        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'], $config['BROKER_PORT'], $config['USER'], $config['PASSWORD'], $config['VHOST']
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

    public function search($query) {
        $this->response = null;
        $this->corr_id = uniqid();

        $request = [
            'type' => 'search',
            'query' => $query
        ];

        $msg = new AMQPMessage(json_encode($request), ['correlation_id' => $this->corr_id, 'reply_to' => $this->callback_queue]);
        $this->channel->basic_publish($msg, '', $this->target_queue);
        
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}

try {
    $client = new SearchRpcClient();
    echo $client->search($query);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
