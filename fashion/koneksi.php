<?php
$host = "localhost"; // Sesuaikan jika berbeda
$user = "u1775096_umart";      // Ganti dengan username database Anda
$pass = "Admin_local";          // Ganti dengan password database Anda
$db   = "u1775096_dmart"; // Ganti dengan nama database Anda

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>