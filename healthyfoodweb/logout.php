<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

// Redirect to login page
header("Location: /app/login.php");
exit();
?>