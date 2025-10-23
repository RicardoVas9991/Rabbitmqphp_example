<?php

$db_host = '127.0.0.1';
$db_user = 'testuser';
$db_pass = 'rv9991$#';
$db_name = 'testdb';

$mydb = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mydb->connect_errno != 0) {
    error_log("Failed to connect to database: " . $mydb->connect_error);
    exit(0);
}

 try {
     $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 } catch (PDOException $e) {
     error_log("DB Connection Failed: " . $e->getMessage());
     exit(0);
 }
?>

