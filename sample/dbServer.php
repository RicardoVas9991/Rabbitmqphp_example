#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function getDBConnection() {
    $mysqli = new mysqli("127.0.0.1", "testuser", "rv9991$#", "testdb");
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
    $expiration = date('Y-m-d H:i:s', time() + (86400 * 30));

    $stmt = $db->prepare("INSERT INTO user_cookies (session_id, username, auth_token, expiration_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $session_id, $username, $auth_token, $expiration);
    $stmt->execute();

    return [
        'session_id' => $session_id,
        'auth_token' => $auth_token,
        'expiration_time' => $expiration
    ];
}

function doRegister($username, $password, $email)
{
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT userId FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return ["returnCode" => 1, "message" => "Username already exists"];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);
    $insert = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $insert->bind_param("sss", $username, $hash, $email);
    if (!$insert->execute()) {
        return ["returnCode" => 2, "message" => "DB insert failed: " . $db->error];
    }

    $session = createSession($username);
    return ["returnCode" => 0, "message" => "Registration successful", "session" => $session];
}

function doLogin($username, $password)
{
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        return ["returnCode" => 1, "message" => "User not found"];
    }

    $stmt->bind_result($hash);
    $stmt->fetch();
    if (password_verify($password, $hash)) {
        $session = createSession($username);
        return ["returnCode" => 0, "message" => "Login successful", "session" => $session];
    } else {
        return ["returnCode" => 2, "message" => "Invalid password"];
    }
}

function doValidate($sessionId)
{
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT username, expiration_time FROM user_cookies WHERE session_id = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        return ["returnCode" => 1, "message" => "Invalid session"];
    }

    $stmt->bind_result($username, $expiration);
    $stmt->fetch();
    if (strtotime($expiration) < time()) {
        return ["returnCode" => 2, "message" => "Session expired"];
    }

    return ["returnCode" => 0, "message" => "Session valid", "username" => $username];
}

function requestProcessor($request)
{
    echo "DB Server received:" . PHP_EOL;
    var_dump($request);

    switch (strtolower($request['type'])) {
        case "register":
            return doRegister($request['username'], $request['password'], $request['email']);
        case "login":
            return doLogin($request['username'], $request['password']);
        case "validate_session":
            return doValidate($request['sessionId']);
        default:
            return ["returnCode" => 98, "message" => "Unknown DB request type"];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "dbServer");
echo "DB Server listening..." . PHP_EOL;
$server->process_requests('requestProcessor');
?>

