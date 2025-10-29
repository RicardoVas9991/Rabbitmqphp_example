<?php
require __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPTimeoutException;

class RmqRpcClientIni {
    private array $cfg;
    private AMQPStreamConnection $connection;
    private $channel;
    private ?string $replyQueue = null;
    private bool $useSharedReply= false;
    private ?string $response = null;
    private ?string $correlationId = null;

    public function __construct(string $iniFile, string $profile){
        if (!is_file($iniFile)) {
            throw new InvalidArgumentException("INI file not found: $iniFile");
        }

        $ini = parse_ini_file($iniFile, true, INI_SCANNER_TYPED);

        if (!$ini || !isset($ini[$profile])) {
            throw new InvalidArgumentException("Profile '$profile' not found in INI file");
        }
        $c = $ini[$profile];

        $this->cfg = [
            'host'=>$c['BROKER_HOST'],
            'port'=>$c['BROKER_PORT'],
            'user'=>$c['USER'],
            'password'=>$c['PASSWORD'],
            'vhost'=>$c['VHOST'],
            'exchange'=>$c['EXCHANGE'],
            'exchange_type'=>strtolower($c['EXCHANGE_TYPE']),
            'queue'=>$c['QUEUE'],  
            'routing_key'=>$c['ROUTING_KEY'] ?? $c['QUEUE'],
            'reply_queue'=>$c['REPLY_QUEUE'] ?? null,
        ];
        
        $this->connection = new AMQPStreamConnection(
        $this->cfg['host'],
        $this->cfg['port'],
        $this->cfg['user'],
        $this->cfg['password'],
        $this->cfg['vhost']
        );
        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare(
            $this->cfg['exchange'],
            $this->cfg['exchange_type'],
            true,
            false,
            false
        );

        $this->channel->queue_declare(
            $this->cfg['queue'],
            true,
            false,
            false,
            false
        );

        try {
            [$q, , ] = $this->channel->queue_declare(
                "",
                false,
                false,
                true,
                true
            );
            $this->replyQueue = $q;
            $this->channel->basic_consume(
                $this->replyQueue,
                '',
                false,
                true,
                false,
                false,
                function($msg){
                    if ($msg->get('correlation_id') === $this->correlationId) {
                        $this->response = $msg->getBody();
                    }
                }
            );
        }catch (\Throwable $e){
            if (empty($this->cfg['reply_queue'])) 
                throw new RuntimeException("Failed to declare exclusive reply queue and no REPLY_QUEUE specified in INI");
            $this->useSharedReply = true;
            $this->channel->queue_declare($this->cfg['reply_queue'], true, false, false,false);
            $this->channel->basic_consume($this->cfg['reply_queue'], 'frontend-'.bin2hex(random_bytes(8)), false, true, false, false, function($msg){
                if ($msg->get('correlation_id') === $this->correlationId) $this->response = $msg->getBody();               
            }); 
        }

        $this->channel->set_return_listener(function($replyCode, $replyText, $exchange, $routingKey, $msg){
            $this->response = json_encode(['ok' => false,'error' => "Message returned: $replyText (code $replyCode)"]);
        });
    }

    public function call(string $type, string $key, int $timeoutMs=6000):?array{
        $this->response = null;
        $this->correlationId = bin2hex(random_bytes(16));

        $routingKey = $this->cfg['routing_key'];
        $props = [
            'content_type'      => 'application/json',
            'delivery_mode'     => 2,
            'correlation_id'    => $this->correlationId,
            'reply_to'          => $this->useSharedReply ? ($this->cfg['reply_queue'] ?? '') : $this->replyQueue,
        ];
        $msg = new AMQPMessage(json_encode(['type'=>$type,'key'=>$key], JSON_UNESCAPED_SLASHES), $props);
        $this->channel->basic_publish($msg, $this->cfg['exchange'], $routingKey, true);
        $deadline = microtime(true) + ($timeoutMs / 1000);
        while (microtime(true) < $deadline && $this->response === null){
            $remaining = max(0.1, $deadline - microtime(true));
            try{
                $this->channel->wait(null, false, $remaining);
            }catch(AMQPTimeoutException $e){
            }
        }
        if ($this->response === null) {
            return null;
        }   
        return json_decode($this->response, true);
    }
    
    public function close(): void {
        try{$this->channel->close();}catch(\Throwable $e){}
        try{$this->connection->close();}catch(\Throwable $e){}
    }
}
?>