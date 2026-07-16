<?php 
include 'koneksi.php'; 

// Mengambil ID paket dari URL
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Query detail paket
$query = mysqli_query($conn, "SELECT * FROM tra_paket WHERE id_paket = '$id'");
$data = mysqli_fetch_array($query);

// Jika data tidak ditemukan
if (!$data) {
    echo "<script>alert('Paket tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['nama_paket']; ?> - Maluku Paradise Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .hero-detail {
            height: 50vh;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('img/<?= $data['gambar']; ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            color: white;
            padding-bottom: 30px;
        }
        .price-sticky {
            position: sticky;
            top: 20px;
        }
        .icon-box {
            width: 45px;
            height: 45px;
            background: #e7f1ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 15px;
        }
    </style>
</head>
<body>

<!-- Hero Section -->
<header class="hero-detail">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">Detail Paket</li>
            </ol>
        </nav>
        <h1 class="display-4 fw-bold"><?= $data['nama_paket']; ?></h1>
        <p class="lead"><i class="bi bi-geo-alt-fill me-2"></i><?= $data['destinasi']; ?></p>
    </div>
</header>

<main class="container my-5">
    <div class="row">
        <!-- Kolom Kiri: Konten -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="fw-bold mb-4">Deskripsi Perjalanan</h4>
                <p style="white-space: pre-line; line-height: 1.8;">
                    <?= $data['deskripsi']; ?>
                </p>
                
                <hr class="my-4">
                
                <h4 class="fw-bold mb-4">Detail Perjalanan</h4>
                <div class="row g-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="icon-box"><i class="bi bi-calendar-check fs-4"></i></div>
                        <div>
                            <small class="text-muted d-block">Tersedia Dari</small>
                            <strong><?= date('d M Y', strtotime($data['tgl_mulai'])); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="icon-box"><i class="bi bi-calendar-x fs-4"></i></div>
                        <div>
                            <small class="text-muted d-block">Hingga</small>
                            <strong><?= date('d M Y', strtotime($data['tgl_selesai'])); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="icon-box"><i class="bi bi-tags fs-4"></i></div>
                        <div>
                            <small class="text-muted d-block">Kategori</small>
                            <strong><?= isset($data['kategori']) ? $data['kategori'] : 'Eksplorasi Maluku'; ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="icon-box"><i class="bi bi-shield-check fs-4"></i></div>
                        <div>
                            <small class="text-muted d-block">Status</small>
                            <strong class="text-success">Available</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mengapa memilih kami? (Statis sesuai brief) -->
            <div class="card border-0 shadow-sm p-4 border-start border-primary border-4">
                <h5 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Informasi Tambahan</h5>
                <ul class="mb-0 mt-3">
                    <li>Fokus pada pariwisata ramah lingkungan dan bertanggung jawab.</li>
                    <li>Akses ke lokasi terpencil yang belum terjamah.</li>
                    <li>Pemandu lokal dengan pengetahuan mendalam tentang wilayah tersebut.</li>
                </ul>
            </div>
        </div>

        <!-- Kolom Kanan: Harga & CTA -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 price-sticky mt-4 mt-lg-0">
                <p class="text-muted mb-1">Mulai Dari</p>
                <h2 class="text-primary fw-bold mb-4">Rp <?= number_format($data['harga']); ?> <small class="text-muted fs-6 fw-normal">/org</small></h2>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Lama Perjalanan</span>
                        <strong>Customizable</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Min. Peserta</span>
                        <strong>1 Orang</strong>
                    </div>
                </div>

                <a href="booking.php?id=<?= $data['id_paket']; ?>" class="btn btn-primary btn-lg w-100 fw-bold py-3 shadow">
                    PESAN SEKARANG
                </a>
                
                <div class="text-center mt-3">
                    <p class="small text-muted mb-0">Butuh bantuan atau kustomisasi?</p>
                    <a href="https://wa.me/628123456789" class="text-success fw-bold text-decoration-none">
                        <i class="bi bi-whatsapp me-1"></i> Hubungi Kami via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; 2026 Maluku Paradise Travel - Gateway to the Spice Islands</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>