<?php
require_once('config.php');

// The connection object below:
$mydb = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mydb->connect_errno != 0) {
    echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
    exit(1);
}
?>
