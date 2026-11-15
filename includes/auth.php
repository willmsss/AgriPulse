<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
        header("Location: login.php");
        exit();
    }
}