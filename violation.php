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

// Step 2: Insert or Update violation
$stmt2 = $conn->prepare("
    INSERT INTO violations (vehicle_id, sensor_code, pollution_value, violation_count, violation_date)
    VALUES (?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE
        violation_count = violation_count + 1,
        pollution_value = VALUES(pollution_value),
        violation_date  = NOW()
");

$stmt2->bind_param("isd", $vehicle_id, $sensor_code, $pollution_value);

if (!$stmt2->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => $stmt2->error
    ]);
    exit;
}

// Step 3: Get updated count
$stmt3 = $conn->prepare("SELECT violation_count FROM violations WHERE vehicle_id=?");
$stmt3->bind_param("i", $vehicle_id);
$stmt3->execute();
$countResult = $stmt3->get_result();
$countRow    = $countResult->fetch_assoc();

// Step 4: Check pollution value against configured thresholds and trigger alerts
$alert_triggered = false;
$alert_type      = null;
$alert_result    = null;

$pollution_float = (float) $pollution_value;

if ($pollution_float >= POLLUTION_LIMIT) {
    // Critical — reading at or above the hard limit
    $alert_type = 'critical';
} elseif ($pollution_float >= ALERT_THRESHOLD) {
    // Warning — reading in the caution zone
    $alert_type = 'warning';
}

if ($alert_type !== null) {
    // Log the alert and dispatch notifications via the dedicated endpoint.
    // We call it internally using an HTTP POST so all notification logic
    // stays in one place and can be tested independently.
    $notify_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\')
                . '/alert_notifications.php';

    $post_data = http_build_query([
        'vehicle_id'      => $vehicle_id,
        'alert_type'      => $alert_type,
        'pollution_value' => $pollution_float,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                       . "Content-Length: " . strlen($post_data) . "\r\n",
            'content' => $post_data,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents($notify_url, false, $ctx);
    if ($raw !== false) {
        $alert_result    = json_decode($raw, true);
        $alert_triggered = ($alert_result['status'] ?? '') === 'success';
    }
}

// Final response
echo json_encode([
    "status"          => "success",
    "message"         => "Violation recorded",
    "vehicle_id"      => $vehicle_id,
    "vehicle_number"  => $row['vehicle_number'],
    "violation_count" => $countRow['violation_count'] ?? 0,
    "pollution_value" => $pollution_float,
    "alert_triggered" => $alert_triggered,
    "alert_type"      => $alert_type,
]);

$conn->close();
?>
