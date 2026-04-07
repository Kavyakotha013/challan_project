<?php
header("Content-Type: application/json");

include __DIR__ . '/db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$challan_id     = isset($_POST['challan_id']) ? intval($_POST['challan_id']) : 0;
$payment_amount = $_POST['payment_amount'] ?? null;

// Validate inputs
if ($challan_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid challan ID"]);
    exit;
}

if ($payment_amount === null || !is_numeric($payment_amount) || floatval($payment_amount) <= 0) {
    echo json_encode(["status" => "error", "message" => "Payment amount must be a positive number"]);
    exit;
}

$payment_amount = floatval($payment_amount);

// Calculate how many counts to decrement (each ₹250 = 1 decrement)
$decrements = intdiv((int)$payment_amount, 250);

if ($decrements <= 0) {
    echo json_encode(["status" => "error", "message" => "Payment amount must be at least ₹250"]);
    exit;
}

// Fetch current challan
$stmt = $conn->prepare("SELECT id, count, status FROM challans WHERE id = ?");
$stmt->bind_param("i", $challan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Challan not found"]);
    exit;
}

$challan = $result->fetch_assoc();

if ($challan['status'] === 'paid') {
    echo json_encode(["status" => "error", "message" => "Challan is already paid"]);
    exit;
}

$new_count = $challan['count'] - $decrements;

if ($new_count <= 0) {
    // Delete the challan record when fully paid
    $del = $conn->prepare("DELETE FROM challans WHERE id = ?");
    $del->bind_param("i", $challan_id);
    $del->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Payment processed. Challan fully settled and removed."
    ]);
} else {
    // Decrement count and update timestamp
    $upd = $conn->prepare("UPDATE challans SET count = ?, updated_at = NOW() WHERE id = ?");
    $upd->bind_param("ii", $new_count, $challan_id);
    $upd->execute();

    echo json_encode([
        "status"        => "success",
        "message"       => "Payment processed. Remaining count: " . $new_count,
        "remaining_count" => $new_count
    ]);
}

$conn->close();
?>
