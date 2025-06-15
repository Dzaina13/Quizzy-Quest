// assets/js/register.js

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthDiv = document.getElementById('password-strength');
    const strengthText = document.getElementById('strength-text');
    const strengthFeedback = document.getElementById('strength-feedback');
    
    if (password.length === 0) {
        strengthDiv.style.display = 'none';
        return;
    }
    
    strengthDiv.style.display = 'block';
    
    const length = password.length;
    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[^a-zA-Z0-9]/.test(password);
    
    let strength = 0;
    if (length >= 6) strength++;
    if (hasLower) strength++;
    if (hasUpper) strength++;
    if (hasNumber) strength++;
    if (hasSpecial) strength++;
    
    let strengthClass = '';
    let strengthLabel = '';
    
    if (strength < 2) {
        strengthLabel = 'Lemah';
        strengthClass = 'weak';
    } else if (strength < 4) {
        strengthLabel = 'Sedang';
        strengthClass = 'medium';
    } else {
        strengthLabel = 'Kuat';
        strengthClass = 'strong';
    }
    
    strengthText.textContent = 'Password: ' + strengthLabel;
    strengthText.className = 'strength-' + strengthClass;
    
    if (length < 6) {
        strengthFeedback.textContent = 'Perlu: minimal 6 karakter';
        strengthFeedback.style.display = 'block';
    } else {
        strengthFeedback.style.display = 'none';
    }
}

// Password confirmation checker
function checkPasswordConfirmation() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const confirmField = document.getElementById('confirmPassword');
    const feedback = document.getElementById('confirm-feedback');
    
    if (confirmPassword.length === 0) {
        feedback.style.display = 'none';
        confirmField.classList.remove('password-match', 'password-mismatch');
        return;
    }
    
    feedback.style.display = 'block';
    
    if (password === confirmPassword) {
        confirmField.classList.remove('password-mismatch');
        confirmField.classList.add('password-match');
        feedback.textContent = 'Password cocok';
        feedback.className = 'password-feedback match';
    } else {
        confirmField.classList.remove('password-match');
        confirmField.classList.add('password-mismatch');
        feedback.textContent = 'Password tidak cocok';
        feedback.className = 'password-feedback mismatch';
    }
}

// Form validation
function validateForm(event) {
    const fullname = document.getElementById('fullname').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Reset previous error styling
    document.querySelectorAll('.input-field').forEach(field => {
        field.classList.remove('error');
    });
    
    let hasError = false;
    
    // Validate fullname
    if (fullname === '') {
        document.getElementById('fullname').classList.add('error');
        hasError = true;
    }
    
    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === '' || !emailRegex.test(email)) {
        document.getElementById('email').classList.add('error');
        hasError = true;
    }
    
    // Validate password
    if (password.length < 6) {
        document.getElementById('password').classList.add('error');
        hasError = true;
    }
    
    // Validate password confirmation
    if (password !== confirmPassword) {
        document.getElementById('confirmPassword').classList.add('error');
        hasError = true;
    }
    
    if (hasError) {
        event.preventDefault();
        showErrorMessage('Mohon periksa kembali data yang Anda masukkan.');
        return false;
    }
    
    return true;
}

// Show error message
function showErrorMessage(message) {
    // Remove existing error message if any
    const existingError = document.querySelector('.js-error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Create new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message js-error-message';
    errorDiv.textContent = message;
    
    // Insert after title
    const title = document.querySelector('.auth-title');
    title.parentNode.insertBefore(errorDiv, title.nextSibling);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Real-time email validation
function validateEmail() {
    const email = document.getElementById('email').value.trim();
    const emailField = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email === '') {
        emailField.classList.remove('valid', 'invalid');
        return;
    }
    
    if (emailRegex.test(email)) {
        emailField.classList.remove('invalid');
        emailField.classList.add('valid');
    } else {
        emailField.classList.remove('valid');
        emailField.classList.add('invalid');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmPassword');
    const emailField = document.getElementById('email');
    const form = document.querySelector('.auth-form');
    
    if (passwordField) {
        passwordField.addEventListener('input', checkPasswordStrength);
        passwordField.addEventListener('input', checkPasswordConfirmation);
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', checkPasswordConfirmation);
    }
    
    if (emailField) {
        emailField.addEventListener('input', validateEmail);
        emailField.addEventListener('blur', validateEmail);
    }
    
    if (form) {
        form.addEventListener('submit', validateForm);
    }
    
    // Auto-focus first empty field
    const fields = ['fullname', 'email', 'password', 'confirmPassword'];
    for (let fieldId of fields) {
        const field = document.getElementById(fieldId);
        if (field && field.value.trim() === '') {
            field.focus();
            break;
        }
    }
});

// Utility function to clear all form data
function clearForm() {
    if (confirm('Apakah Anda yakin ingin menghapus semua data?')) {
        document.querySelectorAll('.input-field').forEach(field => {
            field.value = '';
            field.classList.remove('error', 'valid', 'invalid', 'password-match', 'password-mismatch');
        });
        
        // Hide all feedback elements
        document.querySelectorAll('.password-strength, .password-feedback').forEach(element => {
            element.style.display = 'none';
        });
        
        // Focus first field
        document.getElementById('fullname').focus();
    }
}
