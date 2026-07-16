<?php
date_default_timezone_set('Asia/Makassar'); 

$json_file = 'setting_soal.json';
$bisa_tayang = false;
$tanggal_target_js = '';
$allow_dl = 'ya';

if (file_exists($json_file)) {
    $json_data = json_decode(file_get_contents($json_file), true);
    $target_time_str = $json_data['tanggal_tayang'] ?? '';
    $allow_dl = $json_data['ijinkan_download'] ?? 'ya';
    
    if (!empty($target_time_str)) {
        $time_target = strtotime($target_time_str);
        $time_now    = time();
        $tanggal_target_js = date('Y-m-d\TH:i:s', $time_target);
        
        if ($time_now >= $time_target) {
            $bisa_tayang = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisi-Kisi Soal Cerdas Cermat - Milad XV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #e9ecef; min-height: 100vh; display: flex; align-items: center; }
        .countdown-box { font-size: 2.5rem; font-weight: 800; color: #0d6efd; letter-spacing: 2px; }
        .card-box { border-0: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-radius: 20px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center w-100 m-0">
        <div class="col-md-7 text-center">
            <div class="card card-box p-4 p-md-5 bg-white">
                <div class="card-body">
                    
                    <?php if ($bisa_tayang): ?>
                        <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 5rem;"></i>
                        <h3 class="fw-bold text-dark mt-3">Kisi-Kisi Soal Telah Tersedia</h3>
                        <p class="text-muted small px-md-4 mb-4">Materi kisi-kisi babak penyisihan cabang lomba Cerdas Cermat Milad XV sudah resmi dirilis oleh panitia.</p>
                        
                        <?php if($allow_dl == 'ya'): ?>
                            <a href="soalcc.pdf" target="_blank" class="btn btn-success btn-lg fw-bold rounded-pill px-5 py-3 shadow-sm w-100 mb-2">
                                <i class="bi bi-box-arrow-up-right me-2"></i> BUKA / OPEN FILE PDF
                            </a>
                            <small class="text-muted d-block mt-2">Klik tombol di atas untuk membuka file melalui browser atau aplikasi PDF default handphone Anda.</small>
                        <?php else: ?>
                            <div class="alert alert-warning d-flex align-items-center justify-content-center border-0 py-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> Tombol akses file dinonaktifkan sementara oleh panitia.
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <i class="bi bi-lock-fill text-warning" style="font-size: 4.5rem;"></i>
                        <h3 class="fw-bold text-dark mt-3">Materi Belum Dirilis</h3>
                        <p class="text-muted small">Mohon bersabar, akses materi kisi-kisi Cerdas Cermat Milad XV akan otomatis terbuka dalam:</p>
                        
                        <?php if(!empty($tanggal_target_js)): ?>
                            <div class="bg-light p-3 rounded-3 my-4 border border-dashed border-primary">
                                <div id="countdown_timer" class="countdown-box text-danger fs-2">00:00:00:00</div>
                                <div class="row text-muted small fw-bold mt-1 text-center justify-content-center" style="font-size:0.75rem;">
                                    <div class="col-2">Hari</div>
                                    <div class="col-2">Jam</div>
                                    <div class="col-2">Mnt</div>
                                    <div class="col-2">Det</div>
                                </div>
                            </div>
                            <small class="text-muted d-block" style="font-size:0.8rem;"><i class="bi bi-clock"></i> Jadwal Rilis: <strong><?= date('d M Y - H:i', strtotime($target_time_str)) ?> WITA</strong></small>
                        <?php else: ?>
                            <div class="alert alert-secondary mt-3">Jadwal rilis belum dikonfigurasi oleh panitia.</div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$bisa_tayang && !empty($tanggal_target_js)): ?>
<script>
    const targetDate = new Date("<?= $tanggal_target_js ?>").getTime();
    const interval = setInterval(function() {
        const now = new Date().getTime();
        const distance = targetDate - now;

        if (distance < 0) {
            clearInterval(interval);
            window.location.reload();
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const format = (num) => String(num).padStart(2, '0');
        document.getElementById("countdown_timer").innerHTML = `${format(days)}:${format(hours)}:${format(minutes)}:${format(seconds)}`;
    }, 1000);
</script>
<?php endif; ?>

</body>
</html>