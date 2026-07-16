<?php
$json_file = 'setting_soal.json';
$status_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? '';
    $jam = $_POST['jam'] ?? '00:00';
    $ijinkan_download = $_POST['ijinkan_download'] ?? 'ya';
    
    if (!empty($tanggal)) {
        $datetime_string = $tanggal . ' ' . $jam . ':00';
        
        $data = [
            'tanggal_tayang' => $datetime_string,
            'ijinkan_download' => $ijinkan_download
        ];
        file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
        
        $status_msg = '<div class="alert alert-success shadow-sm"><i class="bi bi-check-circle-fill"></i> Konfigurasi berhasil diperbarui!</div>';
    }
}

// Baca settingan saat ini
$current_setting = [];
if (file_exists($json_file)) {
    $current_setting = json_decode(file_get_contents($json_file), true);
}
$tanggal_tayang = $current_setting['tanggal_tayang'] ?? '';
$allow_dl = $current_setting['ijinkan_download'] ?? 'ya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Jadwal & Tombol Soal CC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; }
        .card-setting { border: none; border-radius: 15px; }
    </style>
</head>
<body>
<div class="container mt-5" style="max-width: 600px;">
    <div class="card card-setting shadow-sm">
        <div class="card-header bg-dark text-white p-3 rounded-top-4">
            <h5 class="mb-0 fw-bold"><i class="bi bi-sliders me-2 text-warning"></i> Kontrol Rilis & Tombol PDF</h5>
        </div>
        <div class="card-body p-4">
            <?= $status_msg ?>

            <form method="POST" action="">
                <?php
                $def_date = ''; $def_time = '08:00';
                if (!empty($tanggal_tayang)) {
                    $parts = explode(' ', $tanggal_tayang);
                    $def_date = $parts[0] ?? '';
                    $def_time = substr($parts[1] ?? '', 0, 5);
                }
                ?>
                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3"><i class="bi bi-calendar-check"></i> Waktu Penayangan</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Tanggal Rilis</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $def_date ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Jam (WITA)</label>
                        <input type="time" name="jam" class="form-control" value="<?= $def_time ?>" required>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3"><i class="bi bi-link-45deg"></i> Akses Tombol PDF</h6>
                
                <div class="mb-4 bg-light p-3 rounded-3 border">
                    <label class="form-label fw-bold d-block mb-2">Tampilkan Tombol Akses/Buka PDF?</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="ijinkan_download" id="dl_yes" value="ya" <?= $allow_dl == 'ya' ? 'checked' : '' ?>>
                        <label class="form-check-label" type="button" for="dl_yes">Ya (Tombol Muncul)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="ijinkan_download" id="dl_no" value="tidak" <?= $allow_dl == 'tidak' ? 'checked' : '' ?>>
                        <label class="form-check-label" type="button" for="dl_no">Tidak (Sembunyikan/Kunci)</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">Simpan Konfigurasi</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>