<?php
$conn = new mysqli(
    getenv("MYSQLHOST"),
    getenv("MYSQLUSER"),
    getenv("MYSQLPASSWORD"),
    getenv("MYSQLDATABASE"),
    getenv("MYSQLPORT")
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete existing admin if it exists, then insert fresh
$conn->query("DELETE FROM gov_users WHERE username='admin'");

$sql = "INSERT INTO gov_users (username, password_hash) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')";

if ($conn->query($sql)) {
    echo "Admin user created successfully! Username: admin, Password: admin123";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
