<?php
$host = "localhost";
$user = "u1775096_um2"; // sesuaikan dengan username database Anda
$pass = "Admin_local";     // sesuaikan dengan password database Anda
$db   = "u1775096_m2"; // GANTI dengan nama database Anda

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>