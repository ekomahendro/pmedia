<?php
// db_connect.php

$servername = "localhost";
$username = "u1775096_um2"; // Ganti dengan user database Anda
$password = "Admin_local"; // Ganti dengan password database Anda
$dbname = "u1775096_m2"; // Ganti dengan nama database Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
// Set encoding agar data multi-bahasa aman
$conn->set_charset("utf8mb4");

// Fungsi untuk mengamankan input
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>