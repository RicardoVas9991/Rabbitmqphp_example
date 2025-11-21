<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');

$alphaVantageApiKey = 'X06XHO4GPPMMFGJJ';
$targetSymbol = $_GET['symbol'] ?? '';

if (empty($targetSymbol)) {
    json_response(['error' => 'A valid stock symbol is required for search.'], 400);
}

$apiUrl = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$targetSymbol&apikey=$alphaVantageApiKey";

$rawApiResponse = @file_get_contents($apiUrl);
if ($rawApiResponse === FALSE) {
    json_response(['error' => 'Failed to establish connection with Alpha Vantage API.'], 500);
}

$alphaVantageResponse = json_decode($rawApiResponse, true);

if (empty($alphaVantageResponse) || isset($alphaVantageResponse['Note']) || !isset($alphaVantageResponse['Global Quote'])) {
    $apiErrorMessage = $alphaVantageResponse['Note'] ?? 'Invalid symbol provided or API rate limit reached.';
    json_response(['error' => $apiErrorMessage], 500);
}

$globalQuoteData = $alphaVantageResponse['Global Quote'];

if (empty($globalQuoteData)) {
    json_response(['error' => "No market data found for symbol: $targetSymbol"], 404);
}

try {
    $formattedStockData = [
        'symbol' => $globalQuoteData['01. symbol'],
        'price'  => $globalQuoteData['05. price'],
        'name'   => $targetSymbol
    ];
    
    $stockInsertStatement = $mydb->prepare("INSERT INTO stocks (symbol, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name");
    $stockInsertStatement->bind_param("ss", $formattedStockData['symbol'], $formattedStockData['symbol']);
    $stockInsertStatement->execute();
    $stockInsertStatement->close();

    json_response($formattedStockData, 200);

} catch (Exception $databaseException) {
    json_response(['error' => 'Database synchronization error: ' . $databaseException->getMessage()], 500);
}
?>
