<?php
$file = 'data.json';
$data = json_decode(file_get_contents($file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Masjid & Pengumuman
    $data['masjid']['nama'] = $_POST['nama_masjid'];
    $data['masjid']['alamat'] = $_POST['alamat_masjid'];
    $data['masjid']['telp'] = $_POST['telp_masjid'];
    $data['pengumuman'] = $_POST['pengumuman'];

    // Update Imam
    foreach(['Subuh','Dzuhur','Ashar','Maghrib','Isya'] as $wkt) {
        $data['imam'][$wkt] = $_POST['imam_'.$wkt];
    }

    // Update Takmir
    $newTakmir = [];
    for($i=0; $i < count($_POST['t_nama']); $i++) {
        if(!empty($_POST['t_nama'][$i])) {
            $newTakmir[] = ['nama' => $_POST['t_nama'][$i], 'jabatan' => $_POST['t_jabatan'][$i]];
        }
    }
    $data['takmir'] = $newTakmir;

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    $status = "Data Berhasil Disimpan!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Jadwal Shalat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body { background: #f4f7f6; padding: 20px; }</style>
</head>
<body>
    <div class="container bg-white p-4 rounded shadow">
        <h2 class="mb-4">Panel Kontrol Masjid</h2>
        <?php if(isset($status)) echo "<div class='alert alert-success'>$status</div>"; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Nama Masjid</label>
                    <input type="text" name="nama_masjid" class="form-control" value="<?= $data['masjid']['nama'] ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>No. Telp</label>
                    <input type="text" name="telp_masjid" class="form-control" value="<?= $data['masjid']['telp'] ?>">
                </div>
                <div class="col-12 mb-3">
                    <label>Alamat</label>
                    <input type="text" name="alamat_masjid" class="form-control" value="<?= $data['masjid']['alamat'] ?>">
                </div>
                <div class="col-12 mb-3">
                    <label>Pengumuman (Running Text)</label>
                    <textarea name="pengumuman" class="form-control"><?= $data['pengumuman'] ?></textarea>
                </div>
            </div>

            <hr>
            <h4>Nama Imam Shalat</h4>
            <div class="row mb-4">
                <?php foreach(['Subuh','Dzuhur','Ashar','Maghrib','Isya'] as $wkt): ?>
                <div class="col">
                    <label><?= $wkt ?></label>
                    <input type="text" name="imam_<?= $wkt ?>" class="form-control" value="<?= $data['imam'][$wkt] ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <hr>
            <h4>Pengurus Takmir</h4>
            <div id="takmir-container">
                <?php foreach($data['takmir'] as $t): ?>
                <div class="row mb-2">
                    <div class="col"><input type="text" name="t_jabatan[]" class="form-control" value="<?= $t['jabatan'] ?>"></div>
                    <div class="col"><input type="text" name="t_nama[]" class="form-control" value="<?= $t['nama'] ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="btn btn-primary mt-4 w-100">SIMPAN SEMUA PERUBAHAN</button>
        </form>
    </div>
</body>
</html>