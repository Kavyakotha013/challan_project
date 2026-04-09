<?php
include __DIR__ . '/db_connect.php';

$sql = file_get_contents(__DIR__ . '/init.sql');

if ($sql === false) {
    echo "Error: Could not read init.sql\n";
    exit(1);
}

if ($conn->multi_query($sql)) {
    // Drain all result sets so the connection is left in a clean state
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    echo "Database initialized successfully!\n";
} else {
    echo "Error initializing database: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

$conn->close();
exit(0);
