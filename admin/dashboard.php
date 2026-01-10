<?php
require_once 'config.php';
requireLogin();

$admin = getAdminInfo();
if (!$admin) {
    header('Location: setup.php');
    exit();
}
$conn = getDBConnection();

// Get statistics
$stats = [];
$recent_activities = [];

// Check if tables exist, if not show setup message
$table_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
if (!$table_check || $table_check->num_rows == 0) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Setup Required</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f3f4f6; }
            .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin: 20px 0; }
            h1 { color: #1f2937; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin-top: 10px; }
            .btn:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>⚠️ Database Setup Required</h1>
            <div class='warning'>
                <p><strong>The database tables have not been created yet.</strong></p>
                <p>Please run the setup script to create the database and admin user.</p>
            </div>
            <a href='setup.php' class='btn'>Run Setup Now</a>
        </div>
    </body>
    </html>";
    exit();
}

// Total admins
$result = $conn->query("SELECT COUNT(*) as total FROM admin_users WHERE status = 'active'");
if ($result && $result !== false) {
    $stats['total_admins'] = $result->fetch_assoc()['total'];
} else {
    $stats['total_admins'] = 0;
}

// Recent logins
$result = $conn->query("SELECT COUNT(*) as total FROM admin_users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($result && $result !== false) {
    $stats['recent_logins'] = $result->fetch_assoc()['total'];
} else {
    $stats['recent_logins'] = 0;
}

// Total activity logs
$result = $conn->query("SELECT COUNT(*) as total FROM admin_activity_log");
if ($result && $result !== false) {
    $stats['total_activities'] = $result->fetch_assoc()['total'];
} else {
    $stats['total_activities'] = 0;
}

// Recent activities
$result = $conn->query("
    SELECT al.*, au.full_name, au.username 
    FROM admin_activity_log al 
    JOIN admin_users au ON al.user_id = au.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
if ($result && $result !== false) {
    $recent_activities = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $recent_activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin Panel</title>
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
                <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($admin['full_name']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Admins</h3>
                        <p class="stat-value"><?php echo $stats['total_admins']; ?></p>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Recent Logins</h3>
                        <p class="stat-value"><?php echo $stats['recent_logins']; ?></p>
                        <p class="stat-label">Last 7 days</p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Activities</h3>
                        <p class="stat-value"><?php echo $stats['total_activities']; ?></p>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Last Login</h3>
                        <p class="stat-value">
                            <?php 
                            if ($admin['last_login']) {
                                echo date('M d, Y', strtotime($admin['last_login']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="bi bi-clock-history"></i> Recent Activities</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_activities) && count($recent_activities) > 0): ?>
                        <div class="activity-list">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi bi-circle-fill"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4><?php echo htmlspecialchars($activity['action']); ?></h4>
                                        <p><?php echo htmlspecialchars($activity['description'] ?: 'No description'); ?></p>
                                        <span class="activity-meta">
                                            <?php echo htmlspecialchars($activity['full_name']); ?> • 
                                            <?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No activities found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="bi bi-lightning"></i> Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="change-password.php" class="quick-action-item">
                            <div class="quick-action-icon">
                                <i class="bi bi-key"></i>
                            </div>
                            <div class="quick-action-content">
                                <h3>Change Password</h3>
                                <p>Update your account password</p>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

