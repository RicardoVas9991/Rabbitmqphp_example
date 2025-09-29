<?php
$mydb = new mysqli('127.0.0.1','testuser','rv9991$#','testdb');

if ($mydb->errno != 0)
{
    echo "failed to connect to database: ". $mydb->error . PHP_EOL;
    exit(0);
}
$my_request = $_POST;   
$response = "unsupported request type, politely";

if (!isset($my_request["type"]))
{
    $response = "NO POST MESSAGE SET, POLITELY";
}

switch ($my_request["type"])
{
    case "login":
        $username = $_POST["uname"];
        $authorization_token = bin2hex(random_bytes(32));

        setcookie('auth_token', $authorization_token, [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'domain' => 'localhost',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        $sql_cookie_access_var = $mydb->prepare("INSERT INTO 
        user_cookies (
            session_id, 
            username, 
            auth_token, 
            expiration_time, 
            ip_address, 
            user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $session_id_val = bin2hex(random_bytes(32));

        $sql_cookie_access_var->bind_param("ssssss", $session_id_val, $username, $authorization_token, date('Y-m-d H:i:s', time() + (86400 * 30)), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        $sql_cookie_access_var->execute();
        $response = array("status" => "success", "redirect" => "loggedin.html");
        echo json_encode($response);
        exit(0);
    break;
}
?>
