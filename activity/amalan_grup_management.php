<?php
ob_start();
require_once '_header.php';

// Hak Akses: Super Admin DAN Pembina
if ($_SESSION['status'] !== 'super_admin' && $_SESSION['status'] !== 'pembina') {
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

// Cek ownership grup (kecuali Super Admin)
if (!$is_super_admin && $group_id > 0) {
    $sql_check_group = "SELECT nama_grup FROM groups WHERE id = ? AND pembina_id = ?";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("ii", $group_id, $user_id);
    $stmt_check_group->execute();
    $result_group = $stmt_check_group->get_result();
    if ($result_group->num_rows == 0) {
        // Jika grup tidak ditemukan atau bukan miliknya, redirect ke manajemen grup utama
        header("location: group_management.php");
        exit;
    }
    $group_name = $result_group->fetch_assoc()['nama_grup'];
    $stmt_check_group->close();
} elseif ($group_id == 0) {
    // Jika tidak ada group_id, arahkan ke halaman utama manajemen grup
    header("location: group_management.php");
    exit;
} else {
    // Untuk Super Admin, ambil nama grup saja
    $sql_group_name = "SELECT nama_grup FROM groups WHERE id = ?";
    $stmt_group_name = $conn->prepare($sql_group_name);
    $stmt_group_name->bind_param("i", $group_id);
    $stmt_group_name->execute();
    $group_name = $stmt_group_name->get_result()->fetch_assoc()['nama_grup'] ?? "Grup Tidak Dikenal";
    $stmt_group_name->close();
}


// --- LOGIKA FORM (Tambah/Edit/Hapus) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // Logika 1: TAMBAH AMALAN BARU
    if ($action == 'add_amalan') {
        $nama_amalan = sanitize_input($conn, $_POST['nama_amalan']);
        $target = (int)($_POST['target'] ?? 0);
        $satuan = sanitize_input($conn, $_POST['satuan']);
        
        // Amalan yang dibuat terhubung ke group_id
        $sql = "INSERT INTO amalan_grup (nama_amalan, target, satuan, is_aktif, group_id) VALUES (?, ?, ?, TRUE, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $nama_amalan, $target, $satuan, $group_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Item amalan **{$nama_amalan}** berhasil ditambahkan ke Grup **{$group_name}**!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menambahkan amalan. Error: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
    
    // Logika 2: EDIT AMALAN
    elseif ($action == 'edit_amalan') {
        $amalan_id = (int)($_POST['amalan_id'] ?? 0);
        $nama_amalan = sanitize_input($conn, $_POST['nama_amalan']);
        $target = (int)($_POST['target'] ?? 0);
        $satuan = sanitize_input($conn, $_POST['satuan']);
        
        // Update berdasarkan ID Amalan dan GROUP ID saat ini
        $sql = "UPDATE amalan_grup SET nama_amalan=?, target=?, satuan=? WHERE id=? AND group_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisii", $nama_amalan, $target, $satuan, $amalan_id, $group_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Amalan berhasil diperbarui.</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal memperbarui. Pastikan amalan ini milik grup ini.</div>";
        }
        $stmt->close();
    }
    // Redirect untuk menghindari form resubmission
    header("location: amalan_grup_management.php?group_id=" . $group_id . "&msg=" . urlencode(strip_tags($message)));
    exit;
}

// Cek pesan dari redirect
if (isset($_GET['msg'])) {
    $message = "<div class='alert alert-success'>" . htmlspecialchars($_GET['msg']) . "</div>";
}


// --- PENGAMBILAN DATA AMALAN GRUP ---
$sql_amalan = "
    SELECT id, nama_amalan, target, satuan, group_id
    FROM amalan_grup
    WHERE group_id = ?
    ORDER BY nama_amalan ASC
";
$stmt_amalan = $conn->prepare($sql_amalan);
$stmt_amalan->bind_param("i", $group_id);
$stmt_amalan->execute();
$result_amalan = $stmt_amalan->get_result();
?>

    <h1 class="mb-4 text-warning"><i class="fas fa-list-check"></i> Kelola Amalan Grup: **<?= $group_name ?>**</h1>
    <?= $message ?>
    
    <div class="d-flex justify-content-between mb-4">
        <a href="group_management.php" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Grup
        </a>
        <button class="btn btn-warning shadow-sm" data-bs-toggle="modal" data-bs-target="#addAmalanModal">
            <i class="fas fa-plus me-1"></i> Tambah Item Amalan Baru
        </button>
    </div>

    <div class="alert alert-info shadow-sm">
        <i class="fas fa-info-circle"></i> Amalan yang Anda tambahkan di sini hanya akan terlihat oleh anggota yang tergabung dalam Grup **<?= $group_name ?>**.
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Daftar Amalan Grup</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Amalan</th><th>Target</th><th>Satuan</th><th>Grup ID</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_amalan->num_rows > 0): ?>
                            <?php while($row = $result_amalan->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_amalan']) ?></td>
                                <td><?= $row['target'] ?></td>
                                <td><?= htmlspecialchars($row['satuan']) ?></td>
                                <td><span class="badge bg-secondary"><?= $row['group_id'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit Amalan"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Edit Amalan: <?= htmlspecialchars($row['nama_amalan']) ?></h5>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit_amalan">
                                                <input type="hidden" name="amalan_id" value="<?= $row['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Amalan</label>
                                                    <input type="text" name="nama_amalan" class="form-control" value="<?= htmlspecialchars($row['nama_amalan']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Target Harian</label>
                                                    <input type="number" name="target" class="form-control" value="<?= $row['target'] ?>" min="1" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Satuan (Contoh: Rakaat, Kali, Lembar)</label>
                                                    <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($row['satuan']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada item amalan di grup ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addAmalanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Tambah Amalan untuk Grup **<?= $group_name ?>**</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_amalan">
                        <div class="mb-3">
                            <label class="form-label">Nama Amalan</label>
                            <input type="text" name="nama_amalan" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Harian</label>
                            <input type="number" name="target" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="satuan" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Tambah Amalan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<?php 
$stmt_amalan->close();
$conn->close();
require_once '_footer.php';
?>