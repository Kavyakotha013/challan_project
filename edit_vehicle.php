<?php
include 'db_connect.php';

$id             = $_POST['id'] ?? null;
$owner_name     = $_POST['owner_name'] ?? null;
$vehicle_number = $_POST['vehicle_number'] ?? null;
$vehicle_type   = $_POST['vehicle_type'] ?? null;
$sensor_code    = $_POST['sensor_code'] ?? null;
$contact_details= $_POST['contact_details'] ?? null;
$owner_email    = $_POST['owner_email'] ?? null;

if (!$id || !$vehicle_number || !$sensor_code) {
    echo json_encode(["status"=>"error","message"=>"Missing required fields"]);
    exit;
}

// Check uniqueness
$stmt = $conn->prepare("SELECT id FROM vehicles WHERE (vehicle_number=? OR sensor_code=?) AND id<>?");
$stmt->bind_param("ssi", $vehicle_number, $sensor_code, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status"=>"error","message"=>"Vehicle number or sensor code already exists"]);
    $conn->close();
    exit;
}

// Update vehicle
$stmt2 = $conn->prepare("
    UPDATE vehicles 
    SET owner_name=?, vehicle_number=?, vehicle_type=?, sensor_code=?, contact_details=?, owner_email=? 
    WHERE id=?
");
$stmt2->bind_param("ssssssi", $owner_name, $vehicle_number, $vehicle_type, $sensor_code, $contact_details, $owner_email, $id);
$stmt2->execute();

echo json_encode(["status"=>"success","message"=>"Vehicle updated"]);
$conn->close();
?>
