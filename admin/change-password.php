<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            $conn = getDBConnection();
            $admin_id = $_SESSION['admin_id'];
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                
                if ($user && password_verify($current_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $hashed_password, $admin_id);
                        
                        if ($update_stmt->execute()) {
                            logActivity('PASSWORD_CHANGE', 'Password changed successfully');
                            $success = 'Password changed successfully';
                        } else {
                            $error = 'Failed to update password. Please try again.';
                        }
                        $update_stmt->close();
                    } else {
                        $error = 'Database error. Please try again.';
                    }
                } else {
                    $error = 'Current password is incorrect';
                }
            } else {
                $error = 'Database error. Please check your database connection.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

$admin = getAdminInfo();
if (!$admin) {
    header('Location: setup.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <h1><i class="bi bi-key"></i> Change Password</h1>
                <p>Update your account password</p>
            </div>

            <div class="card">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="form-container">
                    <div class="form-group">
                        <label>
                            <i class="bi bi-lock"></i> Current Password
                        </label>
                        <input type="password" name="current_password" required
                            class="form-input"
                            placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="bi bi-key"></i> New Password
                        </label>
                        <input type="password" name="new_password" required
                            class="form-input"
                            placeholder="Enter new password (min 6 characters)"
                            minlength="6">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="bi bi-key-fill"></i> Confirm New Password
                        </label>
                        <input type="password" name="confirm_password" required
                            class="form-input"
                            placeholder="Confirm new password"
                            minlength="6">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Password
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

