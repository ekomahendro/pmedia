<?php
require_once '_header.php';

// Hak Akses: Hanya Anggota dan Pembina yang bisa melihat riwayat amalannya sendiri
if ($_SESSION['status'] !== 'anggota' && $_SESSION['status'] !== 'pembina') {
    header("location: dashboard.php");
    exit;
}

// Re-establish DB connection after _header
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$message = '';

// --- LOGIKA PENGAMBILAN DATA UNTUK RIWAYAT DAN GRAFIK ---

// 1. Ambil Semua Catatan Amalan User dalam 30 hari terakhir
$sql_history = "
    SELECT 
        ca.tanggal, 
        ag.nama_amalan, 
        ca.jumlah_capaian, 
        ag.target,
        ag.satuan
    FROM catatan_amalan ca
    JOIN amalan_grup ag ON ca.amalan_grup_id = ag.id
    WHERE ca.anggota_id = ? 
      AND ca.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY ca.tanggal DESC, ag.nama_amalan ASC
";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();

$history_data = [];
$chart_raw_data = []; // Untuk pemrosesan grafik

while ($row = $result_history->fetch_assoc()) {
    $history_data[] = $row;

    // Kumpulkan data per amalan dan per tanggal untuk grafik
    $amalan_name = $row['nama_amalan'];
    $date_key = date('Y-m-d', strtotime($row['tanggal']));

    if (!isset($chart_raw_data[$amalan_name])) {
        $chart_raw_data[$amalan_name] = [
            'target' => $row['target'],
            'satuan' => $row['satuan'],
            'data' => []
        ];
    }
    // Simpan capaian harian
    $chart_raw_data[$amalan_name]['data'][$date_key] = $row['jumlah_capaian'];
}
$stmt_history->close();

// 2. Siapkan Struktur Data Final untuk Grafik (Chart.js)
$chart_final_data = [];
$all_dates = [];

// Kumpulkan semua tanggal unik dalam 30 hari terakhir
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$current_date = $start_date;

while ($current_date <= $end_date) {
    $all_dates[] = $current_date;
    $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
}

// Isi data untuk setiap amalan, mengisi 0 jika tidak ada catatan
$colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#20c997']; // Bootstrap colors

$color_index = 0;
foreach ($chart_raw_data as $amalan_name => $amalan_info) {
    $data_points = [];
    
    foreach ($all_dates as $date) {
        // Jika ada data capaian, gunakan nilainya, jika tidak ada, gunakan 0
        $data_points[] = $amalan_info['data'][$date] ?? 0;
    }

    $color = $colors[$color_index % count($colors)];
    $color_index++;

    $chart_final_data[] = [
        'label' => $amalan_name,
        'data' => $data_points,
        'borderColor' => $color,
        'backgroundColor' => $color . '33', // Tambah transparansi
        'tension' => 0.3,
        'fill' => true,
    ];
}

// Labels untuk sumbu X (tanggal)
$chart_labels = array_map(function($date) {
    return date('d M', strtotime($date));
}, $all_dates);

?>

    <h1 class="mb-4 text-primary"><i class="fas fa-chart-line"></i> Riwayat & Grafik Amalan</h1>
    
    <p class="lead text-muted">Ringkasan capaian Anda dalam 30 hari terakhir.</p>

    <div class="row mb-5">
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small">Total Catatan (30 Hari)</div>
                            <h3 class="mb-0"><?= count($history_data) ?></h3>
                        </div>
                        <i class="fas fa-list-ol fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small">Hari Aktif Mencatat (30 Hari)</div>
                            <?php 
                                $unique_days = count(array_unique(array_column($history_data, 'tanggal')));
                            ?>
                            <h3 class="mb-0"><?= $unique_days ?> Hari</h3>
                        </div>
                        <i class="fas fa-calendar-check fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small">Jumlah Amalan Unik Dilaporkan</div>
                            <?php 
                                $unique_amalan = count(array_keys($chart_raw_data));
                            ?>
                            <h3 class="mb-0"><?= $unique_amalan ?> Jenis</h3>
                        </div>
                        <i class="fas fa-award fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="card shadow-lg mb-5">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Grafik Capaian Amalan (30 Hari)</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($chart_final_data)): ?>
                <canvas id="amalanChart" style="max-height: 450px;"></canvas>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-warning"></i> Belum ada data amalan yang tercatat dalam 30 hari terakhir.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Riwayat Detail Catatan Amalan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Amalan</th>
                            <th>Capaian</th>
                            <th>Target</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history_data)): ?>
                            <?php foreach ($history_data as $data): 
                                $capaian = $data['jumlah_capaian'];
                                $target = $data['target'];
                                $status_badge = '';
                                if ($capaian >= $target) {
                                    $status_badge = '<span class="badge bg-success"><i class="fas fa-check"></i> Tercapai</span>';
                                } elseif ($capaian > 0) {
                                    $status_badge = '<span class="badge bg-warning text-dark">Progress</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-danger">Gagal</span>';
                                }
                            ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($data['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($data['nama_amalan']) ?></td>
                                <td><?= $capaian . ' ' . htmlspecialchars($data['satuan']) ?></td>
                                <td><?= $target . ' ' . htmlspecialchars($data['satuan']) ?></td>
                                <td><?= $status_badge ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada riwayat amalan yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php if (!empty($chart_final_data)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('amalanChart');
            
            const labels = <?= json_encode($chart_labels); ?>;
            const dataSets = <?= json_encode($chart_final_data); ?>;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: dataSets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Capaian Amalan per Hari'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Capaian'
                            }
                        }
                    }
                }
            });
        });
    </script>
    <?php endif; ?>

<?php 
$conn->close();
require_once '_footer.php';
?>