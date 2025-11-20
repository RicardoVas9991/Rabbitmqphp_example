<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');

$api_key = 'X06XHO4GPPMMFGJJ'; 
$symbol = $_GET['symbol'] ?? '';

if (empty($symbol)) {
    json_response(['error' => 'No symbol provided'], 400);
}

$url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$api_key";

$response_json = @file_get_contents($url);
if ($response_json === FALSE) {
    json_response(['error' => 'Failed to contact API'], 500);
}

$data = json_decode($response_json, true);

if (empty($data) || isset($data['Note']) || !isset($data['Global Quote'])) {
    $message = $data['Note'] ?? 'Invalid symbol or API error.';
    json_response(['error' => $message], 500);
}

$quote = $data['Global Quote'];
if (empty($quote)) {
    json_response(['error' => "No data found for symbol $symbol"], 404);
}

try {
    $result = [
        'symbol' => $quote['01. symbol'],
        'price' => $quote['05. price'],
        'name' => $symbol 
    ];
    
    $stmt = $mydb->prepare("INSERT INTO stocks (symbol, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name");
    $stmt->bind_param("ss", $result['symbol'], $result['symbol']);
    $stmt->execute();

    json_response($result, 200);

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
?>
