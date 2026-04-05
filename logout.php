<?php
session_start();
session_destroy();
header("Location: gov_login.php");
exit();
?>