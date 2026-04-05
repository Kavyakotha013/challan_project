<?php
$servername = "mysql.railway.internal";
$username   = "root";
$password   = "kRtQVumqwUQPtraipUrslkOStzvSuIzv";
$dbname     = "railway_pollution_db";
$port       = 3306;

$conn = new mysqli($servername, $username, $password, $dbname,$port);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$vehicle_number = $_POST['vehicle_number'];
$sql = "SELECT * FROM vehicles WHERE vehicle_number='$vehicle_number'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Challan Status</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h2>💳 Challan Status</h2>
    <?php
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<div class='message success'>Owner: <b>" . $row['owner_name'] . "</b><br>";
        echo "Vehicle: <b>" . $row['vehicle_number'] . "</b><br>";
        echo "Violations: <b>" . $row['violation_count'] . "</b></div>";

        if ($row['violation_count'] > 5) { // Example threshold
            echo "<div class='message warning'>⚠️ Challan Issued! Please pay.</div>";
            echo "<button>Pay Now</button>"; // integrate payment gateway here
        } else {
            echo "<div class='message success'>✅ No challan issued yet.</div>";
        }
    } else {
        echo "<div class='message error'>❌ Vehicle not found!</div>";
    }
    $conn->close();
    ?>
    <div class="footer">Pollution Monitoring & Payment Portal</div>
  </div>
</body>
</html>