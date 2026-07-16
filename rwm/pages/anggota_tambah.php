<?php 
include '../auth/session.php'; 

$id_kk = $_GET['id_kk'];

// Ambil data KK untuk judul halaman
$stmt_kk = $pdo->prepare("SELECT nama_kk, wilayah, blok FROM tr_warga_kk WHERE id_kk = ?");
$stmt_kk->execute([$id_kk]);
$kk = $stmt_kk->fetch();

if (!$kk) { die("Data KK tidak ditemukan."); }

if (isset($_POST['simpan_anggota'])) {
    try {
        $target_dir = "../assets/uploads/";
        $sql = "INSERT INTO tr_warga_anggota (id_kk, nama, nik, jk, pekerjaan, tmp_lahir, tgl_lahir, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($_POST['nama'] as $key => $nama) {
            if (!empty($nama)) {
                $foto_name = NULL;
                if (!empty($_FILES['foto']['name'][$key])) {
                    $ext = pathinfo($_FILES['foto']['name'][$key], PATHINFO_EXTENSION);
                    $foto_name = "agt_" . time() . "_" . $key . "." . $ext;
                    move_uploaded_file($_FILES['foto']['tmp_name'][$key], $target_dir . $foto_name);
                }

                $stmt->execute([
                    $id_kk, $nama, $_POST['nik'][$key], $_POST['jk'][$key], 
                    $_POST['pekerjaan'][$key], $_POST['tmp_lahir'][$key], 
                    $_POST['tgl_lahir'][$key], $foto_name
                ]);
            }
        }
        header("Location: warga_detail.php?id=$id_kk&msg=success");
    } catch (Exception $e) {
        die("Gagal menambah anggota: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Anggota - <?= $kk['nama_kk'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Tambah Anggota Keluarga Baru</h5>
                <small>Keluarga: <?= $kk['nama_kk'] ?> (<?= $kk['wilayah'] ?> - <?= $kk['blok'] ?>)</small>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div id="container-anggota">
                        <div class="row g-3 border-bottom pb-3 mb-3 item-anggota">
                            <div class="col-md-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama[]" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NIK</label>
                                <input type="text" name="nik[]" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">JK</label>
                                <select name="jk[]" class="form-select">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Lahir (Kota)</label>
                                <input type="text" name="tmp_lahir[]" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tgl Lahir</label>
                                <input type="date" name="tgl_lahir[]" class="form-control">
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Pekerjaan</label>
                                <input type="text" name="pekerjaan[]" class="form-control">
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Foto Profil (Opsional)</label>
                                <input type="file" name="foto[]" class="form-control">
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="tambahBaris()">+ Tambah Baris Lagi</button>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="simpan_anggota" class="btn btn-primary px-5">Simpan Anggota</button>
                        <a href="warga_detail.php?id=<?= $id_kk ?>" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function tambahBaris() {
            const container = document.getElementById('container-anggota');
            const row = document.querySelector('.item-anggota').cloneNode(true);
            // Reset input values
            row.querySelectorAll('input').forEach(input => input.value = '');
            container.appendChild(row);
        }
    </script>
</body>
</html>