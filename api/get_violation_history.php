<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include __DIR__ . '/../db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Parameters
$vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : 0;
$days       = isset($_GET['days'])       ? intval($_GET['days'])       : 7;

// Clamp days to a sensible range
if ($days < 1)   $days = 1;
if ($days > 365) $days = 365;

// If no vehicle_id supplied, return history for ALL vehicles (summary per vehicle per day)
if ($vehicle_id > 0) {
    // Single-vehicle detailed history
    $stmt = $conn->prepare("
        SELECT
            vi.id,
            v.vehicle_number,
            v.owner_name,
            vi.pollution_value,
            vi.violation_count,
            vi.violation_date
        FROM violations vi
        JOIN vehicles v ON v.id = vi.vehicle_id
        WHERE vi.vehicle_id = ?
          AND vi.violation_date >= NOW() - INTERVAL ? DAY
        ORDER BY vi.violation_date DESC
    ");
    $stmt->bind_param("ii", $vehicle_id, $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            "id"              => intval($row['id']),
            "vehicle_number"  => $row['vehicle_number'],
            "owner_name"      => $row['owner_name'],
            "pollution_value" => floatval($row['pollution_value']),
            "violation_count" => intval($row['violation_count']),
            "violation_date"  => $row['violation_date'],
        ];
    }

    echo json_encode([
        "status"     => "success",
        "vehicle_id" => $vehicle_id,
        "days"       => $days,
        "count"      => count($history),
        "history"    => $history,
    ]);
} else {
    // All vehicles — return latest violation record per vehicle within the window
    $stmt = $conn->prepare("
        SELECT
            vi.id,
            vi.vehicle_id,
            v.vehicle_number,
            v.owner_name,
            vi.pollution_value,
            vi.violation_count,
            vi.violation_date
        FROM violations vi
        JOIN vehicles v ON v.id = vi.vehicle_id
        WHERE vi.violation_date >= NOW() - INTERVAL ? DAY
        ORDER BY vi.violation_date DESC
    ");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            "id"              => intval($row['id']),
            "vehicle_id"      => intval($row['vehicle_id']),
            "vehicle_number"  => $row['vehicle_number'],
            "owner_name"      => $row['owner_name'],
            "pollution_value" => floatval($row['pollution_value']),
            "violation_count" => intval($row['violation_count']),
            "violation_date"  => $row['violation_date'],
        ];
    }

    echo json_encode([
        "status"  => "success",
        "days"    => $days,
        "count"   => count($history),
        "history" => $history,
    ]);
}

$conn->close();
?>
