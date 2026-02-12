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

// 1. Check Authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to view booking confirmation.";
    header("Location: login.php");
    exit;
}

// 2. Get and validate parameters
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

if (!$status || !$event_id) {
    header("Location: index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 3. Fetch event details
$stmt = $conn->prepare("SELECT event_id, title, date, location, price FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: index.php");
    exit;
}

// 4. Fetch booking details if status is success
$booking = null;
if ($status === 'success') {
    $stmt = $conn->prepare("SELECT booking_id, seats_quantity, total_price, status, booking_date FROM bookings WHERE user_id = ? AND event_id = ? ORDER BY booking_date DESC LIMIT 1");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
}

// 5. Determine message and icon based on status
$messages = [
    'success' => [
        'title' => 'Booking Confirmed!',
        'message' => 'Your booking has been successfully confirmed. Check your email for details.',
        'icon' => 'fas fa-check-circle',
        'color' => 'success'
    ],
    'duplicate' => [
        'title' => 'Already Booked',
        'message' => 'You have already booked this event. Please check your bookings.',
        'icon' => 'fas fa-info-circle',
        'color' => 'warning'
    ],
    'failed' => [
        'title' => 'Booking Failed',
        'message' => 'Unfortunately, your booking could not be processed. Please try again.',
        'icon' => 'fas fa-times-circle',
        'color' => 'danger'
    ]
];

$current_status = $messages[$status] ?? $messages['failed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation | TICKET AAYO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .confirmation-card {
            max-width: 700px;
            width: 100%;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .confirmation-card::before {
            content: '';
            display: block;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .status-header {
            text-align: center;
            padding: 50px 40px 30px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
        }

        .status-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .status-icon.success { color: #10b981; }
        .status-icon.warning { color: #f59e0b; }
        .status-icon.danger { color: #ef4444; }

        .status-header h1 {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .status-header p {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
        }

        .card-body {
            padding: 40px;
        }

        .event-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .event-summary h3 {
            color: #1e3c72;
            font-weight: 800;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .detail-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 10px 0;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-row i {
            width: 35px;
            color: #667eea;
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .detail-row .label {
            color: #64748b;
            font-weight: 600;
            min-width: 150px;
        }

        .detail-row .value {
            color: #1e293b;
            font-weight: 700;
            flex: 1;
        }

        .booking-info {
            background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 2px solid #667eea;
        }

        .booking-info h4 {
            color: #1e3c72;
            font-weight: 800;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 12px;
        }

        .info-item .info-label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item .info-value {
            color: #1e293b;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .total-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            flex: 1;
            background: #f8fafc;
            color: #64748b;
            border: 2px solid #e2e8f0;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .notice-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .notice-box i {
            color: #d97706;
            font-size: 1.5rem;
            margin-top: 2px;
        }

        .notice-box p {
            color: #78350f;
            margin: 0;
            font-weight: 600;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .status-header {
                padding: 40px 25px 25px;
            }

            .status-header h1 {
                font-size: 1.8rem;
            }

            .status-icon {
                font-size: 4rem;
            }

            .card-body {
                padding: 25px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="status-header">
            <div class="status-icon <?= e($current_status['color']) ?>">
                <i class="<?= e($current_status['icon']) ?>"></i>
            </div>
            <h1><?= e($current_status['title']) ?></h1>
            <p><?= e($current_status['message']) ?></p>
        </div>
        
        <div class="card-body">
            <!-- Event Summary -->
            <div class="event-summary">
                <h3><?= e($event['title']) ?></h3>
                
                <div class="detail-row">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="label">Event Date:</span>
                    <span class="value"><?= date('l, F j, Y', strtotime($event['date'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="label">Location:</span>
                    <span class="value"><?= e($event['location']) ?></span>
                </div>
                
                <div class="detail-row">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="label">Price per Ticket:</span>
                    <span class="value">Rs. <?= number_format($event['price'], 2) ?></span>
                </div>
            </div>

            <!-- Booking Details (only for successful bookings) -->
            <?php if ($status === 'success' && $booking): ?>
                <div class="booking-info">
                    <h4><i class="fas fa-receipt me-2"></i>Booking Details</h4>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Booking ID</div>
                            <div class="info-value">#<?= str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Booking Date</div>
                            <div class="info-value"><?= date('M j, Y', strtotime($booking['booking_date'])) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Number of Tickets</div>
                            <div class="info-value"><?= e($booking['seats_quantity']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Total Amount</div>
                            <div class="info-value total-highlight">Rs. <?= number_format($booking['total_price'], 2) ?></div>
                        </div>
                    </div>
                </div>

                <div class="notice-box">
                    <i class="fas fa-info-circle"></i>
                    <p>
                        A confirmation email has been sent to your registered email address. 
                        Please present your Booking ID at the venue. Keep this information safe for future reference.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($status === 'success'): ?>
                    <a href="bookings.php" class="btn-primary">
                        <i class="fas fa-list"></i> View My Bookings
                    </a>
                <?php endif; ?>
                
                <a href="index.php" class="btn-<?= $status === 'success' ? 'secondary' : 'primary' ?>">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>