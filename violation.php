<?php
$conn = new mysqli($_ENV["MYSQLHOST"], $_ENV["MYSQLUSER"], $_ENV["MYSQLPASSWORD"], $_ENV["MYSQLDATABASE"], $_ENV["MYSQLPORT"]);

$sensor_code     = $_POST['sensor_code'];
$pollution_value = $_POST['pollution_value'];

$stmt = $conn->prepare("SELECT id FROM vehicles WHERE sensor_code=?");
$stmt->bind_param("s", $sensor_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vehicle_id = $row['id'];

    $conn->query("UPDATE vehicles SET violation_count = violation_count + 1 WHERE id=$vehicle_id");

    $stmt2 = $conn->prepare("INSERT INTO violations (vehicle_id, sensor_code, pollution_value) VALUES (?, ?, ?)");
    $stmt2->bind_param("iss", $vehicle_id, $sensor_code, $pollution_value);
    $stmt2->execute();

    echo json_encode(["status"=>"success","message"=>"Violation recorded"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Sensor code not found"]);
}
$conn->close();
?>