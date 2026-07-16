<?php 
include '../auth/session.php'; 

$id_kk = $_GET['id'];
$target_dir = "../assets/uploads/";

// 1. Ambil Data Lama
$stmt = $pdo->prepare("SELECT * FROM tr_warga_kk WHERE id_kk = ?");
$stmt->execute([$id_kk]);
$kk = $stmt->fetch();

$stmt_agt = $pdo->prepare("SELECT * FROM tr_warga_anggota WHERE id_kk = ?");
$stmt_agt->execute([$id_kk]);
$anggota_lama = $stmt_agt->fetchAll();

// 2. Proses Update
if (isset($_POST['update_warga'])) {
    try {
        $pdo->beginTransaction();

        // --- UPDATE KEPALA KELUARGA ---
        $foto_kk = $kk['foto']; // Gunakan foto lama sebagai default
        if (!empty($_FILES['foto_kk']['name'])) {
            $ext_kk = pathinfo($_FILES['foto_kk']['name'], PATHINFO_EXTENSION);
            $foto_kk = "kk_" . time() . "_" . rand(100,999) . "." . $ext_kk;
            move_uploaded_file($_FILES['foto_kk']['tmp_name'], $target_dir . $foto_kk);
        }

        $sql_upd_kk = "UPDATE tr_warga_kk SET nama_kk=?, nik=?, telepon=?, jk=?, alamat=?, wilayah=?, blok=?, status_rumah=?, pekerjaan=?, tmp_lahir=?, tgl_lahir=?, pendidikan=?, foto=? WHERE id_kk=?";
        $pdo->prepare($sql_upd_kk)->execute([
            $_POST['nama_kk'], $_POST['nik_kk'], $_POST['telepon_kk'], $_POST['jk_kk'], 
            $_POST['alamat'], $_POST['wilayah'], $_POST['blok'], $_POST['status_rumah'], 
            $_POST['pekerjaan_kk'], $_POST['tmp_lahir_kk'], $_POST['tgl_lahir_kk'], 
            $_POST['pendidikan_kk'], $foto_kk, $id_kk
        ]);

        // --- UPDATE ANGGOTA KELUARGA (Hapus Dulu, Masukkan Lagi) ---
        // Strategi: Hapus semua anggota lama lalu insert yang baru dari form edit 
        // agar tidak pusing mengelola ID satu per satu.
        $pdo->prepare("DELETE FROM tr_warga_anggota WHERE id_kk = ?")->execute([$id_kk]);

        if (!empty($_POST['nama_anggota'])) {
            $sql_ins_agt = "INSERT INTO tr_warga_anggota (id_kk, nama, nik, jk, pekerjaan, tmp_lahir, tgl_lahir, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_ins = $pdo->prepare($sql_ins_agt);

            foreach ($_POST['nama_anggota'] as $key => $nama) {
                if (!empty($nama)) {
                    // Cek foto: Jika ada upload baru gunakan yang baru, jika tidak gunakan hidden input foto_lama
                    $foto_agt = $_POST['foto_lama_anggota'][$key] ?? NULL;
                    if (!empty($_FILES['foto_anggota']['name'][$key])) {
                        $ext_agt = pathinfo($_FILES['foto_anggota']['name'][$key], PATHINFO_EXTENSION);
                        $foto_agt = "agt_" . time() . "_" . $key . "." . $ext_agt;
                        move_uploaded_file($_FILES['foto_anggota']['tmp_name'][$key], $target_dir . $foto_agt);
                    }

                    $stmt_ins->execute([
                        $id_kk, $nama, $_POST['nik_anggota'][$key], $_POST['jk_anggota'][$key], 
                        $_POST['pekerjaan_anggota'][$key], $_POST['tmp_lahir_anggota'][$key], 
                        $_POST['tgl_lahir_anggota'][$key], $foto_agt
                    ]);
                }
            }
        }

        $pdo->commit();
        header("Location: warga_detail.php?id=$id_kk&status=updated");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal update: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Keluarga - <?= $kk['nama_kk'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <form method="POST" enctype="multipart/form-data">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark fw-bold">Edit Kepala Keluarga</div>
                <div class="card-body row g-3">
                    <div class="col-md-2">
                        <img src="<?= $kk['foto'] ? '../assets/uploads/'.$kk['foto'] : 'https://via.placeholder.com/100' ?>" class="img-thumbnail mb-2">
                        <input type="file" name="foto_kk" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4"><label>Nama KK</label><input type="text" name="nama_kk" class="form-control" value="<?= $kk['nama_kk'] ?>"></div>
                    <div class="col-md-3"><label>NIK</label><input type="text" name="nik_kk" class="form-control" value="<?= $kk['nik'] ?>"></div>
                    <div class="col-md-3"><label>Telepon</label><input type="text