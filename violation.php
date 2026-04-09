<?php
header("Content-Type: application/json");

// DB connection
include __DIR__ . '/db_connect.php';
include __DIR__ . '/config.php';

// Check DB connection
if (!isset($conn) || $conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]));
}

// Get POST data
$sensor_code     = $_POST['sensor_code'] ?? null;
$pollution_value = $_POST['pollution_value'] ?? null;
$violation_count = $_POST['violation_count'] ?? null;
$min_count       = $_POST['min_count'] ?? null;

// Validate input
if (!$sensor_code || !$pollution_value) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing sensor_code or pollution_value"
    ]);
    exit;
}

if (!is_numeric($pollution_value)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid pollution value"
    ]);
    exit;
}

// Step 1: Get vehicle
$stmt = $conn->prepare("SELECT id, vehicle_number FROM vehicles WHERE sensor_code=?");
$stmt->bind_param("s", $sensor_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Sensor not registered"
    ]);
    exit;
}

$row        = $result->fetch_assoc();
$vehicle_id = $row['id'];

// Step 2: Update violation record each minute
$stmt2 = $conn->prepare("
    INSERT INTO violations (vehicle_id, sensor_code, pollution_value, violation_count, min_count, violation_date)
    VALUES (?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        pollution_value = VALUES(pollution_value),
        violation_count = VALUES(violation_count),
        min_count       = VALUES(min_count),
        violation_date  = NOW()
");

$stmt2->bind_param("isdii", $vehicle_id, $sensor_code, $pollution_value, $violation_count, $min_count);

if (!$stmt2->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => $stmt2->error
    ]);
    exit;
}

// Step 3: If min_count == 3 → generate challan
if ((int)$min_count === 3) {
    // Reset min_count in DB
    $resetStmt = $conn->prepare("UPDATE violations SET min_count=0 WHERE vehicle_id=?");
    $resetStmt->bind_param("i", $vehicle_id);
    $resetStmt->execute();

    // Call challan creation logic
    include __DIR__ . '/generate_challan.php';
    generateChallan($vehicle_id, $sensor_code);
}

// Step 4: Response
echo json_encode([
    "status"          => "success",
    "message"         => "Violation recorded",
    "vehicle_id"      => $vehicle_id,
    "vehicle_number"  => $row['vehicle_number'],
    "violation_count" => $violation_count,
    "pollution_value" => (float)$pollution_value,
    "min_count"       => $min_count
]);

$conn->close();
?>
