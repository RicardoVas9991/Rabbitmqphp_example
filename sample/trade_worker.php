#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

require_once('mysqlconnect.php');

function forwardToDB($request)
{
    $dbClient = new rabbitMQClient("testRabbitMQ.ini", "sharedServer");
    return $dbClient->send_request($request);
}

function processTrade($request, $pdo)
{
    echo "Processing trade: \n";
    var_dump($request);

    $auth_request = [
        'type' => 'validate_session',
        'session_id' => $request['session_id'],
        'auth_token' => $request['auth_token']
    ];
    $auth_response = forwardToDB($auth_request);

    if (!isset($auth_response['status']) || $auth_response['status'] !== 'success' || !isset($auth_response['user_id'])) {
        return ['status' => 'error', 'message' => 'Trade failed: Invalid session'];
    }

    $user_id = $auth_response['user_id'];

    $symbol = $request['symbol'];
    $quantity = (int)$request['quantity'];
    $trade_type = $request['trade_type'];

    $pdo->beginTransaction();
    try {
	    $stmt = $pdo->prepare("SELECT s.id, sp.current_price FROM stocks s JOIN stock_prices sp ON s.id = sp.stock_id WHERE s.symbol = ?");
        $stmt->execute([$symbol]);
        $stock = $stmt->fetch();

        if (!$stock) {
            throw new Exception("Stock symbol '$symbol' not found.");
        }
        $stock_id = $stock['id'];
	$current_price = $stock['current_price'];

	$stmt = $pdo->prepare("SELECT id, cash_balance FROM portfolios WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $portfolio = $stmt->fetch();
        $portfolio_id = $portfolio['id'];
	$cash_balance = $portfolio['cash_balance'];

	if ($trade_type === 'BUY') {
            $cost = $quantity * $current_price;
            if ($cash_balance < $cost) {
                throw new Exception("Insufficient funds.");
	    }

	    $stmt = $pdo->prepare("UPDATE portfolios SET cash_balance = cash_balance - ? WHERE id = ?");
	    $stmt->execute([$cost, $portfolio_id]);

	    $stmt = $pdo->prepare("INSERT INTO holdings (portfolio_id, stock_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
	    $stmt->execute([$portfolio_id, $stock_id, $quantity, $quantity]);

	    } elseif ($trade_type === 'SELL') {
            $stmt = $pdo->prepare("SELECT quantity FROM holdings WHERE portfolio_id = ? AND stock_id = ?");
            $stmt->execute([$portfolio_id, $stock_id]);
	    $holding = $stmt->fetch();
	    
	    if (!$holding || $holding['quantity'] < $quantity) {
                throw new Exception("Not enough shares to sell.");
            }

	    $proceeds = $quantity * $current_price;
            $stmt = $pdo->prepare("UPDATE portfolios SET cash_balance = cash_balance + ? WHERE id = ?");
	    $stmt->execute([$proceeds, $portfolio_id]);

	    $stmt = $pdo->prepare("UPDATE holdings SET quantity = quantity - ? WHERE portfolio_id = ? AND stock_id = ?");
            $stmt->execute([$quantity, $portfolio_id, $stock_id]);
	    }

        $stmt = $pdo->prepare("INSERT INTO transactions (portfolio_id, stock_id, trade_type, quantity, price_per_share) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$portfolio_id, $stock_id, $trade_type, $quantity, $current_price]);

        
        $pdo->commit();
        return ['status' => 'success', 'message' => "Trade executed successfully."];

    } catch (Exception $e) {
        
        $pdo->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function requestProcessor($request)
{
    global $pdo;

    echo "Broker received request:" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return ["returnCode" => 1, "message" => "No request type provided"];
    }

    switch (strtolower($request['type'])) {
        case "login":
        case "register":
        case "validate_session":
            return forwardToDB($request);

        case "place_trade":
            return processTrade($request, $pdo);

        default:
            return ["returnCode" => 98, "message" => "Unknown request type"];
    }
}

?>
