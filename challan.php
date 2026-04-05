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
<!DOCTYPE html>
<html>
<head>
  <title>Challan Status</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h2>💳 Challan Status</h2>

  <?php if ($message) echo $message; ?>

  <?php if ($row): ?>
    <div class="message success">
      Owner: <b><?php echo htmlspecialchars($row['owner_name']); ?></b><br>
      Vehicle: <b><?php echo htmlspecialchars($row['vehicle_number']); ?></b><br>
      Violations in last 1 minute: <b><?php echo $violations_last_minute; ?></b><br>
      Total Challans: <b><?php echo $total_challans; ?></b><br>
      Unpaid Challans: <b><?php echo $unpaid_challans; ?></b>
    </div>

    <?php if ($unpaid_challans >= 3): ?>
      <div class="message danger">🚨 This vehicle has skipped 3 or more challans! Strict action required.</div>
    <?php elseif ($unpaid_challans > 0 && $latest_challan): ?>
      <div class="message warning">⚠️ Payment Pending! Please pay.</div>
      <p>Amount Due: <b>₹<?php echo $latest_challan['amount']; ?></b></p>
      <p>Last Challan Date: <b><?php echo $latest_challan['challan_date']; ?></b></p>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=upi://pay?pa=gov@upi&pn=PollutionDept&am=<?php echo $latest_challan['amount']; ?>&cu=INR" alt="Pay QR">
    <?php else: ?>
      <div class="message success">✅ No pending challans.</div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
