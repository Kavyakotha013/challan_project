<?php
session_start();
if (!isset($_SESSION['gov_user'])) {
    header("Location: error.php?type=unauthorized");
    exit();
}
$conn = new mysqli(getenv("MYSQLHOST"), getenv("MYSQLUSER"), getenv("MYSQLPASSWORD"), getenv("MYSQLDATABASE"), getenv("MYSQLPORT"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO vehicles (owner_name, vehicle_number, vehicle_type, sensor_code, contact_details, owner_email) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $_POST['owner_name'], $_POST['vehicle_number'], $_POST['vehicle_type'], $_POST['sensor_code'], $_POST['contact_details'], $_POST['owner_email']);
    if ($stmt->execute()) {
        $message = "<div class='message success'>✅ Vehicle registered successfully!</div>";
    } else {
        $message = "<div class='message error'>❌ Error: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Register Vehicle</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
  <h2>📋 Register Vehicle</h2>
  <?php if (isset($message)) echo $message; ?>
  <form method="POST">
    <label>Owner Name:</label><input type="text" name="owner_name" required>
    <label>Vehicle Number:</label><input type="text" name="vehicle_number" required>
    <label>Vehicle Type:</label><input type="text" name="vehicle_type" required>
    <label>Sensor Code:</label><input type="text" name="sensor_code" required>
    <label>Contact Details:</label><input type="text" name="contact_details" required>
    <label>Email:</label><input type="email" name="owner_email" required>
    <button type="submit">Register</button>
  </form>
</div>
</body>
</html>