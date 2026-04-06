<?php
include __DIR__ . '/db_connect.php';


// Reset all violation counts
$sql = "UPDATE violations SET violation_count = 0, violation_date = NOW()";
if ($conn->query($sql) === TRUE) {
    echo json_encode(["status"=>"success","message"=>"All violations cleared"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Failed to clear violations"]);
}

$conn->close();
?>
