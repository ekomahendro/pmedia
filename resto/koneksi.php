<?php
$servername = "localhost";
$username = "u1775096_umart"; // Ganti dengan username MySQL Anda
$password = "Admin_local";     // Ganti dengan password MySQL Anda
$dbname = "u1775096_dmart"; // Ganti dengan nama database Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
// echo "Koneksi berhasil"; // Hapus baris ini setelah pengujian
?>