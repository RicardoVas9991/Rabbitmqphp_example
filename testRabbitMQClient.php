#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

if (isset($argv[1])) {
    $msg = $argv[1];
} else {
    $msg = "test message";
}

$request = [
    'type' => 'register',
    'username' => 'steve',
    'password' => '$2y$12$Qe5INaDqD3nPy5hzp3OjFeJMSscq0TwxLhj5X6YhNnPEO4fAe0r8i',
    'email' => 'steve@example.com'
];


$request['message'] = $msg;
$response = $client->send_request($request);

echo "Client received response: " . PHP_EOL;
print_r($response);
echo "\n\n";

if (isset($response['returnCode']) && $response['returnCode'] === 0 && isset($response['session']['auth_token'])) {
    $auth_token = $response['session']['auth_token'];

    // Set cookie for web environment
    setcookie('auth_token', $auth_token, [
        'expires' => time() + (86400 * 30), // 30 days
        'path' => '/',
        'domain' => 'localhost',            // adjust as needed
        'secure' => true,                   // only HTTPS
        'httponly' => true,                 // inaccessible to JS
        'samesite' => 'Strict'
    ]);

    echo "Auth token cookie set successfully!" . PHP_EOL;
} else {
    echo "Registration failed or no session returned." . PHP_EOL;
}

echo $argv[0] . " END" . PHP_EOL;
?>

