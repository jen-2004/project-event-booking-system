<?php 
session_start();

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TICKET AAYO | Discover & Book Events</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
body{
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
}

/* MAIN WRAPPER */
.app-container{
    max-width: 1200px;
    margin: 40px auto;
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 50px 100px rgba(0, 0, 0, .25);
}

/* NAVBAR */
.navbar{
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    backdrop-filter: blur(10px);
    padding: 1rem 0;
    box-shadow: 0 4px 30px rgba(0, 0, 0, .1);
}
.navbar-brand{
    font-weight: 700;
    font-size: 1.4rem;
    letter-spacing: -0.5px;
    transition: transform .3s ease;
}
.navbar-brand:hover{
    transform: scale(1.05);
}
.nav-link{
    color: #e0e7ff !important;
    font-size: .95rem;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    border-radius: 8px;
    transition: all .3s ease;
    position: relative;
}
.nav-link:hover{
    color: #fff !important;
    background: rgba(255, 255, 255, .1);
}
.nav-link::after{
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: #ffcc33;
    transition: all .3s ease;
    transform: translateX(-50%);
}
.nav-link:hover::after{
    width: 70%;
}

/* HERO */
.hero{
    position: relative;
    padding: 120px 40px;
    color: #fff;
    text-align: center;
    background:
      linear-gradient(135deg, rgba(30, 60, 114, 0.9) 0%, rgba(42, 82, 152, 0.85) 100%),
      url('https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=1600&q=80')
      center/cover no-repeat;
    overflow: hidden;
}
.hero::before{
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, .1) 0%, transparent 70%);
    animation: pulse 15s ease-in-out infinite;
}
@keyframes pulse{
    0%, 100% { transform: scale(1) rotate(0deg); opacity: .3; }
    50% { transform: scale(1.1) rotate(180deg); opacity: .5; }
}
.hero h1{
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
    letter-spacing: -1px;
    text-shadow: 0 4px 20px rgba(0, 0, 0, .3);
    animation: fadeInUp .8s ease;
}
@keyframes fadeInUp{
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.hero p{
    font-size: 1.25rem;
    margin-top: 10px;
    opacity: .95;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 10px rgba(0, 0, 0, .2);
    animation: fadeInUp .8s ease .2s both;
}
.hero .btn{
    border-radius: 50px;
    padding: 14px 32px;
    font-weight: 600;
    font-size: 1rem;
    position: relative;
    z-index: 1;
    transition: all .3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
    animation: fadeInUp .8s ease .4s both;
}
.btn-outline-light{
    background: #fff;
    color: #1e3c72;
    border: none;
}
.btn-outline-light:hover{
    background: #f1f5f9;
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, .3);
}
.btn-warning{
    background: linear-gradient(135deg, #ffcc33 0%, #ffb703 100%);
    border: none;
    color: #1e3c72;
}
.btn-warning:hover{
    background: linear-gradient(135deg, #ffb703 0%, #ff9500 100%);
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(255, 183, 3, .4);
}

/* WHY US */
.why{
    padding: 80px 40px 100px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}
.why h2{
    font-weight: 800;
    color: #1e3c72;
    font-size: 2.5rem;
    letter-spacing: -1px;
    margin-bottom: 3rem;
    position: relative;
    display: inline-block;
}
.why h2::after{
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #ffcc33, #ff9500);
    border-radius: 2px;
}
.feature-card{
    background: #fff;
    border-radius: 20px;
    padding: 45px 30px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, .08);
    transition: all .4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}
.feature-card::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transform: scaleX(0);
    transition: transform .4s ease;
}
.feature-card:hover{
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 20px 60px rgba(102, 126, 234, .25);
    border-color: rgba(102, 126, 234, .2);
}
.feature-card:hover::before{
    transform: scaleX(1);
}
.feature-card i{
    font-size: 2.8rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
    display: inline-block;
    transition: transform .3s ease;
}
.feature-card:hover i{
    transform: scale(1.15) rotateY(360deg);
}
.feature-card h5{
    font-weight: 700;
    margin-bottom: 12px;
    color: #1e3c72;
    font-size: 1.25rem;
}
.feature-card p{
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

/* FOOTER */
.footer{
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #cbd5e1;
    padding: 30px 20px;
    text-align: center;
    font-size: .95rem;
    position: relative;
    overflow: hidden;
}
.footer::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #ffcc33, transparent);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .hero h1{
        font-size: 2.5rem;
    }
    .hero p{
        font-size: 1.1rem;
    }
    .why h2{
        font-size: 2rem;
    }
    .app-container{
        margin: 20px;
        border-radius: 16px;
    }
}
</style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark px-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                🎟️ TICKET AAYO
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-2">
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>

                    <?php if ($is_logged_in): ?>
                        <li class="nav-item"><a class="nav-link" href="event.php"> Events</a></li>
                        <li class="nav-item"><a class="nav-link" href="bookings.php"> My Bookings</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"> Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"> Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php"> Register</a></li>
                        <li class="nav-item"><a class="nav-link text-info" href="admin_login.php"> Admin Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>


    <section class="hero">
        <h1>Discover. Book. Enjoy</h1>
        <p>Your gateway to concerts, workshops, and unforgettable events.</p>

        <div class="mt-4">
            <a href="<?= $is_logged_in ? 'event.php' : 'login.php' ?>" class="btn btn-outline-light me-3">
                <i class="fa-regular fa-calendar-check"></i> Browse Events
            </a>
            <?php if (!$is_logged_in): ?>
                <a href="register.php" class="btn btn-warning">
                    <i class="fa-solid fa-user-plus"></i> Get Started
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="why text-center">
        <h2 class="mb-5">Why Choose Us?</h2>

        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fa-solid fa-bolt"></i>
                        <h5>Fast Booking</h5>
                        <p>Book tickets instantly with smooth and secure processing.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fa-solid fa-calendar-check"></i>
                        <h5>Verified Events</h5>
                        <p>All events are reviewed to ensure quality and safety.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fa-solid fa-ticket"></i>
                        <h5>Digital Tickets</h5>
                        <p>Instant digital tickets — no paper required.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="footer">
        © 2024 TICKET AAYO — All rights reserved
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>