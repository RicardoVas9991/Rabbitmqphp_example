#!/usr/bin/php
<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Detect caller (IP or hostname)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$db_host = '127.0.0.1';
$db_user = 'testuser';
$db_pass = 'rv9991$#';
$db_name = 'testdb';

// Database connection
$mydb = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mydb->connect_errno != 0) {
    echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
    exit(0);
}

echo "Successfully connected to database as user: $db_user" . PHP_EOL;

// -----------------------------
// Registration
// -----------------------------
function doRegister($username, $password)
{
    global $mydb;
    $stmt = $mydb->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return ["returnCode" => 1, "message" => "Username already exists"];
    }

    $stmt = $mydb->prepare("INSERT INTO users(username, password, email) VALUES(?, ?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        return ["returnCode" => 0, "message" => "Registration successful"];
    } else {
        return ["returnCode" => 1, "message" => "Registration failed"];
    }
}

// -----------------------------
// Login
// -----------------------------
function doLogin($username, $password)
{
    global $mydb;
    $stmt = $mydb->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        return ["returnCode" => 1, "message" => "User not found"];
    }

    $row = $result->fetch_assoc();
    if ($row['password'] === $password) {
        $session_id = bin2hex(random_bytes(16));
        $expiration = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $mydb->prepare("INSERT INTO user_cookies(session_id, username, expiration_time) VALUES(?, ?, ?)");
        $stmt->bind_param("sss", $session_id, $username, $expiration);
        $stmt->execute();

        return ["returnCode" => 0, "message" => "Login successful", "sessionID" => $session_id];
    } else {
        return ["returnCode" => 1, "message" => "Invalid password"];
    }
}

// -----------------------------
// Session Validation
// -----------------------------
function doValidate($sessionId)
{
    global $mydb;
    $stmt = $mydb->prepare("SELECT username, expiration_time FROM user_cookies WHERE session_id = ? LIMIT 1");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        return ["returnCode" => 1, "message" => "Invalid session"];
    }

    $row = $result->fetch_assoc();
    if (strtotime($row['expiration_time']) < time()) {
        return ["returnCode" => 1, "message" => "Session expired"];
    }

    return ["returnCode" => 0, "message" => "Valid session", "username" => $row['username']];
}

// -----------------------------
// Request Processor
// -----------------------------
function requestProcessor($request)
{
    echo "Received request:" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return ["returnCode" => 1, "message" => "No type provided"];
    }

    switch (strtolower($request['type'])) {
        case "register":
            return doRegister($request['username'], $request['password']);
        case "login":
            return doLogin($request['username'], $request['password']);
        case "validate_session":
            return doValidate($request['sessionId']);
        default:
            return ["returnCode" => 0, "message" => "Invalid request type"];
    }
}

// -----------------------------
// RabbitMQ Server Setup
// -----------------------------
$server = new rabbitMQServer("testRabbitMQ.ini", "sharedServer");
echo "Database server active, waiting for requests..." . PHP_EOL;
$server->process_requests('requestProcessor');

echo "Database server shutting down." . PHP_EOL;
exit();

?>

