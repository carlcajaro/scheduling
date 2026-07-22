<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carl_db');

try {
    // Establish PDO connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start PHP session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Log activity to the database.
 * 
 * @param PDO $pdo The database connection instance
 * @param string $username The username related to the activity
 * @param string $activity_type The type of activity ('REGISTRATION', 'LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT')
 */
function logActivity($pdo, $username, $activity_type) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        // Handle IPv6 localhost mapping
        if ($ip_address === '::1') {
            $ip_address = '127.0.0.1';
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (username, activity_type, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $activity_type, $ip_address, $user_agent]);
    } catch (Exception $e) {
        // Fail silently or handle error to prevent app crash due to logging
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
