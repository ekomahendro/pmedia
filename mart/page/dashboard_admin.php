<?php
session_start();
include('../config/koneksi.php');

// Cek otorisasi dan level
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// --- LOGIKA PENGAMBILAN DATA UNTUK DASHBOARD ---

// 1. Total Penjualan Hari Ini
$today = date('Y-m-d');
$query_sales = "SELECT SUM(total_bayar) as total FROM penjualan WHERE DATE(datetime_transaksi) = '$today'";
$result_sales = mysqli_query($koneksi, $query_sales);
$data_sales = mysqli_fetch_assoc($result_sales);
$total_penjualan_hari_ini = number_format($data_sales['total'] ?? 0, 0, ',', '.');

// 2. Jumlah Stok Kritis (< 10)
$query_critical = "SELECT COUNT(kode_produk) as count FROM produk WHERE stok < 10";
$result_critical = mysqli_query($koneksi, $query_critical);
$data_critical = mysqli_fetch_assoc($result_critical);
$stok_kritis = $data_critical['count'] ?? 0;

// 3. Jumlah Customer Baru Bulan Ini
$this_month = date('Y-m');
$query_customer = "SELECT COUNT(id_customer) as count FROM customer WHERE DATE_FORMAT(tanggal_daftar, '%Y-%m') = '$this_month'";
// Catatan: Tambahkan kolom `tanggal_daftar` di tabel `customer` untuk realita.
// $result_customer = mysqli_query($koneksi, $query_customer);
// $data_customer = mysqli_fetch_assoc($result_customer);
// $new_customer = $data_customer['count'] ?? 0;
$new_customer = 15; // Data simulasi

// 4. Data Penjualan Terlaris per Kategori (Simulasi Data Grafik)
$chart_data = [
    ['Kategori', 'Penjualan (Qty)'],
    ['Makanan', 450],
    ['Minuman', 620],
    ['ATK', 150],
    ['Perlengkapan', 200]
];
$chart_json = json_encode($chart_data);

// Sertakan template (Header, Sidebar)
include('../template.php');
?>

<h1 class="mt-4">Dashboard Admin 👋</h1>
<p class="lead">Selamat datang, <?= $_SESSION['nama_lengkap'] ?>. Berikut adalah ringkasan operasional minimarket.</p>

<hr>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-primary text-white shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Penjualan Hari Ini</div>
                        <div class="h5 mb-0 fw-bold">Rp <?= $total_penjualan_hari_ini ?></div>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-money-bill-wave fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-danger text-white shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Stok Kritis</div>
                        <div class="h5 mb-0 fw-bold"><?= $stok_kritis ?> Produk</div>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-success text-white shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Customer Baru (Bulan Ini)</div>
                        <div class="h5 mb-0 fw-bold"><?= $new_customer ?> Orang</div>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-user-plus fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-info text-white shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="text-xs fw-bold text-uppercase mb-1">Total Kategori</div>
                        <div class="h5 mb-0 fw-bold"><?= mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kategori")) ?> Jenis</div>
                    </div>
                    <div class="col-4 text-end"><i class="fas fa-tags fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white fw-bold">Laporan Penjualan Terlaris (Qty) per Kategori</div>
            <div class="card-body">
                <div id="piechart_kategori" style="width: 100%; height: 300px;"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white fw-bold">Grafik Penjualan Bulanan (Tahun <?= date('Y') ?>)</div>
            <div class="card-body">
                <canvas id="monthlySalesChart" style="width: 100%; height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
// Script untuk Grafik (Memerlukan Google Charts dan Chart.js)
// Anda harus menyertakan library ini di bagian head/akhir template.php
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    // --- Google Pie Chart ---
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable(<?= $chart_json ?>);

        var options = {
            title: 'Persentase Penjualan',
            is3D: true,
            legend: { position: 'bottom' }
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart_kategori'));
        chart.draw(data, options);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- Chart.js Monthly Sales Chart (Simulasi) ---
    var ctx = document.getElementById('monthlySalesChart').getContext('2d');
    var monthlySalesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Total Penjualan (Juta Rupiah)',
                data: [12, 19, 3, 5, 2, 3, 15, 20, 10, 25, 18, 22], // Data simulasi
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include('../template_footer.php'); // Anggap ada file penutup template ?>