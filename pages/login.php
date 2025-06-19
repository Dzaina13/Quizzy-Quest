<?php
require_once '../assets/php/koneksi_db.php';
require_once '../assets/php/user_handler.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'Anda telah berhasil logout';
}

// Handle error messages from URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error = 'Email atau password salah!';
            break;
        case 'empty_fields':
            $error = 'Mohon isi semua field!';
            break;
        case 'invalid_email':
            $error = 'Format email tidak valid!';
            break;
        case 'database_error':
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            break;
        case 'invalid_request':
            $error = 'Request tidak valid!';
            break;
        case 'account_locked':
            $error = 'Akun Anda terkunci sementara karena terlalu banyak percobaan login yang gagal. Silakan coba lagi nanti.';
            break;
        default:
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}

// Konfigurasi halaman
$page_title = "Quizzy Quest - Login";
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
    <style>
        .error-message {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .error-message::before {
            content: "⚠️";
            margin-right: 8px;
        }
        
        .success-message {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .success-message::before {
            content: "✅";
            margin-right: 8px;
        }
        
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading .auth-button {
            position: relative;
        }
        
        .loading .auth-button::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            font-size: 16px;
        }
        
        .password-toggle-btn:hover {
            color: #374151;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background-color: #f3f4f6;
            transform: translateY(-2px);
        }

        .social-icon img {
            width: 20px;
            height: 20px;
        }

        .forgot-password-link {
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password-link:hover {
            text-decoration: underline;
        }

        .signup-text {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .signup-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link:hover {
            text-decoration: underline;
        }
    </style>
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
                <a href="#" class="social-icon" onclick="handleSocialLogin('google')">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
                </a>
                <a href="#" class="social-icon" onclick="handleSocialLogin('facebook')">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form - Submit to login_process.php -->
            <form method="POST" action="../assets/php/login_process.php" class="auth-form" id="loginForm">
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="Email" 
                       required 
                       class="input-field"
                       value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>"
                       autocomplete="email">
                
                <div class="password-toggle">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Password" 
                           required 
                           class="input-field"
                           autocomplete="current-password">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                        👁️
                    </button>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0;">
                    <label style="display: flex; align-items: center; font-size: 14px; color: #6b7280;">
                        <input type="checkbox" name="remember" style="margin-right: 8px;">
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-password-link">Forgot your password?</a>
                </div>
                
                <button type="submit" class="auth-button sign-in-button" id="loginBtn">
                    SIGN IN
                </button>
            </form>
            
            <p class="signup-text">
                Don't have an account? 
                <a href="register.php" class="signup-link">Sign Up</a>
            </p>
        </div>
    </div>

    <script>
        let isPasswordVisible = false;
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle-btn');
            
            if (isPasswordVisible) {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
                isPasswordVisible = false;
            } else {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
                isPasswordVisible = true;
            }
        }
        
        function handleSocialLogin(provider) {
            alert(`Social login dengan ${provider} belum tersedia. Fitur ini akan segera hadir!`);
        }
        
        // Form validation and submission
        document.getElementById("loginForm").addEventListener("submit", function(e) {
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
            const loginBtn = document.getElementById("loginBtn");
            const form = this;
            
            // Basic validation
            if (!email || !password) {
                e.preventDefault();
                showError("Mohon isi semua field yang diperlukan!");
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showError("Format email tidak valid!");
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showError("Password minimal 6 karakter!");
                return false;
            }
            
            // Show loading state
            form.classList.add('loading');
            loginBtn.textContent = 'Signing In...';
            loginBtn.disabled = true;
            
            // Remove loading state after 10 seconds (fallback)
            setTimeout(() => {
                form.classList.remove('loading');
                loginBtn.textContent = 'SIGN IN';
                loginBtn.disabled = false;
            }, 10000);
        });
        
        function showError(message) {
            // Remove existing error messages
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Create new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            
            // Insert before form
            const form = document.querySelector('.auth-form');
            form.parentNode.insertBefore(errorDiv, form);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
        
        // Auto-hide success/error messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.error-message, .success-message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Focus on email field when page loads
        window.addEventListener('load', () => {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
