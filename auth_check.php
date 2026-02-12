<?php
// auth_check.php - Checks if the user is logged in AND has the 'admin' role.

session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to admin login page
    header("Location: admin_login.php");
    exit;
}

// 2. Check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    // If logged in but not an admin, redirect them away (e.g., to the user homepage)
    header("Location: index.php");
    exit;
}

// If both checks pass, the script continues to the admin page content.
?>