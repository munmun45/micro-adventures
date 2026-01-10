<?php
$admin = getAdminInfo();
?>
<div class="header">
    <div class="header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="breadcrumb">
            <span>Admin Panel</span>
            <i class="bi bi-chevron-right"></i>
            <span><?php echo ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?></span>
        </div>
    </div>
    <div class="header-right">
        <div class="header-user">
            <div class="user-dropdown">
                <div class="user-dropdown-toggle">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="user-dropdown-menu">
                    <a href="change-password.php">
                        <i class="bi bi-key"></i> Change Password
                    </a>
                    <a href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
}

// User dropdown toggle
document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.querySelector('.user-dropdown-toggle');
    const menu = document.querySelector('.user-dropdown-menu');
    
    if (dropdown && menu) {
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            menu.classList.remove('show');
        });
    }
});
</script>

