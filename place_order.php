<?php
declare(strict_types= 1);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Vary: Origin');



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
} 

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');  

header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;


$raw = file_get_contents('php://input');
$input = null;

if(is_string($raw) && $raw !== '') {
    $decoded = json_decode($raw, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
        $input = $decoded;
    } 
} 

if (!is_array($input)) {
    json_response(['error' => 'Invalid JSON input'], 400);
}

$type = strtolower((string)($input['type'] ?? ''));
if ($type !== 'place_trade') { // We only accept 'place_trade'
    json_response(['error' => 'Unknown request type'], 400);
}

$session_id   = (string)($input['session_id'] ?? '');
$auth_token   = (string)($input['auth_token'] ?? '');

$symbol       = (string)($input['symbol'] ?? '');
$quantity     = (int)($input['quantity'] ?? 0);
$trade_type   = strtoupper((string)($input['trade_type'] ?? ''));

if (empty($session_id) || empty($auth_token)) {
    json_response(['status' => 'error', 'message' => 'Authentication required'], 401);
}
if (empty($symbol) || $quantity <= 0 || !in_array($trade_type, ['BUY', 'SELL'])) {
    json_response(['status' => 'error', 'message' => 'Invalid trade data: Check symbol, quantity, and type.'], 400);
}

$request = [
    'type'         => $type, // This will be 'place_trade'
    'session_id'   => $session_id,
    'auth_token'   => $auth_token,
    'symbol'       => $symbol,
    'quantity'     => $quantity,
    'trade_type'   => $trade_type,
];

try {
    $client = new rabbitMQClient("testRabbitMQ.ini","sharedServer");
    $response = $client->send_request($request); // Send the trade request

    if(!is_array($response)) {
        $response = ['status' => 'error', 'message' => (string)$response];
    }

    $ok = false;
    if (isset($response['status']) && strtolower((string)$response['status']) === 'success') {
        $ok = true;
    }

    json_response([
        'status'  => $ok ? 'success' : 'error',
        'message' => $response['message'] ?? 'An unknown error occurred.',
    ], 200);

} catch (Throwable $e){
    error_log('place_order.php exception: ' . $e->getMessage());
    json_response([
        'status'  => 'error',
        'message' => 'Gateway Error: Could not communicate with backend.',
    ], 500);
}

