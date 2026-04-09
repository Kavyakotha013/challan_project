<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include __DIR__ . '/../db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Pollution thresholds
define('THRESHOLD_WARNING',  150);
define('THRESHOLD_CRITICAL', 250);

// Optional filter: all | warning | critical
$filter = $_GET['status'] ?? 'all';

// Build the query — join vehicles with their latest violation reading
$sql = "
    SELECT
        v.id            AS vehicle_id,
        v.vehicle_number,
        v.owner_name,
        v.vehicle_type,
        v.contact_details,
        vi.pollution_value  AS current_pollution_value,
        vi.violation_count,
        vi.violation_date   AS last_reading_time
    FROM vehicles v
    LEFT JOIN violations vi ON vi.vehicle_id = v.id
    ORDER BY vi.pollution_value DESC, v.vehicle_number ASC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => $conn->error]);
    exit;
}

$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $pollution = $row['current_pollution_value'] !== null
        ? floatval($row['current_pollution_value'])
        : null;

    // Determine status label
    if ($pollution === null) {
        $statusLabel = 'no_data';
    } elseif ($pollution >= THRESHOLD_CRITICAL) {
        $statusLabel = 'critical';
    } elseif ($pollution >= THRESHOLD_WARNING) {
        $statusLabel = 'warning';
    } else {
        $statusLabel = 'safe';
    }

    // Apply filter
    if ($filter !== 'all' && $statusLabel !== $filter) {
        continue;
    }

    $vehicles[] = [
        "vehicle_id"              => intval($row['vehicle_id']),
        "vehicle_number"          => $row['vehicle_number'],
        "owner_name"              => $row['owner_name'],
        "vehicle_type"            => $row['vehicle_type'],
        "contact_details"         => $row['contact_details'],
        "current_pollution_value" => $pollution,
        "violation_count"         => $row['violation_count'] !== null ? intval($row['violation_count']) : 0,
        "last_reading_time"       => $row['last_reading_time'],
        "status"                  => $statusLabel,
    ];
}

echo json_encode([
    "status"   => "success",
    "filter"   => $filter,
    "count"    => count($vehicles),
    "vehicles" => $vehicles,
]);

$conn->close();
?>
