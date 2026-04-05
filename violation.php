<?php
$conn = new mysqli($_ENV["MYSQLHOST"], $_ENV["MYSQLUSER"], $_ENV["MYSQLPASSWORD"], $_ENV["MYSQLDATABASE"], $_ENV["MYSQLPORT"]);

$sensor_code     = $_POST['sensor_code'] ?? null;
$pollution_value = $_POST['pollution_value'] ?? null;

if (!$sensor_code || !$pollution_value) {
    echo json_encode(["status"=>"error","message"=>"Missing sensor_code or pollution_value"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM vehicles WHERE sensor_code=?");
$stmt->bind_param("s", $sensor_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vehicle_id = $row['id'];

    // Insert violation linked to vehicle_id
    $stmt2 = $conn->prepare("INSERT INTO violations (vehicle_id, sensor_code, pollution_value, violation_date) VALUES (?, ?, ?, NOW())");
    $stmt2->bind_param("iss", $vehicle_id, $sensor_code, $pollution_value);
    $stmt2->execute();

    echo json_encode(["status"=>"success","message"=>"Violation recorded"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Sensor code not found"]);
}
$conn->close();
?>
