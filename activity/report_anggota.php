<?php
require_once '_header.php';

// Hak Akses: Hanya Pembina dan Super Admin
if ($_SESSION['status'] !== 'pembina' && $_SESSION['status'] !== 'super_admin') {
    header("location: dashboard.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$user_status = $_SESSION['status'];

// --- 1. Filter Tanggal & Data Anggota ---
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days')); // Default: 7 hari terakhir

// Ambil filter tanggal dari GET jika tersedia
if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['start_date']) || isset($_GET['end_date']))) {
    $start_date = sanitize_input($conn, $_GET['start_date']);
    $end_date = sanitize_input($conn, $_GET['end_date']);
}

// 1.1 Tentukan Anggota yang harus dilihat (berdasarkan status user)
$anggota_ids = [];

if ($user_status === 'pembina') {
    // Ambil semua Anggota yang dibimbing oleh Pembina ini
    $sql_anggota = "SELECT anggota_id FROM bimbingan WHERE pembina_id = ? AND is_active = TRUE";
    $stmt_anggota = $conn->prepare($sql_anggota);
    $stmt_anggota->bind_param("i", $user_id);
    $stmt_anggota->execute();
    $result_anggota = $stmt_anggota->get_result();
    while ($row = $result_anggota->fetch_assoc()) {
        $anggota_ids[] = $row['anggota_id'];
    }
    $stmt_anggota->close();
} elseif ($user_status === 'super_admin') {
    // Ambil semua Anggota di sistem
    $sql_anggota = "SELECT id FROM users WHERE status = 'anggota'";
    $result_anggota = $conn->query($sql_anggota);
    while ($row = $result_anggota->fetch_assoc()) {
        $anggota_ids[] = $row['id'];
    }
}

