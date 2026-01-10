<?php
require_once 'config.php';

// Try to log activity, but don't fail if it doesn't work
try {
    if (isLoggedIn()) {
        logActivity('LOGOUT', 'User logged out');
    }
} catch (Exception $e) {
    // Ignore logging errors during logout
}

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

header('Location: login.php');
exit();
?>

