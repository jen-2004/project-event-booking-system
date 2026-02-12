<?php
session_start(); // MUST be first

// Database connection
include 'db.php';

// If admin already logged in, redirect
if (isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: adminfrontend.php");
    exit;
}

$message = '';

// Rate limiting (basic implementation)
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt_time'] = time();
}

// Reset attempts after 15 minutes
if (time() - $_SESSION['admin_last_attempt_time'] > 900) {
    $_SESSION['admin_login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    
    // Check rate limiting
    if ($_SESSION['admin_login_attempts'] >= 5) {
        $wait_time = 900 - (time() - $_SESSION['admin_last_attempt_time']);
        $message = "Too many failed attempts. Please try again in " . ceil($wait_time / 60) . " minutes.";
    } else {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password_input = $_POST['password'] ?? '';

        if (empty($email)) {
            $message = "Please enter your email address.";
        } elseif (empty($password_input)) {
            $message = "Please enter your password.";
        } else {
            $sql = "SELECT user_id, password_hash, role, name FROM users WHERE email = ? LIMIT 1";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && password_verify($password_input, $user['password_hash'])) {
                    if ($user['role'] === 'admin') {
                        // Reset attempts on successful login
                        $_SESSION['admin_login_attempts'] = 0;
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Login success for admin
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['role'] = 'admin';
                        $_SESSION['login_time'] = time();
                        
                        header("Location: adminfrontend.php");
                        exit;
                    } else {
                        // User account trying to access admin portal
                        $message = "Access denied. This portal is for administrators only.";
                        $_SESSION['admin_login_attempts']++;
                        $_SESSION['admin_last_attempt_time'] = time();
                    }
                } else {
                    $message = "Invalid email or password.";
                    $_SESSION['admin_login_attempts']++;
                    $_SESSION['admin_last_attempt_time'] = time();
                }
            } else {
                $message = "Database error. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | TICKET AAYO</title>

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

/* Animated Background - Admin Theme (Red/Orange) */
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
    background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
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
    background: radial-gradient(circle, rgba(220, 38, 38, 0.15) 0%, transparent 70%);
    top: -100px;
    left: -100px;
    animation: float 20s infinite ease-in-out;
}

.particle-2 {
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(234, 88, 12, 0.15) 0%, transparent 70%);
    bottom: -50px;
    right: -50px;
    animation: float 15s infinite ease-in-out reverse;
}

.particle-3 {
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(220, 38, 38, 0.1) 0%, transparent 70%);
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

/* Left Panel - Admin Branding */
.branding-panel {
    flex: 1;
    background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
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

/* Security Badge */
.security-badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 20px;
    margin-top: 40px;
    position: relative;
    z-index: 1;
}

.security-badge i {
    font-size: 2rem;
    color: #fff;
    margin-bottom: 10px;
    display: block;
}

.security-badge p {
    font-size: 0.9rem;
    margin: 0;
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

.admin-badge-header {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
}

.admin-badge-header i {
    font-size: 2rem;
    color: #fff;
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
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.95rem;
    font-weight: 500;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.message-error i {
    font-size: 1.2rem;
}

/* Attempts Indicator */
.attempts-indicator {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-left: 4px solid #f59e0b;
    color: #fbbf24;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    font-weight: 600;
}

/* Form Elements */
.input-group {
    margin-bottom: 24px;
}

.input-label {
    display: block;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 0.95rem;
}

.input-wrapper {
    position: relative;
}

.input-wrapper input {
    width: 100%;
    padding: 16px 20px 16px 50px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
}

.input-wrapper input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.input-wrapper input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
}

.input-wrapper i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.4);
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.input-wrapper input:focus ~ i {
    color: #dc2626;
}

.password-toggle {
    left: auto !important;
    right: 18px !important;
    cursor: pointer;
    padding: 8px;
}

.password-toggle:hover {
    color: #dc2626 !important;
}

.input-wrapper.focused {
    animation: inputFocus 0.3s ease;
}

@keyframes inputFocus {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Login Button */
.btn-login {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
    border: none;
    border-radius: 14px;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
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
    background: linear-gradient(135deg, #b91c1c 0%, #c2410c 100%);
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(220, 38, 38, 0.4);
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

.security-notice {
    background: rgba(59, 130, 246, 0.1);
    border-left: 4px solid #3b82f6;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.9rem;
    color: #93c5fd;
    font-weight: 500;
}

.security-notice i {
    font-size: 1.1rem;
    color: #3b82f6;
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
    color: #dc2626;
}

.link-item.user-link {
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.2);
    color: #818cf8;
}

.link-item.user-link:hover {
    background: rgba(102, 126, 234, 0.15);
    border-color: rgba(102, 126, 234, 0.4);
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

    .input-wrapper input {
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
        <!-- Left Panel - Admin Branding -->
        <div class="branding-panel">
            <div class="brand-logo">🛡️</div>
            <h1>ADMIN PORTAL</h1>
            <p>Secure administrative access to manage TICKET AAYO platform, users, and events.</p>
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <p>Secured with industry-standard encryption and multi-layer authentication</p>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="form-panel">
            <div class="form-header">
                <div class="admin-badge-header">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2>Admin Access</h2>
                <p>Enter your credentials to continue</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['admin_login_attempts']) && $_SESSION['admin_login_attempts'] > 0 && $_SESSION['admin_login_attempts'] < 5): ?>
                <div class="attempts-indicator">
                    <i class="fas fa-info-circle"></i>
                    <span>Login attempts: <?= $_SESSION['admin_login_attempts'] ?>/5</span>
                </div>
            <?php endif; ?>

            <form method="POST" id="adminLoginForm">
                <div class="input-group">
                    <label class="input-label">Admin Email Address</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="   " 
                            required
                            autocomplete="email"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>"
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
                            placeholder="Enter your admin password" 
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock"></i>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" name="admin_login" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> <span id="btnText">Access Dashboard</span>
                </button>
            </form>

            <div class="form-footer">
                <div class="security-notice">
                    <i class="fas fa-shield-alt"></i>
                    <span>This is a restricted area. All access attempts are logged and monitored.</span>
                </div>
                
                <div class="additional-links">
                    <a href="index.php" class="link-item">
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </a>
                    <a href="login.php" class="link-item user-link">
                        <i class="fas fa-user"></i>
                        User Portal
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
        const loginForm = document.getElementById('adminLoginForm');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btnText');

        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            btnText.innerHTML = 'Authenticating...';
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

        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="email"]').focus();
        });
    </script>
</body>
</html>