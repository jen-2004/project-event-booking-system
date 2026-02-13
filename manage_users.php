<?php
include 'db.php';
session_start();

// CRITICAL: Access Control - Only Admins can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h3 style='color: red; text-align: center;'>Access denied. You must be logged in as an administrator.</h3>");
}

$message = '';
$error = '';

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Delete User
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent admin from deleting themselves
        if ($user_id === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = "You cannot delete your own account!";
        } else {
            // Delete user's bookings first (foreign key constraint)
            $stmt_bookings = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt_bookings->bind_param("i", $user_id);
            $stmt_bookings->execute();
            $stmt_bookings->close();
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = "User deleted successfully!";
                } else {
                    $_SESSION['error'] = "User not found!";
                }
            } else {
                $_SESSION['error'] = "Error deleting user: " . $conn->error;
            }
            $stmt->close();
        }
        header("Location: manage_users.php");
        exit;
    }
    
    // Update User Role
    if (isset($_POST['update_role'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = trim($_POST['role']);
        
        // Validate role value
        if (!in_array($new_role, ['customer', 'admin'])) {
            $_SESSION['error'] = "Invalid role selected!";
        } elseif ($user_id === (int)$_SESSION['user_id']) {
            // Prevent admin from demoting themselves
            $_SESSION['error'] = "You cannot change your own role!";
        } else {
            // First check the current role
            $check_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $current_user = $result->fetch_assoc();
                $current_role = trim($current_user['role']);
                
                if ($current_role === $new_role) {
                    $_SESSION['error'] = "User already has the " . ucfirst($new_role) . " role.";
                } else {
                    // Proceed with update
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $new_role, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "User role updated successfully from " . ucfirst($current_role) . " to " . ucfirst($new_role) . "!";
                    } else {
                        $_SESSION['error'] = "Error updating role: " . $conn->error;
                    }
                    $stmt->close();
                }
            } else {
                $_SESSION['error'] = "User not found!";
            }
            $check_stmt->close();
        }
        header("Location: manage_users.php");
        exit;
    }
    
    // Add New User
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Validate inputs
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All fields are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format!";
        } else {
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = "Email already exists!";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "User added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding user: " . $conn->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
        header("Location: manage_users.php");
        exit;
    }
}

// Get messages from session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch Users with search functionality
if (!empty($search)) {
    $search_term = "%$search%";
    $stmt = $conn->prepare("SELECT user_id, name, email, role, created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result_users = $stmt->get_result();
} else {
    $result_users = $conn->query("SELECT user_id, name, email, role, created_at FROM users ORDER BY created_at DESC");
}

// Get user statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | TICKET AAYO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            overflow-x: hidden;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            padding: 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 100%;
            width: 100%;
            height: calc(100vh - 30px);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .content-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        /* Custom scrollbar */
        .content-wrapper::-webkit-scrollbar {
            width: 8px;
        }
        
        .content-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .content-wrapper::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        .content-wrapper::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b4298 100%);
        }
        
        .page-title {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.75rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .stat-card h5 {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .stat-card h2 {
            font-size: 1.75rem;
            margin: 0;
        }
        
        .table-container {
            margin-top: 20px;
            flex: 1;
        }
        
        .table-responsive {
            max-height: 100%;
            overflow-y: auto;
        }
        
        .table {
            font-size: 0.9rem;
            white-space: nowrap;
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b4298 100%);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                height: calc(100vh - 20px);
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .stat-card h2 {
                font-size: 1.5rem;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }
        }
        
        /* Make action buttons stack on small screens */
        @media (max-width: 576px) {
            .table td .btn {
                display: inline-block;
                margin: 2px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="content-wrapper">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h1 class="page-title mb-0"><i class="fas fa-users"></i> Manage Users</h1>
            <a href="adminfrontend.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-3">
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <h5><i class="fas fa-users"></i> Total Users</h5>
                <h2><?= $total_users ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <h5><i class="fas fa-user-shield"></i> Admins</h5>
                <h2><?= $total_admins ?></h2>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <h5><i class="fas fa-user"></i> Customers</h5>
                <h2><?= $total_customers ?></h2>
            </div>
        </div>
    </div>

    <!-- Search and Add -->
    <div class="row mb-3 g-2">
        <div class="col-lg-8 col-md-7">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                <?php if ($search): ?>
                    <a href="manage_users.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-lg-4 col-md-5 text-md-end">
            <button class="btn btn-success btn-sm w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_users && $result_users->num_rows > 0): ?>
                        <?php while ($user = $result_users->fetch_assoc()): ?>
                            <tr>
                                <td><?= $user['user_id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['user_id'] ?>" title="Edit Role">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?= $user['user_id'] ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editUserModal<?= $user['user_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User Role</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="manage_users.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Role</label>
                                                    <select name="role" class="form-select" required>
                                                        <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_role" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Update Role
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete User Modal -->
                            <div class="modal fade" id="deleteUserModal<?= $user['user_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="manage_users.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <p>Are you sure you want to delete <strong><?= htmlspecialchars($user['name']) ?></strong>?</p>
                                                <p class="text-danger"><i class="fas fa-info-circle"></i> This will also delete all their bookings!</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_user" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Delete User
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No users found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="manage_users.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
</body>
</html>