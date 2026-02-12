<?php
include 'auth_check.php'; // ADMIN PROTECTION
// Include the database connection file
include 'db.php'; 

// --- Security Helper Function ---
// Function to safely display data (prevents XSS)
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Check for database connection error
if (!isset($conn) || $conn->connect_error) {
    // If the connection failed, log the error and display a generic message
    error_log("Database connection failed in add_event.php");
    die("A required service is unavailable. Please check the database connection.");
}

// Variables to store feedback messages
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    
    // 1. Sanitize and fetch input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = trim($_POST['date']);
    $location = trim($_POST['location']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT); 
    $total_seats = filter_var($_POST['total_seats'], FILTER_VALIDATE_INT);
    
    // 2. Handle image upload
    $image_path = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/events/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_tmp = $_FILES['event_image']['tmp_name'];
        $file_name = $_FILES['event_image']['name'];
        $file_size = $_FILES['event_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Validate file
        if (in_array($file_ext, $allowed_extensions) && $file_size <= 5000000) { // 5MB max
            // Generate unique filename
            $new_filename = uniqid('event_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = $destination;
            } else {
                $message = "Failed to upload image.";
                $message_type = 'danger';
            }
        } else {
            $message = "Invalid file type or file too large. Max 5MB, allowed: JPG, PNG, GIF, WEBP";
            $message_type = 'warning';
        }
    }
    
    // 3. Simple Validation
    if (empty($message) && $title && $description && $date && $location && $price !== false && $total_seats !== false) {
        
        // 4. Secure: Use Prepared Statement
        $sql = "INSERT INTO events (title, description, date, location, price, total_seats, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // 'ssssdis' means string, string, string, string, decimal, integer, string
        $stmt->bind_param("ssssdis", $title, $description, $date, $location, $price, $total_seats, $image_path);

        if ($stmt->execute()) {
            $message = "Event added successfully!";
            $message_type = 'success';
        } else {
            $message = "Error: " . e($conn->error);
            $message_type = 'danger';
        }

        $stmt->close();
    } elseif (empty($message)) {
        $message = "All fields are required and numeric fields must be valid numbers.";
        $message_type = 'warning';
    }
}?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event | TICKET AAYO Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6013ad;
            --primary-dark: #a855f7;
            --secondary: #e9d5ff;
            --success: #096e4d;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #c4b5fd;
            --dark: #2e1065;
            --light: #faf5ff;
            --gray: #9333ea;
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(192, 132, 252, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(233, 213, 255, 0.2) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .main-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Navbar */
        .navbar {
            background: rgba(75, 9, 141, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(192, 132, 252, 0.15);
            padding: 1rem 0;
            border-bottom: 2px solid rgba(192, 132, 252, 0.2);
        }

        .navbar-brand {
            color: var(--primary-dark) !important;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand i {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: var(--primary-dark) !important;
            font-weight: 600;
            padding: 0.6rem 1.2rem !important;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            background: rgba(192, 132, 252, 0.15);
            color: var(--primary) !important;
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            padding-top: 30px;
            padding-bottom: 30px;
        }

        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(241, 110, 235, 0.95);
            backdrop-filter: blur(10px);
            color: var(--primary-dark);
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(192, 132, 252, 0.2);
            transition: all 0.3s ease;
            border: 2px solid rgba(192, 132, 252, 0.2);
        }

        .btn-back:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateX(-5px);
            box-shadow: 0 6px 20px rgba(192, 132, 252, 0.3);
            border-color: var(--primary);
        }

        /* Page Header */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
            animation: slideDown 0.6s ease-out;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h2 {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
            font-size: 2.2rem;
            margin: 0;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h2 i {
            font-size: 2rem;
        }

        .page-header p {
            color: #7c3aed;
            margin: 10px 0 0 0;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Alert Messages */
        .alert-custom {
            padding: 18px 24px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            animation: slideInRight 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            color: #065f46;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            color: #7f1d1d;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid var(--danger);
        }

        .alert-warning {
            color: #78350f;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid var(--warning);
        }

        .alert-custom i {
            font-size: 1.3rem;
        }

        /* Form Card */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 35px;
            padding-bottom: 35px;
            border-bottom: 2px solid rgba(192, 132, 252, 0.15);
        }

        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, var(--primary), var(--secondary)) 1;
        }

        .form-section-title i {
            font-size: 1.4rem;
            color: var(--primary);
        }

        /* Form Labels */
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .form-label i {
            color: var(--primary);
            font-size: 1rem;
        }

        .required {
            color: var(--danger);
            margin-left: 4px;
        }

        /* Form Controls */
        .form-control {
            border: 2px solid #5104a3;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(83, 27, 139, 0.15);
            background: var(--white);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .form-text {
            color: #7c3aed;
            font-size: 0.85rem;
            margin-top: 6px;
            font-weight: 500;
        }

        /* Image Upload Area */
        .image-upload-area {
            border: 3px dashed #641fad;
            border-radius: 16px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            position: relative;
        }

        .image-upload-area:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            transform: translateY(-3px);
        }

        .image-upload-area.dragover {
            border-color: var(--success);
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            transform: scale(1.02);
        }

        .image-upload-area i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 15px;
            display: block;
            opacity: 0.7;
        }

        .upload-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .upload-hint {
            font-size: 0.9rem;
            color: #7c3aed;
            font-weight: 500;
        }

        .image-upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        /* Image Preview */
        .image-preview {
            display: none;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .image-preview img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            border: 3px solid var(--white);
        }

        .image-preview-actions {
            margin-top: 15px;
        }

        .btn-remove-image {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: var(--white);
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-remove-image:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 16px 32px;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            box-shadow: 0 8px 25px rgba(84, 36, 133, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-submit:hover::before {
            width: 400px;
            height: 400px;
        }

        .btn-submit i {
            position: relative;
            z-index: 1;
            font-size: 1.2rem;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(192, 132, 252, 0.5);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 1.8rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .form-card {
                padding: 25px;
            }

            .form-section-title {
                font-size: 1.1rem;
            }

            .image-upload-area {
                padding: 40px 20px;
            }

            .image-upload-area i {
                font-size: 3rem;
            }

            .btn-submit {
                font-size: 1rem;
                padding: 14px 28px;
            }
        }

        /* Loading Animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading {
            pointer-events: none;
            opacity: 0.6;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--white);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Floating particles */
        .particle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.08;
            animation: float-particle 20s infinite;
        }

        @keyframes float-particle {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg);
            }
            33% {
                transform: translateY(-100px) translateX(100px) rotate(120deg);
            }
            66% {
                transform: translateY(-50px) translateX(-100px) rotate(240deg);
            }
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            background: var(--primary);
            top: 10%;
            left: 10%;
            animation-duration: 25s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            background: var(--secondary);
            top: 60%;
            right: 10%;
            animation-duration: 30s;
            animation-delay: 5s;
        }
    </style>
