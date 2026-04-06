<?php
// Railway provides these environment variables automatically
$servername = getenv("MYSQLHOST") ?: "mysql-ifj.railway.internal";
$username   = getenv("MYSQLUSER") ?: "root";
$password   = getenv("MYSQLPASSWORD") ?: "OqBOdvXusaRyGzBoZbGVQLSrUeQgoRtt";
$dbname     = getenv("MYSQLDATABASE") ?: "railway";
$port       = getenv("MYSQLPORT") ?: 3306;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}
?>
