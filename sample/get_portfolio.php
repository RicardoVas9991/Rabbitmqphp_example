<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php');
session_start(); 

$activeUserId = 0;
if (isset($_SESSION['user_id'])) {
    $activeUserId = $_SESSION['user_id'];
} elseif (isset($_GET['user_id'])) {
    $activeUserId = (int)$_GET['user_id'];
}

if ($activeUserId <= 0) {
    json_response(['error' => 'User authentication required to view portfolio.'], 401);
}

try {
    $holdingsSql = "SELECT
                h.quantity AS shares_owned,
                s.symbol AS stock_ticker,
                s.name AS company_name,
                sp.current_price AS market_price,
                (h.quantity * sp.current_price) AS total_position_value
            FROM holdings h
            JOIN stocks s ON h.stock_id = s.id
            JOIN stock_prices sp ON h.stock_id = sp.stock_id
            JOIN portfolios p ON h.portfolio_id = p.id
            WHERE p.user_id = ?";

    $holdingsQuery = $mydb->prepare($holdingsSql);
    $holdingsQuery->bind_param("i", $activeUserId);
    $holdingsQuery->execute();

    $holdingsResultSet = $holdingsQuery->get_result();
    $portfolioPositions = $holdingsResultSet->fetch_all(MYSQLI_ASSOC);
    $holdingsQuery->close();

    $cashBalanceQuery = $mydb->prepare("SELECT cash_balance FROM portfolios WHERE user_id = ?");
    $cashBalanceQuery->bind_param("i", $activeUserId);
    $cashBalanceQuery->execute();
    
    $cashResult = $cashBalanceQuery->get_result();
    $cashRecord = $cashResult->fetch_assoc();
    $liquidCash = $cashRecord['cash_balance'] ?? 0.00;
    $cashBalanceQuery->close();

    json_response([
        'liquid_cash' => $liquidCash,
        'stock_positions' => $portfolioPositions
    ]);

} catch (Exception $databaseException) {
    json_response(['error' => 'Portfolio retrieval failed: ' . $databaseException->getMessage()], 500);
}
?>
