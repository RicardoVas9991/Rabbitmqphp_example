<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>

<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <title>IT490 Logged In Page</title>
    </head>
    <body>
        <h1>Welcome to the IT490 Logged In Page</h1>
    </body>

    <form id="logoutForm" action="logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>
</html>