<?php
session_start(); // MUST be first

// Database connection
include 'db.php';

// If user already logged in, redirect
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: adminfrontend.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password_input = $_POST['password'] ?? '';

    if (empty($email)) {
        $message = "Please enter your email address.";
    } elseif (empty($password_input)) {
        $message = "Please enter your password.";
    } else {

        $sql = "SELECT user_id, password_hash, role FROM users WHERE email = ? LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password_input, $user['password_hash'])) {
                // Check if user is admin
                if ($user['role'] === 'admin') {
                    // Block admin from logging in through user portal
                    $message = "Access denied. Admin accounts must use the Admin Login portal.";
                } else {
                    // Login success for regular users
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: index.php");
                    exit;
                }
            } else {
                $message = "Invalid email or password.";
            }
        } else {
            $message = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In | TICKET AAYO</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #0f0f23;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

/* Animated Background */
.bg-animation {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    overflow: hidden;
    z-index: 0;
}

.bg-gradient {
    position: absolute;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0.1;
}

.particle {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.particle-1 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.15) 0%, transparent 70%);
    top: -100px;
    left: -100px;
    animation: float 20s infinite ease-in-out;
}

.particle-2 {
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(118, 75, 162, 0.15) 0%, transparent 70%);
    bottom: -50px;
    right: -50px;
    animation: float 15s infinite ease-in-out reverse;
}

.particle-3 {
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
    top: 50%;
    left: 50%;
    animation: float 25s infinite ease-in-out;
}

@keyframes float {
    0%, 100% {
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        transform: translate(100px, -100px) rotate(90deg);
    }
    50% {
        transform: translate(-100px, -200px) rotate(180deg);
    }
    75% {
        transform: translate(-200px, 100px) rotate(270deg);
    }
}

/* Main Container */
.login-wrapper {
    display: flex;
    max-width: 1100px;
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border-radius: 30px;
    overflow: hidden;
    box-shadow: 0 50px 100px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 1;
    animation: slideUp 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
    opacity: 0;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Left Panel - Branding */
.branding-panel {
    flex: 1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.branding-panel::before {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    top: -100px;
    right: -100px;
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1) rotate(0deg);
        opacity: 0.3;
    }
    50% {
        transform: scale(1.2) rotate(180deg);
        opacity: 0.5;
    }
}

.brand-logo {
    font-size: 5rem;
    margin-bottom: 30px;
    filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
    animation: bounce 2s ease infinite;
    position: relative;
    z-index: 1;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

.branding-panel h1 {
    color: #fff;
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 20px;
    letter-spacing: -1px;
    text-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.branding-panel p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    line-height: 1.7;
    max-width: 400px;
    position: relative;
    z-index: 1;
}

.features-list {
    list-style: none;
    margin-top: 40px;
    text-align: left;
    position: relative;
    z-index: 1;
}

.features-list li {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 15px;
    padding-left: 30px;
    position: relative;
    font-size: 0.95rem;
}

.features-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #fff;
    font-weight: 900;
    font-size: 1.2rem;
}

/* Right Panel - Form */
.form-panel {
    flex: 1;
    padding: 60px 50px;
    background: rgba(15, 15, 35, 0.95);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-header {
    margin-bottom: 40px;
}

.form-header h2 {
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}

.form-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Error Message */
.message-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-left: 4px solid #ef4444;
    color: #fca5a5;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.message-error i {
    font-size: 1.2rem;
}

/* Form Styling */
.input-group {
    position: relative;
    margin-bottom: 25px;
}

.input-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.4);
    font-size: 1.1rem;
    transition: all 0.3s ease;
    z-index: 1;
}

input {
    width: 100%;
    padding: 16px 18px 16px 50px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}

input::placeholder {
    color: rgba(255, 255, 255, 0.3);
}

input:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

input:focus ~ i {
    color: #667eea;
}

/* Password Toggle */
.password-toggle {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 2;
    font-size: 1.1rem;
}

.password-toggle:hover {
    color: #667eea;
}

/* Button Styling */
.btn-login {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    letter-spacing: 0.5px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
    margin-top: 10px;
}

.btn-login::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.btn-login:hover::before {
    left: 100%;
}

.btn-login:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
}

.btn-login:active {
    transform: translateY(0);
}

/* Links Section */
.form-footer {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.register-link {
    text-align: center;
    font-size: 0.95rem;
    margin-bottom: 20px;
    color: rgba(255, 255, 255, 0.6);
}

.register-link a {
    color: #667eea;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.register-link a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #667eea;
    transition: width 0.3s ease;
}

.register-link a:hover::after {
    width: 100%;
}

.register-link a:hover {
    color: #764ba2;
}

.additional-links {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.link-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.link-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #667eea;
}

.link-item.admin-link {
    background: rgba(255, 204, 51, 0.1);
    border: 1px solid rgba(255, 204, 51, 0.2);
    color: #ffcc33;
}

.link-item.admin-link:hover {
    background: rgba(255, 204, 51, 0.15);
    border-color: rgba(255, 204, 51, 0.4);
}

/* Responsive Design */
@media (max-width: 992px) {
    .login-wrapper {
        flex-direction: column;
        max-width: 500px;
    }

    .branding-panel {
        padding: 40px 30px;
    }

    .brand-logo {
        font-size: 4rem;
    }

    .branding-panel h1 {
        font-size: 2rem;
    }

    .features-list {
        margin-top: 30px;
    }

    .form-panel {
        padding: 40px 30px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .branding-panel {
        padding: 30px 20px;
    }

    .brand-logo {
        font-size: 3rem;
    }

    .branding-panel h1 {
        font-size: 1.75rem;
    }

    .form-panel {
        padding: 30px 20px;
    }

    .form-header h2 {
        font-size: 1.75rem;
    }

    input {
        padding: 14px 14px 14px 44px;
    }

    .btn-login {
        padding: 16px;
        font-size: 1rem;
    }

    .additional-links {
        flex-direction: column;
        align-items: stretch;
    }

    .link-item {
        justify-content: center;
    }
}

/* Loading State */
.btn-login.loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn-login.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin-left: -10px;
    margin-top: -10px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</head>

<body>
    <div class="bg-animation">
        <div class="bg-gradient"></div>
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
    </div>

    <div class="login-wrapper">
        <!-- Left Panel - Branding -->
        <div class="branding-panel">
            <div class="brand-logo">🎟️</div>
            <h1>TICKET AAYO</h1>
            <p>Your gateway to unforgettable experiences. Book tickets to concerts, workshops, and events seamlessly.</p>
            
        </div>

        <!-- Right Panel - Form -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your account</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="input-group">
                    <label class="input-label">Email Address</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="Enter your email" 
                            required
                            autocomplete="email"
                        >
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock"></i>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="form-footer">
                <div class="register-link">
                    Don't have an account? <a href="register.php">Create Account</a>
                </div>
                
                <div class="additional-links">
                    <a href="index.php" class="link-item">
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </a>
                    <a href="admin_login.php" class="link-item admin-link">
                        <i class="fas fa-user-shield"></i>
                        Admin Access
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Form submission loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });

        // Input focus animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>