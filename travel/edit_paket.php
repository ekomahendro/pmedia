<?php
session_start();
if(!isset($_SESSION['admin'])) header("Location: login.php");
include 'koneksi.php';

$id = $_GET['id'];
$data = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM tra_paket WHERE id_paket=$id"));

if (isset($_POST['update'])) { // sesuaikan dengan nama atribut button submit update Anda
    if ($_SESSION['role'] == 'Demo') {
        echo "<script>alert('Gagal! Akun DEMO tidak diizinkan mengubah/mengupdate data paket.'); window.location='admin.php';</script>";
        exit;
    }
    $nama = $_POST['nama'];
    $dest = $_POST['destinasi'];
    $harga = $_POST['harga'];
    $desc = $_POST['desc'];
    $tgl_m = $_POST['tgl_m'];
    $tgl_s = $_POST['tgl_s'];
    
    // Logika Ganti Gambar
    if($_FILES['gambar']['name'] != ""){
        $img = $_FILES['gambar']['name'];
        move_uploaded_file($_FILES['gambar']['tmp_name'], "img/".$img);
        $sql = "UPDATE tra_paket SET nama_paket='$nama', destinasi='$dest', harga='$harga', deskripsi='$desc', tgl_mulai='$tgl_m', tgl_selesai='$tgl_s', gambar='$img' WHERE id_paket=$id";
    } else {
        $sql = "UPDATE tra_paket SET nama_paket='$nama', destinasi='$dest', harga='$harga', deskripsi='$desc', tgl_mulai='$tgl_m', tgl_selesai='$tgl_s' WHERE id_paket=$id";
    }
    
    if(mysqli_query($conn, $sql)){
        echo "<script>alert('Data Berhasil Diperbarui!'); window.location='admin.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Paket - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Edit Paket Wisata</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3 text-center">
                                <label class="d-block mb-2 text-muted">Gambar Saat Ini</label>
                                <img src="img/<?= $data['gambar'] ?>" class="rounded shadow-sm" width="200">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Nama Paket</label>
                                    <input type="text" name="nama" class="form-control" value="<?= $data['nama_paket'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Destinasi</label>
                                    <input type="text" name="destinasi" class="form-control" value="<?= $data['destinasi'] ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Harga (Rp)</label>
                                    <input type="number" name="harga" class="form-control" value="<?= $data['harga'] ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Tgl Mulai</label>
                                    <input type="date" name="tgl_m" class="form-control" value="<?= $data['tgl_mulai'] ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Tgl Selesai</label>
                                    <input type="date" name="tgl_s" class="form-control" value="<?= $data['tgl_selesai'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Deskripsi Paket</label>
                                <textarea name="desc" class="form-control" rows="5" required><?= $data['deskripsi'] ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Ganti Gambar (Kosongkan jika tidak diganti)</label>
                                <input type="file" name="gambar" class="form-control">
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="admin.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" name="update" class="btn btn-warning px-5 fw-bold">UPDATE PAKET</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>