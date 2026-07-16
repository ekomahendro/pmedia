<?php
$host = 'localhost';
$user = 'u1775096_uinvit';
$pass = 'Admin_local';
$dbname = 'u1775096_dinvit';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>