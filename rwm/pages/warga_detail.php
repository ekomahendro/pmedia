<?php 
include '../auth/session.php'; 

$id_kk = $_GET['id'];

// Ambil Data Kepala Keluarga
$stmt = $pdo->prepare("SELECT * FROM tr_warga_kk WHERE id_kk = ?");
$stmt->execute([$id_kk]);
$kk = $stmt->fetch();

if (!$kk) {
    die("Data tidak ditemukan!");
}

// Ambil Anggota Keluarga (Logical Relationship)
$stmt_agt = $pdo->prepare("SELECT * FROM tr_warga_anggota WHERE id_kk = ?");
$stmt_agt->execute([$id_kk]);
$anggota = $stmt_agt->fetchAll();

// Proses Upload Foto (Opsional)
if (isset($_POST['upload_foto'])) {
    $target_dir = "../assets/uploads/";
    $id_target = $_POST['id_target'];
    $tipe = $_POST['tipe']; // 'kk' atau 'anggota'
    
    $file_ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
    $new_name = $tipe . "_" . $id_target . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_name;

    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        if ($tipe == 'kk') {
            $pdo->prepare("UPDATE tr_warga_kk SET foto = ? WHERE id_kk = ?")->execute([$new_name, $id_target]);
        } else {
            $pdo->prepare("UPDATE tr_warga_anggota SET foto = ? WHERE id_anggota = ?")->execute([$new_name, $id_target]);
        }
        header("Location: warga_detail.php?id=$id_kk&msg=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Keluarga - <?= $kk['nama_kk'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .img-profile { width: 150px; height: 150px; object-fit: cover; border-radius: 10px; border: 3px solid #dee2e6; }
        .img-member { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><span class="text-muted">Profil Keluarga:</span> <?= $kk['nama_kk'] ?></h3>
            <a href="warga_list.php" class="btn btn-secondary">Kembali</a>
        </div>
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span>Daftar Anggota Keluarga</span>
        <a href="anggota_tambah.php?id_kk=<?= $kk['id_kk'] ?>" class="btn btn-success btn-sm">+ Tambah Anggota Baru</a>
    </div>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center border-end">
                        <img src="<?= $kk['foto'] ? '../assets/uploads/'.$kk['foto'] : 'https://via.placeholder.com/150' ?>" class="img-profile mb-3">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_target" value="<?= $kk['id_kk'] ?>">
                            <input type="hidden" name="tipe" value="kk">
                            <input type="file" name="foto" class="form-control form-control-sm mb-2" required>
                            <button name="upload_foto" class="btn btn-primary btn-sm w-100">Ganti Foto KK</button>
                        </form>
                    </div>
                    <div class="col-md-9 px-4">
                        <div class="row">
                            <div class="col-6 mb-2"><strong>NIK:</strong><br><?= $kk['nik'] ?></div>
                            <div class="col-6 mb-2"><strong>Telepon:</strong><br><?= $kk['telepon'] ?></div>
                            <div class="col-6 mb-2"><strong>Wilayah / Blok:</strong><br><?= $kk['wilayah'] ?> / <?= $kk['blok'] ?></div>
                            <div class="col-6 mb-2"><strong>Status Rumah:</strong><br><?= $kk['status_rumah'] ?></div>
                            <div class="col-6 mb-2"><strong>Pekerjaan:</strong><br><?= $kk['pekerjaan'] ?></div>
                            <div class="col-6 mb-2"><strong>Pendidikan:</strong><br><?= $kk['pendidikan'] ?></div>
                            <div class="col-12 mb-2"><strong>Alamat:</strong><br><?= $kk['alamat'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">Daftar Anggota Keluarga</div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>Lahir</th>
                            <th>Pekerjaan</th>
                            <th width="200">Upload Foto Anggota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($anggota as $agt): ?>
                        <tr>
                            <td>
                                <img src="<?= $agt['foto'] ? '../assets/uploads/'.$agt['foto'] : 'https://via.placeholder.com/50' ?>" class="img-member">
                            </td>
                            <td class="fw-bold"><?= $agt['nama'] ?></td>
                            <td><?= $agt['nik'] ?></td>
                            <td><?= $agt['tmp_lahir'] ?>, <?= $agt['tgl_lahir'] ?></td>
                            <td><?= $agt['pekerjaan'] ?></td>
                            <td>
                                <form method="POST" enctype="multipart/form-data" class="d-flex gap-1">
                                    <input type="hidden" name="id_target" value="<?= $agt['id_anggota'] ?>">
                                    <input type="hidden" name="tipe" value="anggota">
                                    <input type="file" name="foto" class="form-control form-control-sm" required>
                                    <button name="upload_foto" class="btn btn-success btn-sm">OK</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>