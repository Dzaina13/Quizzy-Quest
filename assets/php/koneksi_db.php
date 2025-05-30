<?php
$host = "localhost";
$user = "root";
$password = "13Juni2**5";
$database = "quizuas";

$koneksi = new mysqli($host, $user, $password, $database);

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
?>