// Persiapan Query Laporan
if (empty($anggota_ids)) {
    $report_data = [];
    $message = "<div class='alert alert-warning shadow-sm'><i class='fas fa-info-circle'></i> Tidak ada anggota yang tercatat dalam sistem atau dalam bimbingan Anda.</div>";
} else {
    // Buat klausa WHERE IN (ID Anggota) menggunakan prepared statement
    $in_clause = implode(',', array_fill(0, count($anggota_ids), '?'));
    $anggota_where_clause = " AND ca.anggota_id IN ({$in_clause})";

    // --- 2. Query Data Laporan ---
    $sql_report = "
        SELECT 
            ca.tanggal, 
            u.full_name, 
            ag.nama_amalan, 
            ag.target,
            ag.satuan, 
            ca.jumlah_capaian
        FROM catatan_amalan ca
        JOIN users u ON ca.anggota_id = u.id
        JOIN amalan_grup ag ON ca.amalan_grup_id = ag.id
        WHERE ca.tanggal BETWEEN ? AND ? 
        {$anggota_where_clause}
        ORDER BY u.full_name ASC, ca.tanggal DESC, ag.nama_amalan ASC
    ";

    $stmt_report = $conn->prepare($sql_report);
    
    // Bind parameters: ss (untuk start/end date) diikuti oleh sebanyak jumlah anggota_ids (untuk klausa IN)
    $types = 'ss' . str_repeat('i', count($anggota_ids));
    $params = array_merge([$start_date, $end_date], $anggota_ids);
    
    // Gunakan call_user_func_array karena jumlah parameter dinamis
    $bind_names[] = $types;
    for ($i=0; $i<count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    
    call_user_func_array([$stmt_report, 'bind_param'], $bind_names);
    $stmt_report->execute();
    $result_report = $stmt_report->get_result();

    $report_data_raw = $result_report->fetch_all(MYSQLI_ASSOC);
    $stmt_report->close();
    
    // Kelompokkan data per Anggota dan Tanggal
    $report_data = [];
    foreach ($report_data_raw as $row) {
        $key = $row['full_name'] . '|' . $row['tanggal'];
        if (!isset($report_data[$key])) {
            $report_data[$key] = [
                'nama' => $row['full_name'],
                'tanggal' => $row['tanggal'],
                'amalans' => []
            ];
        }
        $report_data[$key]['amalans'][] = $row;
    }
}

// Fungsi helper untuk format tanggal ke Bahasa Indonesia
function format_date_id($date_str) {
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $parts = explode('-', $date_str);
    if (count($parts) === 3) {
        return $parts[2] . ' ' . ($bulan[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
    }
    return $date_str;
}

?>

<h1 class="mb-4 text-info"><i class="fas fa-chart-line"></i> Laporan Amalan Anggota</h1>

<?= $message ?? '' ?>

<div class="card shadow-lg mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Filter Laporan (Rentang Tanggal)</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
            </div>
            <div class="col-md-5">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($report_data)): ?>
    <div class="alert alert-success shadow-sm">
        Menampilkan laporan dari **<?= format_date_id($start_date) ?>** hingga **<?= format_date_id($end_date) ?>**.
    </div>

    <div class="accordion shadow-lg" id="reportAccordion">
        <?php 
        $current_member = '';
        $i = 0;
        foreach ($report_data as $data): 
            $i++;
            $new_member = ($data['nama'] !== $current_member);

            // Cek apakah ini anggota yang berbeda (untuk mengelompokkan tampilan)
            if ($new_member) {
                // Tutup kartu anggota sebelumnya jika bukan yang pertama
                if ($current_member !== '') {
                    echo '</tbody></table></div></div></div>'; // Tutup tabel, body, dan item accordion sebelumnya
                }
                $current_member = $data['nama'];
                
                // Mulai kartu anggota baru (Accordion Item)
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $i ?>">
                        <button class="accordion-button bg-info text-white fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>" aria-expanded="false" aria-controls="collapse<?= $i ?>">
                            <?= htmlspecialchars($data['nama']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $i ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $i ?>" data-bs-parent="#reportAccordion">
                        <div class="accordion-body p-0">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr class="table-secondary">
                                        <th style="width: 20%">Tanggal</th>
                                        <th>Amalan</th>
                                        <th style="width: 15%">Target</th>
                                        <th style="width: 15%">Capaian</th>
                                        <th style="width: 10%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
            <?php } ?>
            
            <?php 
            $rowspan_set = false;
            $row_count = count($data['amalans']);
            $current_date = $data['tanggal'];
            foreach ($data['amalans'] as $amalan_entry): 
                $is_achieved = $amalan_entry['jumlah_capaian'] >= $amalan_entry['target'];
                $status_badge = $is_achieved ? 'success' : 'danger';
            ?>
                <tr>
                    <?php if (!$rowspan_set): ?>
                        <td rowspan="<?= $row_count ?>" class="align-middle fw-bold"><?= format_date_id($amalan_entry['tanggal']) ?></td>
                        <?php $rowspan_set = true; ?>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($amalan_entry['nama_amalan']) ?></td>
                    <td><?= $amalan_entry['target'] ?> <?= htmlspecialchars($amalan_entry['satuan']) ?></td>
                    <td class="fw-bold"><?= $amalan_entry['jumlah_capaian'] ?> <?= htmlspecialchars($amalan_entry['satuan']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $status_badge ?>"><i class="fas fa-<?= $is_achieved ? 'check' : 'times' ?>"></i></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php 
            // Reset rowspan set untuk entri tanggal berikutnya (walaupun sudah otomatis di loop utama)
            if ($rowspan_set) {
                $rowspan_set = false;
            }
        endforeach; 
        
        // Tutup kartu anggota terakhir
        if ($current_member !== '') {
            echo '</tbody></table></div></div></div>'; 
        }
        ?>
    </div>
<?php else: ?>
    <?php if (!isset($message)): // Jika belum ada pesan, berikan pesan default ?>
    <div class="alert alert-info shadow-sm">
        <i class="fas fa-search"></i> Silakan pilih rentang tanggal di atas untuk menampilkan laporan amalan anggota.
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php 
$conn->close();
require_once '_footer.php';
?>