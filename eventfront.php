<?php
// This file assumes $conn, $result, and e() are set in event.php
if (!isset($result) || $result === false) {
    die("Error: Event data not available.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎟️ TICKET AAYO - Upcoming Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* --- General Styling --- */
        html {
            height: 100%;
        }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        
        /* Main content wrapper */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* --- Navbar Styling --- */
        .navbar { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            padding: 1.2rem 0;
        }
        .navbar-brand { 
            color: #fff !important; 
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
            transition: transform .3s ease;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        /* --- Page Header --- */
        .page-header {
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.95) 0%, rgba(42, 82, 152, 0.9) 100%);
            padding: 60px 0;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, .08) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }
        .page-header h2 {
            color: #fff;
            font-weight: 800;
            font-size: 3rem;
            letter-spacing: -1px;
            margin: 0;
            text-shadow: 0 4px 20px rgba(0, 0, 0, .3);
            position: relative;
            z-index: 1;
        }
        .page-header p {
            color: rgba(255, 255, 255, .9);
            font-size: 1.1rem;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }
        
        /* --- Event Card Styling --- */
        .card { 
            border: none;
            background: #fff;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform .4s ease;
        }
        .card:hover { 
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
        }
        .card:hover::before {
            transform: scaleX(1);
        }

        /* --- NEW: Event Image Styling --- */
        .event-image-wrapper {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        }
        .event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .card:hover .event-image {
            transform: scale(1.1);
        }
        .event-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 4rem;
        }
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
            padding: 15px;
            color: white;
        }
        .image-overlay .price-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.95);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .card-body {
            padding: 30px;
            display: flex;
            flex-direction: column;
        }
        .card-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }
        .card-text {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* --- Event Details Section --- */
        .event-details {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .event-details p {
            margin-bottom: 0;
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.8;
        }
        .event-detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .event-detail-row:last-child {
            margin-bottom: 0;
        }
        .event-detail-row i {
            width: 24px;
            color: #667eea;
            font-size: 1rem;
            margin-right: 10px;
        }
        .event-details b {
            font-weight: 700;
            color: #1e293b;
            margin-right: 8px;
        }
        
        /* --- Button Styling --- */
        .btn-book { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; 
            border: none; 
            font-size: 1.1rem;
            font-weight: 700;
            padding: 14px 0;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-book:hover::before {
            left: 100%;
        }
        .btn-book:hover { 
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
            color: #fff;
        }
        .btn-book i {
            margin-right: 8px;
        }

        /* --- Alert Styling --- */
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: none;
            border-left: 5px solid #dc2626;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2);
            color: #7f1d1d;
            padding: 20px;
        }
        .alert-danger strong {
            color: #991b1b;
        }

        /* --- Empty State --- */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        .empty-state p {
            color: #64748b;
            font-size: 1.2rem;
            margin: 0;
        }

        /* --- Footer Styling --- */
        .footer { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #cbd5e1;
            padding: 30px 0;
            margin-top: auto;
            position: relative;
            overflow: hidden;
        }
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ffcc33, transparent);
        }
        .footer p {
            margin: 0;
            font-size: 0.95rem;
        }
        
        /* --- Responsive Design --- */
        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 2.2rem;
            }
            .card-body {
                padding: 20px;
            }
            .btn-book {
                font-size: 1rem;
                padding: 12px 0;
            }
            .event-image-wrapper {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">🎟️ TICKET AAYO</a>
        </div>
    </nav>
    
    <div class="main-content">
        <?php
        // Check for and display booking error message
        if (isset($_SESSION['booking_error'])):
            $error_message = $_SESSION['booking_error'];
            unset($_SESSION['booking_error']); // Clear the session variable after display
        ?>
            <div class="container my-4">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle"></i> Booking Failed:</strong> <?= e($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="page-header text-center">
            <div class="container">
                <h2>🎉 Upcoming Events</h2>
                <p>Discover amazing experiences and book your tickets now</p>
            </div>
        </div>

        <div class="container" style="padding-bottom: 50px;">
            <div class="row g-4">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <!-- Event Image Section -->
                                <div class="event-image-wrapper">
                                    <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                        <img src="<?= e($row['image_path']) ?>" alt="<?= e($row['title']) ?>" class="event-image">
                                    <?php else: ?>
                                        <div class="event-image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="image-overlay">
                                        <span class="price-badge">Rs. <?= number_format($row['price'], 2) ?></span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <h4 class="card-title"><?= e($row['title']) ?></h4>
                                    <p class="card-text"><?= e($row['description']) ?></p>
                                    
                                    <div class="event-details">
                                        <div class="event-detail-row">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><b>Date:</b> <?= date('M d, Y', strtotime($row['date'])) ?></span>
                                        </div>
                                        <div class="event-detail-row">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><b>Location:</b> <?= e($row['location']) ?></span>
                                        </div>
                                        <div class="event-detail-row">
                                            <i class="fas fa-chair"></i>
                                            <span><b>Available:</b> <?= e($row['total_seats']) ?> seats</span>
                                        </div>
                                    </div>
                                    
                                    <a href="eventbookingprocessor.php?event_id=<?= (int)$row['event_id'] ?>&action=book" class="btn btn-book w-100">
                                        <i class="fas fa-ticket-alt"></i> Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p class="lead">No upcoming events currently scheduled</p>
                            <p class="text-muted mt-2">Check back soon for exciting new events!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <p>&copy; <?= date('Y') ?> TICKET AAYO — All rights reserved</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>