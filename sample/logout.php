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
    case "auth-token":
        $mysqlstatement = $mydb->prepare("DELETE FROM user_cookies WHERE auth_token = ?");
        $mysqlstatement->bind_param("s", $_COOKIE['auth_token']);
        $mysqlstatement->execute();
        setcookie('auth_token', '', time() - 3600, '/', 'localhost');
        break;
}
// Redirect to login page
header("Location: login.html");
exit();
?>
