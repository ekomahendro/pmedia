<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u1775096_uinvit'); // Ganti dengan username database Anda
define('DB_PASSWORD', 'Admin_local');     // Ganti dengan password database Anda
define('DB_NAME', 'u1775096_dinvit'); // Nama database Anda
define('PASSWORD_ADMIN', 'Admin7b'); // Password admin

// Membuat koneksi
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if($link === false){
    die("ERROR: Tidak dapat terhubung. " . mysqli_connect_error());
}
?>