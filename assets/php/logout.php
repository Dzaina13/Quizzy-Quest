<?php
require_once 'assets/php/session.php';

// Log logout activity (optional)
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'koneksi_db.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $log_query = "INSERT INTO user_activity_logs (user_id, activity_type, activity_description, ip_address, created_at) 
                      VALUES (?, 'logout', 'User logged out', ?, NOW())";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (Exception $e) {
        // Log error but don't stop logout process
        error_log("Logout logging error: " . $e->getMessage());
    }
}

logout();
?>
