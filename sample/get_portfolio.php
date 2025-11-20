<?php
require_once('api_helpers.php');
require_once('mysqlconnect.php'); 
session_start(); 

if (!isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 0;
} else {
    $user_id = $_SESSION['user_id'];
}

if (empty($user_id)) {
    json_response(['error' => 'Authentication required'], 401);
}

try {
    $sql = "SELECT
                h.quantity,
                s.symbol,
                s.name,
                sp.current_price,
                (h.quantity * sp.current_price) AS total_value
            FROM holdings h
            JOIN stocks s ON h.stock_id = s.id
            JOIN stock_prices sp ON h.stock_id = sp.stock_id
            JOIN portfolios p ON h.portfolio_id = p.id
            WHERE p.user_id = ?";

    $stmt = $mydb->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $portfolio = $result->fetch_all(MYSQLI_ASSOC);

    $stmt2 = $mydb->prepare("SELECT cash_balance FROM portfolios WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $cash_row = $res2->fetch_assoc();
    $cash = $cash_row['cash_balance'] ?? 0;

    json_response([
        'cash' => $cash,
        'holdings' => $portfolio
    ]);

} catch (Exception $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>