</head>
<body>

<!-- Floating particles -->
<div class="particle"></div>
<div class="particle"></div>

<div class="main-wrapper">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="adminfrontend.php">
                <i class="fas fa-ticket-alt"></i>
                TICKET AAYO
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="mb-3">
            <a href="adminfrontend.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="page-header">
            <h2>
                <i class="fas fa-plus-circle"></i>
                Add New Event
            </h2>
            <p>Create a new event for your ticket booking system</p>
        </div>

        <?php if ($message): ?>
            <div class="alert-custom alert-<?= $message_type ?>">
                <?php if ($message_type === 'success'): ?>
                    <i class="fas fa-check-circle"></i>
                <?php elseif ($message_type === 'danger'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle"></i>
                <?php endif; ?>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i>
                    Event Information
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-heading"></i>
                        Event Title<span class="required">*</span>
                    </label>
                    <input type="text" name="title" class="form-control" placeholder="Enter event title" required>
                    <div class="form-text">Provide a clear and attractive title for your event</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i>
                        Description<span class="required">*</span>
                    </label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Enter event description" required></textarea>
                    <div class="form-text">Describe what attendees can expect from this event</div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-image"></i>
                    Event Image
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-camera"></i>
                        Upload Event Image
                    </label>
                    <div class="image-upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="upload-text">Click to upload or drag and drop</div>
                        <div class="upload-hint">JPG, PNG, GIF or WEBP (Max 5MB)</div>
                        <input type="file" name="event_image" id="eventImage" accept="image/*">
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="" alt="Preview">
                        <div class="image-preview-actions">
                            <button type="button" class="btn-remove-image" id="removeImage">
                                <i class="fas fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Event Details
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-day"></i>
                            Date<span class="required">*</span>
                        </label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Location<span class="required">*</span>
                        </label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., City Hall, Kathmandu" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-ticket-alt"></i>
                    Pricing & Capacity
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-rupee-sign"></i>
                            Price (Rs.)<span class="required">*</span>
                        </label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                        <div class="form-text">Ticket price in Nepali Rupees</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-chair"></i>
                            Total Seats<span class="required">*</span>
                        </label>
                        <input type="number" name="total_seats" class="form-control" min="1" placeholder="100" required>
                        <div class="form-text">Maximum number of available seats</div>
                    </div>
                </div>
            </div>

            <button type="submit" name="add_event" class="btn-submit">
                <i class="fas fa-plus-circle"></i>
                Create Event
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Image upload preview functionality
    const eventImage = document.getElementById('eventImage');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImage = document.getElementById('removeImage');

    // Handle file selection
    eventImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            displayImage(file);
        }
    });

    // Handle drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            eventImage.files = e.dataTransfer.files;
            displayImage(file);
        }
    });

    // Display image preview
    function displayImage(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = 'block';
            uploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    // Remove image
    removeImage.addEventListener('click', function() {
        eventImage.value = '';
        imagePreview.style.display = 'none';
        uploadArea.style.display = 'block';
        previewImg.src = '';
    });

    // Auto-hide success messages after 5 seconds
    setTimeout(function() {
        const alert = document.querySelector('.alert-success');
        if (alert) {
            alert.style.transition = 'all 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100px)';
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);

    // Add input validation animations
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() !== '') {
                this.style.borderColor = 'var(--success)';
                setTimeout(() => {
                    this.style.borderColor = '';
                }, 1000);
            }
        });
    });

    // Form submission loading state
    document.querySelector('form').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('.btn-submit');
        submitBtn.classList.add('loading');
        submitBtn.style.pointerEvents = 'none';
    });

    // Add ripple effect to submit button
    document.querySelector('.btn-submit').addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            transform: scale(0);
            animation: ripple-effect 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `;
        
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });

    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple-effect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
</script>
</body>
</html>