<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Proteksi Akses: Hanya Super Admin dan Demo yang boleh masuk ke manajemen paket
if ($_SESSION['role'] != 'Super Admin' && $_SESSION['role'] != 'Demo') {
    echo "<script>alert('Akses Ditolak! Anda tidak memiliki hak akses ke halaman ini.'); window.location='admin_bookings.php';</script>";
    exit;
}

// Variabel penanda untuk mempermudah pengecekan status demo di elemen HTML bawah
$is_demo = ($_SESSION['role'] == 'Demo');

// Logika Ganti Header Homepage
if(isset($_POST['update_header'])){
    if($is_demo) {
        echo "<script>alert('Gagal! Akun DEMO tidak diizinkan mengubah banner.'); window.location='admin.php';</script>";
        exit;
    }
    $img = $_FILES['header_file']['name'];
    if($img != "") {
        move_uploaded_file($_FILES['header_file']['tmp_name'], "img/".$img);
        mysqli_query($conn, "UPDATE tra_settings SET header_img='$img' WHERE id_setting=1");
        echo "<script>alert('Header homepage berhasil diperbarui!'); window.location='admin.php';</script>";
    }
}

// Logika Tambah Paket Wisata Baru
if(isset($_POST['tambah'])){
    if($is_demo) {
        echo "<script>alert('Gagal! Akun DEMO tidak diizinkan menambahkan paket baru.'); window.location='admin.php';</script>";
        exit;
    }
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $dest = mysqli_real_escape_string($conn, $_POST['destinasi']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $tgl_m = mysqli_real_escape_string($conn, $_POST['tgl_m']);
    $tgl_s = mysqli_real_escape_string($conn, $_POST['tgl_s']);
    $kat = mysqli_real_escape_string($conn, $_POST['kategori']);
    
    $img = $_FILES['gambar']['name'];
    move_uploaded_file($_FILES['gambar']['tmp_name'], "img/".$img);
    
    $sql = "INSERT INTO tra_paket (nama_paket, destinasi, deskripsi, harga, tgl_mulai, tgl_selesai, kategori, gambar, is_active) 
            VALUES ('$nama', '$dest', '$desc', '$harga', '$tgl_m', '$tgl_s', '$kat', '$img', 1)";
    
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Paket wisata berhasil ditambahkan!'); window.location='admin.php';</script>";
    }
}

// Logika Ubah Status Tampil/Sembunyi (Hide/Show)
if(isset($_GET['toggle_status'])){
    if($is_demo) {
        echo "<script>alert('Gagal! Akun DEMO tidak diizinkan mengubah status visibilitas paket.'); window.location='admin.php';</script>";
        exit;
    }
    $id_toggle = mysqli_real_escape_string($conn, $_GET['toggle_status']);
    $status_skrg = mysqli_real_escape_string($conn, $_GET['current']);
    $status_baru = ($status_skrg == 1) ? 0 : 1;
    
    mysqli_query($conn, "UPDATE tra_paket SET is_active='$status_baru' WHERE id_paket='$id_toggle'");
    header("Location: admin.php");
    exit;
}

