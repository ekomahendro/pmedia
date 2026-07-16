<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
include '../koneksi.php';

$id_edit = null;
$data_produk = null;
$error = '';
$success = '';

// 1. Ambil Data Produk
if (isset($_GET['id'])) {
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = "SELECT * FROM produkummi WHERE id='$id_edit'";
    $result = mysqli_query($koneksi, $query);
    $data_produk = mysqli_fetch_assoc($result);

    if (!$data_produk) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}

// 2. Proses Update Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    // Ambil urutan gambar lama dari input hidden yang dikirim form
    $gambar_urut_input = isset($_POST['urutan_gambar']) ? $_POST['urutan_gambar'] : [];
    
    $gambar_files = $_FILES['gambar'];
    $target_dir = "../images/";
    $list_nama_gambar_baru = [];
    $ekstensi_diizinkan = ["jpg", "jpeg", "png", "gif"];
    $gagal_upload = false;

    // Periksa apakah admin mengunggah file baru
    if (isset($gambar_files['name'][0]) && !empty($gambar_files['name'][0])) {
        $total_files = count($gambar_files['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $error_file = $gambar_files['error'][$i];

            if ($error_file === 0) {
                $nama_asli = basename($gambar_files['name'][$i]);
                $imageFileType = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

                if (!in_array($imageFileType, $ekstensi_diizinkan)) {
                    $error = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
                    $gagal_upload = true;
                    break;
                }

                $nama_file_baru = uniqid() . '_' . $i . '.' . $imageFileType;
                $target_file = $target_dir . $nama_file_baru;

                if (move_uploaded_file($gambar_files['tmp_name'][$i], $target_file)) {
                    $list_nama_gambar_baru[] = $nama_file_baru;
                } else {
                    $error = "Maaf, terjadi kesalahan saat mengupload gambar baru.";
                    $gagal_upload = true;
                    break;
                }
            } else {
                $error = "Terjadi masalah pada berkas gambar ke-" . ($i + 1);
                $gagal_upload = true;
                break;
            }
        }

        // Jika upload berkas baru gagal di tengah jalan, bersihkan berkas yang terlanjur masuk
        if ($gagal_upload) {
            foreach ($list_nama_gambar_baru as $hapus_baru) {
                if(file_exists($target_dir . $hapus_baru)) unlink($target_dir . $hapus_baru);
            }
        }
    }

    // Jika tidak ada error upload berkas baru, satukan urutan gambar
    if (empty($error)) {
        // Gabungkan urutan gambar lama yang sudah di-sortir/dipertahankan dengan gambar baru di paling belakang
        $arr_gambar_final = array_merge($gambar_urut_input, $list_nama_gambar_baru);
        
        // Ubah menjadi JSON string untuk database
        $json_gambar_update = mysqli_real_escape_string($koneksi, json_encode($arr_gambar_final));

        $query_update = "UPDATE produkummi SET 
                            judul='$judul', 
                            deskripsi='$deskripsi',
                            gambar='$json_gambar_update'
                         WHERE id='$id_edit'";
        
        if (mysqli_query($koneksi, $query_update)) {
            header('Location: dashboard.php?status=update_sukses');
            exit;
        } else {
            $error = "Gagal memperbarui data ke database.";
            foreach ($list_nama_gambar_baru as $hapus_baru) {
                if(file_exists($target_dir . $hapus_baru)) unlink($target_dir . $hapus_baru);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Gaya kursor genggam agar user tahu elemen bisa diseret */
        .sortable-item {
            cursor: grab;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .sortable-item:active {
            cursor: grabbing;
        }
        /* Penanda bayangan kotak saat sedang diseret */
        .sortable-ghost {
            opacity: 0.4;
            background-color: #e2e8f0 !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <h2>Edit Produk & Susunan Gambar</h2>
        <hr>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali ke Dashboard</a>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Produk</label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?php echo htmlspecialchars($data_produk['judul']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi (termasuk harga)</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?php echo htmlspecialchars($data_produk['deskripsi']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label d-block">Urutan Gambar Saat Ini (Klik dan seret kotak untuk merubah urutan)</label>
                <!-- Container ber-ID #sortable-container -->
                <div class="d-flex flex-wrap gap-3" id="sortable-container">
                    <?php 
                    $gambar_raw = $data_produk['gambar'];
                    $arr_gambar_lama = [];

                    if (!empty($gambar_raw)) {
                        $decoded = json_decode($gambar_raw, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $arr_gambar_lama = $decoded;
                        } else {
                            $arr_gambar_lama = [$gambar_raw];
                        }
                    }

                    if (count($arr_gambar_lama) > 0): 
                        foreach ($arr_gambar_lama as $index => $img):
                    ?>
                        <!-- Setiap elemen gambar memiliki input hidden name="urutan_gambar[]" -->
                        <div class="position-relative border rounded p-1 text-center bg-light sortable-item" id="box-image-<?php echo $index; ?>" style="width: 130px;">
                            <input type="hidden" name="urutan_gambar[]" value="<?php echo htmlspecialchars($img); ?>">
                            
                            <img src="../images/<?php echo htmlspecialchars($img); ?>" width="120" height="120" style="object-fit: cover;" class="rounded" draggable="false">
                            
                            <!-- Tombol Hapus Tetap Dipertahankan -->
                            <button type="button" 
                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle" 
                                    style="padding: 2px 6px; font-size: 11px; z-index: 10;"
                                    onclick="hapusSatuGambar('<?php echo $id_edit; ?>', '<?php echo htmlspecialchars($img); ?>', 'box-image-<?php echo $index; ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <p class="text-muted" id="no-image-text">Tidak ada gambar untuk produk ini.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="gambar" class="form-label">Tambah Gambar Baru (Otomatis masuk urutan paling belakang)</label>
                <input type="file" class="form-control" id="gambar" name="gambar[]" accept="image/*" multiple>
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>

    <!-- Ambil Pustaka SortableJS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <script>
    // 1. Inisialisasi Fitur Drag & Drop Urutan Gambar
    const container = document.getElementById('sortable-container');
    if (container && container.querySelector('.sortable-item')) {
        new Sortable(container, {
            animation: 150, // kecepatan animasi pertukaran kotak dalam milidetik
            ghostClass: 'sortable-ghost', // kelas CSS saat elemen diseret
            handle: '.sortable-item', // elemen yang bisa di-handle untuk diseret
        });
    }

    // 2. Fungsi AJAX Hapus Gambar (Masih dipertahankan dari versi sebelumnya)
    function hapusSatuGambar(idProduk, namaGambar, idElemenBox) {
        if (confirm("Apakah Anda yakin ingin menghapus gambar ini secara permanen?")) {
            fetch('hapus_gambar_single.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + idProduk + '&nama_gambar=' + encodeURIComponent(namaGambar)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById(idElemenBox).remove();
                    alert("Gambar berhasil dihapus!");
                } else {
                    alert("Gagal menghapus gambar.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Terjadi gangguan koneksi sistem.");
            });
        }
    }
    </script>
</body>
</html>