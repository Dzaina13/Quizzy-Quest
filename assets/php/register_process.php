<?php
session_start();
include 'koneksi_db.php';

// Validasi method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/register.php?error=invalid_request");
    exit();
}

// Ambil dan validasi input
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

// Validasi field kosong
if (empty($fullname) || empty($email) || empty($password) || empty($confirmPassword)) {
    header("Location: ../../pages/register.php?error=empty_fields&fullname=" . urlencode($fullname) . "&email=" . urlencode($email));
    exit();
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../pages/register.php?error=invalid_email&fullname=" . urlencode($fullname) . "&email=" . urlencode($email));
    exit();
}

// Validasi panjang password
if (strlen($password) < 6) {
    header("Location: ../../pages/register.php?error=password_too_short&fullname=" . urlencode($fullname) . "&email=" . urlencode($email));
    exit();
}

// Validasi konfirmasi password
if ($password !== $confirmPassword) {
    header("Location: ../../pages/register.php?error=password_mismatch&fullname=" . urlencode($fullname) . "&email=" . urlencode($email));
    exit();
}

try {
    // Cek apakah email sudah terdaftar
    $stmt = $koneksi->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: ../../pages/register.php?error=email_exists&fullname=" . urlencode($fullname));
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate username dari email atau fullname
    $username = strtolower(str_replace(' ', '_', $fullname)); // Atau bisa dari email: explode('@', $email)[0]
    
    // Cek apakah username sudah ada, jika ya tambahkan angka
    $originalUsername = $username;
    $counter = 1;
    
    do {
        $stmt = $koneksi->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $username = $originalUsername . '_' . $counter;
            $counter++;
        } else {
            break;
        }
    } while (true);
    
    // Set default role (sesuaikan dengan kebutuhan)
    $role = 'participant'; // atau 'student', 'member', dll sesuai sistem Anda
    
    // Insert user baru - sesuaikan dengan struktur tabel
    $stmt = $koneksi->prepare("INSERT INTO users (username, password_hash, email, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $hashedPassword, $email, $role);
    
    if ($stmt->execute()) {
        // Simpan informasi fullname di session atau tabel terpisah jika diperlukan
        $_SESSION['temp_fullname'] = $fullname;
        
        header("Location: ../../pages/login.php?success=registered");
        exit();
    } else {
        header("Location: ../../pages/register.php?error=registration_failed&fullname=" . urlencode($fullname) . "&email=" . urlencode($email));
        exit();
    }
    
} catch (Exception $e) {
    // Log error untuk debugging (jangan tampilkan ke user)
    error_log("Registration error: " . $e->getMessage());
    header("Location: ../../pages/register.php?error=database_error&fullname=" . urlencode($fullname) . "&email=" . urlencode($email));
    exit();
}
?>
