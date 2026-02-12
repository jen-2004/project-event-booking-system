<?php
// --- Database Configuration ---
// These details are only accessible on your local server (XAMPP/WAMPP)
$host = "localhost";
$user = "root";
// The default password for 'root' in XAMPP/WAMPP is typically an empty string
$pass = ""; 
$db = "eventbooking"; 

// --- Establish Connection ---
$conn = new mysqli($host, $user, $pass, $db);

// --- Handle Connection Errors Securely ---
if ($conn->connect_error) {
    // 1. Log the full, detailed error message privately to the server logs (CRITICAL SECURITY STEP).
    error_log("Database Connection Failed: " . $conn->connect_error);
    
    // 2. Terminate execution and display a generic, non-informative error to the public.
    // This prevents potential attackers from gaining information about your database status.
    die("A required server service is unavailable. Please try again later.");
    
}
// The $conn object is now available for use in all files that include 'db.php'.
?>