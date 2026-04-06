<?php
include 'db_connect.php';

$vehicle_id   = $_POST['vehicle_id'] ?? null;
$violation_id = $_POST['violation_id'] ?? null;

if (!$vehicle_id || !$violation_id) {
    echo json_encode(["status"=>"error","message"=>"Missing vehicle_id or violation_id"]);
    exit;
}

// Upsert challan: one row per vehicle+violation+status
$stmt = $conn->prepare("
    INSERT INTO challans (vehicle_id, violation_count, status, count, challan_date, updated_at, amount)
    VALUES (?, 1, 'unpaid', 1, NOW(), NOW(), 500.00)
    ON DUPLICATE KEY UPDATE
        count = count + 1,
        violation_count = violation_count + 1,
        updated_at = NOW()
");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();

echo json_encode(["status"=>"success","message"=>"Challan recorded"]);
$conn->close();
?>
