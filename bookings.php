<?php
include 'db.php';
session_start();

// --- CRITICAL: AUTHENTICATION CHECK ---
// If the user is not logged in, redirect them to the login page immediately.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    // Admins should use adminfrontend.php
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: adminfrontend.php");
        exit;
    }
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Security Helper Function
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Fetch user's bookings using a prepared statement
$sql = "SELECT b.*, e.title, e.date, e.price, e.location 
        FROM bookings b 
        JOIN events e ON b.event_id = e.event_id 
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC"; // Show newest bookings first

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check for and clear any session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Count bookings by status
$pending_count = 0;
$approved_count = 0;
$cancelled_count = 0;
$total_spent = 0;

$result_temp = $conn->query("SELECT b.*, e.price FROM bookings b JOIN events e ON b.event_id = e.event_id WHERE b.user_id = $user_id");
while ($row = $result_temp->fetch_assoc()) {
    if ($row['status'] === 'pending') $pending_count++;
    elseif ($row['status'] === 'approved') $approved_count++;
    elseif ($row['status'] === 'cancelled') $cancelled_count++;
    $total_spent += (float)$row['seats_quantity'] * (float)$row['price'];
}
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

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 1.2rem 0;
            margin-bottom: 40px;
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #fff !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: #ffcc33;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 70%;
        }

        /* Page Header */
        .page-header {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            text-align: center;
            border-top: 5px solid #667eea;
        }

        .page-header h1 {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            letter-spacing: -1px;
        }

        .page-header p {
            color: #64748b;
            margin: 0;
            font-size: 1.05rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card.total {
            border-color: #3b82f6;
        }

        .stat-card.pending {
            border-color: #f59e0b;
        }

        .stat-card.approved {
            border-color: #10b981;
        }

        .stat-card.spent {
            border-color: #667eea;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .stat-card.total h3 { color: #3b82f6; }
        .stat-card.pending h3 { color: #f59e0b; }
        .stat-card.approved h3 { color: #10b981; }
        .stat-card.spent h3 { color: #667eea; }

        .stat-card p {
            margin: 0;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .stat-card i {
            font-size: 1.5rem;
            opacity: 0.5;
            margin-bottom: 10px;
        }

        /* Alert */
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            border-radius: 12px;
            color: #065f46;
            font-weight: 600;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
            animation: slideIn 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Table Card */
        .card-booking {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        /* Table */
        .table {
            margin: 0;
        }

        .table thead {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }

        .table thead th {
            font-weight: 700;
            padding: 18px;
            border: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 18px;
            vertical-align: middle;
            color: #475569;
            font-size: 0.95rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.002);
        }

        /* Event Details */
        .event-details h6 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e3c72;
            margin: 0 0 5px 0;
        }

        .event-details small {
            color: #64748b;
            display: block;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #f59e0b;
        }

        .status-approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 25px;
        }

        .empty-state h4 {
            color: #1e3c72;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        /* Bottom Button */
        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            color: #64748b;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: #667eea;
            color: #fff;
            border-color: #667eea;
        }

        /* Container */
        .container {
            max-width: 1400px;
            padding-bottom: 60px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th,
            .table tbody td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">🎟️ TICKET AAYO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="event.php">
                            <i class="fas fa-calendar-alt"></i> Browse Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">
                            <i class="fas fa-ticket-alt"></i> My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-clipboard-list"></i> Your Event Bookings
            </h1>
            <p>Manage and view all your ticket bookings in one place</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= e($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card total">
                <i class="fas fa-list"></i>
                <h3><?= $result->num_rows ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="stat-card pending">
                <i class="fas fa-clock"></i>
                <h3><?= $pending_count ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card approved">
                <i class="fas fa-check-circle"></i>
                <h3><?= $approved_count ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card spent">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Rs. <?= number_format($total_spent, 0) ?></h3>
                <p>Total Spent</p>
            </div>
        </div>
        
        <div class="card-booking">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Event Details</th>
                                <th>Seats</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Booking Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $total_price = (float)$row['seats_quantity'] * (float)$row['price'];
                                $status_class = 'status-' . strtolower($row['status']);
                            ?>
                            <tr>
                                <td class="event-details">
                                    <h6><?= e($row['title']) ?></h6>
                                    <small>
                                        <i class="fas fa-map-marker-alt"></i> <?= e($row['location']) ?> | 
                                        <i class="fas fa-calendar-day"></i> <?= date('M j, Y', strtotime($row['date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-primary" style="font-size: 0.95rem;">
                                        <i class="fas fa-ticket-alt"></i> <?= (int)$row['seats_quantity'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success" style="font-size: 1.1rem;">
                                    Rs. <?= number_format($total_price, 2) ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= ucfirst(e($row['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <small style="color: #64748b;">
                                        <?= date('M j, Y', strtotime($row['booking_date'])) ?><br>
                                        <?= date('g:i A', strtotime($row['booking_date'])) ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Bookings Yet</h4>
                    <p>You haven't booked any events yet. Start exploring amazing events now!</p>
                    <a href="event.php" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Browse Events
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <p class="text-center mt-5">
            <a href="event.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Continue Browsing
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>