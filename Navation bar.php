<?php
// Note: Session must be started in the file that includes this navbar.
$is_logged_in = isset($_SESSION['user_id']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-ticket-alt me-2"></i> EventBooker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                </li>
                <?php if ($is_logged_in): ?>
                    li class="nav-item">
                        <a class="nav-link" href="event.php"><i class="fas fa-receipt me-1"></i> Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php"><i class="fas fa-receipt me-1"></i> My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-lg-2" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout (<?= e($_SESSION['user_name']) ?>)</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary btn-sm ms-lg-2" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>