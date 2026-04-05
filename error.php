<!DOCTYPE html>
<html>
<head><title>Error</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
<?php
$type = $_GET['type'] ?? 'general';
if ($type === 'password') {
    echo "<div class='message error'>❌ Invalid username or password.</div>";
} elseif ($type === 'unauthorized') {
    echo "<div class='message error'>❌ Unauthorized access. Please login as government official.</div>";
} else {
    echo "<div class='message error'>❌ Something went wrong. Please try again.</div>";
}
?>
<a href="index.html"><button>Return Home</button></a>
</div>
</body>
</html>