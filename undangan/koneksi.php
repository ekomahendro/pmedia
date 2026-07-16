<?php
$host = "localhost";
$user = "u1775096_um2";
$pass = "Admin_local";
$db   = "u1775096_m2";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>