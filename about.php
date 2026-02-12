<?php 
session_start();
function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | TICKET AAYO</title>

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
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Navbar */
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
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: #e0e7ff !important;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.1);
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

        /* About Header */
        .about-header {
            padding: 100px 20px;
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.95) 0%, rgba(42, 82, 152, 0.9) 100%),
                        url('https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1600&q=80') center/cover;
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .about-header::before {
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
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.3; }
            50% { transform: scale(1.1) rotate(180deg); opacity: 0.5; }
        }

        .about-header h1 {
            font-weight: 800;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
            letter-spacing: -1px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, .3);
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .about-header .lead {
            font-size: 1.3rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .2);
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        /* About Section */
        .about-section {
            padding: 80px 0;
            background: #fff;
        }

        .section-title {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 25px;
            letter-spacing: -0.5px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .about-section p {
            color: #475569;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .about-section .fs-5 {
            color: #1e293b !important;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Feature Cards */
        .feature-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 5px solid #667eea;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .feature-card:hover {
            transform: translateX(10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            border-left-width: 8px;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            margin-right: 20px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-card h5 {
            color: #1e3c72;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #64748b;
            margin-bottom: 0;
            line-height: 1.7;
        }

        /* Tech Stack Section */
        .tech-section {
            padding: 80px 0;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .tech-card {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            border-top: 4px solid #667eea;
        }

        .tech-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.2);
        }

        .tech-card i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .tech-card h5 {
            color: #1e3c72;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .tech-card p {
            color: #64748b;
            font-size: 0.95rem;
            margin: 0;
        }

        /* Mission Box */
        .mission-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px;
            border-radius: 20px;
            margin: 40px 0;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .mission-box::before {
            content: '🎯';
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 6rem;
            opacity: 0.1;
        }

        .mission-box h3 {
            font-weight: 800;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .mission-box p {
            font-size: 1.1rem;
            line-height: 1.7;
            margin: 0;
            opacity: 0.95;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #cbd5e1;
            margin-top: auto;
            padding: 30px 0;
            position: relative;
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

        /* Responsive */
        @media (max-width: 768px) {
            .about-header h1 {
                font-size: 2.5rem;
            }

            .about-header .lead {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .feature-card {
                padding: 20px;
            }

            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
                margin-right: 15px;
            }

            .mission-box {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">🎟️ TICKET AAYO</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item"><a class="nav-link active" href="about.php">About Us</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="event.php">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="bookings.php">My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link text-info" href="admin_login.php">Admin Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<header class="about-header">
    <div class="container">
        <h1 class="display-4 fw-bold">Our Project: TICKET AAYO</h1>
        <p class="lead">Building the next generation of seamless event booking</p>
    </div>
</header>

<section class="about-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <h2 class="section-title">Mission and Vision</h2>
                <p class="fs-5 text-muted">TICKET AAYO was created to solve the common pain points in event ticketing: overbooking, fraudulent sales, and slow processing.</p>
                
                <div class="mission-box">
                    <h3>Our Mission</h3>
                    <p>To provide a <strong>secure, reliable, and user-friendly platform</strong> for both event organizers and attendees. We leverage modern database transactions and concurrency control to guarantee that every booking is unique and every ticket count is accurate.</p>
                </div>

                <h2 class="section-title mt-5">Core Features Solved</h2>
                
                <div class="feature-card d-flex align-items-start">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h5>Duplicate Booking Prevention</h5>
                        <p>Using database <strong>UNIQUE constraints</strong> and application-level checks to ensure a user can only book a single ticket (or set of tickets) per unique event ID. This prevents accidental double bookings and maintains data integrity.</p>
                    </div>
                </div>

                <div class="feature-card d-flex align-items-start">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h5>Concurrency Control & Anti-Overbooking</h5>
                        <p>The booking process is wrapped in a <strong>database transaction</strong> with <strong>row-level locking (SELECT FOR UPDATE)</strong>. This prevents multiple users from booking the last ticket simultaneously, eliminating race conditions entirely.</p>
                    </div>
                </div>

                <div class="feature-card d-flex align-items-start">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <h5>Atomic Integrity</h5>
                        <p>The booking and inventory update steps are treated as a single, atomic unit. If either step fails, the entire transaction is rolled back, guaranteeing data consistency and preventing partial bookings.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<footer class="footer text-white text-center">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y') ?> TICKET AAYO — All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>