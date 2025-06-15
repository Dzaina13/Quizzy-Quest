<?php
session_start();

// Include koneksi database
require_once 'koneksi_db.php';

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
$sql = "SELECT user_id, username, email, password_hash FROM users WHERE email = '$email'";
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
        

        
        // Redirect ke lobby
        header('Location: ../../pages/lobby.php');
        exit();
        
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

?>