// Logika Hapus Paket Wisata
if(isset($_GET['hapus'])){
    if($is_demo) {
        echo "<script>alert('Gagal! Akun DEMO tidak diizinkan menghapus paket.'); window.location='admin.php';</script>";
        exit;
    }
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM tra_paket WHERE id_paket='$id_hapus'");
    header("Location: admin.php");
    exit;
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Paket - Maluku Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Roboto, sans-serif; }
        .sidebar { background: #1e293b; min-height: 100vh; color: #fff; position: sticky; top: 0; }
        .sidebar .nav-link { color: #94a3b8; border-radius: 8px; margin-bottom: 5px; padding: 10px 15px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #334155; color: #fff; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Memanggil Navigasi Sidebar (Mobile & Desktop Support) -->
        <?php include 'sidebar.php'; ?>

        <!-- Kolom Konten Utama -->
        <div class="col-12 col-md-9 col-lg-10 p-3 p-md-4 p-lg-5">
            
            <!-- Top Dashboard Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-0">Manajemen Paket Wisata</h4>
                    <small class="text-muted">Tambah, atur status tampil, atau hapus katalog trip Anda.</small>
                </div>
                <div>
                    <?php if($is_demo): ?>
                        <span class="badge bg-danger p-2 me-2"><i class="bi bi-shield-lock-fill me-1"></i> Mode Demo (Terbatas)</span>
                    <?php endif; ?>
                    <a href="index.php" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold"><i class="bi bi-eye me-1"></i> Lihat Live Web</a>
                </div>
            </div>

            <!-- Form & Banner Grid -->
            <div class="row">
                <!-- Form Tambah Paket -->
                <div class="col-lg-8 mb-4">
                    <div class="card card-custom p-4 bg-white shadow-sm">
                        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-plus-circle-fill me-2 text-success"></i>Tambah Paket Wisata Baru</h5>
                        <form method="POST" enctype="multipart/form-data" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">NAMA PAKET</label>
                                <input type="text" name="nama" class="form-control bg-light" placeholder="Contoh: Banda Neira Heritage" required <?= $is_demo ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">DESTINASI</label>
                                <input type="text" name="destinasi" class="form-control bg-light" placeholder="Contoh: Banda Islands" required <?= $is_demo ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">HARGA (RP)</label>
                                <input type="number" name="harga" class="form-control bg-light" placeholder="5500000" required <?= $is_demo ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">KATEGORI</label>
                                <select name="kategori" class="form-select bg-light" <?= $is_demo ? 'disabled' : '' ?>>
                                    <option value="Island Hopping">Island Hopping</option>
                                    <option value="Diving">Diving</option>
                                    <option value="Cultural">Cultural</option>
                                    <option value="Adventure">Adventure</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">UPLOAD FOTO</label>
                                <input type="file" name="gambar" class="form-control bg-light" <?= $is_demo ? 'required disabled' : 'required' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">TANGGAL MULAI PERIODE</label>
                                <input type="date" name="tgl_m" class="form-control bg-light" required <?= $is_demo ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">TANGGAL SELESAI PERIODE</label>
                                <input type="date" name="tgl_s" class="form-control bg-light" required <?= $is_demo ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">DESKRIPSI LENGKAP PAKET</label>
                                <textarea name="desc" class="form-control bg-light" rows="3" placeholder="Tuliskan itinerary singkat..." required <?= $is_demo ? 'disabled' : '' ?>></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <!-- Jika demo, tombol simpan di-disabled -->
                                <button type="submit" name="tambah" class="btn btn-success px-4 fw-semibold" <?= $is_demo ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i> Simpan Paket</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Pengaturan Banner -->
                <div class="col-lg-4 mb-4">
                    <div class="card card-custom p-4 bg-white shadow-sm">
                        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-image text-primary me-2"></i>Header Banner</h5>
                        <p class="text-muted small">Ganti gambar latar belakang utama (Hero banner) halaman depan.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="header_file" class="form-control bg-light" <?= $is_demo ? 'required disabled' : 'required' ?>>
                            </div>
                            <!-- Jika demo, tombol update banner di-disabled -->
                            <button type="submit" name="update_header" class="btn btn-primary btn-sm w-100 fw-semibold" <?= $is_demo ? 'disabled' : '' ?>><i class="bi bi-upload me-1"></i> Perbarui Banner</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bagian Tabel List Paket -->
            <div class="card card-custom border-0 bg-white shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark m-0">Semua Katalog Paket</h5>
                    <form method="GET" action="" class="d-flex" style="max-width: 300px;">
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" class="form-control bg-light border-end-0" placeholder="Cari nama paket..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-light border border-start-0 text-muted" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Modifikasi Tabel khusus Mobile -->
                <div class="table-responsive shadow-sm rounded-3">
                    <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3" style="width: 80px;">Foto</th>
                                <th>Nama Paket</th>
                                <th>Kategori</th>
                                <th>Periode Berlaku</th>
                                <th>Harga / Orang</th>
                                <th class="pe-3 text-center">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM tra_paket WHERE nama_paket LIKE '%$search%' ORDER BY id_paket DESC");
                            if(mysqli_num_rows($res) > 0) {
                                while($row = mysqli_fetch_array($res)){ 
                                    $status_tampil = $row['is_active'];
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <img src="img/<?= $row['gambar'] ?>" class="rounded shadow-sm" style="width: 65px; height: 42px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_paket']) ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['destinasi']) ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.75rem;"><?= $row['kategori'] ?></span></td>
                                        <td>
                                            <small class="text-muted d-block"><i class="bi bi-calendar-event me-1"></i><?= date('d M y', strtotime($row['tgl_mulai'])) ?></small>
                                            <small class="text-muted d-block"><i class="bi bi-arrow-right-short me-1"></i><?= date('d M y', strtotime($row['tgl_selesai'])) ?></small>
                                        </td>
                                        <td class="fw-bold text-dark">Rp <?= number_format($row['harga']) ?></td>
                                        <td class="pe-3 text-center">
                                            <div class="btn-group btn-group-sm">
                                                
                                                <!-- KONTROL HIDE/SHOW UNTUK AKUN DEMO -->
                                                <?php if($is_demo): ?>
                                                    <!-- Jika akun demo, tombol diubah menjadi button biasa (bukan link) yang disabled -->
                                                    <button type="button" class="btn btn-secondary fw-semibold opacity-50" style="cursor: not-allowed;" title="Fitur tidak tersedia untuk akun Demo">
                                                        <i class="bi <?= $status_tampil == 1 ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?> me-1"></i> <?= $status_tampil == 1 ? 'Tampil' : 'Hidden' ?>
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Jika Super Admin, link normal berjalan -->
                                                    <?php if($status_tampil == 1): ?>
                                                        <a href="?toggle_status=<?= $row['id_paket'] ?>&current=1" class="btn btn-success fw-semibold" title="Klik untuk Hide">
                                                            <i class="bi bi-eye-fill me-1"></i> Tampil
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?toggle_status=<?= $row['id_paket'] ?>&current=0" class="btn btn-secondary fw-semibold" title="Klik untuk Show">
                                                            <i class="bi bi-eye-slash-fill me-1"></i> Hidden
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <!-- TOMBOL EDIT: Tetap aktif agar akun demo bisa masuk ke halaman melihat detail/mengedit form -->
                                                <a href="edit_paket.php?id=<?= $row['id_paket'] ?>" class="btn btn-outline-warning" title="Edit Data"><i class="bi bi-pencil"></i></a>

                                                <!-- KONTROL HAPUS UNTUK AKUN DEMO -->
                                                <?php if($is_demo): ?>
                                                    <button type="button" class="btn btn-outline-secondary opacity-50" style="cursor: not-allowed;" title="Fitur tidak tersedia untuk akun Demo"><i class="bi bi-trash"></i></button>
                                                <?php else: ?>
                                                    <a href="?hapus=<?= $row['id_paket'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus paket ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>

                                            </div>
                                        </td>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-folder-x fs-3 d-block mb-1"></i> Paket tidak ditemukan.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <!-- End Kolom Konten Utama -->
    </div> <!-- End Row -->
</div> <!-- End Container fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>