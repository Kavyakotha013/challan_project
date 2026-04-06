<?php
include 'db_connect.php';

$vehicle_id   = $_POST['vehicle_id'];
$violation_id = $_POST['violation_id'];

// Check if challan already exists for this vehicle & violation
$query  = "SELECT * FROM challan 
           WHERE vehicle_id = '$vehicle_id' 
           AND violation_id = '$violation_id' 
           AND status = 'pending'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    // Update existing challan (increment count or update timestamp)
    $update = "UPDATE challan 
               SET count = count + 1, updated_at = NOW() 
               WHERE vehicle_id = '$vehicle_id' 
               AND violation_id = '$violation_id' 
               AND status = 'pending'";
    mysqli_query($conn, $update);
} else {
    // Insert new challan
    $insert = "INSERT INTO challan (vehicle_id, violation_id, status, count, created_at) 
               VALUES ('$vehicle_id', '$violation_id', 'pending', 1, NOW())";
    mysqli_query($conn, $insert);
}
?>
