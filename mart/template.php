<?php
// template.php - Berisi Header, Sidebar, dan Footer Bootstrap
// Digunakan oleh dashboard dan halaman page/ lainnya

// Cek sesi (Pastikan file ini dipanggil setelah session_start() dan cek otorisasi)
if (!isset($_SESSION['level'])) {
    header('Location: ../index.php'); // Redirect ke login jika belum login
    exit();
}
$nama_toko = "MINIMARKET KITA";
$user_level = $_SESSION['level'];
$user_nama = $_SESSION['nama_lengkap'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | <?= $nama_toko ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Sederhana untuk Layout Sidebar */
        body { display: flex; }
        #sidebar-wrapper {
            min-height: 100vh;
            width: 250px;
            background-color: #343a40; /* Dark sidebar */
            color: white;
        }
        #page-content-wrapper {
            flex-grow: 1;
            padding: 20px;
        }
        .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
            background-color: #212529;
        }
        .list-group-item {
            background-color: transparent;
            color: #ccc;
            border: none;
            padding: 10px 15px;
        }
        .list-group-item:hover {
            background-color: #495057;
            color: white;
        }
        /* Header style */
        .navbar-brand { font-weight: bold; }
    </style>
</head>
<body>

<div id="sidebar-wrapper">
    <div class="sidebar-heading"><?= $nama_toko ?></div>
    <div class="list-group list-group-flush">
        <a href="<?= $user_level == 'admin' ? 'dashboard_admin.php' : 'dashboard_kasir.php' ?>" class="list-group-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

        <?php if ($user_level == 'admin'): ?>
            <div class="list-group-item fw-bold text-light">MANAJEMEN MASTER</div>
            <a href="kasir.php" class="list-group-item"><i class="fas fa-users-cog"></i> Kelola Kasir</a>
            <a href="produk.php" class="list-group-item"><i class="fas fa-boxes"></i> Data Produk</a>
            <a href="kategori.php" class="list-group-item"><i class="fas fa-tags"></i> Kategori</a>
            <a href="supplier.php" class="list-group-item"><i class="fas fa-truck-loading"></i> Supplier</a>
            <a href="customer.php" class="list-group-item"><i class="fas fa-user-friends"></i> Data Customer</a>
            
            <div class="list-group-item fw-bold text-light">TRANSAKSI & INVENTORY</div>
            <a href="barang_masuk.php" class="list-group-item"><i class="fas fa-cart-arrow-down"></i> Barang Masuk</a>
            <a href="transaksi.php" class="list-group-item"><i class="fas fa-cash-register"></i> Transaksi Ecer/Grosir</a>
            <a href="retur_produk.php" class="list-group-item"><i class="fas fa-undo"></i> Return Produk</a>

            <div class="list-group-item fw-bold text-light">LAPORAN</div>
            <a href="laporan_penjualan.php" class="list-group-item"><i class="fas fa-chart-line"></i> Laporan Penjualan</a>

        <?php else: ?>
            <a href="produk.php" class="list-group-item"><i class="fas fa-boxes"></i> Produk</a>
            <a href="transaksi_ecer.php" class="list-group-item"><i class="fas fa-receipt"></i> Transaksi Ecer</a>
            <a href="transaksi_grosir.php" class="list-group-item"><i class="fas fa-shopping-cart"></i> Transaksi Grosir</a>
            <a href="customer.php" class="list-group-item"><i class="fas fa-user-friends"></i> Data Customer</a>
            <a href="retur_produk.php" class="list-group-item"><i class="fas fa-undo"></i> Return Produk</a>
            <a href="laporan_harian.php" class="list-group-item"><i class="fas fa-file-alt"></i> Laporan Harian</a>

        <?php endif; ?>
    </div>
</div>

<div id="page-content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><?= $nama_toko ?></a>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> **<?= $user_nama ?>** (<?= strtoupper($user_level) ?>)
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="#">Profil</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid pt-4">
        ```

### D. Logout (`logout.php`)

```php
<?php
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hapus sesi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Redirect ke halaman login
header("Location: index.php");
exit;
?>