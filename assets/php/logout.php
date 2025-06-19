<?php
session_start();

// Include koneksi database dan user handler jika diperlukan untuk logging
require_once 'koneksi_db.php';
require_once 'user_handler.php';

// Log logout activity jika user sedang login
if (isset($_SESSION['user_id'])) {
    try {
        $userHandler = new UserHandler($koneksi);
        $userId = $_SESSION['user_id'];
        $sessionId = session_id();
        
        // Record logout activity
        $userHandler->recordLogout($userId, $sessionId);
        
        // Optional: Update last activity timestamp
        $stmt = $koneksi->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
    } catch (Exception $e) {
        // Log error tapi tetap lanjutkan logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Hapus semua session variables
session_unset();

// Destroy session
session_destroy();

// Hapus session cookie jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hapus remember me cookie jika ada
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Hapus token dari database jika ada tabel remember_tokens
    try {
        if (isset($userId)) {
            $stmt = $koneksi->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Remember token cleanup error: " . $e->getMessage());
    }
}

// Clear any other custom cookies
$cookiesToClear = ['user_preferences', 'theme_mode', 'language_pref'];
foreach ($cookiesToClear as $cookieName) {
    if (isset($_COOKIE[$cookieName])) {
        setcookie($cookieName, '', time() - 3600, '/');
    }
}

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Logout berhasil',
        'redirect' => '../../pages/login.php'
    ]);
    exit();
}

// Check if there's a specific redirect parameter
$redirectUrl = '../../pages/login.php';
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    // Validate redirect URL to prevent open redirect attacks
    $allowedRedirects = [
        '../../pages/login.php',
        '../../pages/register.php',
        '../../index.php'
    ];
    
    if (in_array($_GET['redirect'], $allowedRedirects)) {
        $redirectUrl = $_GET['redirect'];
    }
}

// Add logout success message to URL
$redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'logout=success';

// Redirect ke halaman login dengan pesan sukses
header("Location: " . $redirectUrl);
exit();
?>
