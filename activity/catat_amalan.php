<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '_header.php';

// Hak Akses: Anggota dan Pembina dapat mengakses DAN MENCATAT
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION['status'] !== 'anggota' && $_SESSION['status'] !== 'pembina')) {
    header("location: dashboard.php");
    exit;
}

// Variabel untuk menentukan apakah user ini memiliki hak untuk MENCATAT AMALAN.
$can_record_amalan = ($_SESSION['status'] === 'anggota' || $_SESSION['status'] === 'pembina');

// Re-establish DB connection after _header
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$user_status = $_SESSION['status'];
$tanggal_hari_ini = date('Y-m-d');
$message = '';

// --- LOGIKA FORM PENCATATAN AMALAN (TIDAK BERUBAH) ---
if ($can_record_amalan && $_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (Logika POST, UPDATE/INSERT Catatan Amalan) ...
    $amalan_grup_id = (int)($_POST['amalan_grup_id'] ?? 0);
    $jumlah_capaian = (int)($_POST['jumlah_capaian'] ?? 0);

    if ($amalan_grup_id > 0 && $jumlah_capaian >= 0) {
        
        $sql_check = "SELECT id FROM catatan_amalan WHERE anggota_id = ? AND amalan_grup_id = ? AND tanggal = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iis", $user_id, $amalan_grup_id, $tanggal_hari_ini);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $row_id = $result_check->fetch_assoc()['id'];
            $sql_action = "UPDATE catatan_amalan SET jumlah_capaian = ? WHERE id = ?";
            $stmt_action = $conn->prepare($sql_action);
            $stmt_action->bind_param("ii", $jumlah_capaian, $row_id);
        } else {
            $sql_action = "INSERT INTO catatan_amalan (anggota_id, amalan_grup_id, tanggal, jumlah_capaian) VALUES (?, ?, ?, ?)";
            $stmt_action = $conn->prepare($sql_action);
            $stmt_action->bind_param("iisi", $user_id, $amalan_grup_id, $tanggal_hari_ini, $jumlah_capaian);
        }
        $stmt_check->close();

        if ($stmt_action->execute()) {
            header("location: amalan_history.php?success=1");
            exit;
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menyimpan catatan amalan.</div>";
        }
        $stmt_action->close();

    } else {
        $message = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Mohon isi semua kolom dengan benar.</div>";
    }
}


// --- PENGAMBILAN DATA (Amalan Grup yang Relevan) ---
$relevant_owner_id = NULL;

// if ($user_status === 'anggota') {
    // Anggota hanya perlu melihat amalan global (owner_id IS NULL) dan amalan dari pembinanya.
    $sql_pembina = "SELECT pembina_id FROM bimbingan WHERE anggota_id = ? AND is_active = TRUE";
    $stmt_pembina = $conn->prepare($sql_pembina);
    $stmt_pembina->bind_param("i", $user_id);
    $stmt_pembina->execute();
    $result_pembina = $stmt_pembina->get_result();
    
    if ($row = $result_pembina->fetch_assoc()) {
        $relevant_owner_id = $row['pembina_id']; // ID Pembina anggota ini
    }
    $stmt_pembina->close();
    
// } elseif ($user_status === 'pembina') {
//     // Pembina melihat amalan global (owner_id IS NULL) dan amalan yang dibuatnya sendiri.
//     $relevant_owner_id = $user_id;
// }

// 1. Ambil daftar Amalan Grup yang aktif dan relevan
// Kriteria: owner_id IS NULL (Global) ATAU owner_id = $relevant_owner_id
// $sql_amalan_grup = "
//     SELECT id, nama_amalan, target, satuan, owner_id 
//     FROM amalan_grup 
//     WHERE is_aktif = TRUE and pembina_id = $relevant_owner_id 
//     AND (owner_id IS NULL 
//     " . ($relevant_owner_id !== NULL ? " OR owner_id = ?" : "") . ")
//     ORDER BY owner_id DESC, nama_amalan ASC
// ";
$sql_amalan_grup = "
SELECT 
    ag.id, 
    ag.nama_amalan, 
    ag.target, 
    ag.satuan, 
    ag.owner_id
FROM 
    amalan_grup ag
INNER JOIN 
    bimbingan p ON ag.group_id = p.group_id
WHERE 
    ag.is_aktif = TRUE 
    AND p.anggota_id = $relevant_owner_id
    AND p.group_id IS NOT NULL 
    AND p.group_id != 0 -- KONDISI BARU UNTUK MENGABAIKAN NILAI 0
    AND (ag.owner_id IS NULL 
        " . ($relevant_owner_id !== NULL ? " OR ag.owner_id = ?" : "") . ")
ORDER BY 
    ag.owner_id DESC, 
    ag.nama_amalan ASC
";
$stmt_amalan_grup = $conn->prepare($sql_amalan_grup);

if ($relevant_owner_id !== NULL) {
    // Jika ada ID Pembina/Owner yang relevan, bind parameter tersebut
    $stmt_amalan_grup->bind_param("i", $relevant_owner_id);
}

