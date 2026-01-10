<?php
$current_page = basename($_SERVER['PHP_SELF']);
$admin = getAdminInfo();
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            <div class="logo-text">
                <h2>TravelApp</h2>
                <p>Admin Panel</p>
            </div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="user-info">
            <p class="user-name"><?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?></p>
            <p class="user-email"><?php echo htmlspecialchars($admin['email'] ?? ''); ?></p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="change-password.php" class="nav-item <?php echo $current_page === 'change-password.php' ? 'active' : ''; ?>">
            <i class="bi bi-key"></i>
            <span>Change Password</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

