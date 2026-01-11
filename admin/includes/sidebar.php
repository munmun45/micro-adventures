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

    

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="countries.php" class="nav-item <?php echo $current_page === 'countries.php' ? 'active' : ''; ?>">
            <i class="bi bi-globe"></i>
            <span>Countries</span>
        </a>
        <a href="states.php" class="nav-item <?php echo $current_page === 'states.php' ? 'active' : ''; ?>">
            <i class="bi bi-geo-alt"></i>
            <span>States</span>
        </a>
        <a href="districts.php" class="nav-item <?php echo $current_page === 'districts.php' ? 'active' : ''; ?>">
            <i class="bi bi-geo"></i>
            <span>Cities</span>
        </a>
        <a href="activities.php" class="nav-item <?php echo $current_page === 'activities.php' ? 'active' : ''; ?>">
            <i class="bi bi-activity"></i>
            <span>Activities</span>
        </a>
        <a href="hotels.php" class="nav-item <?php echo $current_page === 'hotels.php' ? 'active' : ''; ?>">
            <i class="bi bi-building"></i>
            <span>Hotels</span>
        </a>
        <a href="transport.php" class="nav-item <?php echo $current_page === 'transport.php' ? 'active' : ''; ?>">
            <i class="bi bi-truck"></i>
            <span>Transport</span>
        </a>
        <a href="places.php" class="nav-item <?php echo $current_page === 'places.php' ? 'active' : ''; ?>">
            <i class="bi bi-geo-alt-fill"></i>
            <span>Places</span>
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

