<?php
header("Content-Type: application/json");

include __DIR__ . '/db_connect.php';
include __DIR__ . '/config.php';

// ─── Only accept GET ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use GET."]);
    exit;
}

// ─── Validate DB connection ───────────────────────────────────────────────────
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// ─── Optional filter: ?status=active (default) | resolved | all ──────────────
$status_filter = $_GET['status'] ?? 'active';

$allowed_statuses = ['active', 'resolved', 'all'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid status filter. Allowed values: active, resolved, all",
    ]);
    exit;
}

// ─── Build query ──────────────────────────────────────────────────────────────
// Restrict history to the configured tracking window (VIOLATION_DURATION_DAYS).
$duration_days = (int) VIOLATION_DURATION_DAYS;

$base_sql = "
    SELECT
        a.id            AS alert_id,
        v.vehicle_number,
        v.owner_name,
        v.owner_email,
        v.contact_details,
        a.alert_type,
        a.pollution_value,
        a.alert_date,
        a.status
    FROM alerts a
    JOIN vehicles v ON a.vehicle_id = v.id
    WHERE a.alert_date >= NOW() - INTERVAL {$duration_days} DAY
";

if ($status_filter !== 'all') {
    $safe_status = $conn->real_escape_string($status_filter);
    $base_sql   .= " AND a.status = '{$safe_status}'";
}

$base_sql .= " ORDER BY a.alert_date DESC";

$result = $conn->query($base_sql);

if ($result === false) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
    exit;
}

// ─── Collect rows ─────────────────────────────────────────────────────────────
$alerts = [];
while ($row = $result->fetch_assoc()) {
    $alerts[] = [
        "alert_id"        => (int)   $row['alert_id'],
        "vehicle_number"  =>         $row['vehicle_number'],
        "owner_name"      =>         $row['owner_name'],
        "owner_email"     =>         $row['owner_email'],
        "contact_details" =>         $row['contact_details'],
        "alert_type"      =>         $row['alert_type'],
        "pollution_value" => (float) $row['pollution_value'],
        "alert_date"      =>         $row['alert_date'],
        "status"          =>         $row['status'],
    ];
}

// ─── Return result ────────────────────────────────────────────────────────────
echo json_encode([
    "status"        => "success",
    "filter"        => $status_filter,
    "tracking_days" => $duration_days,
    "total"         => count($alerts),
    "alerts"        => $alerts,
]);

$conn->close();
