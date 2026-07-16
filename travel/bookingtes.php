<?php 
include 'koneksi.php'; 
$id = intval($_GET['id']);

// Mengambil data paket
$query_paket = mysqli_query($conn, "SELECT * FROM tra_paket WHERE id_paket=$id");
$paket = mysqli_fetch_array($query_paket);

if (!$paket) {
    echo "<script>alert('Paket tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Tentukan kuota maksimal bawaan jika kolom kuota belum dibuat di tabel Anda
$kuota_maksimal = isset($paket['kuota_maksimal']) ? $paket['kuota_maksimal'] : 15; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking <?= htmlspecialchars($paket['nama_paket']) ?> - Maluku Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/font-awesome.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .booking-container { max-width: 900px; margin: 50px auto; }
        .card-booking { border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .booking-img { background: url('img/<?= $paket['gambar'] ?>') center/cover; min-height: 100%; }
        .btn-book { background-color: #0d6efd; border: none; padding: 12px; font-weight: 600; transition: 0.3s; }
        .btn-book:hover { background-color: #0b5ed7; transform: translateY(-2px); }
        .info-tag { font-size: 0.8rem; font-weight: bold; color: #0d6efd; text-transform: uppercase; letter-spacing: 1px; }
        .status-kuota { font-size: 0.9rem; font-weight: 600; }
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
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($paket['nama_paket']) ?></h2>
                <p class="text-muted small mb-4"><?= htmlspecialchars($paket['destinasi']) ?></p>
                
                <form action="proses_booking.php" method="POST" id="formBooking">
                    <input type="hidden" name="id_paket" id="idPaket" value="<?= $id ?>">
                    <input type="hidden" id="maxKuota" value="<?= $kuota_maksimal ?>">
                    
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
                        <!-- LIVE AVAILABILITY CALENDAR LAYER -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tgl Keberangkatan</label>
                            <input type="date" name="tgl" id="tglKeberangkatan" class="form-control" min="<?= $paket['tgl_mulai'] ?>" max="<?= $paket['tgl_selesai'] ?>" required>
                            <!-- Tempat render status sisa sisa kuota otomatis -->
                            <div id="kuotaFeedback" class="mt-1 small"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Jumlah Peserta</label>
                            <div class="input-group">
                                <input type="number" name="jml" id="jmlPeserta" class="form-control" value="1" min="1" required>
                                <span class="input-group-text text-muted">Orang</span>
                            </div>
                        </div>
                    </div>

                    <!-- INPUT MANIFEST SYSTEM DATA (Keterangan Logistik Lapangan) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Data Manifes & Catatan Khusus <span class="text-muted small fw-normal">(Opsional)</span></label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh manifest: 
1. John Doe (L) - Ukuran Kaos L - No Alergi
2. Jane Doe (P) - Ukuran Kaos M - Vegetarian / Penjemputan Bandara"></textarea>
                    </div>

                    <div class="p-3 bg-light rounded mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Estimasi Harga Per Orang</span>
                            <span class="fw-bold text-dark">Rp <?= number_format($paket['harga']) ?></span>
                        </div>
                    </div>

                    <button type="submit" name="book" id="btnSubmitBooking" class="btn btn-primary btn-book w-100">KONFIRMASI PEMESANAN</button>
                    <p class="text-center text-muted mt-3 small">Admin kami akan menghubungi Anda via WhatsApp setelah konfirmasi.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LIVE AVAILABILITY ENGINE -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const tglInput = document.getElementById("tglKeberangkatan");
    const jmlInput = document.getElementById("jmlPeserta");
    const feedback = document.getElementById("kuotaFeedback");
    const btnSubmit = document.getElementById("btnSubmitBooking");
    const idPaket = document.getElementById("idPaket").value;
    
    let sisaKuotaGlobal = null;

    tglInput.addEventListener("change", function() {
        const tanggalTercetak = this.value;
        if (!tanggalTercetak) return;

        feedback.innerHTML = '<span class="text-muted"><div class="spinner-border spinner-border-sm me-1" role="status"></div> Memeriksa sisa kuota...</span>';
        btnSubmit.disabled = true;

        // Fetch data sisa kuota ke file backend khusus (cek_kuota.php)
        fetch(`cek_kuota.php?id_paket=${idPaket}&tanggal=${tanggalTercetak}`)
            .then(response => response.json())
            .then(data => {
                sisaKuotaGlobal = data.sisa_kuota;
                
                if (sisaKuotaGlobal <= 0) {
                    feedback.innerHTML = `<span class="text-danger status-kuota">❌ Maaf, Kuota Habis untuk tanggal ini!</span>`;
                    btnSubmit.disabled = true;
                } else {
                    feedback.innerHTML = `<span class="text-success status-kuota">✅ Tersedia (Sisa ${sisaKuotaGlobal} slot kursi)</span>`;
                    validasiJumlahPeserta();
                }
            })
            .catch(error => {
                console.error("Error:", error);
                feedback.innerHTML = '<span class="text-danger">Gagal memverifikasi kuota perkiraan.</span>';
                btnSubmit.disabled = false;
            });
    });

    // Validasi langsung agar jumlah input orang tidak melebihi sisa slot kuota yang ada
    jmlInput.addEventListener("input", validasiJumlahPeserta);

    function validasiJumlahPeserta() {
        if (sisaKuotaGlobal !== null && tglInput.value !== "") {
            const jmlMendaftar = parseInt(jmlInput.value) || 0;
            if (jmlMendaftar > sisaKuotaGlobal) {
                feedback.innerHTML = `<span class="text-danger status-kuota">⚠️ Jumlah pendaftar (${jmlMendaftar} orang) melebihi sisa kuota (${sisaKuotaGlobal} kursi)!</span>`;
                btnSubmit.disabled = true;
            } else if (sisaKuotaGlobal > 0) {
                feedback.innerHTML = `<span class="text-success status-kuota">✅ Tersedia (Sisa ${sisaKuotaGlobal} slot kursi)</span>`;
                btnSubmit.disabled = false;
            }
        }
    }
});
</script>

</body>
</html>