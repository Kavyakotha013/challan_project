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
$violations_last_minute = 0;
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

        // Violations in last 1 minute
        $stmtV = $conn->prepare("
            SELECT COUNT(*) AS violations_last_minute
            FROM violations
            WHERE vehicle_id = ?
              AND violation_date >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmtV->bind_param("i", $row['id']);
        $stmtV->execute();
        $resultV = $stmtV->get_result();
        $violations_last_minute = $resultV->fetch_assoc()['violations_last_minute'];

        // 🚨 Auto-create challan if violations > 5 in last minute
        if ($violations_last_minute > 5) {
            $stmtCh = $conn->prepare("
                INSERT INTO challans (vehicle_id, challan_date, amount, status)
                VALUES (?, NOW(), 500, 'unpaid')
            ");
            $stmtCh->bind_param("i", $row['id']);
            $stmtCh->execute();
        }

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

        // Latest challan info
        $stmtL = $conn->prepare("SELECT amount, status, challan_date FROM challans WHERE vehicle_id=? ORDER BY challan_date DESC LIMIT 1");
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
