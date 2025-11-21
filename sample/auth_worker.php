#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$remoteClientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$databaseHost = '127.0.0.1';
$databaseUser = 'testuser';
$databasePassword = 'rv9991$#';
$databaseName = 'testdb';

$authDatabaseConnection = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
if ($authDatabaseConnection->connect_errno != 0) {
    echo "Failed to connect to database: " . $authDatabaseConnection->connect_error . PHP_EOL;
    exit(0);
}

echo "Successfully connected to database as user: $databaseUser" . PHP_EOL;

function performUserRegistration($targetUsername, $targetPassword, $targetEmail)
{
    global $authDatabaseConnection;

    $userLookupQuery = $authDatabaseConnection->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $userLookupQuery->bind_param("s", $targetUsername);
    $userLookupQuery->execute();
    $userLookupQuery->store_result();

    if ($userLookupQuery->num_rows > 0) {
        $userLookupQuery->close();
        return ["returnCode" => 1, "message" => "Username already exists"];
    }

    $userLookupQuery->close();

    $securePasswordHash = password_hash($targetPassword, PASSWORD_BCRYPT);

    $userInsertionQuery = $authDatabaseConnection->prepare("INSERT INTO users (username, password, email) VALUES(?, ?, ?)");
    $userInsertionQuery->bind_param("sss", $targetUsername, $securePasswordHash, $targetEmail);

    if (!$userInsertionQuery->execute()) {
        return ["returnCode" => 1, "message" => "Registration failed: " . $userInsertionQuery->error];
    }

    $userInsertionQuery->close();
    return ["returnCode" => 0, "message" => "Registration successful"];
}

function performUserLogin($targetUsername, $targetPassword)
{
    global $authDatabaseConnection;

    $passwordFetchQuery = $authDatabaseConnection->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
    $passwordFetchQuery->bind_param("s", $targetUsername);
    $passwordFetchQuery->execute();
    $passwordFetchQuery->bind_result($storedPasswordHash);
    $passwordFetchQuery->fetch();
    $passwordFetchQuery->close();

    if (!$storedPasswordHash) {
        return ["returnCode" => 1, "message" => "User not found"];
    }

    if (!password_verify($targetPassword, $storedPasswordHash)) {
        return ["returnCode" => 1, "message" => "Invalid password"];
    }

    $generatedSessionId = bin2hex(random_bytes(16));
    $generatedAuthToken = bin2hex(random_bytes(32));
    $sessionExpirationTime = date('Y-m-d H:i:s', time() + 3600);

    $sessionInsertionQuery = $authDatabaseConnection->prepare("
        INSERT INTO user_cookies(session_id, username, auth_token, expiration_time)
        VALUES(?, ?, ?, ?)
    ");
    $sessionInsertionQuery->bind_param("ssss", $generatedSessionId, $targetUsername, $generatedAuthToken, $sessionExpirationTime);

    if (!$sessionInsertionQuery->execute()) {
        return ["returnCode" => 1, "message" => "Failed to create session: " . $sessionInsertionQuery->error];
    }
    $sessionInsertionQuery->close();

    return [
        "returnCode" => 0,
        "message" => "Login successful",
        "session" => [
            "session_id" => $generatedSessionId,
            "auth_token" => $generatedAuthToken,
            "expires" => $sessionExpirationTime
        ]
    ];
}

function performSessionValidation($targetSessionId, $targetAuthToken)
{
    global $authDatabaseConnection;

    $sessionLookupQuery = $authDatabaseConnection->prepare("
        SELECT username
        FROM user_cookies
        WHERE session_id = ? AND auth_token = ? AND expiration_time > NOW()
    ");
    $sessionLookupQuery->bind_param("ss", $targetSessionId, $targetAuthToken);
    $sessionLookupQuery->execute();
    $sessionLookupQuery->store_result();

    $isSessionActive = $sessionLookupQuery->num_rows > 0;
    $sessionLookupQuery->close();

    if ($isSessionActive) {
        return ["returnCode" => 0, "message" => "Valid session"];
    } else {
        return ["returnCode" => 1, "message" => "Invalid session"];
    }
}

function processIncomingRequest($incomingDataPayload)
{
    echo "Received request:" . PHP_EOL;
    var_dump($incomingDataPayload);

    if (!isset($incomingDataPayload['type'])) {
        return ["returnCode" => 1, "message" => "No type provided"];
    }

    switch ($incomingDataPayload['type']) {
        case "register":
            return performUserRegistration($incomingDataPayload['username'], $incomingDataPayload['password'], $incomingDataPayload['email']);
        case "login":
            return performUserLogin($incomingDataPayload['username'], $incomingDataPayload['password']);
        case "validate_session":
            return performSessionValidation($incomingDataPayload['sessionId'], $incomingDataPayload['authToken']);
        default:
            return ["returnCode" => 1, "message" => "Invalid request type"];
    }
}

$rabbitMqConfigFile = "testRabbitMQ.ini";
if (!file_exists($rabbitMqConfigFile)) {
    die("Error: Configuration file '$rabbitMqConfigFile' not found.\n");
}
$parsedIniConfig = parse_ini_file($rabbitMqConfigFile, true);

$serverConfig = $parsedIniConfig['testServer']; 

$brokerHost = $serverConfig['BROKER_HOST'];
$brokerPort = $serverConfig['BROKER_PORT'];
$brokerUser = $serverConfig['USER'];
$brokerPassword = $serverConfig['PASSWORD'];
$brokerVhost = $serverConfig['VHOST'];
$targetQueueName = $serverConfig['QUEUE'];

try {
    $authServiceConnection = new AMQPStreamConnection($brokerHost, $brokerPort, $brokerUser, $brokerPassword, $brokerVhost);
    $authServiceChannel = $authServiceConnection->channel();
} catch (\Exception $connectionException) {
    echo "Failed to connect: " . $connectionException->getMessage() . PHP_EOL;
    exit(1);
}

$authServiceChannel->queue_declare($targetQueueName, false, true, false, false);
echo "Connected to RabbitMQ Broker on vhost '{$brokerVhost}'..." . PHP_EOL;

$incomingAuthRequestHandler = function(AMQPMessage $amqpEnvelope) use ($authServiceChannel) {

    echo "received message: " . $amqpEnvelope->body . PHP_EOL;
    $decodedJsonBody = json_decode($amqpEnvelope->body, true);

    if ($decodedJsonBody) {

        $processingResult = processIncomingRequest($decodedJsonBody);
        echo "processed message: " . json_encode($processingResult) . PHP_EOL;

        $outgoingResponseMessage = new AMQPMessage(
            json_encode($processingResult),
            [
                'correlation_id' => $amqpEnvelope->get('correlation_id')
            ]
        );

        $authServiceChannel->basic_publish(
            $outgoingResponseMessage,
            '',
            $amqpEnvelope->get('reply_to')
        );

    } else {
        echo "invalid message" . PHP_EOL;
    }

    $amqpEnvelope->ack();
};

$authServiceChannel->basic_consume($targetQueueName, '', false, false, false, false, $incomingAuthRequestHandler);

echo "Waiting for incoming RPC requests..." . PHP_EOL;

while ($authServiceChannel->is_consuming()) {
    $authServiceChannel->wait();
}

register_shutdown_function(function() use ($authServiceChannel, $authServiceConnection) {
    $authServiceChannel->close();
    $authServiceConnection->close();
});
