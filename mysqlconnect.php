#!/usr/bin/php
<?php

// Detect caller (IP or hostname)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';


$db_host = '127.0.0.1';
$db_user = 'testuser';
$db_pass = 'rv9991$#';
$db_name = 'testdb';


$RABBITMQ_VM_IP = 'localhost'; 

if ($client_ip === $RABBITMQ_VM_IP) {
    $db_host = 'localhost';
    $db_user = 'rmq_user';
    $db_pass = 'StrongPassword123!';
}


$mydb = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mydb->connect_errno != 0) {
    echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
    exit(0);
}

echo "Successfully connected to database as user: $db_user".PHP_EOL;


if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER["REQUEST_METHOD"] == "POST"){
    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;
    $email    = $_POST["email"] ?? null;

    if ($username && $password && $email) {
        $my_salt_value = ["cost" => 12];
        $password_hash = password_hash($password, PASSWORD_BCRYPT, $my_salt_value);

        $query = $mydb->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $query->bind_param("sss", $username, $password_hash, $email);
        $query->execute();

        if ($query->errno != 0) {
            echo "Failed to execute query: " . $query->error . PHP_EOL;
            exit(0);
        } else {
            echo "User successfully registered!".PHP_EOL;
        }
    }
}
?>

