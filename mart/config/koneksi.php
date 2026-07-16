<?php
// Pengaturan Koneksi Database
$host     = "localhost";
$user     = "u1775096_umart"; // Ganti dengan user database Anda
$password = "Admin_local";     // Ganti dengan password database Anda
$database = "u1775096_dmart";

// Buat Koneksi
$koneksi = mysqli_connect($host, $user, $password, $database);

// Cek Koneksi
if (mysqli_connect_errno()){
    echo "Koneksi database gagal : " . mysqli_connect_error();
    die(); // Hentikan script jika gagal koneksi
}

// Set Timezone
date_default_timezone_set('Asia/Makassar'); // Sesuaikan dengan lokasi toko
?>