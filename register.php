<?php
$servername = "mysql.railway.internal";
$username   = "root";
$password   = "kRtQVumqwUQPtraipUrslkOStzvSuIzv";
$dbname     = "railway_pollution_db";
$port       = 3306;

$conn = new mysqli($servername, $username, $password, $dbname,$port);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$owner_name      = $_POST['owner_name'];
$vehicle_number  = $_POST['vehicle_number'];
$vehicle_type    = $_POST['vehicle_type'];
$sensor_code     = $_POST['sensor_code'];
$contact_details = $_POST['contact_details'];

$sql_check = "SELECT * FROM vehicles WHERE vehicle_number='$vehicle_number' OR sensor_code='$sensor_code'";
$result = $conn->query($sql_check);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration Result</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h2>🚗 Vehicle Registration</h2>
    <?php
    if ($result->num_rows > 0) {
        echo "<div class='message error'>❌ Error: Vehicle number or sensor code already registered!</div>";
    } else {
        $sql = "INSERT INTO vehicles (owner_name, vehicle_number, vehicle_type, sensor_code, contact_details, registration_date, violation_count) 
                VALUES ('$owner_name', '$vehicle_number', '$vehicle_type', '$sensor_code', '$contact_details', NOW(), 0)";
        if ($conn->query($sql) === TRUE) {
            echo "<div class='message success'>✅ Registration successful! Vehicle <b>$vehicle_number</b> has been added.</div>";
        } else {
            echo "<div class='message error'>❌ Error: " . $conn->error . "</div>";
        }
    }
    $conn->close();
    ?>
    <div class="footer">Powered by Pollution Monitoring System</div>
  </div>
</body>
</html>