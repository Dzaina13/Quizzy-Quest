<?php
session_start();
include 'koneksi_db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $koneksi->prepare("SELECT user_id, username, password_hash, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: ../../pages/lobby.html");
        exit;
    }
}

header("Location: ../../pages/login.html?error=invalid_credentials");
exit;
?>
