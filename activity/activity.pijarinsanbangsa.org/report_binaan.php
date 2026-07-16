<?php
// Pastikan file koneksi database dan fungsi sanitasi dimuat
require_once '_header.php';

// Hak Akses: 
// 1. Super Admin (akses penuh)
// 2. Pembina, ASALKAN ID Pembina yang diminta (pembina_id di GET) adalah BINAAN AKTIFNYA.
// Karena Pembina hanya bisa melihat binaan dari anggota yang dibimbingnya (termasuk anggota yang sudah jadi Pembina), 
// kita akan izinkan akses ini.

$current_user_id = $_SESSION['id'];
$current_status = $_SESSION['status'];

// Re-establish DB connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$pembina_id = (int)($_GET['pembina_id'] ?? 0);

// --- Cek Hak Akses yang Diperlonggar ---

$has_access = false;

if ($current_status === 'super_admin') {
    // Super Admin selalu memiliki akses
    $has_access = true;
} elseif ($current_status === 'pembina' && $pembina_id > 0) {
    // Pembina hanya boleh melihat report binaan jika ID yang diminta (pembina_id)
    // adalah anggota yang sedang AKFIF dibimbing oleh Pembina yang sedang login.
    
    $sql_check_binaan = "SELECT COUNT(id) AS is_binaan FROM bimbingan WHERE anggota_id = ? AND pembina_id = ? AND is_active = TRUE";
    $stmt_check = $conn->prepare($sql_check_binaan);
    $stmt_check->bind_param("ii", $pembina_id, $current_user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if ($result_check['is_binaan'] > 0) {
        $has_access = true;
    }
}

// Jika tidak ada akses, redirect
if (!$has_access) {
    $conn->close();
    header("location: dashboard.php");
    exit;
}

// Lanjutkan logika jika memiliki akses
// 1. Ambil detail Pembina
$sql_pembina = "SELECT full_name, cluster, status FROM users WHERE id = ?";
$stmt_pembina = $conn->prepare($sql_pembina);
$stmt_pembina->bind_param("i", $pembina_id);
$stmt_pembina->execute();
$result_pembina = $stmt_pembina->get_result();

if ($result_pembina->num_rows === 0) {
    $pembina_name = "Pembina Tidak Ditemukan";
    $pembina_cluster = "";
    $pembina_status = "";
    $is_valid_pembina = false;
} else {
    $pembina_data = $result_pembina->fetch_assoc();
    $pembina_name = htmlspecialchars($pembina_data['full_name']);
    $pembina_cluster = htmlspecialchars($pembina_data['cluster']);
    $pembina_status = htmlspecialchars($pembina_data['status']);
    $is_valid_pembina = true;

    // Pastikan user ini adalah seorang Pembina atau Super Admin (sesuai data user)
    if ($pembina_status !== 'pembina' && $pembina_status !== 'super_admin') {
        $is_valid_pembina = false;
        $pembina_name .= " (Bukan Pembina)";
    }
}
$stmt_pembina->close();


// 2. Ambil daftar Anggota Binaan Aktif (HANYA jika Pembina ID tersebut valid)
$anggota_binaan = [];
if ($is_valid_pembina) {
    $sql_members = "
        SELECT u.id, u.full_name, u.username, u.status, u.cluster,
               (SELECT COUNT(id) FROM catatan_amalan WHERE anggota_id=u.id AND tanggal=CURDATE()) AS today_amalan_count
        FROM users u
        JOIN bimbingan b ON u.id = b.anggota_id
        WHERE b.pembina_id = ? AND b.is_active = TRUE
        ORDER BY u.full_name ASC
    ";
    $stmt_members = $conn->prepare($sql_members);
    $stmt_members->bind_param("i", $pembina_id);
    $stmt_members->execute();
    $result_members = $stmt_members->get_result();

    while($row = $result_members->fetch_assoc()) {
        $anggota_binaan[] = $row;
    }
    $stmt_members->close();
}

$conn->close();
?>

<!DOCTYPE html>
<h1 class="mb-4 text-warning"><i class="fas fa-chart-bar"></i> Laporan Binaan Aktif</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Detail Pembina yang Dipilih</h5>
        </div>
        <div class="card-body">
            <?php if (!$is_valid_pembina): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ID Pembina tidak valid atau user yang dimaksud bukan Pembina/Super Admin.
                </div>
                <a href="user_management.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Manajemen User</a>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nama:</strong> <?= $pembina_name ?></p>
                        <p><strong>Cluster:</strong> <span class="badge bg-secondary"><?= $pembina_cluster ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge bg-<?= ($pembina_status == 'super_admin') ? 'danger' : 'info' ?>"><?= strtoupper($pembina_status) ?></span></p>
                        <p><strong>Total Binaan Aktif di bawahnya:</strong> <span class="badge bg-primary"><?= count($anggota_binaan) ?> Anggota</span></p>
                    </div>
                </div>
                <a href="member_management.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Kembali ke Manajemen Anggota</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($is_valid_pembina): ?>
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Daftar Anggota Binaan Aktif Oleh <?= $pembina_name ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Cluster</th>
                                <th>Status</th>
                                <th>Amalan Hari Ini</th>
                                <th>Aksi Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($anggota_binaan) > 0): ?>
                                <?php foreach($anggota_binaan as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['cluster']) ?></span></td>
                                    <td><span class="badge bg-<?= ($row['status'] == 'pembina') ? 'info' : 'primary' ?>"><?= strtoupper($row['status']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= ($row['today_amalan_count'] > 0) ? 'success' : 'danger' ?>">
                                            <?= ($row['today_amalan_count'] > 0) ? 'Sudah Update' : 'Belum Update' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="report_anggota.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="Lihat Capaian Anggota"><i class="fas fa-chart-line"></i> Report</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted">Pembina ini tidak memiliki anggota binaan aktif saat ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php 
require_once '_footer.php';
?>