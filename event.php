<?php
include 'db.php';
session_start();

// Helper function for security (XSS prevention)
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Fetch all events for the frontend display
$sql = "SELECT * FROM events ORDER BY date ASC";
$result = $conn->query($sql);

if ($result === false) {
    // Handle database error
    error_log("Error fetching events in event.php: " . $conn->error);
    die("Error fetching events.");
}

// Include the frontend file for presentation
include 'eventfront.php';
?>