<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'travelapp_admin');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'ADMIN_SESSION');

// Security
define('ADMIN_SALT', 'travelapp_admin_2024');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Database Connection
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                // Try to create database if it doesn't exist
                if ($conn->connect_errno == 1049) {
                    $conn_temp = new mysqli(DB_HOST, DB_USER, DB_PASS);
                    if ($conn_temp->connect_error) {
                        die("Connection failed: " . $conn_temp->connect_error);
                    }
                    $conn_temp->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
                    $conn_temp->close();
                    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                } else {
                    die("Connection failed: " . $conn->connect_error);
                }
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Log activity
function logActivity($action, $description = '') {
    if (isLoggedIn()) {
        try {
            $conn = getDBConnection();
            
            // Check if table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
            if (!$table_check || $table_check->num_rows == 0) {
                return; // Table doesn't exist, skip logging
            }
            
            $user_id = $_SESSION['admin_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            $stmt = $conn->prepare("INSERT INTO admin_activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silently fail - don't break the app if logging fails
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}

// Get admin user info
function getAdminInfo() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $conn = getDBConnection();
        $admin_id = $_SESSION['admin_id'];
        
        // Check if table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
        if (!$table_check || $table_check->num_rows == 0) {
            return null; // Table doesn't exist
        }
        
        $stmt = $conn->prepare("SELECT id, username, email, full_name, last_login FROM admin_users WHERE id = ? AND status = 'active'");
        if ($stmt) {
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();
            return $admin;
        }
    } catch (Exception $e) {
        error_log("Get admin info failed: " . $e->getMessage());
    }
    
    return null;
}
?>

