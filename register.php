<?php
include 'db.php'; // Ensure db.php is in the same directory and contains the $conn object.

// Initialize variables for displaying status and errors
$message = '';
$message_class = '';
$errors = [];
// Initialize input variables to avoid PHP warnings on first load
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
        $role = 'user'; 

        $sql = "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $message = "Error preparing statement: " . $conn->error;
            $message_class = 'message-error';
        } else {
            $stmt->bind_param("ssss", $name, $email_sanitized, $password_hashed, $role);
            
            // FIX: Wrap execute() in try-catch to handle duplicate entry exception
            try {
                if ($stmt->execute()) {
                    $message = "Registration Successful! You can now login.";
                    $message_class = 'message-success';
                    // Clear inputs on success
                    $name = $email = ''; 
                } else {
                    $message = "Error: Registration failed. " . $stmt->error;
                    $message_class = 'message-error';
                }
            } catch (mysqli_sql_exception $e) {
                // Handle duplicate email error specifically
                if ($conn->errno == 1062 || strpos($e->getMessage(), 'Duplicate entry') !== false) { 
                    $message = "Error: This email is already registered.";
                } else {
                    $message = "Error: Registration failed. " . $e->getMessage();
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; position: relative; overflow-x: hidden; }
        .registration-container { background: #fff; padding: 50px 40px; border-radius: 24px; box-shadow: 0 30px 80px rgba(0, 0, 0, .25); width: 100%; max-width: 480px; position: relative; z-index: 1; animation: slideUp 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards; opacity: 0; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(50px); } to { opacity: 1; transform: translateY(0); } }
        .registration-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 24px 24px 0 0; }
        .register-header { text-align: center; margin-bottom: 40px; }
        .brand-logo { font-size: 3rem; margin-bottom: 10px; }
        h2 { color: #1e3c72; font-weight: 800; font-size: 2rem; margin-bottom: 8px; }
        .subtitle { color: #64748b; font-size: 0.95rem; }
        form { display: flex; flex-direction: column; gap: 20px; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 1; }
        input { width: 100%; padding: 16px 16px 16px 48px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc; transition: all 0.3s ease; }
        input:focus { outline: none; border-color: #667eea; background: #fff; box-shadow: 0 0 0 4px rgba(102, 126, 234, .1); }
        button[name="register"] { width: 100%; padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; border-radius: 12px; font-size: 1.05rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; box-shadow: 0 10px 30px rgba(102, 126, 234, .3); }
        button[name="register"]:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(102, 126, 234, .4); }
        .message { text-align: center; padding: 16px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; font-size: 0.95rem; }
        .message-success { color: #065f46; background: #d1fae5; border: 2px solid #10b981; }
        .message-error { color: #991b1b; background: #fee2e2; border: 2px solid #ef4444; text-align: left; }
        .links-section { margin-top: 30px; padding-top: 25px; border-top: 2px solid #f1f5f9; text-align: center; }
        .login-link a { color: #667eea; font-weight: 700; text-decoration: none; }
        .back-home a { color: #64748b; text-decoration: none; font-size: 0.9rem; margin-top: 10px; display: inline-block; }
        .password-hint { font-size: 0.8rem; color: #64748b; margin-top: -10px; padding-left: 10px; }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="register-header">
            <div class="brand-logo">🎟️</div>
            <h2>Create Account</h2>
            <p class="subtitle">Join TICKET AAYO today</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_class ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="input-group">
                <input type="text" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($name) ?>">
                <i class="fas fa-user"></i>
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required value="<?= htmlspecialchars($email) ?>">
                <i class="fas fa-envelope"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required minlength="6">
                <i class="fas fa-lock"></i>
            </div>
            <p class="password-hint">
                <i class="fas fa-info-circle"></i> Minimum 6 characters required
            </p>

            <button type="submit" name="register">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="links-section">
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
            <div class="back-home">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>