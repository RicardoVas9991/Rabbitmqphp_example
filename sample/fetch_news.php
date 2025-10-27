<?php
require_once('api_helpers.php');
$api_key = 'X06XHO4GPPMMFGJJ';
$symbol = $_GET['symbol'] ?? '';

if (empty($symbol)) {
    json_response(['error' => 'No symbol provided'], 400);
}

$url = "https://www.alphavantage.co/query?function=NEWS_SENTIMENT&tickers=$symbol&apikey=$api_key";
$response_json = @file_get_contents($url);
$data = json_decode($response_json, true);

if (empty($data) || isset($data['Note'])) {
    json_response(['error' => $data['Note'] ?? 'API error'], 500);
}

$articles = $data['feed'] ?? [];

json_response(['articles' => $articles], 200);
?>

