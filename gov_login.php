<?php
session_start();
$conn = new mysqli(getenv("MYSQLHOST"), getenv("MYSQLUSER"), getenv("MYSQLPASSWORD"), getenv("MYSQLDATABASE"), getenv("MYSQLPORT"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password_hash FROM gov_users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['gov_user'] = true;
            header("Location: register.php");
            exit();
        } else {
            header("Location: error.php?type=password");
            exit();
        }
    } else {
        header("Location: error.php?type=password");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Government Login</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
  <h2>🔒 Government Login</h2>
  <form method="POST">
    <label>Username:</label><input type="text" name="username" required>
    <label>Password:</label><input type="password" name="password" required>
    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>