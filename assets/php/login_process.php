<?php
session_start();

// Include koneksi database
require_once 'koneksi_db.php';
require_once 'user_handler.php'; // 👈 TAMBAH INI

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Cek apakah request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/login.php?error=invalid_request');
    exit();
}

// Ambil dan sanitasi input
$email = trim($_POST['email']);
$password = trim($_POST['password']);

// Validasi input kosong
if (empty($email) || empty($password)) {
    header('Location: ../../pages/login.php?error=empty_fields&email=' . urlencode($email));
    exit();
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../pages/login.php?error=invalid_email&email=' . urlencode($email));
    exit();
}

// Escape input untuk keamanan
$email = $koneksi->real_escape_string($email);

// Query untuk mencari user berdasarkan email
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $koneksi->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Cek password
    if (password_verify($password, $user['password_hash'])) {
        
        // SET SESSION
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        // 🔥 PAKE USER HANDLER KAYAK DI LOGOUT
        try {
            $userHandler = new UserHandler($koneksi);
            $userId = $user['user_id'];
            $sessionId = session_id();
            
            // Record login activity
            $userHandler->recordLogin($userId, $sessionId); // 👈 PAKE METHOD INI
            
        } catch (Exception $e) {
            error_log("Login logging error: " . $e->getMessage());
        }

        // REDIRECT SESUAI ROLE
        if ($_SESSION['user_role'] == 'admin') {
            header('Location: ../../pages/admin/index.php');
            exit();
        } else {
            header('Location: ../../pages/dashboard.php');
            exit();
        }
        
    } else {
        // Password salah
        header('Location: ../../pages/login.php?error=invalid_credentials&email=' . urlencode($_POST['email']));
        exit();
    }
} else {
    // User tidak ditemukan
    header('Location: ../../pages/login.php?error=invalid_credentials&email=' . urlencode($_POST['email']));
    exit();
}

// Tutup koneksi
$koneksi->close();
?>
