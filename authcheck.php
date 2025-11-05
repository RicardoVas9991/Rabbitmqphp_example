<?php
// authcheck.php
// Protect pages by validating the user's cookie session

// Connect to the database
$mydb = new mysqli('127.0.0.1','testuser','Password1234!','testdb');

if ($mydb->connect_errno !== 0) {
    http_response_code(500);
    die("Database connection failed: " . $mydb->connect_error);
}

// Check if the cookie exists
if (!isset($_COOKIE['auth_token'])) {
    http_response_code(401); // Unauthorized
    die("Not logged in. Please log in first.");
}

$auth_token = $_COOKIE['auth_token'];

// Lookup the session in the DB
$stmt = $mydb->prepare("
    SELECT username, expiration_time, ip_address, user_agent 
    FROM user_cookies 
    WHERE auth_token = ?
    LIMIT 1
");
$stmt->bind_param("s", $auth_token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    die("Invalid session. Please log in again.");
}

$session = $result->fetch_assoc();

// Check if session expired
if (strtotime($session['expiration_time']) < time()) {
    // Delete expired session from DB
    $cleanup = $mydb->prepare("DELETE FROM user_cookies WHERE auth_token = ?");
    $cleanup->bind_param("s", $auth_token);
    $cleanup->execute();

    http_response_code(401);
    die("Session expired. Please log in again.");
}

// Optionally check IP + User Agent consistency
$current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if ($session['ip_address'] !== $current_ip || $session['user_agent'] !== $current_ua) {
    http_response_code(401);
    die("Session validation failed (IP/UA mismatch). Please log in again.");
}

// Session is valid — you can use $session['username'] for personalization
// Example:
echo "Welcome back, " . htmlspecialchars($session['username']);

?>

