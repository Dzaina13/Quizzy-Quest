<?php
session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: lobby.php');
    exit();
}

// Konfigurasi halaman
$page_title = "Quizzy Quest - Login";

// Ambil pesan error dari URL
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error_message = 'Email atau password salah!';
            break;
        case 'empty_fields':
            $error_message = 'Mohon isi semua field!';
            break;
        case 'invalid_email':
            $error_message = 'Format email tidak valid!';
            break;
        case 'database_error':
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            break;
        case 'invalid_request':
            $error_message = 'Request tidak valid!';
            break;
        default:
            $error_message = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="auth-container">
        <!-- Left Section - Image -->
        <div class="left-section">
            <img src="../assets/images/login.png" alt="Login Illustration" class="auth-image">
        </div>
        
        <!-- Right Section - Form -->
        <div class="right-section">
            <h1 class="auth-title">Sign In</h1>
            <p class="or-text">or use your account</p>
            
            <!-- Social Login -->
            <div class="social-login">
                <a href="#" class="social-icon">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
                </a>
                <a href="#" class="social-icon">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                </a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Form yang connect ke login_process.php -->
            <form method="POST" action="../assets/php/login_process.php" class="auth-form">
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="Email" 
                       required 
                       class="input-field"
                       value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Password" 
                       required 
                       class="input-field">
                
                <a href="#" class="forgot-password-link">Forgot your password?</a>
                
                <button type="submit" class="auth-button sign-in-button">SIGN IN</button>
            </form>
            
            <p class="signup-text">
                Don't have an account? 
                <a href="register.php" class="signup-link">Sign Up</a>
            </p>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector(".auth-form").addEventListener("submit", function(e) {
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
            
            if (!email || !password) {
                e.preventDefault();
                alert("Mohon isi semua field yang diperlukan!");
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert("Password minimal 6 karakter!");
                return false;
            }
        });
    </script>
</body>
</html>
