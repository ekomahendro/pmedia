<?php
session_start();

// Periksa apakah pengguna sudah login dan merupakan admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header("location: index.php");
    exit;
}

include_once 'config.php';

$no = $nama = $abi = $ummi = $hpabbi = $hpummi = $asal = $foto = $fotoummi = $jadwaltime = $jadwalhari = "";
$nama_err = $foto_err = $fotoummi_err = "";

// Proses form saat data dikirim
if (isset($_GET["no"]) && !empty(trim($_GET["no"]))) {
    $no = trim($_GET["no"]);
    $sql = "SELECT * FROM data7b WHERE no = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_no);
        $param_no = $no;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $nama = $row["nama"];
                $abi = $row["abi"];
                $ummi = $row["ummi"];
                $hpabbi = $row["hpabbi"];
                $hpummi = $row["hpummi"];
                $asal = $row["asal"];
                $foto = $row["foto"];
                $fotoummi = $row["fotoummi"]; // Ambil nama file foto ummi dari database
                $jadwaltime = $row["jadwaltime"];
                $jadwalhari = $row["jadwalhari"];
            } else {
                header("location: dashboard.php");
                exit();
            }
        } else {
            echo "Terjadi kesalahan. Silakan coba lagi nanti.";
        }
    }
    mysqli_stmt_close($stmt);
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $no = $_POST["no"];

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

    $foto_name = $_POST['current_foto']; // Simpan nama foto siswa lama
    $fotoummi_name = $_POST['current_fotoummi']; // Simpan nama foto ummi lama

    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $max_size = 5 * 1024 * 1024; // 5MB
    $allowed_types = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];

    // Fungsi untuk memproses unggahan file
    function handleFileUpload($file_key, $current_file_name, $upload_dir, $max_size, $allowed_types) {
        $error_message = "";
        $new_file_name = $current_file_name;

        if (isset($_FILES[$file_key]) && $_FILES[$file_key]["error"] == 0) {
            $filename = $_FILES[$file_key]["name"];
            $filetype = $_FILES[$file_key]["type"];
            $filesize = $_FILES[$file_key]["size"];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if (!array_key_exists($ext, $allowed_types)) {
                $error_message = "Error: Harap pilih format file gambar yang valid.";
            } elseif ($filesize > $max_size) {
                $error_message = "Error: Ukuran file lebih besar dari batas yang diizinkan (5MB).";
            }

            if (empty($error_message)) {
                $new_file_name = uniqid('img_', true) . '.' . $ext;
                if (!move_uploaded_file($_FILES[$file_key]["tmp_name"], $upload_dir . $new_file_name)) {
                    $error_message = "Terjadi kesalahan saat mengunggah file Anda.";
                } else {
                    // Hapus foto lama jika ada dan berhasil diunggah
                    if (!empty($current_file_name) && file_exists($upload_dir . $current_file_name)) {
                        unlink($upload_dir . $current_file_name);
                    }
                }
            }
        }
        return [$new_file_name, $error_message];
    }

    // Proses unggahan foto siswa
    list($foto_name, $foto_err) = handleFileUpload('foto', $foto_name, $upload_dir, $max_size, $allowed_types);

    // Proses unggahan foto ummi
    list($fotoummi_name, $fotoummi_err) = handleFileUpload('fotoummi', $fotoummi_name, $upload_dir, $max_size, $allowed_types);

    // Jika tidak ada error, update data di database
    if (empty($nama_err) && empty($foto_err) && empty($fotoummi_err)) {
        $sql = "UPDATE data7b SET nama=?, abi=?, ummi=?, hpabbi=?, hpummi=?, asal=?, foto=?, fotoummi=?, jadwaltime=?, jadwalhari=? WHERE no=?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssssssi", $param_nama, $param_abi, $param_ummi, $param_hpabbi, $param_hpummi, $param_asal, $param_foto, $param_fotoummi, $param_jadwaltime, $param_jadwalhari, $param_no);

            $param_nama = $nama;
            $param_abi = $abi;
            $param_ummi = $ummi;
            $param_hpabbi = $hpabbi;
            $param_hpummi = $hpummi;
            $param_asal = $asal;
            $param_foto = $foto_name;
            $param_fotoummi = $fotoummi_name; // Menggunakan nama file baru atau lama
            $param_jadwaltime = $jadwaltime;
            $param_jadwalhari = $jadwalhari;
            $param_no = $no;

            if (mysqli_stmt_execute($stmt)) {
                header("location: dashboard.php");
                exit();
            } else {
                echo "Terjadi kesalahan saat memperbarui data. Silakan coba lagi nanti.";
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Redirect jika tidak ada parameter 'no' atau method bukan POST
    if (!isset($_GET["no"])) {
        header("location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data - Data 7B</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Edit Data Siswa</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="no" value="<?php echo htmlspecialchars($no); ?>">
            <input type="hidden" name="current_foto" value="<?php echo htmlspecialchars($foto); ?>">
            <input type="hidden" name="current_fotoummi" value="<?php echo htmlspecialchars($fotoummi); ?>">

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
                <label for="foto" class="form-label">Foto Siswa</label>
                <?php if (!empty($foto) && file_exists('uploads/' . $foto)): ?>
                    <div class="mb-2">
                        <img src="uploads/<?php echo htmlspecialchars($foto); ?>" alt="Current Foto Siswa" style="width: 100px; height: 100px; object-fit: cover;">
                        <small class="text-muted d-block">Foto Siswa saat ini</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="foto" id="foto" class="form-control <?php echo (!empty($foto_err)) ? 'is-invalid' : ''; ?>">
                <div class="invalid-feedback"><?php echo $foto_err; ?></div>
                <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah foto.</small>
            </div>
            <div class="mb-3">
                <label for="fotoummi" class="form-label">Foto Ummi</label>
                <?php if (!empty($fotoummi) && file_exists('uploads/' . $fotoummi)): ?>
                    <div class="mb-2">
                        <img src="uploads/<?php echo htmlspecialchars($fotoummi); ?>" alt="Current Foto Ummi" style="width: 100px; height: 100px; object-fit: cover;">
                        <small class="text-muted d-block">Foto Ummi saat ini</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="fotoummi" id="fotoummi" class="form-control <?php echo (!empty($fotoummi_err)) ? 'is-invalid' : ''; ?>">
                <div class="invalid-feedback"><?php echo $fotoummi_err; ?></div>
                <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah foto.</small>
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
                <input type="submit" class="btn btn-primary" value="Update">
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