<?php
include 'auth_check.php'; // ADMIN PROTECTION
include 'db.php';

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$message = '';
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

// --- Handle Status Update POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action']; // 'approved' or 'cancelled'
    $new_status = ($action === 'approved') ? 'approved' : 'cancelled';

    if ($booking_id) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Booking #$booking_id successfully marked as $new_status.";
        } else {
            $_SESSION['flash_message'] = "Error updating booking: " . $stmt->error;
        }
        $stmt->close();
        
        header("Location: manage_bookings.php");
        exit;
    }
}

// --- Fetch all Bookings with User and Event Details ---
$sql = "SELECT 
            b.booking_id, b.seats, b.status, b.booking_date,
            u.name AS user_name, u.email AS user_email,
            e.title AS event_title
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN events e ON b.event_id = e.event_id
        ORDER BY b.booking_date DESC";

$result = $conn->query($sql);

// Count by status
$pending_count = 0;
$approved_count = 0;
$cancelled_count = 0;
$result_temp = $conn->query($sql);
while ($row = $result_temp->fetch_assoc()) {
    if ($row['status'] === 'pending') $pending_count++;
    elseif ($row['status'] === 'approved') $approved_count++;
    elseif ($row['status'] === 'cancelled') $cancelled_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | TICKET AAYO Admin</title>
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

        /* Admin Navbar */
        .navbar {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 1rem 0;
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: 800;
            font-size: 1.5rem;
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
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #fff !important;
        }

        /* Page Header */
        .page-header {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border-left: 5px solid #f59e0b;
        }

        .page-header h1 {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2.2rem;
            margin: 0;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Stats Cards */
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

        .stat-card.cancelled {
            border-color: #ef4444;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            margin-bottom: 8px;
        }

        .stat-card.total h3 {
            color: #3b82f6;
        }

        .stat-card.pending h3 {
            color: #f59e0b;
        }

        .stat-card.approved h3 {
            color: #10b981;
        }

        .stat-card.cancelled h3 {
            color: #ef4444;
        }

        .stat-card p {
            margin: 0;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .stat-card i {
            font-size: 1.5rem;
            opacity: 0.5;
            float: right;
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

        /* Table Card */
        .admin-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
            padding: 20px 25px;
            font-weight: 700;
            color: #1e3c72;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            color: #f59e0b;
        }

        /* Table Styling */
        .table {
            margin: 0;
        }

        .table thead {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }

        .table thead th {
            font-weight: 700;
            padding: 16px;
            border: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            color: #475569;
            font-size: 0.95rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.005);
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

        .status-badge-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #f59e0b;
        }

        .status-badge-approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }

        .status-badge-cancelled {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        /* Action Buttons */
        .btn-sm {
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Back Button */
        .btn-back {
            background: #fff;
            color: #64748b;
            border: 2px solid #e2e8f0;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #f8fafc;
            color: #f59e0b;
            border-color: #f59e0b;
            transform: translateX(-5px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
        }

        /* Container */
        .container {
            max-width: 1400px;
            padding-top: 40px;
            padding-bottom: 60px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.8rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th,
            .table tbody td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="adminfrontend.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_bookings.php">
                            <i class="fas fa-clipboard-check"></i> Manage Bookings
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
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
        <div class="mb-3">
            <a href="adminfrontend.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="page-header">
            <h1>
                <i class="fas fa-clipboard-list"></i>
                Booking Management
            </h1>
        </div>

        <?php if ($flash_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= e($flash_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
            <div class="stat-card cancelled">
                <i class="fas fa-times-circle"></i>
                <h3><?= $cancelled_count ?></h3>
                <p>Cancelled</p>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-header">
                <i class="fas fa-table"></i>
                All Bookings
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event</th>
                            <th>User</th>
                            <th>Seats</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= e($row['booking_id']) ?></strong></td>
                                    <td><?= e($row['event_title']) ?></td>
                                    <td>
                                        <?= e($row['user_name']) ?><br>
                                        <small class="text-muted"><?= e($row['user_email']) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?= e($row['seats']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($row['booking_date'])) ?></td>
                                    <td>
                                        <span class="status-badge status-badge-<?= e($row['status']) ?>">
                                            <?= ucfirst(e($row['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this booking?');">
                                                <input type="hidden" name="booking_id" value="<?= e($row['booking_id']) ?>">
                                                <input type="hidden" name="action" value="approved">
                                                <button type="submit" class="btn btn-success btn-sm me-1">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking?');">
                                                <input type="hidden" name="booking_id" value="<?= e($row['booking_id']) ?>">
                                                <input type="hidden" name="action" value="cancelled">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No bookings found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>