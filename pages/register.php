<?php
session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
  header('Location: dashboard.php');
  exit();
}

// Ambil parameter error dan success dari URL
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Function untuk menampilkan pesan error
function getErrorMessage($error) {
  switch($error) {
      case 'empty_fields':
          return 'Semua field harus diisi!';
      case 'invalid_email':
          return 'Format email tidak valid!';
      case 'password_too_short':
          return 'Password minimal 6 karakter!';
      case 'password_mismatch':
          return 'Konfirmasi password tidak cocok!';
      case 'email_exists':
          return 'Email sudah terdaftar!';
      case 'registration_failed':
          return 'Pendaftaran gagal. Silakan coba lagi.';
      case 'database_error':
          return 'Terjadi kesalahan sistem. Silakan coba lagi.';
      case 'invalid_request':
          return 'Request tidak valid!';
      default:
          return '';
  }
}

// Function untuk menampilkan pesan sukses
function getSuccessMessage($success) {
  switch($success) {
      case 'registered':
          return 'Pendaftaran berhasil! Silakan login.';
      default:
          return '';
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar - Quizzy Quest</title>
  <link rel="stylesheet" href="../assets/css/register.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome untuk icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="register-container">
      <h2 class="auth-title">Daftar Akun Baru</h2>
      
      <?php if ($error): ?>
          <div class="error-message"><?php echo htmlspecialchars(getErrorMessage($error)); ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
          <div class="success-message"><?php echo htmlspecialchars(getSuccessMessage($success)); ?></div>
      <?php endif; ?>
      
      <form method="POST" action="../assets/php/register_process.php" class="auth-form">
          <input type="text" 
                 id="fullname" 
                 name="fullname" 
                 placeholder="Nama Lengkap" 
                 class="input-field" 
                 required
                 value="<?php echo isset($_GET['fullname']) ? htmlspecialchars($_GET['fullname']) : ''; ?>">
          
          <input type="email" 
                 id="email" 
                 name="email" 
                 placeholder="Email" 
                 class="input-field" 
                 required
                 value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
          
          <!-- Password Field with Toggle -->
          <div class="password-container">
              <input type="password" 
                     id="password" 
                     name="password" 
                     placeholder="Password (minimal 6 karakter)" 
                     class="input-field password-input" 
                     required>
              
              <button type="button" class="password-toggle" onclick="togglePassword('password')">
                  <i class="fas fa-eye" id="password-icon"></i>
              </button>
              
              <div id="password-strength" class="password-strength" style="display: none;">
                  <span id="strength-text"></span>
                  <div id="strength-feedback"></div>
              </div>
          </div>
          
          <!-- Confirm Password Field with Toggle -->
          <div class="password-container">
              <input type="password" 
                     id="confirmPassword" 
                     name="confirmPassword" 
                     placeholder="Konfirmasi Password" 
                     class="input-field password-input" 
                     required>
              
              <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                  <i class="fas fa-eye" id="confirmPassword-icon"></i>
              </button>
              
              <div id="confirm-feedback" class="password-feedback" style="display: none;"></div>
          </div>
          
          <button type="submit" class="auth-button register-button">Daftar</button>
      </form>
      
      <div class="form-actions">
          <button type="button" class="clear-button" onclick="clearForm()">
              <i class="fas fa-eraser"></i> Bersihkan Form
          </button>
      </div>
      
      <p class="login-text">
          Sudah punya akun? <a href="login.php" class="login-link">Masuk di sini</a>
      </p>
  </div>

  <!-- Load JavaScript -->
  <script src="../assets/js/register.js"></script>
</body>
</html>
