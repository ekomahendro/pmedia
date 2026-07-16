<?php 
include 'koneksi.php'; 
$id = $_GET['id'];
$paket = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM tra_paket WHERE id_paket=$id"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking <?= $paket['nama_paket'] ?> - Maluku Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .booking-container { max-width: 900px; margin: 50px auto; }
        .card-booking { border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .booking-img { background: url('img/<?= $paket['gambar'] ?>') center/cover; min-height: 100%; }
        .btn-book { background-color: #0d6efd; border: none; padding: 12px; font-weight: 600; transition: 0.3s; }
        .btn-book:hover { background-color: #0b5ed7; transform: translateY(-2px); }
        .info-tag { font-size: 0.8rem; font-weight: bold; color: #0d6efd; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="container booking-container">
    <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none text-muted">← Kembali ke Jelajah Paket</a>
    </div>
    
    <div class="card card-booking">
        <div class="row g-0">
            <div class="col-md-5 d-none d-md-block booking-img">
                <!-- Gambar Latar Belakang via CSS -->
            </div>
            <div class="col-md-7 bg-white p-4 p-lg-5">
                <span class="info-tag">Secure Your Spot</span>
                <h2 class="fw-bold mb-1"><?= $paket['nama_paket'] ?></h2>
                <p class="text-muted small mb-4"><?= $paket['destinasi'] ?></p>
                
                <form action="proses_booking.php" method="POST">
                    <input type="hidden" name="id_paket" value="<?= $id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="John Doe" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nomor WhatsApp</label>
                            <input type="text" name="no_telp" class="form-control" placeholder="08123456789" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tgl Keberangkatan</label>
                            <input type="date" name="tgl" class="form-control" min="<?= $paket['tgl_mulai'] ?>" max="<?= $paket['tgl_selesai'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Jumlah Peserta</label>
                            <div class="input-group">
                                <input type="number" name="jml" class="form-control" value="1" min="1" required>
                                <span class="input-group-text text-muted">Orang</span>
                            </div>
                        </div>
                    </div>

                    <!-- INPUT KETERANGAN BARU -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Keterangan Tambahan <span class="text-muted small fw-normal">(Opsional)</span></label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tuliskan catatan khusus (misal: permintaan vegetarian, ukuran kaos, jemput di bandara, dll)..."></textarea>
                    </div>

                    <div class="p-3 bg-light rounded mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Estimasi Harga Per Orang</span>
                            <span class="fw-bold text-dark">Rp <?= number_format($paket['harga']) ?></span>
                        </div>
                    </div>

                    <button type="submit" name="book" class="btn btn-primary btn-book w-100">KONFIRMASI PEMESANAN</button>
                    <p class="text-center text-muted mt-3 small">Admin kami akan menghubungi Anda via WhatsApp setelah konfirmasi.</p>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>