<?php
session_start();

// Check if government user is logged in
if (!isset($_SESSION['gov_user'])) {
    header("Location: error.php?type=unauthorized");
    exit();
}

// Connect to Railway MySQL
$conn = new mysqli(
    getenv("MYSQLHOST"),
    getenv("MYSQLUSER"),
    getenv("MYSQLPASSWORD"),
    getenv("MYSQLDATABASE"),
    getenv("MYSQLPORT")
);

if ($conn->connect_error) {
    die("<div class='message error'>❌ Connection failed: " . $conn->connect_error . "</div>");
}

$message = null;
$row = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_number  = $_POST['vehicle_number'];
    $owner_name      = $_POST['owner_name'];
    $vehicle_type    = $_POST['vehicle_type'];
    $sensor_code     = $_POST['sensor_code'];
    $contact_details = $_POST['contact_details'];
    $violation_count = $_POST['violation_count'];

    $stmt = $conn->prepare("UPDATE vehicles 
        SET owner_name=?, vehicle_type=?, sensor_code=?, contact_details=?, violation_count=? 
        WHERE vehicle_number=?");
    $stmt->bind_param("ssssss", $owner_name, $vehicle_type, $sensor_code, $contact_details, $violation_count, $vehicle_number);

    if ($stmt->execute()) {
        $message = "<div class='message success'>✅ Vehicle details updated successfully!</div>";
    } else {
        $message = "<div class='message error'>❌ Error updating record: " . $conn->error . "</div>";
    }
}

// Fetch vehicle details for editing
if (isset($_GET['vehicle_number'])) {
    $vehicle_number = $_GET['vehicle_number'];
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_number = ?");
    $stmt->bind_param("s", $vehicle_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        $message = "<div class='message error'>❌ Vehicle not found!</div>";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Vehicle Details</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h2>✏️ Edit Vehicle Details</h2>
    <?php if ($message) echo $message; ?>

    <?php if ($row): ?>
    <form method="POST" action="edit_vehicle.php">
      <input type="hidden" name="vehicle_number" value="<?php echo htmlspecialchars($row['vehicle_number']); ?>">

      <label>Owner Name:</label>
      <input type="text" name="owner_name" value="<?php echo htmlspecialchars($row['owner_name']); ?>" required><br>

      <label>Vehicle Type:</label>
      <input type="text" name="vehicle_type" value="<?php echo htmlspecialchars($row['vehicle_type']); ?>" required><br>

      <label>Sensor Code:</label>
      <input type="text" name="sensor_code" value="<?php echo htmlspecialchars($row['sensor_code']); ?>" required><br>

      <label>Contact Details:</label>
      <input type="text" name="contact_details" value="<?php echo htmlspecialchars($row['contact_details']); ?>" required><br>

      <label>Violation Count:</label>
      <input type="number" name="violation_count" value="<?php echo htmlspecialchars($row['violation_count']); ?>" required><br>

      <button type="submit">Update Vehicle</button>
    </form>
    <?php endif; ?>
  </div>
</body>
</html>