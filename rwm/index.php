<?php
session_start();

// Cek apakah session user_id sudah ada
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, arahkan ke dashboard
    header("Location: pages/dashboard.php");
    exit;
} else {
    // Jika belum login, arahkan ke halaman login
    header("Location: auth/login.php");
    exit;
}
?>