<?php
session_start();
include('../config/koneksi.php');

// Cek otorisasi dan level
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'kasir') {
    header('Location: ../index.php');
    exit();
}

$id_kasir = $_SESSION['id_user'];
$today = date('Y-m-d');

// 1. Total Transaksi yang Dilakukan Kasir Hari Ini
$query_trans_count = "SELECT COUNT(no_faktur) as count FROM penjualan WHERE id_user = $id_kasir AND DATE(datetime_transaksi) = '$today'";
$result_trans_count = mysqli_query($koneksi, $query_trans_count);
$data_trans_count = mysqli_fetch_assoc($result_trans_count);
$jumlah_transaksi = $data_trans_count['count'] ?? 0;

// 2. Total Penjualan yang Dilakukan Kasir Hari Ini
$query_sales = "SELECT SUM(total_bayar) as total FROM penjualan WHERE id_user = $id_kasir AND DATE(datetime_transaksi) = '$today'";
$result_sales = mysqli_query($koneksi, $query_sales);
$data_sales = mysqli_fetch_assoc($result_sales);
$omset_hari_ini = number_format($data_sales['total'] ?? 0, 0, ',', '.');

// Sertakan template
include('../template.php');
?>

<h1 class="mt-4">Dashboard Kasir ✨</h1>
<p class="lead">Selamat bertugas, **<?= $_SESSION['nama_lengkap'] ?>**.</p>

<hr>

<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card bg-info text-white shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Omset Anda Hari Ini</div>
                        <div class="h5 mb-0 fw-bold">Rp <?= $omset_hari_ini ?></div>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-wallet fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card bg-warning text-dark shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Jumlah Transaksi Hari Ini</div>
                        <div class="h5 mb-0 fw-bold"><?= $jumlah_transaksi ?> Transaksi</div>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-receipt fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-4">
        <div class="card bg-primary text-white shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Quick Link</div>
                        <a href="transaksi_ecer.php" class="btn btn-sm btn-light mt-2 fw-bold">Mulai Transaksi! <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-cash-register fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="card shadow mb-4">
    <div class="card-header bg-danger text-white fw-bold">Peringatan Penting</div>
    <div class="card-body">
        <p>Mohon periksa ketersediaan produk berikut di rak/gudang:</p>
        <ul class="list-group">
            <?php
            // Ambil 5 produk dengan stok paling rendah (simulasi)
            $query_low_stock = "SELECT nama_produk, stok FROM produk WHERE stok < 5 ORDER BY stok ASC LIMIT 5";
            $result_low_stock = mysqli_query($koneksi, $query_low_stock);

            if (mysqli_num_rows($result_low_stock) > 0) {
                while($item = mysqli_fetch_assoc($result_low_stock)) {
                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                    echo $item['nama_produk'];
                    echo "<span class='badge bg-danger rounded-pill'>Stok: {$item['stok']}</span>";
                    echo "</li>";
                }
            } else {
                echo "<li class='list-group-item text-success'>Semua stok terlihat aman saat ini!</li>";
            }
            ?>
        </ul>
    </div>
</div>

<?php include('../template_footer.php'); ?>