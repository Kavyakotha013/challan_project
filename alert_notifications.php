<?php
header("Content-Type: application/json");

include __DIR__ . '/db_connect.php';
include __DIR__ . '/config.php';

// ─── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit;
}

// ─── Validate DB connection ───────────────────────────────────────────────────
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// ─── Read & validate input ────────────────────────────────────────────────────
$vehicle_id      = $_POST['vehicle_id']      ?? null;
$alert_type      = $_POST['alert_type']      ?? null;
$pollution_value = $_POST['pollution_value'] ?? null;

if (!$vehicle_id || !$alert_type || $pollution_value === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields: vehicle_id, alert_type, pollution_value"]);
    exit;
}

if (!in_array($alert_type, ['warning', 'critical'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "alert_type must be 'warning' or 'critical'"]);
    exit;
}

if (!is_numeric($pollution_value)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "pollution_value must be numeric"]);
    exit;
}

$vehicle_id      = (int)   $vehicle_id;
$pollution_value = (float) $pollution_value;

// ─── Fetch vehicle / owner details ───────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT owner_name, vehicle_number, owner_email, contact_details
     FROM vehicles WHERE id = ?"
);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Vehicle not found"]);
    exit;
}

$vehicle = $result->fetch_assoc();
$stmt->close();

// ─── Log alert in the database ────────────────────────────────────────────────
$insert = $conn->prepare(
    "INSERT INTO alerts (vehicle_id, alert_type, pollution_value, alert_date, status)
     VALUES (?, ?, ?, NOW(), 'active')"
);
$insert->bind_param("isd", $vehicle_id, $alert_type, $pollution_value);

if (!$insert->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to log alert: " . $insert->error]);
    exit;
}

$alert_id = $conn->insert_id;
$insert->close();

// ─── Send email notification ──────────────────────────────────────────────────
$email_sent = false;

if (!empty($vehicle['owner_email'])) {
    $to      = $vehicle['owner_email'];
    $subject = strtoupper($alert_type) . " Pollution Alert — Vehicle " . $vehicle['vehicle_number'];

    $limit_label = ($alert_type === 'critical')
        ? "the critical pollution limit of " . POLLUTION_LIMIT . " µg/m³"
        : "the warning threshold of " . ALERT_THRESHOLD . " µg/m³";

    $body = "Dear " . $vehicle['owner_name'] . ",\n\n"
          . "A " . strtoupper($alert_type) . " pollution alert has been triggered for your vehicle.\n\n"
          . "Vehicle Number : " . $vehicle['vehicle_number'] . "\n"
          . "Pollution Level: " . $pollution_value . " µg/m³\n"
          . "Alert Type     : " . ucfirst($alert_type) . "\n"
          . "Threshold      : Exceeded " . $limit_label . "\n"
          . "Date/Time      : " . date('Y-m-d H:i:s') . "\n\n"
          . "Please take immediate corrective action to reduce emissions.\n\n"
          . "Regards,\nPollution Monitoring Authority";

    $headers = "From: noreply@pollution-monitor.local\r\n"
             . "X-Mailer: PHP/" . phpversion();

    $email_sent = mail($to, $subject, $body, $headers);
}

// ─── Send SMS notification (stub — integrate real SMS gateway here) ───────────
$sms_sent = false;

if (!empty($vehicle['contact_details'])) {
    // Replace the block below with your SMS gateway API call.
    // Example payload for a typical REST gateway:
    // $sms_payload = [
    //     'to'      => $vehicle['contact_details'],
    //     'message' => "ALERT [{$alert_type}] Vehicle {$vehicle['vehicle_number']} "
    //                . "pollution {$pollution_value} µg/m³ exceeded limit. "
    //                . "Take immediate action.",
    // ];
    // For now we mark it as attempted so the response is honest.
    $sms_sent = false; // set to true once a real gateway is wired up
}

// ─── Return result ────────────────────────────────────────────────────────────
echo json_encode([
    "status"          => "success",
    "message"         => "Alert logged and notifications dispatched",
    "alert_id"        => $alert_id,
    "vehicle_id"      => $vehicle_id,
    "vehicle_number"  => $vehicle['vehicle_number'],
    "alert_type"      => $alert_type,
    "pollution_value" => $pollution_value,
    "email_sent"      => $email_sent,
    "sms_sent"        => $sms_sent,
]);

$conn->close();
