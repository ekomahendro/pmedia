<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $gambar_files = $_FILES['gambar'];

    $target_dir = "../images/"; // Pastikan folder ini ada
    $list_nama_gambar = []; // Wadah untuk menampung nama file acak yang sukses diupload
    $ekstensi_diizinkan = ["jpg", "jpeg", "png", "gif"];
    $gagal_upload = false;

    // Periksa apakah ada file yang dipilih
    if (isset($gambar_files['name'][0]) && !empty($gambar_files['name'][0])) {
        $total_files = count($gambar_files['name']);

        // Loop untuk memproses setiap gambar yang diupload
        for ($i = 0; $i < $total_files; $i++) {
            $error_file = $gambar_files['error'][$i];

            if ($error_file === 0) {
                $nama_asli = basename($gambar_files['name'][$i]);
                $imageFileType = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

                // Cek ekstensi file
                if (!in_array($imageFileType, $ekstensi_diizinkan)) {
                    $error = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
                    $gagal_upload = true;
                    break; // Hentikan proses jika ada file yang tidak sesuai
                }

                // Generate nama file unik agar tidak saling menimpa
                $nama_file_baru = uniqid() . '_' . $i . '.' . $imageFileType;
                $target_file = $target_dir . $nama_file_baru;

                // Pindahkan file ke folder tujuan
                if (move_uploaded_file($gambar_files['tmp_name'][$i], $target_file)) {
                    $list_nama_gambar[] = $nama_file_baru;
                } else {
                    $error = "Maaf, terjadi kesalahan saat mengupload salah satu gambar.";
                    $gagal_upload = true;
                    break;
                }
            } else {
                $error = "Terjadi masalah pada file gambar ke-" . ($i + 1);
                $gagal_upload = true;
                break;
            }
        }

        // Jika semua gambar lolos validasi dan sukses dipindahkan
        if (!$gagal_upload && !empty($list_nama_gambar)) {
            // Encode array nama file menjadi string JSON terenkripsi
            $json_gambar = mysqli_real_escape_string($koneksi, json_encode($list_nama_gambar));

            // Insert data ke database
            $query = "INSERT INTO produkummi (judul, deskripsi, gambar) 
                      VALUES ('$judul', '$deskripsi', '$json_gambar')";
            
            if (mysqli_query($koneksi, $query)) {
                header('Location: dashboard.php?status=tambah_sukses');
                exit;
            } else {
                $error = "Gagal menyimpan data ke database.";
                // Bersihkan/hapus kembali gambar yang telanjur diupload jika query gagal
                foreach ($list_nama_gambar as $hapus_gambar) {
                    unlink($target_dir . $hapus_gambar);
                }
            }
        } else {
            // Jika proses upload gagal di tengah jalan, hapus file yang sudah sempat terupload
            foreach ($list_nama_gambar as $hapus_gambar) {
                unlink($target_dir . $hapus_gambar);
            }
        }

    } else {
        $error = "Minimal wajib memilih 1 gambar produk.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container mt-4">
        <h2>Tambah Produk Baru</h2>
        <hr>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali ke Dashboard</a>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Produk</label>
                <input type="text" class="form-control" id="judul" name="judul" required>
            </div>
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi (termasuk harga)</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Produk (Bisa pilih lebih dari 1)</label>
                <!-- Perubahan utama: menggunakan gambar[] dan atribut multiple -->
                <input type="file" class="form-control" id="gambar" name="gambar[]" accept="image/*" multiple required>
                <div class="form-text text-muted">
                    💡 <strong>Tips:</strong> Tahan tombol <code>Ctrl</code> (Windows) atau <code>Command</code> (Mac) pada keyboard saat memilih berkas untuk menandai banyak gambar sekaligus.
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Produk</button>
        </form>
    </div>
</body>
</html>