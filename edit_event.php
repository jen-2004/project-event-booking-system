<?php
include 'auth_check.php'; // ADMIN PROTECTION
include 'db.php'; 

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$message = "";
$message_type = "";
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$event = [];

if (!$id) {
    header("Location: adminfrontend.php");
    exit;
}

// Fetch existing event details including image_path
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    die("<div class='alert alert-danger text-center mt-5'>Event not found.</div>");
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = trim($_POST['date']);
    $location = trim($_POST['location']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT); 
    $total_seats = filter_var($_POST['total_seats'], FILTER_VALIDATE_INT);
    
    // Handle image upload
    $image_path = $event['image_path']; // Keep existing image by default
    $upload_error = false;
    
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
                // Delete old image if it exists
                if (!empty($event['image_path']) && file_exists($event['image_path'])) {
                    unlink($event['image_path']);
                }
                $image_path = $destination;
            } else {
                $message = "Failed to upload new image.";
                $message_type = 'danger';
                $upload_error = true;
            }
        } else {
            $message = "Invalid file type or file too large. Max 5MB, allowed: JPG, PNG, GIF, WEBP";
            $message_type = 'warning';
            $upload_error = true;
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        if (!empty($event['image_path']) && file_exists($event['image_path'])) {
            unlink($event['image_path']);
        }
        $image_path = null;
    }

    if (!$upload_error && $title && $description && $date && $location && $price !== false && $total_seats !== false) {
        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, date=?, location=?, price=?, total_seats=?, image_path=? WHERE event_id=?");
        $stmt->bind_param("ssssdisi", $title, $description, $date, $location, $price, $total_seats, $image_path, $id);

        if ($stmt->execute()) {
            $message = "Event updated successfully!";
            $message_type = "success";
            $event = [
                'event_id' => $id,
                'title' => $title,
                'description' => $description,
                'date' => $date,
                'location' => $location,
                'price' => $price,
                'total_seats' => $total_seats,
                'image_path' => $image_path
            ];
        } else {
            $message = "Error: " . e($conn->error);
            $message_type = "danger";
        }
        $stmt->close();
    } elseif (!$upload_error) {
        $message = "All fields are required, and numeric fields must be valid.";
        $message_type = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event: <?= e($event['title']) ?> | TICKET AAYO Admin</title>
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
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        /* Admin Navbar */
        .navbar {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 1rem 0;
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.8);
            color: #fff;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background: #fff;
            color: #ef4444;
            border-color: #fff;
        }

        /* Page Header */
        .page-header {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .page-header h3 {
            color: #1e3c72;
            font-weight: 800;
            font-size: 2rem;
            margin: 0;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h3 i {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: #64748b;
            margin: 8px 0 0 0;
            font-size: 0.95rem;
        }

        /* Form Card */
        .form-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Alert Messages */
        .alert-custom {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 2px solid;
            animation: slideIn 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateY(-10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .alert-success {
            color: #065f46;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #10b981;
        }

        .alert-danger {
            color: #991b1b;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #ef4444;
        }

        .alert-warning {
            color: #92400e;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
        }

        .alert-custom i {
            font-size: 1.2rem;
        }

        /* Form Labels */
        .form-label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #667eea;
            font-size: 0.9rem;
        }

        /* Form Controls */
        .form-control {
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Image Upload Area */
        .image-upload-area {
            border: 3px dashed #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .image-upload-area:hover {
            border-color: #667eea;
            background: #fff;
        }

        .image-upload-area.dragover {
            border-color: #10b981;
            background: #d1fae5;
        }

        .image-upload-area i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .image-upload-area:hover i {
            color: #667eea;
        }

        .image-upload-area input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-text {
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .upload-hint {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* Current Image Display */
        .current-image-container {
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .current-image-container img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            display: block;
        }

        .image-overlay-actions {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
            padding: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-change-image, .btn-remove-current-image {
            background: rgba(255, 255, 255, 0.95);
            color: #1e293b;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-current-image {
            background: rgba(239, 68, 68, 0.95);
            color: white;
        }

        .btn-change-image:hover {
            background: #fff;
            transform: translateY(-2px);
        }

        .btn-remove-current-image:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Image Preview */
        .image-preview {
            margin-top: 20px;
            display: none;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .image-preview-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-remove-preview {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-preview:hover {
            background: #dc2626;
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-section-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Buttons */
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn-update::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-update:hover::before {
            left: 100%;
        }

        .btn-update:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #fff;
            color: #64748b;
            border: 2px solid #e2e8f0;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #f8fafc;
            color: #64748b;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        /* Back Button */
        .btn-back {
            background: #fff;
            color: #64748b;
            border: 2px solid #e2e8f0;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #f8fafc;
            color: #667eea;
            border-color: #667eea;
            transform: translateX(-5px);
        }

        /* Event ID Badge */
        .event-badge {
            display: inline-block;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 2px solid #3b82f6;
        }

        /* Container */
        .container {
            max-width: 1000px;
            padding-top: 40px;
            padding-bottom: 60px;
        }

        /* Helper Text */
        .form-text {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 6px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
            }

            .page-header {
                padding: 20px;
            }

            .page-header h3 {
                font-size: 1.5rem;
                flex-direction: column;
                text-align: center;
            }

            .btn-update, .btn-cancel {
                padding: 14px 30px;
                font-size: 1rem;
            }

            .image-upload-area {
                padding: 20px;
            }

            .image-upload-area i {
                font-size: 2rem;
            }

            .current-image-container img {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="adminfrontend.php">
            <i class="fas fa-shield-alt"></i> Admin Panel
        </a>
        <div>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
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
        <h3>
            <i class="fas fa-edit"></i>
            Edit Event Details
        </h3>
        <p>Update information for <strong><?= e($event['title']) ?></strong> <span class="event-badge">ID: #<?= $id ?></span></p>
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
        <input type="hidden" name="remove_image" id="removeImageFlag" value="0">
        
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-info-circle"></i>
                Event Information
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-heading"></i>
                    Event Title
                </label>
                <input type="text" name="title" class="form-control" value="<?= e($event['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-align-left"></i>
                    Description
                </label>
                <textarea name="description" rows="4" class="form-control" required><?= e($event['description']) ?></textarea>
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
                    Event Image
                </label>
                
                <!-- Show current image if exists -->
                <div id="currentImageSection" <?= empty($event['image_path']) || !file_exists($event['image_path']) ? 'style="display:none;"' : '' ?>>
                    <div class="current-image-container">
                        <img src="<?= e($event['image_path']) ?>" alt="Current event image" id="currentImage">
                        <div class="image-overlay-actions">
                            <button type="button" class="btn-change-image" onclick="document.getElementById('eventImage').click()">
                                <i class="fas fa-exchange-alt"></i> Change Image
                            </button>
                            <button type="button" class="btn-remove-current-image" onclick="removeCurrentImage()">
                                <i class="fas fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload area (shown when no image or after removal) -->
                <div class="image-upload-area" id="uploadArea" <?= !empty($event['image_path']) && file_exists($event['image_path']) ? 'style="display:none;"' : '' ?>>
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div class="upload-text">Click to upload or drag and drop</div>
                    <div class="upload-hint">JPG, PNG, GIF or WEBP (Max 5MB)</div>
                    <input type="file" name="event_image" id="eventImage" accept="image/*">
                </div>

                <!-- New image preview -->
                <div class="image-preview" id="imagePreview">
                    <img id="previewImg" src="" alt="Preview">
                    <div class="image-preview-actions">
                        <button type="button" class="btn-remove-preview" onclick="cancelNewImage()">
                            <i class="fas fa-times"></i> Cancel
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

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-calendar-day"></i>
                    Date
                </label>
                <input type="date" name="date" class="form-control" value="<?= e($event['date']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-map-marker-alt"></i>
                    Location
                </label>
                <input type="text" name="location" class="form-control" value="<?= e($event['location']) ?>" required>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-ticket-alt"></i>
                Pricing & Capacity
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-rupee-sign"></i>
                        Price (Rs.)
                    </label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= e($event['price']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-chair"></i>
                        Total Seats
                    </label>
                    <input type="number" name="total_seats" class="form-control" min="1" value="<?= e($event['total_seats']) ?>" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-update">
            <i class="fas fa-save"></i> Update Event
        </button>
        <a href="adminfrontend.php" class="btn-cancel mt-3">
            <i class="fas fa-times"></i> Cancel
        </a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const eventImage = document.getElementById('eventImage');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const currentImageSection = document.getElementById('currentImageSection');
    const removeImageFlag = document.getElementById('removeImageFlag');

    // Handle file selection
    eventImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            displayNewImage(file);
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
            displayNewImage(file);
        }
    });

    // Display new image preview
    function displayNewImage(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = 'block';
            uploadArea.style.display = 'none';
            currentImageSection.style.display = 'none';
            removeImageFlag.value = '0';
        };
        reader.readAsDataURL(file);
    }

    // Cancel new image selection
    function cancelNewImage() {
        eventImage.value = '';
        imagePreview.style.display = 'none';
        
        // Show current image if exists, otherwise show upload area
        if (currentImageSection.querySelector('img').src && currentImageSection.querySelector('img').src !== window.location.href) {
            currentImageSection.style.display = 'block';
        } else {
            uploadArea.style.display = 'block';
        }
    }

    // Remove current image
    function removeCurrentImage() {
        if (confirm('Are you sure you want to remove the current image?')) {
            currentImageSection.style.display = 'none';
            uploadArea.style.display = 'block';
            removeImageFlag.value = '1';
        }
    }
</script>
</body>
</html>