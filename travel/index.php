<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<link rel="manifest" href="manifest.json">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MPT Dashboard">
    <meta charset="UTF-8">
    <title>Maluku Paradise Travel - Discover Spice Islands</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php 
    $set = mysqli_fetch_array(mysqli_query($conn, "SELECT header_img FROM tra_settings WHERE id_setting=1"));
    ?>
    <style>
        .hero { 
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('img/<?= $set['header_img'] ?>'); 
            background-size: cover;
            background-position: center;
            height: 400px; color: white; display: flex; align-items: center; justify-content: center; text-align: center; 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="#">Maluku Paradise</a>
    <div class="navbar-nav ms-auto">
        <a class="nav-link" href="#packages">Tour Packages</a>
        <a class="nav-link btn btn-primary text-white ms-lg-3" href="admin.php">Admin Login</a>
    </div>
  </div>
</nav>

<div class="hero">
    <div>
        <h1>Maluku Paradise Travel</h1>
        <p>Beyond Bali: Authentic. Raw. Unforgettable.</p>
    </div>
</div>
<!-- Letakkan ini tepat di bawah div class="hero" -->
<section class="py-5 bg-white shadow-sm">
    <div class="container text-center" style="max-width: 800px;">
        <span class="text-primary text-uppercase fw-bold tracking-wider" style="font-size: 0.85rem; letter-spacing: 2px;">Welcome to the Spice Islands</span>
        <h2 class="fw-bold my-3 text-dark">The Authentic Alternative to Mainstream Tourism</h2>
        <p class="lead text-muted lh-lg fs-6">
            Maluku Paradise Travel is a travel service focused on showcasing the natural beauty, culture, and hidden gems of the Maluku Islands in eastern Indonesia—often called the “Spice Islands.” These tours are designed for travelers looking for a more authentic, less crowded alternative to mainstream destinations like Bali.
        </p>
    </div>
</section>
<div class="container my-5" id="packages">
    <h2 class="text-center mb-4">Explore Our Hidden Gems</h2>
<div class="row">
    <?php
    include 'koneksi.php';
    // Hanya mengambil paket yang tidak di-hide (is_active = 1)
    $query = mysqli_query($conn, "SELECT * FROM tra_paket WHERE is_active = 1 ORDER BY id_paket DESC");
    while($row = mysqli_fetch_array($query)) {
    ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <!-- GAMBAR MUNCUL DI SINI SEBELUM KLIK BOOK NOW -->
            <img src="img/<?= $row['gambar'] ?>" class="card-img-top" alt="<?= $row['nama_paket'] ?>" style="height: 220px; object-fit: cover;">
            
            <div class="card-body d-flex flex-column">
                <span class="badge bg-primary-subtle text-primary mb-2 align-self-start"><?= $row['kategori'] ?></span>
                <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_paket']) ?></h5>
                <p class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['destinasi']) ?></p>
                <p class="card-text text-secondary small text-truncate-3"><?= htmlspecialchars($row['deskripsi']) ?></p>
                
                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Mulai Dari</small>
                        <span class="fw-bold text-danger">Rp <?= number_format($row['harga']) ?></span>
                    </div>
                    <!-- Tombol Menuju Halaman Booking -->
                    <a href="booking.php?id=<?= $row['id_paket'] ?>" class="btn btn-primary btn-sm fw-semibold px-3">Book Now</a>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
</div>
<!-- Letakkan ini setelah Section List Paket Wisata -->
<section class="py-5 bg-light" id="why-choose-us">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Why Choose Maluku Paradise Travel?</h2>
            <p class="text-muted">Menjelajah lebih dalam dengan cara yang bertanggung jawab</p>
        </div>
        
        <div class="row g-4">
            <!-- Poin 1 -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="text-primary mb-3"><i class="bi bi-tree fs-1"></i></div>
                    <h5 class="fw-bold">Eco & Responsible</h5>
                    <p class="text-muted small mb-0">Focus on eco-friendly and responsible tourism to preserve nature.</p>
                </div>
            </div>
            <!-- Poin 2 -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="text-primary mb-3"><i class="bi bi-compass fs-1"></i></div>
                    <h5 class="fw-bold">Remote Locations</h5>
                    <p class="text-muted small mb-0">Exclusive access to remote, untouched locations away from crowds.</p>
                </div>
            </div>
            <!-- Poin 3 -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="text-primary mb-3"><i class="bi bi-people fs-1"></i></div>
                    <h5 class="fw-bold">Local Experts</h5>
                    <p class="text-muted small mb-0">Local guides with deep, authentic knowledge of the region.</p>
                </div>
            </div>
            <!-- Poin 4 -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="text-primary mb-3"><i class="bi bi-water fs-1"></i></div>
                    <h5 class="fw-bold">World-Class Diving</h5>
                    <p class="text-muted small mb-0">Opportunities for diving in pristine yet uncrowded marine sites.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Letakkan ini tepat sebelum tag <footer> -->
<section class="py-5 bg-dark text-white text-center position-relative overflow-hidden">
    <!-- Efek dekoratif samar latar belakang -->
    <div class="position-absolute top-50 start-50 translate-middle text-white opacity-5" style="font-size: 10rem; pointer-events: none;">
        <i class="bi bi-heart-fill"></i>
    </div>
    
    <div class="container position-relative" style="max-width: 750px;">
        <div class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
            What to Expect
        </div>
        <h3 class="fw-bold mb-3">The Real Petualangan Timur Experience</h3>
        <p class="text-white-50 lh-lg mb-0" style="font-size: 0.95rem;">
            Traveling with Maluku Paradise Travel is less about luxury resorts and more about immersive, raw natural beauty and cultural connection. Expect simple accommodations in some remote areas, but look forward to unforgettable scenery and truly unique experiences.
        </p>
    </div>
</section>
<footer class="bg-light py-4 text-center">
    <p>&copy; 2026 Maluku Paradise Travel - Gateway to the Spice Islands</p>
</footer>

</body>
</html>