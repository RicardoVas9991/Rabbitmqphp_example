#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

// Default type is "register"
$type = "register";
if (isset($argv[1])) {
    $type = strtolower($argv[1]);
}

// Choose the message type dynamically
switch ($type) {
    case "register":
        $request = [
            'type' => 'register',
            'username' => 'steve',
            'password' => '$2y$12$Qe5INaDqD3nPy5hzp3OjFeJMSscq0TwxLhj5X6YhNnPEO4fAe0r8i',
            'email' => 'steve@example.com'
        ];
        break;

    case "update":
        $request = [
            'type' => 'update_user',
            'username' => 'steve',
            'email' => 'steve_updated@example.com',
            'password' => 'NewStrongPassword123!'
        ];
        break;

    case "login":
        $request = [
            'type' => 'login',
            'username' => 'steve',
            'password' => 'NewStrongPassword123!'
        ];
        break;

    default:
        echo "Unknown type. Use one of: register, update, login" . PHP_EOL;
        exit(1);
}

$response = $client->send_request($request);

echo "Client received response:" . PHP_EOL;
print_r($response);
echo PHP_EOL;

// Handle successful session creation
if (isset($response['returnCode']) && $response['returnCode'] === 0 && isset($response['session']['auth_token'])) {
    $auth_token = $response['session']['auth_token'];

    // Simulate web cookie (only applies if running via web)
    setcookie('auth_token', $auth_token, [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'domain' => 'localhost',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    echo "Auth token cookie set successfully!" . PHP_EOL;
} else {
    echo "Operation failed or no session returned." . PHP_EOL;
}

echo $argv[0] . " END" . PHP_EOL;
?>

