#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('mysqlconnect.php');

$api_key = 'X06XHO4GPPMMFGJJ';
$limit = 5; 

global $pdo; 

echo "Starting price fetch (Alpha Vantage, LIMIT $limit)...\n";

try {
    $stmt = $pdo->query(
        "SELECT s.id, s.symbol 
         FROM stocks s
         LEFT JOIN stock_prices sp ON s.id = sp.stock_id
         ORDER BY sp.last_updated ASC
         LIMIT $limit"
    );
    $stocks_to_track = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($stocks_to_track)) {
        echo "No stocks to track. Exiting.\n";
        exit;
    }

    $client = new rabbitMQClient("testRabbitMQ.ini", "priceServer");

    foreach ($stocks_to_track as $stock) {
        $symbol = $stock['symbol'];
        $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$api_key";
        
        $response_json = @file_get_contents($url);
        if ($response_json === FALSE) {
            echo "Failed to fetch price for $symbol\n";
            continue;
        }
        
        $data = json_decode($response_json, true);

        if (empty($data) || isset($data['Note']) || !isset($data['Global Quote']['05. price'])) {
            echo "API Error/Limit for $symbol. Skipping.\n";
            // If we hit the limit, stop immediately
            if(isset($data['Note'])) {
                echo "RATE LIMIT HIT. Shutting down producer.\n";
                break; 
            }
            continue;
        }

        $current_price = $data['Global Quote']['05. price'];

        $request = [
            'type'       => 'update_price',
            'stock_id'   => $stock['id'],
            'symbol'     => $symbol,
            'price'      => $current_price
        ];

        $response = $client->send_request($request);
        echo "Sent $symbol @ $current_price. Worker replied: " . ($response['status'] ?? 'error') . "\n";
        
        sleep(15); 
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Price fetch complete.\n";
?>

