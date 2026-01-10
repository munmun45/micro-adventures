<?php
/**
 * Admin Panel Setup Script
 * Run this file once to set up the database and create admin user
 * DELETE THIS FILE after setup for security
 */

require_once 'config.php';

// Check if database exists, if not create it
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->close();

// Connect to the database
$conn = getDBConnection();

// Create tables one by one to avoid foreign key issues
$tables = [
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        status ENUM('active', 'inactive') DEFAULT 'active'
    )",
    
    "CREATE TABLE IF NOT EXISTS admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS admin_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
    )"
];

$table_errors = [];
foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        $table_errors[] = $conn->error;
    }
}

// Check for table creation errors
if (!empty($table_errors)) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Setup Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .error { background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; }
            h1 { color: #991b1b; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h1>❌ Setup Error</h1>
            <p><strong>Error creating tables:</strong></p>
            <ul>";
    foreach ($table_errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>
            <p>Please check your database permissions and try again.</p>
        </div>
    </body>
    </html>";
    exit();
}

// Check if admin user exists
$result = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
$admin_exists = false;
if ($result && $result !== false) {
    $admin_exists = $result->fetch_assoc()['count'] > 0;
}

if (!$admin_exists) {
    // Create default admin user (password: admin123)
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    $username = 'admin';
    $email = 'admin@travelapp.com';
    $full_name = 'Administrator';
    $stmt->bind_param("ssss", $username, $email, $password, $full_name);
    
    if ($stmt->execute()) {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Setup Complete</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .success { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; }
                .warning { background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; border-radius: 8px; margin-top: 20px; }
                h1 { color: #065f46; }
                code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='success'>
                <h1>✅ Setup Complete!</h1>
                <p><strong>Database and admin user created successfully.</strong></p>
                <p><strong>Default Login Credentials:</strong></p>
                <ul>
                    <li>Username: <code>admin</code></li>
                    <li>Password: <code>admin123</code></li>
                </ul>
            </div>
            <div class='warning'>
                <h2>⚠️ Security Warning</h2>
                <p><strong>IMPORTANT:</strong> Delete this file (<code>setup.php</code>) immediately after setup for security!</p>
                <p><a href='login.php'>Go to Login Page</a></p>
            </div>
        </body>
        </html>";
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Already Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class='info'>
            <h1>ℹ️ Already Setup</h1>
            <p>Admin user already exists. You can proceed to <a href='login.php'>login page</a>.</p>
            <p><strong>Note:</strong> You can delete this setup.php file.</p>
        </div>
    </body>
    </html>";
}

$conn->close();
?>

