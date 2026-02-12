<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Database connection
include 'db.php';

// --- Security Helper Function ---
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. CRITICAL: Check Authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to book an event.";
    header("Location: login.php");
    exit;
}

// 2. Get and validate event_id and action
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

// FIX: Replace FILTER_SANITIZE_STRING with FILTER_SANITIZE_FULL_SPECIAL_CHARS
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS); 

if (!$event_id || $action !== 'book') {
    $_SESSION['booking_error'] = "Invalid booking request.";
    // Assuming 'event.php' displays the event details for the user to select
    header("Location: index.php"); 
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 3. Fetch event details - UPDATED TO INCLUDE image_path
$stmt = $conn->prepare("SELECT event_id, title, date, location, price, total_seats, image_path FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION['booking_error'] = "Event not found.";
    header("Location: index.php");
    exit;
}

// Check for available seats before rendering the form
if ($event['total_seats'] <= 0) {
    $_SESSION['booking_error'] = "Sorry, this event is fully booked.";
    header("Location: index.php");
    exit;
}


// 4. Check if user already booked this event (Prevent duplicate bookings)
$stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE user_id = ? AND event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_booking = $result->fetch_assoc();
$stmt->close();

if ($existing_booking) {
    $_SESSION['booking_error'] = "You have already booked this event.";
    header("Location: booking_confirmation.php?status=duplicate&event_id={$event_id}"); // Redirect to confirmation/status page
    exit;
}

// 5. Handle POST request (actual booking submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    
    // Re-check seat availability after user input, before transaction
    if (!$seats || $seats < 1) {
        $error_message = "Please enter a valid number of seats.";
    } elseif ($seats > $event['total_seats']) {
        $error_message = "Sorry, only {$event['total_seats']} seats are currently available.";
    } else {
        // Start transaction for booking
        $conn->begin_transaction();
        
        try {
            // Re-fetch the seat count *inside* the transaction to prevent race conditions 
            // if multiple people book at the same time (optimistic locking approach)
            $stmt = $conn->prepare("SELECT total_seats FROM events WHERE event_id = ? FOR UPDATE");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_seats = $result->fetch_assoc()['total_seats'];
            $stmt->close();

            if ($seats > $current_seats) {
                throw new Exception("Seats became unavailable during processing.");
            }

            // Calculate total price
            $total_price = $seats * $event['price'];
            $status = 'pending'; // or 'approved' depending on your workflow
            
            // Insert booking
            // Note: I renamed the column 'seats' to 'quantity' in the VALUES list for clarity, 
            // assuming 'seats_booked' will hold the confirmed quantity.
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, seats_quantity, total_price, status, booking_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iidds", $user_id, $event_id, $seats, $total_price, $status);
            $stmt->execute();
            $booking_id = $conn->insert_id;
            $stmt->close();
            
            // Update available seats (CRITICAL STEP in transaction)
            $stmt = $conn->prepare("UPDATE events SET total_seats = total_seats - ? WHERE event_id = ?");
            $stmt->bind_param("ii", $seats, $event_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to confirmation page
            header("Location: booking_confirmation.php?status=success&event_id=$event_id");
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Booking failed due to " . ($e->getMessage() === "Seats became unavailable during processing." ? "lack of available seats." : "a server error.");
            error_log("Booking Transaction error for user $user_id: " . $e->getMessage());
        }
    }
}

// If we reach here, show the booking form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ticket: <?= e($event['title']) ?> | TICKET AAYO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Existing CSS Styles (unchanged as they are already modern and clean) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .booking-card {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .booking-card::before {
            content: '';
            display: block;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 30px;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header h2 {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2rem;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }

        .card-header p {
            color: #64748b;
            margin: 0;
        }

        .card-body {
            padding: 40px;
        }

        .event-detail {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        /* NEW: Event Image Styles */
        .event-image-container {
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .event-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }

        .event-image:hover {
            transform: scale(1.05);
        }

        .event-detail h4 {
            color: #1e3c72;
            font-weight: 800;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-row i {
            width: 30px;
            color: #667eea;
            font-size: 1.1rem;
        }

        .detail-row strong {
            color: #1e293b;
            margin-right: 8px;
        }

        .price-highlight {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 800;
            display: inline-block;
        }

        .form-label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            display: block;
        }

        .form-control {
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .total-box {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #667eea;
            margin: 25px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-box strong {
            color: #1e3c72;
            font-size: 1.2rem;
        }

        .total-amount {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-back {
            display: inline-block;
            color: #64748b;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            color: #667eea;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            border-radius: 12px;
            color: #991b1b;
            padding: 16px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 25px;
            }

            .card-header {
                padding: 20px;
            }

            .card-header h2 {
                font-size: 1.5rem;
            }

            .event-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="booking-card">
        <div class="card-header">
            <h2>Confirm Your Booking</h2>
            <p>Review the details and complete your reservation</p>
        </div>
        
        <div class="card-body">
            <?php if (isset($error_message)): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= e($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="event-detail">
                <?php if (!empty($event['image_path']) && file_exists($event['image_path'])): ?>
                    <div class="event-image-container">
                        <img src="<?= e($event['image_path']) ?>" alt="<?= e($event['title']) ?>" class="event-image">
                    </div>
                <?php endif; ?>
                
                <h4><?= e($event['title']) ?></h4>
                
                <div class="detail-row">
                    <i class="fas fa-calendar-alt"></i>
                    <span><strong>Date:</strong> <?= date('l, F j, Y', strtotime($event['date'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><strong>Location:</strong> <?= e($event['location']) ?></span>
                </div>
                
                <div class="detail-row">
                    <i class="fas fa-ticket-alt"></i>
                    <span><strong>Price per Ticket:</strong> <span class="price-highlight">Rs. <?= number_format($event['price'], 2) ?></span></span>
                </div>
                
                <div class="detail-row">
                    <i class="fas fa-chair"></i>
                    <span><strong>Available Seats:</strong> <?= e($event['total_seats']) ?></span>
                </div>
            </div>

            <form method="POST" action="eventbookingprocessor.php?event_id=<?= $event_id ?>&action=book" id="bookingForm">
                <div class="mb-3">
                    <label for="seats" class="form-label">
                        <i class="fas fa-users me-2"></i>Number of Tickets
                    </label>
                    <input type="number" 
                           id="seats" 
                           name="seats" 
                           class="form-control" 
                           min="1" 
                           max="<?= e($event['total_seats']) ?>" 
                           value="1" 
                           required
                           oninput="calculateTotal()">
                </div>

                <div class="total-box">
                    <strong><i class="fas fa-receipt me-2"></i>Total Amount:</strong>
                    <span class="total-amount" id="totalAmount">Rs. <?= number_format($event['price'], 2) ?></span>
                </div>

                <button type="submit" name="confirm_booking" class="btn-confirm">
                    <i class="fas fa-check-circle me-2"></i>Confirm Booking
                </button>
            </form>

            <div class="text-center">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Ensure price is converted to a JavaScript safe numeric value
        const pricePerTicket = parseFloat(<?= json_encode($event['price']) ?>);
        
        function calculateTotal() {
            // Get current input value (default to 0 if not a valid number)
            let seats = parseInt(document.getElementById('seats').value) || 0;
            
            // Apply Max/Min validation locally for immediate feedback
            const maxSeats = parseInt(document.getElementById('seats').max);
            
            if (seats > maxSeats) {
                seats = maxSeats;
                document.getElementById('seats').value = seats;
            }
            if (seats < 1) {
                seats = 1;
                document.getElementById('seats').value = seats;
            }
            
            const total = seats * pricePerTicket;
            
            // Format output with commas for thousands (assuming Nepali/Indian locale for Rs.)
            // Using Intl.NumberFormat for robust formatting
            document.getElementById('totalAmount').textContent = 'Rs. ' + new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(total);
        }
        
        // Calculate on load
        calculateTotal();
    </script>
</body>
</html>