<?php
include 'auth_check.php'; // ADMIN PROTECTION
include 'db.php';

// Get Event ID from URL
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// If no ID is provided, redirect
if ($event_id === false || $event_id === null) {
    header("Location: adminfrontend.php");
    exit;
}

// --- Secure DELETE Query ---
$sql = "DELETE FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $event_id);
    
    if ($stmt->execute()) {
        // Use a session flash message for feedback
        $_SESSION['flash_message'] = "Event ID $event_id successfully deleted.";
    } else {
        $_SESSION['flash_message'] = "Error deleting event: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['flash_message'] = "Database error: Could not prepare statement.";
}

$conn->close();

// Redirect back to the admin dashboard
header("Location: adminfrontend.php");
exit;
?>