<?php
session_start();
// Sertakan file konfigurasi dan koneksi database
include 'config/app_config.php'; 
include 'config/db_connect.php'; 

// Cek apakah pengguna sudah login
$is_logged_in = isset($_SESSION['user_id']);

// --- 1. Ambil Konten Dinamis (About Us & Kontak) ---

$about_content = [
    'title' => APP_NAME . ' - Selamat Datang',
    'content' => 'Restoran online terbaik untuk Anda.'
];
$contact_content_raw = 'Jl. Contoh Utama No. 123 | (021) 123-456 | email@resto.com';

try {
    // Ambil Konten About Us
    $stmt_about = $pdo->prepare("SELECT title, content FROM t_pages WHERE page_key = 'about_us'");
    $stmt_about->execute();
    $db_about = $stmt_about->fetch(PDO::FETCH_ASSOC);
    if ($db_about) {
        $about_content = $db_about;
    }

    // Ambil Konten Kontak
    $stmt_contact = $pdo->prepare("SELECT content FROM t_pages WHERE page_key = 'contact_info'");
    $stmt_contact->execute();
    $db_contact = $stmt_contact->fetch(PDO::FETCH_ASSOC);
    if ($db_contact) {
        $contact_content_raw = $db_contact['content'];
    }
} catch (PDOException $e) {
    // Opsional: Log error, tapi gunakan konten default
}

// Parse Kontak: Pisahkan Alamat, Telp, Email berdasarkan "|"
$contact_parts = array_map('trim', explode('|', $contact_content_raw));
$alamat = $contact_parts[0] ?? 'N/A';
$telepon = $contact_parts[1] ?? 'N/A';
$email = $contact_parts[2] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($about_content['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            /* Ganti path gambar Anda di sini */
            background: url('path/to/resto-background.jpg') no-repeat center center; 
            background-size: cover;
            height: 80vh; 
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .navbar-custom {
            background-color: #343a40; 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><?php echo APP_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="menu.php">Menu Kami</a>
                </li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-outline-light" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-warning text-dark me-2" href="signup.php">Daftar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-outline-light" href="login.php">Masuk</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section">
    <div class="container">
        <h1 class="display-3">Nikmati Sensasi Rasa Terbaik di Kota</h1>
        <p class="lead mb-4">Pesan makanan dan minuman favorit Anda secara online, cepat dan mudah!</p>
        <a href="menu.php" class="btn btn-warning btn-lg me-3 shadow-lg">
            🍽️ Lihat Menu Sekarang
        </a>
        <a href="#about" class="btn btn-outline-light btn-lg shadow-lg">
            Tentang Kami
        </a>
    </div>
</div>

<div class="container my-5">
    <h2 class="text-center mb-5 fw-bold">Kenapa Memesan di <?php echo APP_NAME; ?>?</h2>
    <div class="row text-center">
        <div class="col-md-4">
            <div class="p-3 border rounded shadow-sm h-100">
                <h3 class="text-primary">Fast Order</h3>
                <p>Proses pemesanan cepat. Cukup pilih, atur meja/takeaway, dan selesai!</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded shadow-sm h-100">
                <h3 class="text-success">Discounted Price</h3>
                <p>Nikmati harga diskon spesial yang langsung terlihat di menu.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded shadow-sm h-100">
                <h3 class="text-info">Realtime Status</h3>
                <p>Lacak status pesanan Anda (Pending, Received, Completed) secara langsung.</p>
            </div>
        </div>
    </div>
</div>

<hr>

<footer class="bg-light text-center text-lg-start mt-5">
    <div class="container p-4">
        <div class="row">
            <div class="col-lg-6 col-md-12 mb-4 mb-md-0" id="about">
                <h5 class="text-uppercase"><?php echo htmlspecialchars($about_content['title']); ?></h5>
                <p>
                    <?php 
                    // Tampilkan konten About Us dari database
                    echo nl2br(htmlspecialchars($about_content['content'])); 
                    ?>
                </p>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase">Link Cepat</h5>
                <ul class="list-unstyled mb-0">
                    <li><a href="menu.php" class="text-dark">Lihat Menu</a></li>
                    <li><a href="signup.php" class="text-dark">Daftar Akun</a></li>
                    <li><a href="cart.php" class="text-dark">Keranjang Saya</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase">Kontak</h5>
                <ul class="list-unstyled mb-0">
                    <li><a href="#!" class="text-dark"><?php echo htmlspecialchars($alamat); ?></a></li>
                    <li><a href="tel:<?php echo htmlspecialchars($telepon); ?>" class="text-dark"><?php echo htmlspecialchars($telepon); ?></a></li>
                    <li><a href="mailto:<?php echo htmlspecialchars($email); ?>" class="text-dark"><?php echo htmlspecialchars($email); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
        © 2025 <?php echo APP_NAME; ?>. All Rights Reserved.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>