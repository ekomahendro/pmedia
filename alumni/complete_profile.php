<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $nama = $_POST['nama'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $domisili = $_POST['domisili'];
    $angkatan = $_POST['angkatan'];
    $perguruan_tinggi = $_POST['perguruan_tinggi'];
    $kota_kuliah = $_POST['kota_kuliah'];
    $jurusan_kuliah = $_POST['jurusan_kuliah'];
    $tahun_masuk_kuliah = $_POST['tahun_masuk_kuliah'];
    $no_hp = $_POST['no_hp'];
    $email = $_POST['email'];
    $wali_kelas = $_POST['wali_kelas'];
    $foto = $_FILES['foto']['name'];

    // Handle file upload
    if ($foto) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($foto);
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
    }

    $stmt = $conn->prepare("INSERT INTO alumni (user_id, nama, tanggal_lahir, domisili, angkatan, perguruan_tinggi, kota_kuliah, jurusan_kuliah, tahun_masuk_kuliah, no_hp, email, wali_kelas, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisssissss", $user_id, $nama, $tanggal_lahir, $domisili, $angkatan, $perguruan_tinggi, $kota_kuliah, $jurusan_kuliah, $tahun_masuk_kuliah, $no_hp, $email, $wali_kelas, $foto);
    $stmt->execute();

    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Complete Your Profile</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="mb-3">
                <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
            </div>
            <div class="mb-3">
                <label for="domisili" class="form-label">Domisili</label>
                <input type="text" class="form-control" id="domisili" name="domisili" required>
            </div>
            <div class="mb-3">
                <label for="angkatan" class="form-label">Angkatan</label>
                <select class="form-select" id="angkatan" name="angkatan" required>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>">Angkatan <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="perguruan_tinggi" class="form-label">Perguruan Tinggi</label>
                <input type="text" class="form-control" id="perguruan_tinggi" name="perguruan_tinggi" required>
            </div>
            <div class="mb-3">
                <label for="kota_kuliah" class="form-label">Kota Kuliah</label>
                <input type="text" class="form-control" id="kota_kuliah" name="kota_kuliah" required>
            </div>
            <div class="mb-3">
                <label for="jurusan_kuliah" class="form-label">Jurusan Kuliah</label>
                <input type="text" class="form-control" id="jurusan_kuliah" name="jurusan_kuliah" required>
            </div>
            <div class="mb-3">
                <label for="tahun_masuk_kuliah" class="form-label">Tahun Masuk Kuliah</label>
                <input type="number" class="form-control" id="tahun_masuk_kuliah" name="tahun_masuk_kuliah" required>
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">No HP</label>
                <input type="text" class="form-control" id="no_hp" name="no_hp" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="wali_kelas" class="form-label">Wali Kelas 12</label>
                <input type="text" class="form-control" id="wali_kelas" name="wali_kelas" required>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>