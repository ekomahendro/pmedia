<?php 
include '../auth/session.php'; 

// Ambil list warga untuk dropdown pengurus
$stmt_warga = $pdo->query("SELECT id_kk as id, nama_kk as nama FROM tr_warga_kk UNION SELECT id_anggota, nama FROM tr_warga_anggota");
$list_warga = $stmt_warga->fetchAll();

if (isset($_POST['simpan_pengurus'])) {
    $stmt = $pdo->prepare("INSERT INTO tr_pengurus (id_warga, jabatan, periode_mulai, periode_selesai) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['id_warga'], $_POST['jabatan'], $_POST['mulai'], $_POST['selesai']]);
}

$pengurus = $pdo->query("SELECT p.*, COALESCE(w.nama_kk, a.nama) as nama_asli 
                         FROM tr_pengurus p 
                         LEFT JOIN tr_warga_kk w ON p.id_warga = w.id_kk
                         LEFT JOIN tr_warga_anggota a ON p.id_warga = a.id_anggota")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurus RWM - Bukit Sanggulan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; // Sebaiknya navbar dipisah agar rapi ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Tambah Pengurus</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-2">
                                <label>Pilih Warga</label>
                                <select name="id_warga" class="form-select shadow-none">
                                    <?php foreach($list_warga as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= $w['nama'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label>Jabatan</label>
                                <input type="text" name="jabatan" class="form-control" placeholder="Ketua RW / Sekertaris">
                            </div>
                            <div class="row mb-3">
                                <div class="col"><label>Dari</label><input type="number" name="mulai" class="form-control" value="2024"></div>
                                <div class="col"><label>Sampai</label><input type="number" name="selesai" class="form-control" value="2027"></div>
                            </div>
                            <button name="simpan_pengurus" class="btn btn-primary w-100">Simpan Pengurus</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">Daftar Pengurus Aktif</div>
                    <div class="card-body table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Nama</th><th>Jabatan</th><th>Periode</th></tr></thead>
                            <tbody>
                                <?php foreach($pengurus as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= $p['nama_asli'] ?></td>
                                    <td><span class="badge bg-info"><?= $p['jabatan'] ?></span></td>
                                    <td><?= $p['periode_mulai'] ?> - <?= $p['periode_selesai'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>