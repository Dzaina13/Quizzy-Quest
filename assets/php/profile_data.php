<?php
require_once 'koneksi_db.php';
/**
 * User Profile Data Handler
 * File ini berisi logika untuk mengambil dan memproses data profil user
 * Lokasi: assets/php/user_profile_data.php
 */

// Pastikan koneksi database sudah di-include sebelum file ini
if (!isset($koneksi)) {
  die("Database connection not found. Please include koneksi_db.php first.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php?error=not_logged_in");
    exit();
}

// Simulasi user login - ganti dengan session yang sebenarnya
$current_user_id = $_SESSION['user_id']; // Ganti dengan $_SESSION['user_id']

// Function to get user data
function getUserData($koneksi, $user_id) {
  $sql = "SELECT user_id, username, email, role, created_at FROM users WHERE user_id = ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
      die("User tidak ditemukan!");
  }

  return $result->fetch_assoc();
}

// Function to get user quiz count
function getUserQuizCount($koneksi, $user_id) {
  $sql = "SELECT COUNT(*) as total FROM quizzes WHERE created_by = ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc()['total'] ?? 0;
}

// Function to get user session count
function getUserSessionCount($koneksi, $user_id) {
  $sql = "SELECT COUNT(DISTINCT qs.session_id) as total 
          FROM quiz_sessions qs 
          JOIN quizzes q ON qs.quiz_id = q.quiz_id 
          WHERE q.created_by = ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc()['total'] ?? 0;
}

// Function to calculate days active
function getDaysActive($created_at) {
  $join_date = new DateTime($created_at);
  $now = new DateTime();
  $diff = $now->diff($join_date);
  return $diff->days;
}

// Function to update user profile
function updateUserProfile($koneksi, $user_id, $username, $email) {
  // Validasi input
  if (empty($username) || empty($email)) {
      return ['success' => false, 'message' => 'Username and email are required!'];
  }
  
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['success' => false, 'message' => 'Invalid email format!'];
  }
  
  // Cek apakah username sudah digunakan user lain
  $sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("si", $username, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
      return ['success' => false, 'message' => 'Username already exists!'];
  }
  
  // Cek apakah email sudah digunakan user lain
  $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("si", $email, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
      return ['success' => false, 'message' => 'Email already exists!'];
  }
  
  // Update profile
  $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("ssi", $username, $email, $user_id);
  
  if ($stmt->execute()) {
      return ['success' => true, 'message' => 'Profile updated successfully!'];
  } else {
      return ['success' => false, 'message' => 'Failed to update profile!'];
  }
}

// Function to change password
function changeUserPassword($koneksi, $user_id, $current_password, $new_password, $confirm_password) {
  if ($new_password !== $confirm_password) {
      return ['success' => false, 'message' => 'New passwords do not match!'];
  }
  
  // Ambil password hash dari database
  $sql = "SELECT password_hash FROM users WHERE user_id = ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  
  // Verifikasi password lama
  if (!password_verify($current_password, $user['password_hash'])) {
      return ['success' => false, 'message' => 'Current password is incorrect!'];
  }
  
  // Hash password baru dan update
  $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
  $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("si", $new_hash, $user_id);
  
  if ($stmt->execute()) {
      return ['success' => true, 'message' => 'Password changed successfully!'];
  } else {
      return ['success' => false, 'message' => 'Failed to change password!'];
  }
}

// Get user data
$user_data = getUserData($koneksi, $current_user_id);

// Handle form submission
$success_message = '';
$error_message = '';

if ($_POST) {
  if (isset($_POST['change_password'])) {
      // Handle password change
      $current_password = $_POST['current_password'];
      $new_password = $_POST['new_password'];
      $confirm_password = $_POST['confirm_password'];
      
      $result = changeUserPassword($koneksi, $current_user_id, $current_password, $new_password, $confirm_password);
      
      if ($result['success']) {
          $success_message = $result['message'];
      } else {
          $error_message = $result['message'];
      }
  } else {
      // Handle profile update
      $username = trim($_POST['username']);
      $email = trim($_POST['email']);
      
      $result = updateUserProfile($koneksi, $current_user_id, $username, $email);
      
      if ($result['success']) {
          $success_message = $result['message'];
          // Refresh data user
          $user_data['username'] = $username;
          $user_data['email'] = $email;
      } else {
          $error_message = $result['message'];
      }
  }
}

// Get user statistics
$quiz_count = getUserQuizCount($koneksi, $current_user_id);
$session_count = getUserSessionCount($koneksi, $current_user_id);
$days_active = getDaysActive($user_data['created_at']);
?>