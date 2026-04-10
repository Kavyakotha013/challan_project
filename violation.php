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

// Get POST data safely
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

// Step 2: Insert or update violation record
$stmt2 = $conn->prepare("
    INSERT INTO violations (vehicle_id, sensor_code, pollution_value, violation_count, min_count, violation_date)
    VALUES (?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        pollution_value = pollution_value + VALUES(pollution_value),
        violation_count = violation_count + VALUES(violation_count),
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

// Step 3: If min_count == 3 → check accumulated violations and maybe generate challan
if ((int)$min_count === 3) {
    // Fetch current (accumulated) violation count after the upsert
    $checkStmt = $conn->prepare("SELECT violation_count FROM violations WHERE vehicle_id=?");
    $checkStmt->bind_param("i", $vehicle_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow    = $checkResult->fetch_assoc();
    $current_violation_count = $checkRow['violation_count'] ?? 0;

    // Reset min_count regardless of whether a challan is issued
    $resetStmt = $conn->prepare("UPDATE violations SET min_count=0 WHERE vehicle_id=?");
    $resetStmt->bind_param("i", $vehicle_id);
    $resetStmt->execute();

    if ($current_violation_count >= 7) {
        // Delegate challan creation entirely to generateChallan()
        // Define constant so challan.php skips its HTML view block
        if (!defined('CHALLAN_INCLUDED')) define('CHALLAN_INCLUDED', true);
        include __DIR__ . '/challan.php';
        generateChallan($vehicle_id, $sensor_code);
    }
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
