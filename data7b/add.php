<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header("location: index.php");
    exit;
}

include_once 'config.php';

$nama = $abi = $ummi = $hpabbi = $hpummi = $asal = $jadwaltime = $jadwalhari = "";
$nama_err = $foto_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi Nama
    if (empty(trim($_POST["nama"]))) {
        $nama_err = "Mohon masukkan nama.";
    } else {
        $nama = trim($_POST["nama"]);
    }

    $abi = trim($_POST["abi"]);
    $ummi = trim($_POST["ummi"]);
    $hpabbi = trim($_POST["hpabbi"]);
    $hpummi = trim($_POST["hpummi"]);
    $asal = trim($_POST["asal"]);
    $jadwaltime = trim($_POST["jadwaltime"]);
    $jadwalhari = trim($_POST["jadwalhari"]);

    $foto_name = '';
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $allowed_types = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["foto"]["name"];
        $filetype = $_FILES["foto"]["type"];
        $filesize = $_FILES["foto"]["size"];

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed_types)) {
            $foto_err = "Error: Harap pilih format file gambar yang valid.";
        }
        $maxsize = 5 * 1024 * 1024; // 5MB
        if ($filesize > $maxsize) {
            $foto_err = "Error: Ukuran file lebih besar dari batas yang diizinkan.";
        }

        if (empty($foto_err)) {
            $new_filename = uniqid('img_', true) . '.' . $ext;
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $new_filename)) {
                $foto_name = $new_filename;
            } else {
                $foto_err = "Terjadi kesalahan saat mengunggah file Anda.";
            }
        }
    }


    if (empty($nama_err) && empty($foto_err)) {
        $sql = "INSERT INTO data7b (nama, abi, ummi, hpabbi, hpummi, asal, foto, jadwaltime, jadwalhari) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssss", $param_nama, $param_abi, $param_ummi, $param_hpabbi, $param_hpummi, $param_asal, $param_foto, $param_jadwaltime, $param_jadwalhari);

            $param_nama = $nama;
            $param_abi = $abi;
            $param_ummi = $ummi;
            $param_hpabbi = $hpabbi;
            $param_hpummi = $hpummi;
            $param_asal = $asal;
            $param_foto = $foto_name;
            $param_jadwaltime = $jadwaltime;
            $param_jadwalhari = $jadwalhari;

            if (mysqli_stmt_execute($stmt)) {
                header("location: dashboard.php");
                exit();
            } else {
                echo "Terjadi kesalahan. Silakan coba lagi nanti.";
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Baru - Data 7B</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Tambah Data Baru</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control <?php echo (!empty($nama_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($nama); ?>">
                <div class="invalid-feedback"><?php echo $nama_err; ?></div>
            </div>
            <div class="mb-3">
                <label for="abi" class="form-label">Nama Abi</label>
                <input type="text" name="abi" class="form-control" value="<?php echo htmlspecialchars($abi); ?>">
            </div>
            <div class="mb-3">
                <label for="ummi" class="form-label">Nama Ummi</label>
                <input type="text" name="ummi" class="form-control" value="<?php echo htmlspecialchars($ummi); ?>">
            </div>
            <div class="mb-3">
                <label for="hpabbi" class="form-label">HP Abi</label>
                <input type="text" name="hpabbi" class="form-control" value="<?php echo htmlspecialchars($hpabbi); ?>">
            </div>
            <div class="mb-3">
                <label for="hpummi" class="form-label">HP Ummi</label>
                <input type="text" name="hpummi" class="form-control" value="<?php echo htmlspecialchars($hpummi); ?>">
            </div>
            <div class="mb-3">
                <label for="asal" class="form-label">Asal</label>
                <input type="text" name="asal" class="form-control" value="<?php echo htmlspecialchars($asal); ?>">
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" name="foto" id="foto" class="form-control <?php echo (!empty($foto_err)) ? 'is-invalid' : ''; ?>">
                <div class="invalid-feedback"><?php echo $foto_err; ?></div>
            </div>
            <div class="mb-3">
                <label for="jadwaltime" class="form-label">Jadwal Telepon (misal: 10:00 - 11:00)</label>
                <input type="text" name="jadwaltime" class="form-control" value="<?php echo htmlspecialchars($jadwaltime); ?>">
            </div>
            <div class="mb-3">
                <label for="jadwalhari" class="form-label">Jadwal Hari</label>
                <select name="jadwalhari" id="jadwalhari" class="form-select">
                    <option value="">Pilih Hari</option>
                    <option value="Senin" <?php echo ($jadwalhari == 'Senin') ? 'selected' : ''; ?>>Senin</option>
                    <option value="Selasa" <?php echo ($jadwalhari == 'Selasa') ? 'selected' : ''; ?>>Selasa</option>
                    <option value="Rabu" <?php echo ($jadwalhari == 'Rabu') ? 'selected' : ''; ?>>Rabu</option>
                    <option value="Kamis" <?php echo ($jadwalhari == 'Kamis') ? 'selected' : ''; ?>>Kamis</option>
                    <option value="Jumat" <?php echo ($jadwalhari == 'Jumat') ? 'selected' : ''; ?>>Jumat</option>
                    <option value="Sabtu" <?php echo ($jadwalhari == 'Sabtu') ? 'selected' : ''; ?>>Sabtu</option>
                    <option value="Minggu" <?php echo ($jadwalhari == 'Minggu') ? 'selected' : ''; ?>>Minggu</option>
                </select>
            </div>
            <div class="mb-3">
                <input type="submit" class="btn btn-primary" value="Simpan">
                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
mysqli_close($link);
?>