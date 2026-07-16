<?php
session_start();
// PERIKSA LOGIN: Jika admin belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
include '../koneksi.php'; // Hubungkan ke database

// -----------------------------------------------------
// LOGIKA HAPUS PRODUK PERMANEN
// -----------------------------------------------------
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // 1. Ambil data gambar (format JSON) yang akan dihapus
    $query_gambar = "SELECT gambar FROM produkummi WHERE id='$id_hapus'";
    $result_gambar = mysqli_query($koneksi, $query_gambar);
    $data_gambar = mysqli_fetch_assoc($result_gambar);
    $string_gambar = $data_gambar['gambar'];

    // 2. Hapus data dari database
    $query_hapus = "DELETE FROM produkummi WHERE id='$id_hapus'";
    if (mysqli_query($koneksi, $query_hapus)) {
        // 3. Decode JSON dan hapus SEMUA file gambar terkait dari folder images/
        $list_gambar = json_decode($string_gambar, true);
        if (is_array($list_gambar)) {
            foreach ($list_gambar as $nama_gambar) {
                if (!empty($nama_gambar) && file_exists('../images/' . $nama_gambar)) {
                    unlink('../images/' . $nama_gambar);
                }
            }
        }
        header('Location: dashboard.php?status=hapus_sukses');
        exit;
    } else {
        header('Location: dashboard.php?status=hapus_gagal');
        exit;
    }
}

// -----------------------------------------------------
// LOGIKA UBAH STATUS PRODUK (HIDE/SHOW)
// -----------------------------------------------------
if (isset($_GET['aksi']) && $_GET['aksi'] == 'ubah_status' && isset($_GET['id']) && isset($_GET['current_status'])) {
    $id_ubah = mysqli_real_escape_string($koneksi, $_GET['id']);
    $current_status = mysqli_real_escape_string($koneksi, $_GET['current_status']);
    
    // Tentukan status baru (toggle: active -> hidden, hidden -> active)
    $new_status = ($current_status == 'active') ? 'hidden' : 'active';

    $query_update = "UPDATE produkummi SET status='$new_status' WHERE id='$id_ubah'";
    
    if (mysqli_query($koneksi, $query_update)) {
        header('Location: dashboard.php?status=status_sukses&new=' . $new_status);
        exit;
    } else {
        header('Location: dashboard.php?status=status_gagal');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Fashion Ummi Ayna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #A34E78;">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">ADMIN Ummi Ayna 👑</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manajemen Produk</h2>
        <hr>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'tambah_sukses'): ?>
                <div class="alert alert-success">Produk berhasil ditambahkan dengan multi-gambar!</div>
            <?php elseif ($_GET['status'] == 'update_sukses'): ?>
                <div class="alert alert-success">Produk berhasil diperbarui!</div>
            <?php elseif ($_GET['status'] == 'hapus_sukses'): ?>
                <div class="alert alert-warning">Produk dan seluruh gambarnya berhasil dihapus PERMANEN!</div>
            <?php elseif ($_GET['status'] == 'status_sukses'): ?>
                <div class="alert alert-info">Status produk berhasil diubah menjadi **<?php echo htmlspecialchars($_GET['new']); ?>**!</div>
            <?php elseif ($_GET['status'] == 'status_gagal' || $_GET['status'] == 'hapus_gagal'): ?>
                <div class="alert alert-danger">Terjadi kesalahan dalam aksi database.</div>
            <?php endif; ?>
        <?php endif; ?>

        <a href="tambah_produk.php" class="btn btn-success mb-3">Tambah Produk Baru (+)</a>
        <a href="../index.php" target="_blank" class="btn btn-outline-primary mb-3">Lihat Pricelist Utama ↗️</a>

        <table class="table table-bordered table-striped">
            <thead>
                <tr class="table-dark">
                    <th>ID</th>
                    <th>Judul</th>
                    <th>Gambar Utama & Status</th>
                    <th style="width: 250px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT id, judul, gambar, status FROM produkummi ORDER BY id DESC";
                $result = mysqli_query($koneksi, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $current_status = $row['status'];
                        $next_status_label = ($current_status == 'active') ? 'Sembunyikan' : 'Tampilkan';
                        $btn_class = ($current_status == 'active') ? 'btn-secondary' : 'btn-info';
                        
                        // 1. Cek apakah kolom gambar kosong
                        if (empty($row['gambar'])) {
                            $gambar_utama = 'no-image.png';
                            $total_gambar = 0;
                        } else {
                            // 2. Coba decode sebagai JSON
                            $arr_gambar = json_decode($row['gambar'], true);
                        
                            if (json_last_error() === JSON_ERROR_NONE && is_array($arr_gambar)) {
                                // Jika data berupa JSON yang valid (Format Baru)
                                $gambar_utama = isset($arr_gambar[0]) ? $arr_gambar[0] : 'no-image.png';
                                $total_gambar = count($arr_gambar);
                            } else {
                                // Jika gagal decode, berarti ini teks biasa dari data lama (Format Lama)
                                $gambar_utama = $row['gambar'];
                                $total_gambar = 1;
                            }
                        }
                ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td>
                            <img src="../images/<?php echo htmlspecialchars($gambar_utama); ?>" width="50" height="50" style="object-fit: cover;" class="img-thumbnail me-2">
                            <span class="badge bg-info me-2"><?php echo $total_gambar; ?> Gambar</span>
                            <span class="badge bg-<?php echo ($current_status == 'active' ? 'success' : 'secondary'); ?>">
                                <?php echo ucfirst($current_status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_produk.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm mb-1">Edit</a>
                            
                            <a href="dashboard.php?aksi=ubah_status&id=<?php echo $row['id']; ?>&current_status=<?php echo $current_status; ?>" 
                               class="btn <?php echo $btn_class; ?> btn-sm mb-1" 
                               onclick="return confirm('Yakin ingin <?php echo strtolower($next_status_label); ?> produk <?php echo htmlspecialchars($row['judul']); ?>?');">
                               <?php echo $next_status_label; ?>
                            </a>

                            <a href="dashboard.php?aksi=hapus&id=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('❗ PERINGATAN: Aksi ini akan MENGHAPUS PERMANEN produk dan SEMUA gambarnya. Lanjutkan?');">Hapus Permanen</a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center'>Belum ada data produk yang tercatat.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>