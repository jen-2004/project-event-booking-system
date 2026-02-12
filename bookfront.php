<?php
// This file assumes $event, $event_id, and $message are set in book.php

// Ensure all variables are set for safety
if (!isset($event) || !isset($event_id)) {
    // If not set, redirect to safety (though book.php should handle this)
    header("Location: index.php");
    exit();
}
?>
<?php
include 'db.php';


// --- Security Helper Function ---
// Function to safely display data (prevents XSS)
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Check Authentication (CRITICAL)
// Assuming a user must be logged in to book an event
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users to the index or login page
    header("Location: index.php");
    exit;
}

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];
$message = '';
$event = null; // Initialize event variable

// 2. Fetch Event Details (Securely)
if ($event_id) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();

    if (!$event) {
        // Event not found
        header("Location: index.php");
        exit;
    }
} else {
    // No event ID provided
    header("Location: index.php");
    exit;
}

// 3. Handle POST Request (Booking)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $available_seats = $event['total_seats']; // Assume total_seats field exists in 'events' table

    // Basic validation
    if ($seats === false || $seats < 1) {
        $message = "<div class='alert alert-danger'>❌ Please enter a valid number of seats.</div>";
    } elseif ($seats > $available_seats) {
         $message = "<div class='alert alert-danger'>❌ Only {$available_seats} seats are currently available.</div>";
    } else {
        // Secure Insertion using prepared statement
        $sql = "INSERT INTO bookings (user_id, event_id, seats, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        // 'iii' means integer, integer, integer
        $stmt->bind_param("iii", $user_id, $event_id, $seats);

        if ($stmt->execute()) {
            // Success: Display success message
            $message = "<div class='alert alert-success'>✅ Booking successful! Your reservation for <b>{$seats} seat(s)</b> is pending admin approval.</div>";

            // IMPORTANT: In a real system, you would also need to decrement the total_seats in the 'events' table here.

        } else {
            // Error
            $message = "<div class='alert alert-danger'>❌ Database Error: " . e($conn->error) . "</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ticket: <?= e($event['title']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Inter', sans-serif;
        }
        .booking-card {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background-color: #ffffff;
        }
        .event-detail {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 0.75rem;
            margin-bottom: 20px;
        }
        .btn-book {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s, transform 0.3s;
        }
        .btn-book:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }
        .total-price-box {
            background-color: #d1e7dd; /* Light green for success/total */
            color: #0f5132;
            padding: 15px;
            border-radius: 0.75rem;
            font-size: 1.25rem;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="booking-card">
            
            <h2 class="text-center mb-4 text-primary">Confirm Your Ticket</h2>
            
            <!-- Event Details Card -->
            <div class="event-detail">
                <h4 class="mb-2"><?= e($event['title']) ?></h4>
                <p class="mb-1">
                    <i class="fas fa-calendar-alt me-2"></i> 
                    Date: <b><?= date('F j, Y', strtotime($event['date'])) ?></b>
                </p>
                <p class="mb-1">
                    <i class="fas fa-map-marker-alt me-2"></i> 
                    Location: <b><?= e($event['location']) ?></b>
                </p>
                <p class="mb-0 text-success">
                    <i class="fas fa-ticket-alt me-2"></i> 
                    Price per seat: <b>Rs. <span id="event-price" data-price="<?= e($event['price']) ?>"><?= number_format($event['price'], 2) ?></span></b>
                </p>
                <p class="mb-0 text-muted small mt-1">
                    <i class="fas fa-chair me-2"></i> 
                    Available Seats: <b><?= e($event['total_seats']) ?></b>
                </p>
            </div>

            <!-- Display Messages (Success/Error/Validation) -->
            <?= $message ?>

            <!-- Booking Form -->
            <form method="POST" action="book.php?event_id=<?= e($event_id) ?>" class="mt-4">
                
                <div class="mb-3">
                    <label for="seats" class="form-label fw-bold">Number of Seats Required</label>
                    <input type="number" id="seats" name="seats" 
                           class="form-control form-control-lg" 
                           placeholder="Enter number of seats (Max: <?= e($event['total_seats']) ?>)" 
                           min="1" 
                           max="<?= e($event['total_seats']) ?>" 
                           value="1"
                           required
                           oninput="calculateTotal()">
                </div>

                <!-- Dynamic Total Price Display -->
                <div class="total-price-box d-flex justify-content-between">
                    <span><i class="fas fa-receipt me-2"></i> Total Payable:</span>
                    <span>Rs. <span id="total-price">0.00</span></span>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" name="book" class="btn btn-book btn-lg">
                        <i class="fas fa-check-circle me-2"></i> Confirm Booking
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-secondary text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Events
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Get the single seat price and the elements for dynamic update
        const seatPriceElement = document.getElementById('event-price');
        const seatsInput = document.getElementById('seats');
        const totalPriceElement = document.getElementById('total-price');

        // Parse the price from the data attribute (ensures clean number)
        const pricePerSeat = parseFloat(seatPriceElement.dataset.price);

        /**
         * Calculates and updates the total price based on the number of seats.
         */
        function calculateTotal() {
            let seats = parseInt(seatsInput.value, 10);

            // Handle non-numeric or empty input gracefully
            if (isNaN(seats) || seats < 1) {
                seats = 0;
            }

            const total = seats * pricePerSeat;
            
            // Format the total price to two decimal places
            totalPriceElement.textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Run calculation once on load to show the default (1 seat) price
        window.onload = function() {
            calculateTotal();
        }
    </script>
</body>
</html>