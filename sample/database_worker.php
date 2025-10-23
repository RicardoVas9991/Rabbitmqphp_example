<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqplib\Connection\AMQPStreamConnection;

$rabbitmq_host = 'localhost'; 
$rabbitmq_port = 5672;
$rabbitmq_user = 'admin';
$rabbitmq_pass = 'password';
$queue_name = 'price_updates';

$db_host = 'localhost';
$db_name = 'your_stock_db';
$db_user = 'your_db_user';
$db_pass = 'your_db_pass';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_pass);
    $channel = $connection->channel();
    $channel->queue_declare($queue_name, false, true, false, false);

    echo " [*] Waiting for price updates. To exit press CTRL+C\n";

    $callback = function ($msg) use ($pdo) {
        echo ' [x] Received ', $msg->body, "\n";
        
        $data = json_decode($msg->body, true);
        $symbol = $data['symbol'];
        $price = $data['price'];

        try {
            $stmt = $pdo->prepare("SELECT id FROM stocks WHERE symbol = ?");
            $stmt->execute([$symbol]);
            $stock = $stmt->fetch();

            if ($stock) {
                $stock_id = $stock['id'];

                $sql = "INSERT INTO stock_prices (stock_id, current_price)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE
                            current_price = VALUES(current_price)";
                
                $update_stmt = $pdo->prepare($sql);
                $update_stmt->execute([$stock_id, $price]);

                echo " [✔] Updated price for $symbol.\n";
            } else {
                echo " [!] Warning: Price received for unknown symbol '$symbol'.\n";
            }

            $msg->ack();

        } catch (Exception $e) {
            echo " [!] Database Error: " . $e->getMessage() . "\n";
            $msg->nack(); 
        }
    };

    $channel->basic_qos(null, 1, false);
    $channel->basic_consume($queue_name, '', false, false, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