$stmt_amalan_grup->execute();
$result_amalan_grup = $stmt_amalan_grup->get_result();

$amalan_grup = [];
while ($row = $result_amalan_grup->fetch_assoc()) {
    $amalan_grup[$row['id']] = $row;
}
$stmt_amalan_grup->close();


// 2. Ambil catatan amalan yang sudah dibuat user hari ini (Berlaku untuk Pembina dan Anggota)
$today_amalan = [];
$sql_today_amalan = "SELECT amalan_grup_id, jumlah_capaian FROM catatan_amalan WHERE anggota_id = ? AND tanggal = ?";
$stmt_today_amalan = $conn->prepare($sql_today_amalan);
$stmt_today_amalan->bind_param("is", $user_id, $tanggal_hari_ini);
$stmt_today_amalan->execute();
$result_today_amalan = $stmt_today_amalan->get_result();

while ($row = $result_today_amalan->fetch_assoc()) {
    $today_amalan[$row['amalan_grup_id']] = $row['jumlah_capaian'];
}
$stmt_today_amalan->close();


// Cek jika ada pesan sukses dari redirect (dikosongkan agar amalan_history.php yang menampilkan)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    // Kosongkan pesan di sini
}
?>

    <h1 class="mb-4 text-success"><i class="fas fa-edit"></i> Catat Amalan Harian</h1>
    
    <?= $message ?>
    
    <?php if ($user_status === 'pembina'): ?>
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-info-circle me-1"></i> Anda login sebagai **PEMBINA**. Anda mencatat amalan untuk diri sendiri. (Item amalan: Global + Milik Anda)
        </div>
    <?php elseif ($user_status === 'anggota' && $relevant_owner_id): ?>
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-info-circle me-1"></i> Anda mencatat amalan. (Item amalan: Global + dari Pembina Anda)
        </div>
    <?php else: ?>
         <div class="alert alert-warning shadow-sm">
            <i class="fas fa-info-circle me-1"></i> Anda mencatat amalan. Hanya item amalan **Global** yang terlihat karena Anda belum memiliki Pembina aktif.
        </div>
    <?php endif; ?>

    <div class="alert alert-info shadow-sm mb-4">
        <i class="fas fa-calendar-alt me-1"></i> **Tanggal Pencatatan:** **<?= date('d F Y') ?>**
        <p class="mt-2 mb-0">Masukkan capaian Anda pada amalan-amalan yang tersedia. Jika Anda mencatat ulang, catatan lama akan diperbarui.</p>
    </div>

    <div class="row">
        <?php if (!empty($amalan_grup)): ?>
            <?php foreach ($amalan_grup as $id => $amalan): 
                $capaian_hari_ini = $today_amalan[$id] ?? 0;
                $is_achieved = ($capaian_hari_ini >= $amalan['target']);
                $card_color = $is_achieved ? 'success' : 'primary';
                
                // Tambahkan keterangan pemilik
                $owner_info = $amalan['owner_id'] === NULL ? 'Global' : 'Grup';
            ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card shadow-lg h-100 border-start border-<?= $card_color ?> border-4">
                    <div class="card-header bg-<?= $card_color ?> text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fs-5"><i class="fas fa-clipboard-list me-2"></i> <?= htmlspecialchars($amalan['nama_amalan']) ?></h5>
                        <?php if ($is_achieved): ?>
                            <span class="badge bg-light text-success"><i class="fas fa-check-circle me-1"></i> Target Tercapai</span>
                        <?php endif; ?>
                        <span class="badge bg-dark ms-2"><?= $owner_info ?></span>
                    </div>
                    
                    <div class="card-body">
                        <p class="card-text">
                            **Target Harian:** <span class="fw-bold text-dark"><?= $amalan['target'] ?> <?= htmlspecialchars($amalan['satuan']) ?></span>
                        </p>
                        
                        <?php if ($capaian_hari_ini > 0): ?>
                            <p class="text-<?= $card_color ?> border-bottom pb-2">
                                <i class="fas fa-edit me-1"></i> **Catatan Anda:** <span class="fw-bold"><?= $capaian_hari_ini ?> <?= htmlspecialchars($amalan['satuan']) ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <form method="post">
                            <input type="hidden" name="amalan_grup_id" value="<?= $id ?>">
                            <div class="mb-3">
                                <label for="capaian_<?= $id ?>" class="form-label fw-bold">Masukkan Capaian Baru (<?= htmlspecialchars($amalan['satuan']) ?>)</label>
                                <input type="number" 
                                    name="jumlah_capaian" 
                                    id="capaian_<?= $id ?>" 
                                    class="form-control form-control-lg" 
                                    min="0"
                                    value="<?= $capaian_hari_ini ?>"
                                    required>
                            </div>
                        
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-<?= $card_color ?> btn-lg w-100">
                            <i class="fas fa-save me-1"></i> Simpan Catatan
                        </button>
                    </div>
                    </form>
                    
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center shadow">
                    <i class="fas fa-info-circle"></i> Belum ada daftar amalan yang relevan yang diatur untuk Anda.
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php 
$conn->close();
require_once '_footer.php';
?>