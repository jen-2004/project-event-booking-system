<?php
include 'db.php';

// Initialize variables for displaying status and errors
$message = '';
$message_class = '';
$errors = [];
$name = $email = ''; 

if (isset($_POST['register'])) {
    // 1. Basic Sanitization & Retrieval
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    
    $minLength = 6;
    $name_regex = "/^[a-zA-Z\s'-]+$/";

    // --- SERVER-SIDE VALIDATION ---
    
    // Validate Name
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Full Name cannot exceed 100 characters.";
    } elseif (!preg_match($name_regex, $name)) {
        $errors[] = "Full Name can only contain letters, spaces, hyphens, and apostrophes.";
    }

    // Validate Email Format
    if (empty($email)) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate Password Length
    if (empty($password_input)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password_input) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long.";
    }

    // 2. Database Logic
    if (empty($errors)) {
        $email_sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        $password_hashed = password_hash($password_input, PASSWORD_DEFAULT);
        $role = 'customer'; 

        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $message = "Error preparing statement: " . $conn->error;
            $message_class = 'message-error';
        } else {
            $stmt->bind_param("ssss", $name, $email_sanitized, $password_hashed, $role);
            
            try {
                if ($stmt->execute()) {
                    $message = "Registration Successful! You can now login.";
                    $message_class = 'message-success';
                    $name = $email = ''; 
                } else {
                    $message = "Error: Registration failed. " . $stmt->error;
                    $message_class = 'message-error';
                }
            } catch (mysqli_sql_exception $e) {
                if ($conn->errno == 1062 || strpos($e->getMessage(), 'Duplicate entry') !== false) { 
                    $message = "This email is already registered. Please use a different email or login.";
                } else {
                    $message = "Registration failed. Please try again.";
                }
                $message_class = 'message-error';
            }
            
            $stmt->close();
        }
    } else {
        $message = implode('<br>', $errors);
        $message_class = 'message-error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | TICKET AAYO</title>

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
.register-wrapper {
    display: flex;
    max-width: 950px;
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border-radius: 24px;
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
    padding: 50px 40px;
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
    font-size: 4rem;
    margin-bottom: 20px;
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
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 15px;
    letter-spacing: -1px;
    text-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.branding-panel p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
    line-height: 1.6;
    max-width: 350px;
    position: relative;
    z-index: 1;
}

.features-list {
    list-style: none;
    margin-top: 30px;
    text-align: left;
    position: relative;
    z-index: 1;
}

.features-list li {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 12px;
    padding-left: 30px;
    position: relative;
    font-size: 0.9rem;
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
    padding: 50px 40px;
    background: rgba(15, 15, 35, 0.95);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-header {
    margin-bottom: 30px;
}

.form-header h2 {
    color: #fff;
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.form-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

/* Messages */
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

.message-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-left: 4px solid #10b981;
    color: #6ee7b7;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-error i,
.message-success i {
    font-size: 1.2rem;
}

/* Form Styling */
.input-group {
    position: relative;
    margin-bottom: 20px;
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
    padding: 14px 16px 14px 48px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.95rem;
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
.btn-register {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    letter-spacing: 0.5px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
    margin-top: 8px;
}

.btn-register::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.btn-register:hover::before {
    left: 100%;
}

.btn-register:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
}

.btn-register:active {
    transform: translateY(0);
}

/* Links Section */
.form-footer {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.login-link {
    text-align: center;
    font-size: 0.95rem;
    margin-bottom: 20px;
    color: rgba(255, 255, 255, 0.6);
}

.login-link a {
    color: #667eea;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.login-link a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #667eea;
    transition: width 0.3s ease;
}

.login-link a:hover::after {
    width: 100%;
}

.login-link a:hover {
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
    .register-wrapper {
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

    .btn-register {
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
.btn-register.loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn-register.loading::after {
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

    <div class="register-wrapper">
        <!-- Left Panel - Branding -->
        <div class="branding-panel">
            <div class="brand-logo">🎟️</div>
            <h1>TICKET AAYO</h1>
            <p>Join thousands of event enthusiasts. Create your account and start booking amazing experiences today!</p>
            
        </div>

        <!-- Right Panel - Form -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Join TICKET AAYO today</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="<?= $message_class ?>">
                    <i class="fas <?= $message_class === 'message-success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="input-group">
                    <label class="input-label">Full Name</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="name" 
                            placeholder="Enter your full name" 
                            required
                            value="<?= htmlspecialchars($name) ?>"
                            maxlength="100"
                        >
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Email Address</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="Enter your email" 
                            required
                            value="<?= htmlspecialchars($email) ?>"
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
                            placeholder="Create a strong password" 
                            required
                            minlength="6"
                        >
                        <i class="fas fa-lock"></i>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn-register" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="form-footer">
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign In</a>
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
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');

        registerForm.addEventListener('submit', function() {
            registerBtn.classList.add('loading');
            registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
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

        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successMsg = document.querySelector('.message-success');
            if (successMsg) {
                successMsg.style.transition = 'all 0.5s ease';
                successMsg.style.opacity = '0';
                successMsg.style.transform = 'translateY(-20px)';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>