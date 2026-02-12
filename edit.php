<?php
include 'db.php';
session_start();

// CRITICAL: Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin access required.");
}

// Sanitize and validate inputs
$booking_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '';

if (!$booking_id || !in_array($action, ['approve', 'cancel'])) {
    $_SESSION['message'] = "Invalid request for booking action.";
    header("Location: adminfrontend.php");
    exit();
}

$new_status = ($action === 'approve') ? 'approved' : 'cancelled';

// Use Prepared Statement for safe update
$sql = "UPDATE bookings SET status = ? WHERE booking_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['message'] = "Error preparing statement: " . $conn->error;
} else {
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Booking ID {$booking_id} successfully {$new_status}.";
    } else {
        $_SESSION['message'] = "Error updating booking: " . $stmt->error;
    }
    $stmt->close();
}

// Redirect back to the admin dashboard
header("Location: adminfrontend.php");
exit();
?>