<?php
include 'db.php';
session_start();

// CRITICAL: Access Control - Only Admins can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h3 style='color: red; text-align: center;'>Access denied. You must be logged in as an administrator.</h3>");
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Fetch Dashboard Statistics
$stats = [];

// Total Events
$result = $conn->query("SELECT COUNT(*) as count FROM events");
$stats['total_events'] = $result->fetch_assoc()['count'];

// Total Bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Pending Bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$stats['pending_bookings'] = $result->fetch_assoc()['count'];

// Approved Bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'approved'");
$stats['approved_bookings'] = $result->fetch_assoc()['count'];

// Total Users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TICKET AAYO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --info: #3b82f6;
            --warning: #f59e0b;
            --dark: #1a202c;
            --light: #f7fafc;
            --gray: #64748b;
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.3) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .main-wrapper {
            position: relative;
            z-index: 1;
            padding: 30px 0 60px 0;
        }

        /* Header Section */
        .admin-header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 25px 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 35px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-header h1 {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
            font-size: 2.2rem;
            margin: 0;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: center;
        }

        .admin-header h1 i {
            font-size: 1.8rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--white);
            margin-top: 12px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        /* Action Bar */
        .action-bar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 18px 25px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-text {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-text i {
            font-size: 1.3rem;
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: var(--white);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: var(--white);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%);
            color: var(--white);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
        }

        .btn-outline-secondary {
            background: transparent;
            border: 2px solid var(--gray);
            color: var(--dark);
        }

        .btn-outline-secondary:hover {
            background: var(--gray);
            color: var(--white);
            border-color: var(--gray);
        }

        /* Statistics Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .stat-card.success::before {
            background: linear-gradient(90deg, var(--success) 0%, #059669 100%);
        }

        .stat-card.danger::before {
            background: linear-gradient(90deg, var(--danger) 0%, #dc2626 100%);
        }

        .stat-card.info::before {
            background: linear-gradient(90deg, var(--info) 0%, #2563eb 100%);
        }

        .stat-card.warning::before {
            background: linear-gradient(90deg, var(--warning) 0%, #ea580c 100%);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            color: var(--primary);
        }

        .stat-icon.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
            color: var(--success);
        }

        .stat-icon.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%);
            color: var(--danger);
        }

        .stat-icon.info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%);
            color: var(--info);
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(234, 88, 12, 0.15) 100%);
            color: var(--warning);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--gray);
            font-weight: 500;
            margin: 0;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            height: 100%;
            animation: fadeInUp 0.8s ease-out;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card h4 {
            color: var(--dark);
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-card h4 i {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
        }

        .dashboard-card hr {
            border: none;
            height: 2px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            margin: 20px 0;
            opacity: 0.3;
        }

        /* Enhanced Table Styling */
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }

        .table {
            margin-bottom: 0;
            font-size: 1rem;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .table thead th {
            color: var(--white);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            padding: 16px 18px;
            border: none;
            white-space: nowrap;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            background: var(--white);
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }

        .table tbody td {
            padding: 16px 18px;
            vertical-align: middle;
            border-color: rgba(102, 126, 234, 0.1);
            font-size: 0.95rem;
        }

        .table tbody td strong {
            color: var(--primary);
            font-weight: 700;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important;
        }

        /* Button sizes in table */
        .table .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        /* Alerts */
        .alert {
            border-radius: 16px;
            padding: 18px 25px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            font-weight: 500;
            animation: slideDown 0.5s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%);
            color: var(--info);
            border-left: 4px solid var(--info);
        }

        .alert i {
            margin-right: 8px;
        }

        /* Quick Actions Grid */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(102, 126, 234, 0.1);
            border-radius: 14px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            border-color: var(--primary);
            color: var(--primary);
        }

        .quick-action-btn i {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        /* Floating particles */
        .particle {
            position: fixed;
            border-radius: 50%;
            opacity: 0.08;
            animation: float 20s infinite ease-in-out;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-100px) rotate(180deg); }
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            background: var(--primary);
            top: 20%;
            right: 10%;
            animation-duration: 25s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            background: var(--secondary);
            top: 60%;
            right: 30%;
            animation-duration: 20s;
            animation-delay: 5s;
        }

        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            background: var(--info);
            bottom: 10%;
            left: 20%;
            animation-duration: 30s;
            animation-delay: 10s;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 1.6rem;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: center;
            }

            .stat-value {
                font-size: 1.8rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Floating particles -->
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>

<div class="main-wrapper">
    <div class="container-fluid px-4">
        <div class="admin-header">
            <div class="text-center">
                <h1>
                    <i class="fas fa-shield-alt"></i>
                    Admin Dashboard
                </h1>
                <span class="admin-badge">
                    <i class="fas fa-user-shield"></i> Administrator Panel
                </span>
            </div>
        </div>

        <div class="action-bar">
            <div class="welcome-text">
                <i class="fas fa-user-circle"></i>
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong></span>
            </div>
            <div class="action-buttons">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-home"></i> Public Site
                </a>
                <a href="view_bookings.php" class="btn btn-info text-white">
                    <i class="fas fa-ticket-alt"></i> All Bookings
                </a>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success text-center" role="alert">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="row g-3 mb-4">
            <div class="col-xl col-lg-4 col-md-6">
                <div class="stat-card primary" style="animation-delay: 0.1s;">
                    <div class="stat-icon primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_events']) ?></div>
                    <p class="stat-label">Total Events</p>
                </div>
            </div>

            <div class="col-xl col-lg-4 col-md-6">
                <div class="stat-card info" style="animation-delay: 0.2s;">
                    <div class="stat-icon info">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_bookings']) ?></div>
                    <p class="stat-label">Total Bookings</p>
                </div>
            </div>

            <div class="col-xl col-lg-4 col-md-6">
                <div class="stat-card warning" style="animation-delay: 0.3s;">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['pending_bookings']) ?></div>
                    <p class="stat-label">Pending</p>
                </div>
            </div>

            <div class="col-xl col-lg-4 col-md-6">
                <div class="stat-card success" style="animation-delay: 0.4s;">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['approved_bookings']) ?></div>
                    <p class="stat-label">Approved</p>
                </div>
            </div>

            <div class="col-xl col-lg-4 col-md-6">
                <div class="stat-card info" style="animation-delay: 0.5s;">
                    <div class="stat-icon info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card mb-4" style="animation-delay: 0.7s;">
            <h4>
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h4>
            <div class="quick-actions">
                <a href="add_event.php" class="quick-action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Event</span>
                </a>
                <a href="view_bookings.php" class="quick-action-btn">
                    <i class="fas fa-list"></i>
                    <span>View All Bookings</span>
                </a>
                <a href="manage_users.php" class="quick-action-btn">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
            </div>
        </div>

        <div class="row g-4">

            <!-- Event Management Card -->
            <div class="col-lg-7">
                <div class="dashboard-card" style="animation-delay: 0.8s;">
                    <h4>
                        <i class="fas fa-calendar-alt"></i>
                        Event Management
                    </h4>
                    <hr>
                    <?php include 'admin.php'; ?>
                </div>
            </div>

            <!-- Booking Approvals Card -->
            <div class="col-lg-5">
                <div class="dashboard-card" style="animation-delay: 0.9s;">
                    <h4>
                        <i class="fas fa-clipboard-check"></i>
                        Pending Booking Approvals
                    </h4>
                    <hr>
                    <?php
                    $sql_bookings = "SELECT b.booking_id, u.name AS user_name, e.title AS event_title, b.seats_quantity, b.booking_date
                                     FROM bookings b
                                     JOIN users u ON b.user_id = u.user_id
                                     JOIN events e ON b.event_id = e.event_id
                                     WHERE b.status = 'pending'
                                     ORDER BY b.booking_date ASC
                                     LIMIT 10";

                    $result_bookings = $conn->query($sql_bookings);

                    if ($result_bookings && $result_bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Event</th>
                                        <th>Seats</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= (int)$row['booking_id'] ?></strong></td>
                                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                                            <td><?= htmlspecialchars(substr($row['event_title'], 0, 20)) ?><?= strlen($row['event_title']) > 20 ? '...' : '' ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= (int)$row['seats_quantity'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit.php?id=<?= (int)$row['booking_id'] ?>&action=approve" 
                                                   class="btn btn-sm btn-success me-1"
                                                   title="Approve Booking">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="edit.php?id=<?= (int)$row['booking_id'] ?>&action=cancel" 
                                                   class="btn btn-sm btn-danger"
                                                   title="Cancel Booking"
                                                   onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($stats['pending_bookings'] > 10): ?>
                            <div class="text-center mt-3">
                                <a href="view_bookings.php?status=pending" class="btn btn-primary btn-sm">
                                    View All Pending (<?= $stats['pending_bookings'] ?>)
                                    <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No pending bookings to approve.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide success messages after 5 seconds
    setTimeout(function() {
        const alert = document.querySelector('.alert-success');
        if (alert) {
            alert.style.transition = 'all 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);

    // Add ripple effect to all buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                transform: scale(0);
                animation: ripple-effect 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple-effect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Add stagger animation to table rows
    document.querySelectorAll('.table tbody tr').forEach((row, index) => {
        row.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s both`;
    });

    // Number counter animation for stats
    document.querySelectorAll('.stat-value').forEach(stat => {
        const target = parseInt(stat.textContent.replace(/[^0-9]/g, ''));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                stat.textContent = stat.textContent.includes('$') 
                    ? '$' + target.toLocaleString() 
                    : target.toLocaleString();
                clearInterval(timer);
            } else {
                stat.textContent = stat.textContent.includes('$') 
                    ? '$' + Math.floor(current).toLocaleString() 
                    : Math.floor(current).toLocaleString();
            }
        }, 16);
    });
</script>
</body>
</html>