<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil World Cup 2026</title>
    <!-- Menggunakan Bootstrap 5 dari CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet font-weight">
    <style>
        body { 
            /* Menggunakan latar belakang gambar stadion sepak bola bernuansa hijau piala dunia */
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=1920') no-repeat center center fixed; 
            background-size: cover;
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card { 
            border: 2px solid #d4af37; /* Aksen warna emas piala dunia */
            box-shadow: 0 15px 35px rgba(0,0,0,0.5); 
            border-radius: 20px; 
            width: 100%; 
            max-width: 420px; 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); /* Gradasi biru khas FIFA/sporty */
            color: #ffffff;
            overflow: hidden;
        }
        /* Elemen dekoratif atas kartu */
        .card-header-deco {
            background: linear-gradient(90deg, #34a853, #fbbc05, #ea4335); /* Representasi warna tuan rumah (USA, Mexico, Canada) */
            height: 6px;
            width: 100%;
        }
        .card-title {
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #f39c12; /* Warna emas cerah */
            text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
        }
        .btn-worldcup { 
            background: linear-gradient(135deg, #107c41 0%, #1f9a55 100%); /* Hijau rumput stadion */
            border: 2px solid #ffffff;
            border-radius: 12px; 
            padding: 14px; 
            font-weight: 700; 
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        .btn-worldcup:hover {
            background: linear-gradient(135deg, #1f9a55 0%, #28b463 100%);
            color: #ffffff;
            border-color: #f39c12;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }
        /* Ikon dekorasi bola */
        .soccer-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            animation: spin 20s linear infinite;
        }
        @keyframes spin { 100% { transform:rotate(360deg); } }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card">
        <div class="card-header-deco"></div>
        <div class="p-4 text-center">
            <!-- Dekorasi Emoji Bola -->
            <div class="soccer-icon">⚽</div>
            
            <!-- Judul yang telah diubah sesuai request -->
            <h3 class="card-title mb-4">Hasil World Cup 2026</h3>
            
            <!-- Link langsung diletakkan di href tanpa teks deskripsi bawahnya -->
            <a href="https://docs.google.com/spreadsheets/d/1fyrLlejoatV3GSFjqGPFs7vLV_ijGBxG/edit?usp=sharing&ouid=117808823630116861598&rtpof=true&sd=true" class="btn btn-worldcup w-100 d-flex align-items-center justify-content-center gap-2">
                <span>Mulai Akses</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-trophy-fill" viewBox="0 0 16 16">
                    <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.319.241.25.434-.07.193-.24.31-.435.255l-2.11-.528-2.11.528c-.194.055-.365-.062-.435-.255-.069-.193.056-.386.25-.434l1.425-.356V10.94c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33 33 0 0 1 2.5.5m.518 1.536a2 2 0 1 0 .439 3.808 33 33 0 0 1 .15-3.321M12.54 5.844a2 2 0 1 0 .438-3.808 33 33 0 0 1 .15 3.321"/>
                </svg>
            </a>
        </div>
    </div>
</div>

</body>
</html>