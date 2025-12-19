<?php
session_start();
session_destroy();
header('Location: farmer_login.php');
exit();
?>

