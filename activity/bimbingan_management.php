<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '_header.php';

// Hak Akses: Hanya Pembina dan Super Admin
if ($_SESSION['status'] !== 'pembina' && $_SESSION['status'] !== 'super_admin') {
    header("location: dashboard.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$is_super_admin = ($_SESSION['status'] === 'super_admin');
$message = '';

// Tentukan GROUP ID yang sedang dikelola (KRITIS)
$group_id = (int)($_GET['group_id'] ?? 0);

// --- Cek Ownership Grup & Ambil Data Grup ---
if ($group_id > 0) {
    $sql_check_group = "SELECT nama_grup, pembina_id FROM groups WHERE id = ?";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("i", $group_id);
    $stmt_check_group->execute();
    $result_group = $stmt_check_group->get_result();
    
    if ($result_group->num_rows == 0) {
        header("location: group_management.php");
        exit;
    }
    $group_data = $result_group->fetch_assoc();
    $group_name = $group_data['nama_grup'];
    $group_owner_id = $group_data['pembina_id'];
    $stmt_check_group->close();
    
    // Validasi kepemilikan (kecuali Super Admin)
    if (!$is_super_admin && $group_owner_id != $user_id) {
        header("location: group_management.php");
        exit;
    }
} else {
    // Jika tidak ada group_id, arahkan ke halaman utama manajemen grup
    header("location: group_management.php");
    exit;
}


// --- LOGIKA FORM (TAMBAH/HAPUS ANGGOTA) ---

    // Logika 1: TAMBAH ANGGOTA KE GRUP
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $anggota_id = (int)($_POST['anggota_id'] ?? 0);
    $redirect_msg = '';

    // Logika 1: TAMBAH ANGGOTA KE GRUP
    if ($action == 'add_anggota' && $anggota_id > 0) {
        
        // 1. Safety Check: Cek apakah anggota sudah punya grup aktif (di grup manapun)
        // Query ini harusnya TIDAK menemukan hasil, karena sudah difilter di GET query.
        $sql_check_active = "SELECT id FROM bimbingan WHERE anggota_id = ? AND is_active = TRUE"; 
        $stmt_check_active = $conn->prepare($sql_check_active);
        $stmt_check_active->bind_param("i", $anggota_id);
        $stmt_check_active->execute();
        
        if ($stmt_check_active->get_result()->num_rows > 0) {
             // ... (Error handling jika ada grup aktif)
             $stmt_check_active->close();
        } else {
            $stmt_check_active->close(); 
            
            // 2. Cek apakah sudah pernah masuk grup ini (is_active = FALSE)
            $sql_check_history = "SELECT id FROM bimbingan WHERE anggota_id = ? AND group_id = ?";
            $stmt_check_history = $conn->prepare($sql_check_history);
            $stmt_check_history->bind_param("ii", $anggota_id, $group_id);
            $stmt_check_history->execute();
            
            if ($stmt_check_history->get_result()->num_rows > 0) {
                // KASUS UPDATE: Anggota pernah masuk grup ini, hanya perlu diaktifkan kembali.
                $stmt_check_history->close();
                
                $sql = "UPDATE bimbingan SET is_active = TRUE WHERE anggota_id = ? AND group_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $anggota_id, $group_id);
                
                if ($stmt->execute()) {
                    $message = "Anggota berhasil **diaktifkan kembali** di Grup **{$group_name}**!";
                    $redirect_msg = urlencode("<div class='alert alert-success'><i class='fas fa-user-check'></i> " . $message . "</div>");
                } else {
                    $message = "Gagal mengaktifkan anggota. Database Error: " . $stmt->error;
                    $redirect_msg = urlencode("<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . $message . "</div>");
                }
                $stmt->close();
                
            } else {
                // KASUS INSERT: Anggota baru di grup ini (atau belum punya entri di bimbingan sama sekali).
                $stmt_check_history->close(); 

                $sql = "INSERT INTO bimbingan (anggota_id, group_id, is_active) VALUES (?, ?, TRUE)"; 
                $stmt = $conn->prepare($sql);
                
                if ($stmt === false) {
                    $message = "Database Error: Gagal mempersiapkan query INSERT. " . $conn->error;
                    $redirect_msg = urlencode("<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . $message . "</div>");
                } else {
                    $stmt->bind_param("ii", $anggota_id, $group_id);
                    
                    if ($stmt->execute()) {
                        $message = "Anggota berhasil **ditambahkan** ke Grup **{$group_name}**!";
                        $redirect_msg = urlencode("<div class='alert alert-success'><i class='fas fa-user-plus'></i> " . $message . "</div>");
                    } else {
                        $message = "Gagal menambahkan anggota. Database Error: " . $stmt->error;
                        $redirect_msg = urlencode("<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . $message . "</div>");
                    }
                    $stmt->close();
                }
            }
        }
    }
    
// Logika 2: HAPUS/NONAKTIFKAN ANGGOTA DARI GRUP
    elseif ($action == 'remove_anggota' && $anggota_id > 0) {
        // Query untuk menonaktifkan keanggotaan (is_active = FALSE)
        $sql_remove = "UPDATE bimbingan SET is_active = FALSE WHERE anggota_id = ? AND group_id = ? AND is_active = TRUE"; 
        $stmt_remove = $conn->prepare($sql_remove);

        if ($stmt_remove === false) {
             $message = "Database Error: Gagal mempersiapkan query UPDATE (Remove). " . $conn->error;
             $redirect_msg = urlencode("<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . $message . "</div>");
        } else {
            // PASTIKAN BARIS bind_param INI ADA DAN BENAR!
            $stmt_remove->bind_param("ii", $anggota_id, $group_id);
            
            if ($stmt_remove->execute() && $stmt_remove->affected_rows > 0) {
                $message = "Anggota berhasil dikeluarkan dari Grup **{$group_name}**.";
                $redirect_msg = urlencode("<div class='alert alert-success'><i class='fas fa-user-minus'></i> " . $message . "</div>");
            } else {
                $message = "Gagal mengeluarkan anggota. Database Error: " . $stmt_remove->error;
                $redirect_msg = urlencode("<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . $message . "</div>");
            }
            $stmt_remove->close();
        }
    }
    
    // Redirect terakhir
    header("location: group_management.php?group_id=" . $group_id . "&msg=" . $redirect_msg);
    exit;
}

// Cek pesan dari redirect
if (isset($_GET['msg'])) {
    $message = "<div class='alert alert-success'>" . htmlspecialchars(urldecode($_GET['msg'])) . "</div>";
}


// --- PENGAMBILAN DATA ---

// 1. Ambil daftar Anggota AKTIF dalam grup ini (sama seperti sebelumnya)
$sql_binaan = "
    SELECT u.id, u.full_name, u.username
    FROM bimbingan b
    JOIN users u ON b.anggota_id = u.id
    WHERE b.group_id = ? AND b.is_active = TRUE
    ORDER BY u.full_name ASC
";
$stmt_binaan = $conn->prepare($sql_binaan);
$stmt_binaan->bind_param("i", $group_id);
$stmt_binaan->execute();
$result_binaan = $stmt_binaan->get_result();
$anggota_binaan = $result_binaan->fetch_all(MYSQLI_ASSOC);
$stmt_binaan->close();


// 2. Ambil daftar user status Anggota yang TIDAK PUNYA GRUP AKTIF
$sql_available_anggota = "
SELECT u.id, u.full_name, u.username
FROM users u
LEFT JOIN bimbingan b ON u.id = b.anggota_id AND b.is_active = TRUE
WHERE u.status IN ('anggota', 'pembina')
AND b.id IS NULL -- Anggota/Pembina tidak memiliki entri aktif di tabel bimbingan
ORDER BY u.full_name ASC
";
$result_available_anggota = $conn->query($sql_available_anggota);
$available_anggota = $result_available_anggota->fetch_all(MYSQLI_ASSOC);

?>

<h1 class="mb-4 text-primary"><i class="fas fa-users-cog"></i> Kelola Anggota Grup: **<?= $group_name ?>**</h1>
<?= $message ?>

<a href="group_management.php" class="btn btn-secondary shadow-sm mb-4">
    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Grup
</a>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-lg h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Anggota Aktif dalam Grup **<?= $group_name ?>** (<?= count($anggota_binaan) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($anggota_binaan)): ?>
                        <?php foreach ($anggota_binaan as $anggota): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($anggota['full_name']) ?></h6>
                                    <small class="text-muted">@<?= htmlspecialchars($anggota['username']) ?></small>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#removeModal<?= $anggota['id'] ?>" title="Keluarkan Anggota">
                                    <i class="fas fa-user-minus"></i> Hapus
                                </button>
                                
                                <div class="modal fade" id="removeModal<?= $anggota['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Konfirmasi Hapus</h5>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body text-center">
                                                    <input type="hidden" name="action" value="remove_anggota">
                                                    <input type="hidden" name="anggota_id" value="<?= $anggota['id'] ?>">
                                                    <p>Keluarkan **<?= htmlspecialchars($anggota['full_name']) ?>** dari grup?</p>
                                                    <p class="text-muted small">Anggota akan berstatus **tanpa grup aktif**.</p>
                                                </div>
                                                <div class="modal-footer justify-content-center">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Ya, Keluarkan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> Belum ada anggota dalam grup binaan ini.
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card shadow-lg h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Anggota Baru</h5>
            </div>
            <div class="card-body">
                <?php if (count($available_anggota) > 0): ?>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="action" value="add_anggota">
                        <div class="col-md-9">
                            <select name="anggota_id" class="form-select form-select-lg" required>
                                <option value="">-- Pilih Anggota yang Belum Punya Grup --</option>
                                <?php foreach ($available_anggota as $anggota): ?>
                                    <option value="<?= $anggota['id'] ?>">
                                        <?= htmlspecialchars($anggota['full_name']) ?> (@<?= htmlspecialchars($anggota['username']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                        </div>
                    </form>
                    <div class="alert alert-info mt-3 small">
                        Hanya anggota yang **belum memiliki grup aktif** yang ditampilkan di sini.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i> **Tidak Ada Anggota Tersedia.**
                        <p class="mt-2 mb-0">Semua user dengan status 'anggota' saat ini sudah tergabung dalam grup aktif.</p>
                    </div>
                <?php endif; ?>
                
                <a href="member_management.php" class="btn btn-outline-danger mt-3 w-100">
                    <i class="fas fa-user-plus me-1"></i> Tambah / Kelola User (Anggota)
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
require_once '_footer.php';
?>