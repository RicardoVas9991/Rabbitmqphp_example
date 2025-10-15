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
}

function set_session_cookies(array $session_cookie): bool {
    if (empty($session_cookie['session_id']) || empty($session_cookie['auth_token'])) return false;

    $expiresAt = $session['expires_at'] ?? '';
    $expTs = 0;

    if (is_string($expiresAt) && $expiresAt !== '')  {
        $ts = strtotime($expiresAt);
        if ($ts !== false) $expTs = $ts;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off";

    $opts = [
        'expires'   => $expTs,
        'path'      => '/',
        'secure'    => $secure,
        'httponly'  => true,
        'samesite'  => 'Lax'
    ];

    $ok1 = @setcookie('session_id', (string)$session_cookie['session_id'], $opts);
    $ok2 = @setcookie('auth_token', (string)$session_cookie['auth_token'], $opts);
    return $ok1 && $ok2;
}

function clear_session_cookies(): void {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off";

    $opts = [
        'expires'    => time() - 3600,
        'path'       => '/',
        'secure'     => $secure,
        'httponly'   => true,
        'samesite'   => 'Lax'
    ];
    @setcookie('session_id', '', $opts);
    @setcookie('auth_token', '', $opts);
}

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
if (!in_array($type, ['login', 'register', 'validate_session'], true)) {
    json_response(['error' => 'Unknown request type'], 400);
}

$username       = (string)($input['username'] ?? '');
$password       = (string)($input['password'] ?? '');
$email          = (string)($input['email'] ?? '');
$session_id     = (string)($input['sessionId'] ?? $input['session_id'] ?? $input['sessionid'] ?? '');

$request = [
    'type'          => $type,
    'username'      => $username,
    'password'      => $password,
    'email'         => $email,
    'session_id'    => $session_id,
    "message"       => "Greeting from RabbitMQClient.php",
];

try {
    $client = new rabbitMQClient("testRabbitMQ.ini","sharedServer");
    $response = $client->send_request($request);

    if(!is_array($response)) {
        $response = ['returnCode' => 99, 'message' => (string)$response];
    }

    $ok = false;
    if (isset($response['status']) && strtolower((string)$response['status']) === 'success') $ok = true;
    if (isset($response['returnCode']) && (int)$response['returnCode'] === 0) $ok = true;

    $cookieSet = false;

    if ($ok && isset($response['session']) && is_array($response['session'])) {
        $cookieSet = set_session_cookies($response['session']);
    }


    if ($type === 'validate_session' && !$ok) {
        clear_session_cookies();
    }   

    $result = [
        'status'        => $ok ? 'success' : 'error',
        'returnCode'    => (int)($response['returnCode'] ?? ($ok ? 0 : 1)),
        'message'       => $response['message'] ?? '',,
        'session'       => $response['session'] ?? null,
        'cookieSet'     => $cookieSet,
        'session_valid' => ($type === 'validate_session') ? $ok : null,
    ];

    json_response($result, 200);
} catch (Throwable $e){
    error_log('testRabbitMQClient.php exception: ' . $e->getMessage());
    json_response([
        'status'    => 'error',
        'returnCode'=> 1,
        'message' => 'Gateway Error communicating with backend',
    ], 500);
}
