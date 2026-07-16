<?php
include 'koneksi.php';
// Tampilkan semua error (opsional, untuk debugging)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fashion Ummi Ayna</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- CSS Fancybox untuk Fitur Zoom Gambar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

    <style>
        .product-item {
            margin-bottom: 30px;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        /* Mengatur kursor mouse menjadi telunjuk/kaca pembesar saat di-hover */
        .carousel-item img, .single-product-img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 4px;
            cursor: zoom-in;
            transition: opacity 0.2s ease;
        }
        .carousel-item img:hover, .single-product-img:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <header class="bg-primary text-white text-center py-3 mb-4">
        <h1>Fashion Ummi Ayna 💖</h1>
        <p class="lead">Koleksi Pakaian Terbaru</p>
        <a href="https://pmediaku.my.id" class="text-white">Web Developer</a><br>
    </header>

    <div class="container">
        <h2>Daftar Produk</h2>
        <hr>
        <div class="row">
            <?php
            // Ambil data produk
            $query = "SELECT id, judul, deskripsi, gambar FROM produkummi WHERE status='active' ORDER BY id DESC";
            $result = mysqli_query($koneksi, $query);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    
                    // Logika cek kompatibilitas data gambar
                    $gambar_raw = $row['gambar'];
                    $arr_gambar = [];

                    if (!empty($gambar_raw)) {
                        $decoded = json_decode($gambar_raw, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $arr_gambar = $decoded; // Format Baru
                        } else {
                            $arr_gambar = [$gambar_raw]; // Format Lama
                        }
                    }
                    
                    $carousel_id = "carouselProduk-" . $row['id'];
                    // Atribut data-gallery unik per produk agar saat di-zoom, user hanya menggeser foto milik produk tersebut
                    $gallery_group = "gallery-" . $row['id'];
            ?>
                <div class="col-12">
                    <div class="product-item">
                        <div class="row align-items-center">
                            
                            <!-- Bagian Kiri: Area Gambar / Slider -->
                            <div class="col-md-5 mb-3 mb-md-0">
                                <?php if (count($arr_gambar) > 1): ?>
                                    <!-- JIKA GAMBAR LEBIH DARI SATU: Slider + Fitur Zoom -->
                                    <div id="<?php echo $carousel_id; ?>" class="carousel slide carousel-dark" data-bs-ride="carousel">
                                        <div class="carousel-inner">
                                            <?php foreach ($arr_gambar as $index => $img): ?>
                                                <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                                                    <!-- Pembungkus tautan Fancybox -->
                                                    <a href="images/<?php echo htmlspecialchars($img); ?>" data-fancybox="<?php echo $gallery_group; ?>" data-caption="<?php echo htmlspecialchars($row['judul']); ?>">
                                                        <img src="images/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <!-- Navigasi Slider -->
                                        <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carousel_id; ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carousel_id; ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    </div>
                                <?php elseif (count($arr_gambar) === 1): ?>
                                    <!-- JIKA HANYA ADA 1 GAMBAR / DATA LAMA: Gambar Tunggal + Fitur Zoom -->
                                    <a href="images/<?php echo htmlspecialchars($arr_gambar[0]); ?>" data-fancybox="<?php echo $gallery_group; ?>" data-caption="<?php echo htmlspecialchars($row['judul']); ?>">
                                        <img src="images/<?php echo htmlspecialchars($arr_gambar[0]); ?>" 
                                             alt="<?php echo htmlspecialchars($row['judul']); ?>" 
                                             class="single-product-img img-fluid">
                                    </a>
                                <?php else: ?>
                                    <!-- JIKA TIDAK ADA GAMBAR -->
                                    <div class="bg-light border text-center d-flex align-items-center justify-content-center" style="height:350px; border-radius:4px;">
                                        <span class="text-muted">Tidak Ada Gambar</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Bagian Kanan: Informasi Detail Produk -->
                            <div class="col-md-7">
                                <h3 class="fw-bold"><?php echo htmlspecialchars($row['judul']); ?></h3>
                                <hr class="my-2">
                                <p style="text-align: justify; line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($row['deskripsi'])); ?>
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

            <?php
                }
            } else {
                echo "<div class='col-12'><p class='alert alert-warning'>Belum ada produk yang tersedia saat ini.</p></div>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- JS Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- JS Fancybox untuk Mengaktifkan Zoom Gambar -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        // Inisialisasi Fancybox untuk mendeteksi semua elemen dengan atribut data-fancybox
        Fancybox.bind("[data-fancybox]", {
            // Pengaturan opsional: menambahkan efek animasi transisi halus
            Carousel: {
                Navigation: true
            }
        });
    </script>
</body>
</html>