<?php
// Pastikan file koneksi database dan fungsi sanitasi dimuat
require_once '_header.php';

// Hak Akses: HANYA untuk Super Admin untuk mengelola SEMUA user
if ($_SESSION['status'] !== 'super_admin') {
    // Arahkan ke dashboard jika bukan Super Admin
    header("location: dashboard.php");
    exit;
}

// Re-establish DB connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$current_user_id = $_SESSION['id'];
$message = '';

// --- A. AMBIL DATA AWAL & HELPER ---

// Ambil daftar cluster yang tersedia dari tabel 'cluster' (ID dan NAMA)
$sql_clusters = "SELECT id, nama_cluster FROM cluster ORDER BY nama_cluster ASC";
$result_clusters = $conn->query($sql_clusters);
$available_clusters = [];
while ($row = $result_clusters->fetch_assoc()) {
    $available_clusters[] = ['id' => $row['id'], 'nama' => $row['nama_cluster']];
}

// Fungsi Helper untuk mendapatkan nama cluster dari ID
function get_cluster_name_by_id($conn, $cluster_id) {
    $sql = "SELECT nama_cluster FROM cluster WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cluster_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = $result->fetch_assoc()['nama_cluster'] ?? null;
    $stmt->close();
    return $name;
}

// --- B. LOGIKA FORM (POST HANDLER) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0); 

    // Logika 1: DAFTARKAN USER BARU (ADD NEW)
    if ($action == 'add_new_user') {
        $username = sanitize_input($conn, $_POST['new_username']);
        $fullname = sanitize_input($conn, $_POST['new_fullname']);
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $status = sanitize_input($conn, $_POST['new_status']);
        $cluster_id = (int)($_POST['new_cluster_id'] ?? 0); 
        $new_cluster_name = get_cluster_name_by_id($conn, $cluster_id);
        
        if (empty($new_cluster_name)) {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: Cluster yang dipilih tidak valid.</div>";
             goto end_post_logic;
        }

        try {
            $sql_user = "INSERT INTO users (username, password, full_name, status, cluster) VALUES (?, ?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("sssss", $username, $password, $fullname, $status, $new_cluster_name);
            $stmt_user->execute();
            $stmt_user->close();

            $message = "<div class='alert alert-success'><i class='fas fa-user-plus'></i> User **{$fullname}** ({$status}) berhasil didaftarkan di cluster **{$new_cluster_name}**!</div>";
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: Username sudah digunakan!</div>";
            } else {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mendaftarkan user.</div>";
            }
        }

    // Logika 2: UPGRADE STATUS USER
    } elseif ($action == 'upgrade_status' && $user_id > 0) {
        $new_status = sanitize_input($conn, $_POST['new_status_val']);
        
        $sql_old_status = "SELECT status FROM users WHERE id = ?";
        $stmt_os = $conn->prepare($sql_old_status);
        $stmt_os->bind_param("i", $user_id);
        $stmt_os->execute();
        $user_data = $stmt_os->get_result()->fetch_assoc();
        $old_status = $user_data['status'] ?? 'anggota';
        $stmt_os->close();

        $conn->begin_transaction();
        try {
            $sql_upgrade = "UPDATE users SET status=? WHERE id=?";
            $stmt_upgrade = $conn->prepare($sql_upgrade);
            $stmt_upgrade->bind_param("si", $new_status, $user_id);
            $stmt_upgrade->execute();
            $stmt_upgrade->close();
            
            $sql_log = "INSERT INTO log_status (user_id, old_status, new_status, changed_by_id) VALUES (?, ?, ?, ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("issi", $user_id, $old_status, $new_status, $current_user_id);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-star'></i> Status user berhasil dinaikkan menjadi **".strtoupper($new_status)."**!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menaikkan status: {$e->getMessage()}</div>";
        }

    // Logika 3: DOWNGRADE STATUS USER
    } elseif ($action == 'downgrade_status' && $user_id > 0) {
        $new_status = sanitize_input($conn, $_POST['new_status_val']);

        $sql_check = "SELECT full_name, status FROM users WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $user_data = $result_check->fetch_assoc();
        $stmt_check->close();

        if (!$user_data) {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: User tidak ditemukan.</div>";
        } elseif ($user_data['status'] === 'super_admin' && $user_id !== $current_user_id) {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: Tidak dapat menurunkan status Super Admin lain.</div>";
        } else {
            // Pengecekan Syarat: Jika user adalah 'pembina' dan status baru lebih rendah dari 'pembina'
            if ($user_data['status'] === 'pembina' && ($new_status === 'anggota')) {
                $sql_bimbingan_check = "SELECT COUNT(id) AS total_binaan FROM bimbingan WHERE pembina_id = ? AND is_active = TRUE";
                $stmt_bimbingan_check = $conn->prepare($sql_bimbingan_check);
                $stmt_bimbingan_check->bind_param("i", $user_id);
                $stmt_bimbingan_check->execute();
                $bimbingan_count = $stmt_bimbingan_check->get_result()->fetch_assoc()['total_binaan'];
                $stmt_bimbingan_check->close();

                if ($bimbingan_count > 0) {
                    $message = "<div class='alert alert-danger'><i class='fas fa-user-lock'></i> Gagal menurunkan status. User ini masih membimbing **{$bimbingan_count}** anggota aktif.</div>";
                    goto end_post_logic; 
                }
            }

            $old_status = $user_data['status']; 
            
            $conn->begin_transaction();
            try {
                $sql_downgrade = "UPDATE users SET status=? WHERE id=?";
                $stmt_downgrade = $conn->prepare($sql_downgrade);
                $stmt_downgrade->bind_param("si", $new_status, $user_id);
                $stmt_downgrade->execute();
                
                $sql_log = "INSERT INTO log_status (user_id, old_status, new_status, changed_by_id) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("issi", $user_id, $old_status, $new_status, $current_user_id);
                $stmt_log->execute();
                
                $conn->commit();
                $message = "<div class='alert alert-warning'><i class='fas fa-user-tag'></i> Status user berhasil diturunkan menjadi **".strtoupper($new_status)."**!</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menurunkan status: {$e->getMessage()}</div>";
            }
        }

    // Logika 4: PINDAH CLUSTER (Transfer User)
    } elseif ($action == 'transfer_cluster') {
        $new_cluster_id = (int)($_POST['new_cluster_id'] ?? 0);
        $new_cluster_name_text = sanitize_input($conn, $_POST['new_cluster_text'] ?? '');
        $final_new_cluster_name = '';

        $conn->begin_transaction();
        try {
            $cluster_status = " ";
            
            if ($new_cluster_id === -1) { 
                if (empty($new_cluster_name_text)) {
                    throw new Exception("Nama cluster tujuan tidak boleh kosong saat menambah baru.");
                }
                $final_new_cluster_name = $new_cluster_name_text;

                $sql_check_cluster = "SELECT id FROM cluster WHERE nama_cluster = ?";
                $stmt_check = $conn->prepare($sql_check_cluster);
                $stmt_check->bind_param("s", $final_new_cluster_name);
                $stmt_check->execute();
                
                if ($stmt_check->get_result()->num_rows == 0) {
                    $sql_insert_cluster = "INSERT INTO cluster (nama_cluster) VALUES (?)";
                    $stmt_insert = $conn->prepare($sql_insert_cluster);
                    $stmt_insert->bind_param("s", $final_new_cluster_name);
                    $stmt_insert->execute();
                    $stmt_insert->close();
                    $cluster_status = "Cluster baru **{$final_new_cluster_name}** berhasil ditambahkan. ";
                } else {
                    $cluster_status = "Cluster sudah ada. ";
                }
                $stmt_check->close();

            } else {
                $final_new_cluster_name = get_cluster_name_by_id($conn, $new_cluster_id);
                if (empty($final_new_cluster_name)) {
                    throw new Exception("ID cluster yang dipilih tidak valid.");
                }
            }

            $sql_name = "SELECT full_name FROM users WHERE id = ?";
            $stmt_name = $conn->prepare($sql_name);
            $stmt_name->bind_param("i", $user_id);
            $stmt_name->execute();
            $user_nama = $stmt_name->get_result()->fetch_assoc()['full_name'];
            $stmt_name->close();

            $sql_transfer = "UPDATE users SET cluster=? WHERE id=?";
            $stmt_transfer = $conn->prepare($sql_transfer);
            $stmt_transfer->bind_param("si", $final_new_cluster_name, $user_id);
            $stmt_transfer->execute();
            $stmt_transfer->close();

            $conn->commit();
            $message = "<div class='alert alert-warning'><i class='fas fa-exchange-alt'></i> {$cluster_status}User **{$user_nama}** berhasil dipindahkan ke cluster **{$final_new_cluster_name}**!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal memindahkan cluster user: {$e->getMessage()}</div>";
        }

    // Logika 5: EDIT PROFIL
    } elseif ($action == 'edit_profile' && $user_id > 0) {
        $fullname = sanitize_input($conn, $_POST['edit_fullname']);
        $username = sanitize_input($conn, $_POST['edit_username']);

        try {
            $sql = "UPDATE users SET full_name=?, username=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $fullname, $username, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "<div class='alert alert-success'><i class='fas fa-user-edit'></i> Profil **{$fullname}** berhasil diperbarui!</div>";
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: Username sudah digunakan!</div>";
            } else {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal memperbarui profil.</div>";
            }
        }

    // Logika 6: RESET PASSWORD
    } elseif ($action == 'reset_password' && $user_id > 0) {
        $new_password = password_hash($_POST['reset_new_password'], PASSWORD_DEFAULT);
        $sql_name = "SELECT full_name FROM users WHERE id = ?";
        $stmt_name = $conn->prepare($sql_name);
        $stmt_name->bind_param("i", $user_id);
        $stmt_name->execute();
        $user_nama = $stmt_name->get_result()->fetch_assoc()['full_name'];
        $stmt_name->close();

        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_password, $user_id);
        
        if ($stmt->execute()) {
             $message = "<div class='alert alert-info'><i class='fas fa-key'></i> Password user **{$user_nama}** berhasil direset!</div>";
        } else {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mereset password.</div>";
        }
        $stmt->close();
    }
}
end_post_logic:

