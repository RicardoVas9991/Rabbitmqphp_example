#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function getDBConnection()
{
    $host = '127.0.0.1';
    $user = 'testuser';
    $pass = 'rv9991$#';
    $db   = 'testdb';

    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: " . $mysqli->connect_error . PHP_EOL;
        exit(1);
    }
    return $mysqli;
}

function createSession($username)
{
    $db = getDBConnection();

    $session_id = bin2hex(random_bytes(16));
    $auth_token = bin2hex(random_bytes(16));
    $expiration = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days
    $ip_address = '127.0.0.1';
    $user_agent = 'RabbitMQClient/1.0';

    $stmt = $db->prepare("INSERT INTO user_cookies (session_id, username, auth_token, expiration_time, ip_address, user_agent)
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $session_id, $username, $auth_token, $expiration, $ip_address, $user_agent);
    $stmt->execute();

    return array(
        'session_id' => $session_id,
        'auth_token' => $auth_token,
        'expiration_time' => $expiration
    );
}

function doRegister($username, $password, $email)
{
    $db = getDBConnection();

    // Check if username already exists
    $stmt = $db->prepare("SELECT userId FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        return array("returnCode" => 1, "message" => "Username already exists");
    }
    $stmt->close();

    // Hash password securely
    $hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);

    // Insert new user
    $insertStmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $insertStmt->bind_param("sss", $username, $hash, $email);
    $success = $insertStmt->execute();

    if (!$success) {
        return array("returnCode" => 2, "message" => "Database insert failed: " . $db->error);
    }

    $insertStmt->close();

    // Optionally, create a session right after registration
    $session = createSession($username);

    return array(
        "returnCode" => 0,
        "message" => "Registration successful",
        "session" => $session
    );
}

function doLogin($username, $password)
{
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        return array("returnCode" => 1, "message" => "User not found");
    }

    $stmt->bind_result($dbPassword);
    $stmt->fetch();

    if (password_verify($password, $dbPassword)) {
        $session = createSession($username);
        return array(
            "returnCode" => 0,
            "message" => "Login successful",
            "session" => $session
        );
    } else {
        return array("returnCode" => 2, "message" => "Invalid password");
    }
}

function doValidate($sessionId)
{
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT username, expiration_time FROM user_cookies WHERE session_id = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        return array("returnCode" => 1, "message" => "Invalid session");
    }

    $stmt->bind_result($username, $expiration);
    $stmt->fetch();

    if (strtotime($expiration) < time()) {
        return array("returnCode" => 2, "message" => "Session expired");
    }

    return array("returnCode" => 0, "message" => "Session valid", "username" => $username);
}

function requestProcessor($request)
{
    echo "Received request:" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return array("returnCode" => 99, "message" => "Unsupported request type");
    }

    switch (strtolower($request['type'])) {
        case "register":
            return doRegister($request['username'], $request['password'], $request['email']);
        case "login":
            return doLogin($request['username'], $request['password']);
        case "validate_session":
            return doValidate($request['sessionId']);
        default:
            return array("returnCode" => 98, "message" => "Unknown request type");
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END" . PHP_EOL;
exit();
?>

