<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Pengaturan Kredensial Database
define('DB_HOST', 'localhost');
define('DB_USER', 'u1775096_uinvit');
define('DB_PASS', 'Admin_local'); // Kosongkan jika menggunakan XAMPP bawaan
define('DB_NAME', 'u1775096_dinvit'); // <--- GANTI SESUAI NAMA DB KAMU

// 2. Membuat Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Validasi Koneksi
if ($conn->connect_error) {
    error_log("Koneksi gagal: " . $conn->connect_error);
    die("Maaf, terjadi gangguan pada sistem. Silakan hubungi admin.");
}

// 4. Set Charset
$conn->set_charset("utf8mb4");

// 5. Helper Proteksi Halaman
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}
?>