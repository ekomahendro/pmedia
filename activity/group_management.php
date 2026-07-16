<?php
require_once '_header.php';

// Hak Akses: Super Admin DAN Pembina
if ($_SESSION['status'] !== 'super_admin' && $_SESSION['status'] !== 'pembina') {
    header("location: dashboard.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$message = '';

// --- LOGIKA FORM (Tambah Grup) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_group') {
    $nama_grup = sanitize_input($conn, $_POST['nama_grup']);
    
    $sql = "INSERT INTO groups (pembina_id, nama_grup) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $nama_grup);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Grup **{$nama_grup}** berhasil dibuat!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal membuat grup.</div>";
    }
    $stmt->close();
}

// --- PENGAMBILAN DATA GRUP ---
$sql_groups = "SELECT id, nama_grup FROM groups WHERE pembina_id = ? ORDER BY nama_grup ASC";
$stmt_groups = $conn->prepare($sql_groups);
$stmt_groups->bind_param("i", $user_id);
$stmt_groups->execute();
$result_groups = $stmt_groups->get_result();
$groups = $result_groups->fetch_all(MYSQLI_ASSOC);
$stmt_groups->close();
?>

    <h1 class="mb-4 text-warning"><i class="fas fa-layer-group"></i> Manajemen Grup Binaan</h1>
    <?= $message ?>
    
    <button class="btn btn-warning mb-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addGroupModal">
        <i class="fas fa-plus me-1"></i> Buat Grup Binaan Baru
    </button>
    
    <div class="alert alert-info shadow-sm">
        <i class="fas fa-info-circle"></i> Setiap Grup Binaan yang Anda buat dapat memiliki set Amalan dan daftar Anggota yang unik.
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Daftar Grup Binaan Anda (<?= count($groups) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $group): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($group['nama_grup']) ?></h6>
                                <small class="text-muted">ID Grup: <?= $group['id'] ?></small>
                            </div>
                            <div>
                                <a href="amalan_grup_management.php?group_id=<?= $group['id'] ?>" class="btn btn-sm btn-primary me-2" title="Kelola Amalan Grup">
                                    <i class="fas fa-list-check"></i> Amalan
                                </a>
                                <a href="bimbingan_management.php?group_id=<?= $group['id'] ?>" class="btn btn-sm btn-success" title="Kelola Anggota Grup">
                                    <i class="fas fa-users"></i> Anggota
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item text-center text-muted py-4">
                        <i class="fas fa-info-circle"></i> Anda belum memiliki grup binaan. Silakan buat satu!
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <div class="modal fade" id="addGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Buat Grup Binaan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_group">
                        <p class="text-muted small">Grup ini akan secara otomatis terhubung dengan ID Pembina Anda.</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Grup</label>
                            <input type="text" name="nama_grup" class="form-control" placeholder="Contoh: Grup Tahajud & Tilawah" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Buat Grup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<?php 
$conn->close();
require_once '_footer.php';
?>