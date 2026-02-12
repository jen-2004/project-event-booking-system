<?php
include 'db.php';
session_start();

// --- Security Helper Function ---
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Check Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect_to=bookings.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check for booking success message from process_payment.php
$message = isset($_SESSION['booking_message']) ? $_SESSION['booking_message'] : '';
$message_type = isset($_SESSION['booking_type']) ? $_SESSION['booking_type'] : '';

// Clear session messages after displaying
unset($_SESSION['booking_message']);
unset($_SESSION['booking_type']);

// 2. Fetch User's Bookings
$sql = "SELECT b.booking_id, b.seats_booked, b.total_price, b.booking_date, b.status, 
               e.title AS event_title, e.date AS event_date, e.location 
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | TICKET AAYO</title>
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        /* Navbar Placeholder */
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 1.2rem 0;
            margin-bottom: 40px;
        }

        /* Page Header */
        .page-header {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 40px;
            text-align: center;
            border-top: 5px solid #667eea;
        }

        .page-header h1 {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2.5rem;
            margin: 0;
            letter-spacing: -1px;
            display: inline-flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: #64748b;
            margin: 10px 0 0 0;
            font-size: 1.05rem;
        }

        /* Stats Badge */
        .bookings-count {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-top: 15px;
        }

        /* Alert Messages */
        .alert {
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 600;
            border: 2px solid;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateY(-10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #10b981;
            color: #065f46;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
            color: #1e40af;
        }

        .alert-info a {
            color: #1e3c72;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid #3b82f6;
        }

        .alert-info a:hover {
            color: #667eea;
        }

        /* Booking Cards */
        .booking-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .booking-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
        }

        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }

        .booking-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, .1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }

        .booking-header h5 {
            margin: 0;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }

        .booking-id-badge {
            position: absolute;
            top: 15px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .card-body {
            padding: 30px;
        }

        /* Info Sections */
        .info-section {
            margin-bottom: 10px;
        }

        .info-label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .info-value {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value i {
            color: #667eea;
            font-size: 1rem;
        }

        /* Divider */
        .booking-divider {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 25px 0;
        }

        /* Stats Row */
        .stats-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-box {
            flex: 1;
            min-width: 150px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-weight: 800;
            font-size: 1.8rem;
        }

        .stat-value.seats {
            color: #667eea;
        }

        .stat-value.price {
            color: #10b981;
        }

        /* Status Badge */
        .status-badge {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid #10b981;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }

        .status-badge i {
            font-size: 1.1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 25px;
        }

        .empty-state h3 {
            color: #1e3c72;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .btn-explore {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-explore:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            color: #fff;
        }

        /* Container */
        .container {
            max-width: 1200px;
            padding-top: 40px;
            padding-bottom: 60px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
                flex-direction: column;
            }

            .booking-header h5 {
                font-size: 1.2rem;
                padding-right: 80px;
            }

            .booking-id-badge {
                font-size: 0.75rem;
                padding: 4px 12px;
            }

            .card-body {
                padding: 20px;
            }

            .stats-row {
                flex-direction: column;
                align-items: stretch;
            }

            .stat-box {
                text-align: center;
            }

            .status-badge {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-ticket-alt"></i>
                My Bookings
            </h1>
            <p>View and manage all your event bookings</p>
            <?php if (!empty($bookings)): ?>
                <span class="bookings-count">
                    <i class="fas fa-list"></i> <?= count($bookings) ?> Total Bookings
                </span>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= e($message_type) ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= e($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't booked any events yet. Start exploring amazing events now!</p>
                <a href="event.php" class="btn-explore">
                    <i class="fas fa-calendar-check"></i>
                    Explore Events
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-12">
                        <div class="booking-card">
                            <div class="booking-header">
                                <h5><?= e($booking['event_title']) ?></h5>
                                <span class="booking-id-badge">#<?= e($booking['booking_id']) ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 info-section">
                                        <div class="info-label">Event Date</div>
                                        <div class="info-value">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= date('l, F j, Y', strtotime($booking['event_date'])) ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 info-section">
                                        <div class="info-label">Location</div>
                                        <div class="info-value">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= e($booking['location']) ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-divider"></div>

                                <div class="stats-row">
                                    <div class="stat-box">
                                        <div class="stat-label">Tickets Booked</div>
                                        <div class="stat-value seats">
                                            <i class="fas fa-ticket-alt"></i> <?= e($booking['seats_booked']) ?>
                                        </div>
                                    </div>

                                    <div class="stat-box">
                                        <div class="stat-label">Total Paid</div>
                                        <div class="stat-value price">
                                            Rs. <?= number_format(e($booking['total_price']), 2) ?>
                                        </div>
                                    </div>

                                    <div class="stat-box" style="text-align: right;">
                                        <span class="status-badge">
                                            <i class="fas fa-check-circle"></i>
                                            Confirmed
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3 text-muted" style="font-size: 0.85rem;">
                                    <i class="fas fa-clock"></i> Booked on <?= date('M j, Y \a\t g:i A', strtotime($booking['booking_date'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>