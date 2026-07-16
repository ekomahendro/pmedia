<?php
// Pengaturan Koneksi Database
$host = 'localhost';
$db   = 'u1775096_m2'; // Ganti dengan nama database Anda
$user = 'u1775096_um2'; // Ganti dengan username database Anda
$pass = 'Admin_local'; // Ganti dengan password database Anda

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     // echo "Koneksi berhasil!"; // Hapus baris ini setelah pengujian
} catch (PDOException $e) {
     // Tampilkan error hanya di lingkungan development
     die("Koneksi gagal: " . $e->getMessage()); 
}
?>