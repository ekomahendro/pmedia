<?php
session_start();

// Periksa apakah user sudah login
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Jika sudah login, redirect langsung ke dashboard
    header("location: dashboard.php");
    exit;
} else {
    // Jika belum login, redirect ke halaman login
    header("location: login.php");
    exit;
}

// Catatan: Jika Anda ingin memiliki halaman landing page yang berisi informasi
// umum sebelum login, Anda bisa menempatkan HTML Bootstrap di sini
// dan menambahkan link ke login.php. Namun, untuk aplikasi internal,
// redirect langsung ke login adalah praktik yang umum.
?>