<?php
// Database Configuration for Frontend
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'travelapp_admin');

// Gemini API Configuration
// Model: gemini-3-flash-preview (2026 version)
// In production, use environment variable: getenv('GEMINI_API_KEY')
// For now, keep it here but NEVER commit to public repos
define('GEMINI_API_KEY', 'AIzaSyARp_0r1JcQOoNCWdjRRiE3jKLuc1dr0qo');

// Database Connection
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}
?>

