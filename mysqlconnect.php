<?php
#!/usr/bin/php
$mydb = new mysqli('127.0.0.1','testuser','rv9991$#','testdb');

if ($mydb->errno != 0) {
    echo "failed to connect to database: ". $mydb->error . PHP_EOL;
    exit(0);
}

echo "successfully connected to database".PHP_EOL;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $email = $_POST["email"];
    $my_salt_value = [
        "cost" => 12,
    ];

    $password_hash = password_hash($password, PASSWORD_BCRYPT, $my_salt_value);

    // Use prepared statements to prevent SQL injection
    $stmt = $mydb->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password_hash, $email);

    if (!$stmt->execute()) {
        echo "failed to execute query:".PHP_EOL;
        echo __FILE__.':'.__LINE__.":error: ".$stmt->error.PHP_EOL;
        exit(0);
    } else {
        echo "User registered successfully.".PHP_EOL;
    }
    $stmt->close();
}
