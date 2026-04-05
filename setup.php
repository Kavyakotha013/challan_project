<?php
$conn = new mysqli(
    getenv("MYSQLHOST"),
    getenv("MYSQLUSER"),
    getenv("MYSQLPASSWORD"),
    getenv("MYSQLDATABASE"),
    getenv("MYSQLPORT")
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents(__DIR__ . '/init.sql');
if ($conn->multi_query($sql)) {
    // Drain all result sets so the connection is left in a clean state
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    echo "Database initialized successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