// --- C. AMBIL DATA USER UNTUK TABEL ---

$sql_all_users = "
    SELECT u.id, u.full_name, u.username, u.status, u.cluster,
           (SELECT COUNT(id) FROM bimbingan WHERE pembina_id = u.id AND is_active = TRUE) AS active_binaan_count
    FROM users u
    ORDER BY FIELD(u.status, 'super_admin', 'pembina', 'anggota'), u.full_name ASC
";
$result_all_users = $conn->query($sql_all_users);
?>

<!DOCTYPE html>
<h1 class="mb-4 text-primary"><i class="fas fa-user-cog"></i> Manajemen Semua Pengguna</h1>
    <?= $message ?>
    
    <button class="btn btn-primary mb-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-plus me-1"></i> Daftarkan User Baru
    </button>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                <input type="text" id="liveSearchInput" class="form-control" placeholder="Cari Pengguna (Nama / Username / Cluster / Status)...">
            </div>
        </div>
    </div>
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Daftar Semua Pengguna Aktif</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th><th>Username</th><th>Cluster</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="anggotaTableBody">
                        <?php if ($result_all_users->num_rows > 0): ?>
                            <?php while($row = $result_all_users->fetch_assoc()): ?>
                            <?php $is_current_user = ($row['id'] == $current_user_id); ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['cluster']) ?></span></td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] == 'super_admin') ? 'danger' : (($row['status'] == 'pembina') ? 'info' : 'primary') ?>">
                                        <?= strtoupper($row['status']) ?>
                                    </span>
                                    <?php if ($is_current_user): ?>
                                        <span class="badge bg-success">(Anda)</span>
                                    <?php endif; ?>
                                    <?php if ($row['status'] == 'pembina' && $row['active_binaan_count'] > 0): ?>
                                        <span class="badge bg-warning text-dark" title="Total Anggota Binaan Aktif"><?= $row['active_binaan_count'] ?> Binaan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit Profil"><i class="fas fa-user-edit"></i></button>
                                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $row['id'] ?>" title="Reset Password"><i class="fas fa-key"></i></button>
                                    
                                    <?php if ($row['status'] == 'pembina' && $row['active_binaan_count'] > 0): ?>
                                    <a href="report_binaan.php?pembina_id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Lihat Anggota Binaan Aktif">
                                       <i class="fas fa-users"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!$is_current_user): ?>
                                        <?php if ($row['status'] == 'anggota'): ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#upgradeModal<?= $row['id'] ?>_pembina" title="Jadikan Pembina"><i class="fas fa-arrow-up"></i> Pembina</button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#upgradeModal<?= $row['id'] ?>_sa" title="Jadikan Super Admin"><i class="fas fa-arrow-up"></i> SA</button>
                                        <?php elseif ($row['status'] == 'pembina'): ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#downgradeModal<?= $row['id'] ?>_anggota" title="Turunkan ke Anggota"><i class="fas fa-arrow-down"></i> Anggota</button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#upgradeModal<?= $row['id'] ?>_sa" title="Jadikan Super Admin"><i class="fas fa-arrow-up"></i> SA</button>
                                        <?php elseif ($row['status'] == 'super_admin'): ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#downgradeModal<?= $row['id'] ?>_pembina" title="Turunkan ke Pembina"><i class="fas fa-arrow-down"></i> Pembina</button>
                                        <?php endif; ?>
                                    
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#transferModal<?= $row['id'] ?>" title="Pindah Cluster"><i class="fas fa-exchange-alt"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white"><h5 class="modal-title">Edit Profil User</h5></div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit_profile">
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <div class="mb-3">
                                                    <label for="edit_fullname" class="form-label">Nama Lengkap</label>
                                                    <input type="text" name="edit_fullname" id="edit_fullname" class="form-control" value="<?= htmlspecialchars($row['full_name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_username" class="form-label">Username</label>
                                                    <input type="text" name="edit_username" id="edit_username" class="form-control" value="<?= htmlspecialchars($row['username']) ?>" required>
                                                    <small class="text-danger">Username harus unik.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan Perubahan</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal fade" id="resetPassModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Reset Password User</h5></div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <p>Anda akan mereset password untuk user **<?= htmlspecialchars($row['full_name']) ?>**.</p>
                                                <div class="mb-3">
                                                    <label for="reset_new_password" class="form-label">Password Baru</label>
                                                    <input type="password" name="reset_new_password" id="reset_new_password" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-info">Reset Password</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php if ($row['status'] == 'anggota' || $row['status'] == 'pembina'): ?>
                                <div class="modal fade" id="upgradeModal<?= $row['id'] ?>_pembina" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white"><h5 class="modal-title">Konfirmasi Upgrade Status</h5></div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="upgrade_status">
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="new_status_val" value="pembina">
                                                    Apakah Anda yakin ingin menaikkan status **<?= htmlspecialchars($row['full_name']) ?>** menjadi **PEMBINA**?
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-info">Ya, Upgrade</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal fade" id="upgradeModal<?= $row['id'] ?>_sa" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white"><h5 class="modal-title">Konfirmasi Upgrade Status</h5></div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="upgrade_status">
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="new_status_val" value="super_admin">
                                                    Apakah Anda yakin ingin menaikkan status **<?= htmlspecialchars($row['full_name']) ?>** menjadi **SUPER ADMIN**?
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Upgrade</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($row['status'] == 'pembina' && !$is_current_user): ?>
                                <div class="modal fade" id="downgradeModal<?= $row['id'] ?>_anggota" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Konfirmasi Downgrade Status</h5></div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="downgrade_status">
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="new_status_val" value="anggota">
                                                    Apakah Anda yakin ingin menurunkan status **<?= htmlspecialchars($row['full_name']) ?>** menjadi **ANGGOTA**?
                                                    <p class="text-danger mt-2"><i class="fas fa-exclamation-circle"></i> Jika Pembina, pastikan tidak ada anggota aktif yang dibimbing.</p>
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Ya, Downgrade</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($row['status'] == 'super_admin' && !$is_current_user): ?>
                                <div class="modal fade" id="downgradeModal<?= $row['id'] ?>_pembina" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Konfirmasi Downgrade Status</h5></div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="downgrade_status">
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="new_status_val" value="pembina">
                                                    Apakah Anda yakin ingin menurunkan status **<?= htmlspecialchars($row['full_name']) ?>** menjadi **PEMBINA**?
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning text-dark">Ya, Downgrade</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$is_current_user): ?>
                            <div class="modal fade" id="transferModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-secondary text-white">
                                            <h5 class="modal-title">Transfer Cluster User</h5>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="transfer_cluster">
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <p>Anda akan memindahkan **<?= htmlspecialchars($row['full_name']) ?>** dari **Cluster <?= htmlspecialchars($row['cluster']) ?>**.</p>
                                                
                                                <div class="mb-3">
                                                    <label for="cluster_select_<?= $row['id'] ?>" class="form-label">Pilih Cluster Tujuan</label>
                                                    <select id="cluster_select_<?= $row['id'] ?>" class="form-select cluster-select-target" required name="new_cluster_id">
                                                        <option value="">-- Pilih Cluster --</option>
                                                        <?php foreach ($available_clusters as $cluster): ?>
                                                            <option value="<?= $cluster['id'] ?>" 
                                                                <?= ($cluster['nama'] == $row['cluster']) ? 'disabled' : '' ?>>
                                                                <?= htmlspecialchars($cluster['nama']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        <option value="-1">-- Tambah Cluster Baru --</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3" id="new_cluster_input_<?= $row['id'] ?>" style="display:none;">
                                                    <label for="cluster_input_<?= $row['id'] ?>" class="form-label">Nama Cluster Baru</label>
                                                    <input type="text" name="new_cluster_text" id="cluster_input_<?= $row['id'] ?>" class="form-control" placeholder="Contoh: Cluster Beta 02">
                                                    <small class="form-text text-muted">Isi nama cluster baru di sini jika opsi 'Tambah Cluster Baru' dipilih.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning"></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada pengguna yang terdaftar dalam sistem.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel">Daftarkan Pengguna Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_new_user">
                        
                        <div class="mb-3">
                            <label for="new_cluster_id" class="form-label">Pilih Cluster</label>
                            <select name="new_cluster_id" id="new_cluster_id" class="form-select" required>
                                <option value="">-- Pilih Cluster --</option>
                                <?php foreach ($available_clusters as $cluster): ?>
                                    <option value="<?= $cluster['id'] ?>"><?= htmlspecialchars($cluster['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="new_status" class="form-label">Pilih Status</label>
                            <select name="new_status" id="new_status" class="form-select" required>
                                <option value="anggota">Anggota</option>
                                <option value="pembina">Pembina</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="new_fullname" class="form-label">Nama Lengkap</label>
                            <input type="text" name="new_fullname" id="new_fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_username" class="form-label">Username (unik)</label>
                            <input type="text" name="new_username" id="new_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Awal</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Daftarkan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Search Logic (Vanilla JS)
    const input = document.getElementById('liveSearchInput');
    const tableBody = document.getElementById('anggotaTableBody');

    if (input && tableBody) {
        input.addEventListener('keyup', function() {
            const filterValue = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const rowText = rows[i].textContent.toLowerCase();
                rows[i].style.display = rowText.indexOf(filterValue) > -1 ? '' : 'none';
            }
        });
    }

    // 2. Transfer Cluster Modal Logic (Toggle input field)
    document.querySelectorAll('.cluster-select-target').forEach(selectElement => {
        selectElement.addEventListener('change', function() {
            const userId = this.id.split('_').pop(); 
            const newClusterInputDiv = document.getElementById('new_cluster_input_' + userId);
            const newClusterInputField = document.getElementById('cluster_input_' + userId);
            
            // Marker untuk "Tambah Cluster Baru" adalah value="-1"
            if (this.value === '-1') {
                // Opsi Tambah Cluster Baru dipilih
                newClusterInputDiv.style.display = 'block';
                newClusterInputField.setAttribute('required', 'required');
            } else {
                // Cluster yang sudah ada dipilih (atau default)
                newClusterInputDiv.style.display = 'none';
                newClusterInputField.removeAttribute('required');
                // Hapus nilainya jika disembunyikan agar tidak terkirim value kosong
                newClusterInputField.value = ''; 
            }
        });
    });
});
</script>


<?php 
if (isset($result_all_users)) $result_all_users->close();
if (isset($result_clusters)) $result_clusters->close();
$conn->close();
require_once '_footer.php';
?>