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

        // Count total challans
        $stmt2 = $conn->prepare("SELECT COUNT(*) AS total_challans FROM challans WHERE vehicle_id=?");
        $stmt2->bind_param("i", $row['id']);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $total_challans = $result2->fetch_assoc()['total_challans'];

        // Count unpaid challans
        $stmt3 = $conn->prepare("SELECT COUNT(*) AS unpaid_challans FROM challans WHERE vehicle_id=? AND status='unpaid'");
        $stmt3->bind_param("i", $row['id']);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        $unpaid_challans = $result3->fetch_assoc()['unpaid_challans'];

        // Latest challan info
        $stmt4 = $conn->prepare("SELECT amount, status, challan_date FROM challans WHERE vehicle_id=? ORDER BY challan_date DESC LIMIT 1");
        $stmt4->bind_param("i", $row['id']);
        $stmt4->execute();
        $result4 = $stmt4->get_result();
        if ($result4->num_rows > 0) {
            $latest_challan = $result4->fetch_assoc();
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
      Total Challans: <b><?php echo $total_challans; ?></b><br>
      Unpaid Challans: <b><?php echo $unpaid_challans; ?></b>
    </div>

    <?php if ($unpaid_challans >= 3): ?>
      <div class="message danger">🚨 This vehicle has skipped 3 or more challans! Strict action required.</div>
    <?php elseif ($unpaid_challans > 0): ?>
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
