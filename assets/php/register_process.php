<?php
include 'koneksi_db.php';

$fullname = $_POST['fullname'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];

if ($password !== $confirmPassword) {
    header("Location: ../../pages/register.html?error=password_mismatch");
    exit;
}

$stmt = $koneksi->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: ../../pages/register.html?error=email_exists");
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$username = explode('@', $email)[0];

$stmt = $koneksi->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashedPassword);
$stmt->execute();

header("Location: ../../pages/login.html?success=registered");
exit;
?>
