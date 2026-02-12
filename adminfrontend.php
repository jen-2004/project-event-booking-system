<?php
include 'db.php';
session_start();

// CRITICAL: Access Control - Only Admins can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h3 style='color: red; text-align: center;'>Access denied. You must be logged in as an administrator.</h3>");
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
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
            padding: 30px 0;
        }

        /* Header Section */
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .admin-header h1 {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
            font-size: 2.5rem;
            margin: 0;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: center;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-header h1 i {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 10px 25px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--white);
            margin-top: 15px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Action Bar */
        .action-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 25px;
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
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            padding: 11px 24px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 0.95rem;
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
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn i {
            position: relative;
            z-index: 1;
        }

        .btn span {
            position: relative;
            z-index: 1;
        }

        .btn-outline-secondary {
            border: 2px solid var(--gray);
            color: var(--gray);
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: var(--gray);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(100, 116, 139, 0.3);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        /* Alert Messages */
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: none;
            border-left: 4px solid var(--success);
            border-radius: 12px;
            color: #065f46;
            font-weight: 600;
            padding: 18px 20px;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
            animation: slideInRight 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success::before {
            content: '✓';
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--success);
            color: white;
            border-radius: 50%;
            font-size: 1.2rem;
            font-weight: bold;
            flex-shrink: 0;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card h4 {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-card h4 i {
            font-size: 1.4rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .dashboard-card hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), transparent);
            margin: 20px 0 25px 0;
            opacity: 0.3;
        }

        /* Buttons */
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        /* Table Styling */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--dark) 0%, #2d3748 100%);
        }

        .table thead th {
            font-weight: 700;
            padding: 18px 16px;
            border: none;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--white);
            position: relative;
        }

        .table thead th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            color: var(--dark);
            font-size: 0.95rem;
            font-weight: 500;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table tbody tr {
            transition: all 0.3s ease;
            background: var(--white);
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8fafc;
        }

        .table-striped tbody tr:nth-of-type(odd):hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        /* Action Buttons in Table */
        .table .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .table .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
            padding: 8px 16px;
        }

        .table .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .table .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
        }

        .table .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        /* Alert Info */
        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: none;
            border-left: 4px solid var(--info);
            border-radius: 12px;
            color: #1e40af;
            font-weight: 600;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
        }

        .alert-info i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* Badge Styling */
        .badge {
            font-size: 0.85rem;
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: capitalize;
            letter-spacing: 0.3px;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Container Styling */
        .container {
            max-width: 1400px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.4;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .empty-state p {
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 2rem;
                flex-direction: column;
                gap: 10px;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                width: 100%;
            }

            .action-buttons .btn {
                flex: 1;
                justify-content: center;
            }

            .dashboard-card {
                padding: 25px;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th,
            .table tbody td {
                padding: 12px 10px;
            }
        }

        /* Fade in animation for cards */
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .col-lg-6:nth-child(1) .dashboard-card {
            animation-delay: 0.1s;
        }

        .col-lg-6:nth-child(2) .dashboard-card {
            animation-delay: 0.2s;
        }

        /* Floating particles effect */
        .particle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.1;
            animation: float-particle 15s infinite;
        }

        @keyframes float-particle {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg);
            }
            33% {
                transform: translateY(-100px) translateX(100px) rotate(120deg);
            }
            66% {
                transform: translateY(-50px) translateX(-100px) rotate(240deg);
            }
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            background: var(--primary);
            top: 10%;
            left: 10%;
            animation-duration: 20s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            background: var(--secondary);
            top: 60%;
            right: 10%;
            animation-duration: 25s;
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
    </style>
</head>
<body>

<!-- Floating particles -->
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>

<div class="main-wrapper">
    <div class="container">
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
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="row gy-4">

            <!-- Event Management Card -->
            <div class="col-lg-6">
                <div class="dashboard-card fade-in">
                    <h4>
                        <i class="fas fa-calendar-alt"></i>
                        Event Management
                    </h4>
                    <hr>
                    <p>
                        <a href="add_event.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Add New Event
                        </a>
                    </p>
                    <?php include 'admin.php'; ?>
                </div>
            </div>

            <!-- Booking Approvals Card -->
            <div class="col-lg-6">
                <div class="dashboard-card fade-in">
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
                                     ORDER BY b.booking_date ASC";

                    $result_bookings = $conn->query($sql_bookings);

                    if ($result_bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Event</th>
                                        <th>Seats</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= (int)$row['booking_id'] ?></strong></td>
                                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                                            <td><?= htmlspecialchars($row['event_title']) ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= (int)$row['seats_quantity'] ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($row['booking_date'])) ?></td>
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
            alert.style.transform = 'translateX(100px)';
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

    // Confirm before canceling booking
    document.querySelectorAll('a[href*="action=cancel"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Add stagger animation to table rows
    document.querySelectorAll('.table tbody tr').forEach((row, index) => {
        row.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s both`;
    });
</script>
</body>
</html>