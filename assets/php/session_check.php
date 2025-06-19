<?php
session_start();

// Fungsi untuk mengecek apakah user sudah login
function checkUserLogin() {
    if (!isset($_SESSION['user_logged_in']) || 
        $_SESSION['user_logged_in'] !== true || 
        !isset($_SESSION['user_id']) || 
        !isset($_SESSION['user_name']) || 
        !isset($_SESSION['user_email']) || 
        !isset($_SESSION['user_role'])) {
        
        header("Location: ../../pages/login.php");
        exit();
    }
}

// Fungsi untuk mengecek apakah user adalah admin
function checkAdminRole() {
    checkUserLogin();
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: ../../pages/admin/index.php");
        exit();
    }else{
        header("Location: ../../pages/dashboard.php");
        exit();

    }
}

// Fungsi untuk mendapatkan informasi user
function getUserInfo() {
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}
?>
