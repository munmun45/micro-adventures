<?php
/**
 * Quick setup check - redirects to setup if needed
 */
require_once 'config.php';

try {
    $conn = getDBConnection();
    $result = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if ($result && $result->num_rows > 0) {
        // Tables exist, check if admin user exists
        $result = $conn->query("SELECT COUNT(*) as count FROM admin_users");
        if ($result && $result->fetch_assoc()['count'] > 0) {
            return true; // Setup complete
        }
    }
} catch (Exception $e) {
    // Error, need setup
}

// Redirect to setup
if (basename($_SERVER['PHP_SELF']) !== 'setup.php' && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: setup.php');
    exit();
}
return false;
?>


