<?php
$host = $_ENV['MYSQLHOST'];
$user = $_ENV['MYSQLUSER'];
$pass = $_ENV['MYSQLPASSWORD'];
$db   = $_ENV['MYSQLDATABASE'];
$port = $_ENV['MYSQLPORT'];

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
