<?php
include 'db.php';
session_start();

/* 🔐 ADMIN ACCESS ONLY */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h3 style='color:red;text-align:center;'>Access Denied</h3>");
}

/* Fetch all bookings with user & event details */
$sql = "
SELECT 
    b.booking_id,
    u.name AS user_name,
    u.email,
    e.title AS event_title,
    b.seats_quantity,
    b.total_price,
    b.status,
    b.booking_date
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN events e ON b.event_id = e.event_id
ORDER BY b.booking_date DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Bookings | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">🎟️ All Ticket Bookings</h2>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Event</th>
                        <th>Seats</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Booked On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['booking_id'] ?></td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['event_title']) ?></td>
                            <td><?= $row['seats_quantity'] ?></td>
                            <td>Rs. <?= number_format($row['total_price'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $row['status'] === 'approved' ? 'success' : 
                                    ($row['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y h:i A', strtotime($row['booking_date'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
<?php else: ?>
        <div class="alert alert-info">No bookings found.</div>
    <?php endif; ?>

    <a href="Adminfrontend.php" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> Back to admin panel
    </a>
</div>
</body>
</html>
