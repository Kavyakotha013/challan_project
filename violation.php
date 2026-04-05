<?php
$servername = "mysql.railway.internal";
$username   = "root";
$password   = "kRtQVumqwUQPtraipUrslkOStzvSuIzv";
$dbname     = "railway_pollution_db";
$port       = 3306;                        // MYSQLPORT

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Get POST data from ESP32
$sensor_code     = $_POST['sensor_code'];
$pollution_value = $_POST['pollution_value'];

// Find vehicle by sensor code
$sql = "SELECT id FROM vehicles WHERE sensor_code='$sensor_code'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vehicle_id = $row['id'];

    // Increment violation count
    $conn->query("UPDATE vehicles SET violation_count = violation_count + 1 WHERE id=$vehicle_id");

    // Log violation
    $conn->query("INSERT INTO violations (vehicle_id, sensor_code, pollution_value) 
                  VALUES ($vehicle_id, '$sensor_code', '$pollution_value')");

    echo "✅ Violation recorded successfully for sensor $sensor_code.";
} else {
    echo "❌ Sensor code not found.";
}

$conn->close();
?>