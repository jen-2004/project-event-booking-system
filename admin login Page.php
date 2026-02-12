<?php
include 'db.php';
session_start();

// Check if user is already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: adminfrontend.php");
    exit;
}

$message = '';

if (isset($_POST['login'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password_input = $_POST['password'];

    // 1. Prepare statement to fetch user details by email
    $sql = "SELECT user_id, password, role FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // 2. Verify password AND check role
        if (password_verify($password_input, $user['password']) && $user['role'] === 'admin') {
            // Success: Admin Login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: adminfrontend.php");
            exit;
        } elseif (password_verify($password_input, $user['password']) && $user['role'] === 'user') {
             $message = "Access denied. Your account is for general users, not administrators.";
        } else {
            // Password verification failed
            $message = "Invalid email or password.";
        }
    } else {
        // User not found
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        /* Reusing improved CSS styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #e6e9f0; /* Slightly cooler background for admin */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border-top: 5px solid #dc3545; /* Admin accent color (Red) */
        }

        h2 {
            text-align: center;
            color: #dc3545; /* Red title */
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #dc3545; /* Highlight focus with admin color */
            outline: none;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.2);
        }

        button[name="login"] {
            background-color: #dc3545; /* Admin button color */
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        button[name="login"]:hover {
            background-color: #c82333;
        }

        .message-error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .user-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
        }

        .user-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        .user-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login 🛠️</h2>
        
        <?php if ($message): ?>
            <p class="message-error"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Admin Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button name="login">Log in as Admin</button>
        </form>
        
        <p class="user-link">Go to User Portal: <a href="login.php">User Login</a>.</p>
    </div>
</body>
</html>