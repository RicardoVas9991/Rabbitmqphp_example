<?php
require_once('api_helpers.php');

$alphaVantageApiKey = 'X06XHO4GPPMMFGJJ';
$targetStockSymbol = $_GET['symbol'] ?? '';

if (empty($targetStockSymbol)) {
    json_response(['error' => 'No symbol provided'], 400);
}

$newsApiUrl = "https://www.alphavantage.co/query?function=NEWS_SENTIMENT&tickers=$targetStockSymbol&apikey=$alphaVantageApiKey";
$rawApiResponse = @file_get_contents($newsApiUrl);
$sentimentApiResponse = json_decode($rawApiResponse, true);

if (empty($sentimentApiResponse) || isset($sentimentApiResponse['Note'])) {
    json_response(['error' => $sentimentApiResponse['Note'] ?? 'API error'], 500);
}

$newsFeedItems = $sentimentApiResponse['feed'] ?? [];

json_response(['articles' => $newsFeedItems], 200);
?>
