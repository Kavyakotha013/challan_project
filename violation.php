<?php
// Always include the DB connection first
include __DIR__ . '/db_connect.php';

// Check if connection exists
if (!isset($conn) || $conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . ($conn->connect_error ?? "No connection")
    ]));
}

$sensor_code     = $_POST['sensor_code'] ?? null;
$pollution_value = $_POST['pollution_value'] ?? null;

if (!$sensor_code || !$pollution_value) {
    echo json_encode(["status"=>"error","message"=>"Missing sensor_code or pollution_value"]);
    exit;
}

// Step 1: Find vehicle by sensor_code
$stmt = $conn->prepare("SELECT id, vehicle_number FROM vehicles WHERE sensor_code=?");
$stmt->bind_param("s", $sensor_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status"=>"error","message"=>"Sensor code not found"]);
    $conn->close();
    exit;
}

$row        = $result->fetch_assoc();
$vehicle_id = $row['id'];

// Step 2: Upsert violation record
$stmt2 = $conn->prepare("
    INSERT INTO violations (vehicle_id, sensor_code, pollution_value, violation_count, violation_date)
    VALUES (?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE
        violation_count = violation_count + 1,
        pollution_value = VALUES(pollution_value),
        violation_date  = NOW()
");
$stmt2->bind_param("isd", $vehicle_id, $sensor_code, $pollution_value);
$stmt2->execute();

// Step 3: Return updated violation count
$stmt3 = $conn->prepare("SELECT violation_count FROM violations WHERE vehicle_id=?");
$stmt3->bind_param("i", $vehicle_id);
$stmt3->execute();
$countResult = $stmt3->get_result();
$countRow    = $countResult->fetch_assoc();

$response = [
    "status"          => "success",
    "message"         => "Violation recorded",
    "vehicle_id"      => $vehicle_id,
    "vehicle_number"  => $row['vehicle_number'],
    "violation_count" => $countRow['violation_count'] ?? 0
];

echo json_encode($response);
$conn->close();
?>
