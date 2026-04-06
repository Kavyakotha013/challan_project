<?php
session_start();

// Connect to DB
$conn = new mysqli(
    $_ENV["MYSQLHOST"],
    $_ENV["MYSQLUSER"],
    $_ENV["MYSQLPASSWORD"],
    $_ENV["MYSQLDATABASE"],
    $_ENV["MYSQLPORT"]
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$vehicle_number = $_POST['vehicle_number'] ?? null;
$message = null;
$row = null;
$violation_count = 0;
$total_challans = 0;
$unpaid_challans = 0;
$latest_challan = null;

if ($vehicle_number) {
    // Fetch vehicle details
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_number=?");
    $stmt->bind_param("s", $vehicle_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Current violation_count from the single violations row for this vehicle
        $stmtV = $conn->prepare("
            SELECT violation_count
            FROM violations
            WHERE vehicle_id = ?
            ORDER BY violation_date DESC
            LIMIT 1
        ");
        $stmtV->bind_param("i", $row['id']);
        $stmtV->execute();
        $resultV = $stmtV->get_result();
        $violation_count = ($resultV->num_rows > 0) ? (int)$resultV->fetch_assoc()['violation_count'] : 0;

        // Total challans
        $stmtC = $conn->prepare("SELECT COUNT(*) AS total_challans FROM challans WHERE vehicle_id=?");
        $stmtC->bind_param("i", $row['id']);
        $stmtC->execute();
        $resultC = $stmtC->get_result();
        $total_challans = $resultC->fetch_assoc()['total_challans'];

        // Unpaid challans
        $stmtU = $conn->prepare("SELECT COUNT(*) AS unpaid_challans FROM challans WHERE vehicle_id=? AND status='unpaid'");
        $stmtU->bind_param("i", $row['id']);
        $stmtU->execute();
        $resultU = $stmtU->get_result();
        $unpaid_challans = $resultU->fetch_assoc()['unpaid_challans'];

        // Latest challan info (including violation_count snapshot at time of issue)
        $stmtL = $conn->prepare("SELECT amount, status, challan_date, violation_count FROM challans WHERE vehicle_id=? ORDER BY challan_date DESC LIMIT 1");
        $stmtL->bind_param("i", $row['id']);
        $stmtL->execute();
        $resultL = $stmtL->get_result();
        if ($resultL->num_rows > 0) {
            $latest_challan = $resultL->fetch_assoc();
        }

    } else {
        $message = "<div class='message error'>❌ Vehicle not found. Please check the number and try again.</div>";
    }
}
$conn->close();
?>
