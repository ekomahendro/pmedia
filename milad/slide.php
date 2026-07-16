<?php
// =========================================================================
// PANDUAN LINK DIRECT GOOGLE DRIVE:
// Agar gambar & video bisa diputar langsung di slide, gunakan format link:
// Gambar: https://drive.google.com/uc?export=view&id=ID_FILE_DRIVE
// Video : https://drive.google.com/uc?export=download&id=ID_FILE_DRIVE
// =========================================================================

$media_slides = [
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1Md0JckF1VfSvsK53rdJG6WNvY1ZZHbCn',
        'caption' => 'Dokumentasi Milad Ummi XV - 1'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1qj8IKtkprPMonKQpTjTNn1uY6_ckXkA8',
        'caption' => 'Dokumentasi Milad Ummi XV - 2'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/17mQVeJ-mzXPmxKLHJEd3xyARfRU5cQ91',
        'caption' => 'Dokumentasi Milad Ummi XV - 3'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1dPdzEol_djzXO2atO-_B8QmEPT0n0rVn',
        'caption' => 'Dokumentasi Milad Ummi XV - 4'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1p5o_0cM4VlnRgCnQl5SX0hrsSz2oNxbg',
        'caption' => 'Dokumentasi Milad Ummi XV - 5'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1RbnAyvSUaDLdGaeD-K6Ua4Isvac10sFE',
        'caption' => 'Dokumentasi Milad Ummi XV - 6'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1CuueFxO5oufzPzEQJGAHg1pa1WrRz6W3',
        'caption' => 'Dokumentasi Milad Ummi XV - 7'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1_g5UI2ltwy6BDl9qJdcb0CJ_Eu1dNneS',
        'caption' => 'Dokumentasi Milad Ummi XV - 8'
    ],
    [
        'type' => 'image', 
        'src'  => 'https://lh3.googleusercontent.com/u/0/d/1IxVrok5R7KlHzGSnVgELUx8t8VHyNX_k',
        'caption' => 'Dokumentasi Milad Ummi XV - 9'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slide Dokumentasi Milad Ummi XV</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #0f1015;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
        }
        
        /* Header Styling */
        .slide-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-bottom: 4px solid #ffc107;
            padding: 12px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }
        .logo-img {
            max-height: 55px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.3));
        }

        /* Main Slideshow */
        .content-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        
        .carousel-box {
            width: 100%;
            max-width: 950px;
            background-color: #000;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border: 2px solid #2d3035;
        }

        .carousel-item {
            height: 55vh; /* Sempurna untuk layar TV / Proyektor */
            min-height: 380px;
            background: #000;
        }

        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Memastikan foto tidak terpotong */
        }

        .carousel-item video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Caption overlay styling */
        .custom-caption {
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            padding: 10px 20px;
            bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        /* Footer Styling */
        .slide-footer {
            background-color: #16171d;
            border-top: 3px solid #22252a;
            padding: 12px 0;
        }
        .footer-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ffc107;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .badge-lomba {
            font-size: 0.9rem;
            padding: 6px 14px;
            margin: 3px;
            background-color: #22252a;
            color: #e0e0e0;
            border: 1px solid #32373f;
            border-radius: 30px;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .badge-lomba:hover {
            background-color: #ffc107;
            color: #000;
            border-color: #ffc107;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<!-- HEADER SECTION -->
<header class="slide-header">
    <div class="container d-flex align-items-center justify-content-center gap-3">
        <img src="https://cdn-icons-png.flaticon.com/512/5654/5654215.png" alt="Logo Milad" class="logo-img">
        <div>
            <h2 class="fw-bold mb-0 text-white tracking-wide" style="font-size: 1.65rem;">GEBYAR MILAD XV MT MUALLAF UMI TAUFIQIYAH</h2>
            <p class="mb-0 text-white-50 small" style="font-size: 0.8rem;"><i class="bi bi-play-btn-fill text-warning me-1"></i> Autoplay Slideshow Dokumentasi</p>
        </div>
    </div>
</header>

<!-- AUTOPLAY SLIDESHOW -->
<main class="content-container container">
    <div class="carousel-box">
        
        <!-- Bootstrap Carousel: interval="4000" (ganti slide setiap 4 detik secara otomatis) -->
        <div id="miladCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
            
            <!-- Indicators -->
            <div class="carousel-indicators">
                <?php foreach ($media_slides as $index => $slide): ?>
                    <button type="button" data-bs-target="#miladCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
            </div>

            <!-- Slides Wrapper -->
            <div class="carousel-inner">
                <?php foreach ($media_slides as $index => $slide): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        
                        <?php if ($slide['type'] === 'image'): ?>
                            <!-- Render Gambar -->
                            <img src="<?= htmlspecialchars($slide['src']) ?>" alt="Slide <?= $index + 1 ?>">
                        
                        <?php elseif ($slide['type'] === 'video'): ?>
                            <!-- Render Video dengan autoplay bawaan, mute untuk kelancaran browser, dan loop -->
                            <video class="slide-video" src="<?= htmlspecialchars($slide['src']) ?>" autoplay loop muted playsinline></video>
                        <?php endif; ?>

                        <!-- Keterangan Slide (Optional) -->
                        <?php if (!empty($slide['caption'])): ?>
                            <div class="carousel-caption d-none d-md-block custom-caption text-start shadow-sm">
                                <p class="mb-0 fw-bold"><i class="bi bi-info-circle text-warning me-1"></i> <?= htmlspecialchars($slide['caption']) ?></p>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Tombol Kontrol Manual -->
            <button class="carousel-control-prev" type="button" data-bs-target="#miladCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#miladCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

        </div>

    </div>
</main>

<!-- FOOTER SECTION (Cabang Lomba) -->
<footer class="slide-footer text-center">
    <div class="container">
        <div class="footer-title">
            <i class="bi bi-trophy-fill text-warning me-1"></i> Cabang Lomba Yang Dipertandingkan
        </div>
        <div class="d-flex flex-wrap justify-content-center align-items-center">
            <span class="badge-lomba shadow-sm">Cerdas Cermat</span>
            <span class="badge-lomba shadow-sm">Metode Jilid 1</span>
            <span class="badge-lomba shadow-sm">Jilid 2</span>
            <span class="badge-lomba shadow-sm">Jilid 3</span>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Logic Tambahan: Jika slide aktif adalah "video", matikan auto-slide sementara agar video selesai diputar.
    // Setelah video selesai (atau berpindah), jalankan kembali sirkulasi otomatisnya.
    const myCarousel = document.getElementById('miladCarousel');
    const carouselInstance = bootstrap.Carousel.getOrCreateInstance(myCarousel);

    myCarousel.addEventListener('slide.bs.carousel', function (event) {
        // Hentikan semua video yang tidak sedang aktif agar hemat bandwidth & suara tidak tabrakan
        document.querySelectorAll('.slide-video').forEach(video => {
            video.pause();
        });

        // Deteksi jika slide tujuan/selanjutnya berisi video
        const nextSlide = event.relatedTarget;
        const videoInNextSlide = nextSlide.querySelector('.slide-video');

        if (videoInNextSlide) {
            // Hentikan transisi slide otomatis sementara
            carouselInstance.pause();
            
            // Putar video dari awal
            videoInNextSlide.currentTime = 0;
            videoInNextSlide.play();

            // Jalankan kembali slide setelah video selesai diputar (ended)
            videoInNextSlide.onended = function() {
                carouselInstance.next();
                carouselInstance.cycle();
            };
        } else {
            // Mulai siklus slide normal kembali jika slide berikutnya berupa gambar
            carouselInstance.cycle();
        }
    });
</script>

</body>
</html>