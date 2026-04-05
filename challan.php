<?php
$conn = new mysqli(
    $_ENV["MYSQLHOST"],
    $_ENV["MYSQLUSER"],
    $_ENV["MYSQLPASSWORD"],
    $_ENV["MYSQLDATABASE"],
    $_ENV["MYSQLPORT"]
);

$vehicle_number = $_POST['vehicle_number'] ?? null;
$phone_number   = $_POST['phone_number'] ?? null;

$message = null;
$row = null;

if ($vehicle_number) {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_number=?");
    $stmt->bind_param("s", $vehicle_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
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
      Violations: <b><?php echo htmlspecialchars($row['violation_count']); ?></b>
    </div>

    <?php if ($row['violation_count'] > 5): ?>
      <div class="message warning">⚠️ Challan Issued! Please pay.</div>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=upi://pay?pa=gov@upi&pn=PollutionDept&am=500&cu=INR" alt="Pay QR">
    <?php else: ?>
      <div class="message success">✅ No challan issued yet.</div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>