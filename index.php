<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already authenticated, redirect to dashboard; otherwise go to login
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>