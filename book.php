<?php
include 'db.php';
session_start();

// CRITICAL: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to book an event.";
    header("Location: login.php");
    exit();
}

$message = '';
$event_id = null;

// Sanitize and validate event_id from GET parameter
if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    $event_id = (int)$_GET['event_id'];
} elseif (isset($_POST['event_id']) && is_numeric($_POST['event_id'])) {
    $event_id = (int)$_POST['event_id'];
} else {
    die("Invalid event ID specified.");
}

$user_id = (int)$_SESSION['user_id']; 

// Handle booking submission
if (isset($_POST['book'])) {
    $seats = filter_var($_POST['seats'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($seats === false) {
        $message = "Invalid number of seats.";
    } else {
        $status = 'pending';

        // 1. Use Prepared Statement for safe insertion
        $sql = "INSERT INTO bookings (user_id, event_id, seats, status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $message = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("iiis", $user_id, $event_id, $seats, $status);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Booking successful! Waiting for admin approval.";
                header("Location: bookings.php");
                exit();
            } else {
                $message = "Error: Booking failed. " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch event details for display in the frontend
$sql_event = "SELECT title, price FROM events WHERE event_id = ?";
$stmt_event = $conn->prepare($sql_event);
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$event_result = $stmt_event->get_result();

if ($event_result->num_rows === 0) {
    die("Event not found.");
}

$event = $event_result->fetch_assoc();
$stmt_event->close();

// Pass $event and $event_id to the frontend file
include 'bookfront.php';
?>

