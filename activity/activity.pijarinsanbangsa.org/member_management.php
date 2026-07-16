<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan file koneksi database dan fungsi sanitasi dimuat
require_once '_header.php';

// Hak Akses: Hanya Pembina dan Super Admin
if ($_SESSION['status'] !== 'pembina' && $_SESSION['status'] !== 'super_admin') {
    header("location: dashboard.php");
    exit;
}

// Re-establish DB connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$pembina_id = $_SESSION['id'];
$message = '';

// --- A. AMBIL DATA AWAL & HELPER ---

// Ambil cluster dari pembina yang sedang login
$pembina_cluster = '';
$sql_pembina_cluster = "SELECT cluster FROM users WHERE id = ?";
$stmt_pc = $conn->prepare($sql_pembina_cluster);
$stmt_pc->bind_param("i", $pembina_id);
$stmt_pc->execute();
$result_pc = $stmt_pc->get_result();
if ($row_pc = $result_pc->fetch_assoc()) {
    $pembina_cluster = $row_pc['cluster'];
}
$stmt_pc->close();

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
    $anggota_id = (int)($_POST['anggota_id'] ?? 0); 

    // Logika 1: DAFTARKAN USER BARU (ADD NEW)
    if ($action == 'add_new') {
        $username = sanitize_input($conn, $_POST['new_username']);
        $fullname = sanitize_input($conn, $_POST['new_fullname']);
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $cluster_id = (int)($_POST['new_cluster_id'] ?? 0); // Ambil ID cluster
        $new_cluster_name = get_cluster_name_by_id($conn, $cluster_id);
        
        if (empty($new_cluster_name)) {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: Cluster yang dipilih tidak valid.</div>";
             goto end_post_logic;
        }

        $conn->begin_transaction();
        try {
            // 1. Insert ke tabel users (gunakan NAMA cluster)
            $sql_user = "INSERT INTO users (username, password, full_name, status, cluster) VALUES (?, ?, ?, 'anggota', ?)";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("ssss", $username, $password, $fullname, $new_cluster_name);
            $stmt_user->execute();
            $new_anggota_id = $stmt_user->insert_id;
            $stmt_user->close();

            // 2. Insert ke tabel bimbingan
            $sql_bimbingan = "INSERT INTO bimbingan (anggota_id, pembina_id, is_active) VALUES (?, ?, false)";
            $stmt_bimbingan = $conn->prepare($sql_bimbingan);
            $stmt_bimbingan->bind_param("ii", $new_anggota_id, $pembina_id);
            $stmt_bimbingan->execute();
            $stmt_bimbingan->close();

            $conn->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-user-plus'></i> Anggota baru **{$fullname}** berhasil didaftarkan di cluster **{$new_cluster_name}** dan dibimbing!</div>";
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($e->getCode() == 1062) {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: Username sudah digunakan!</div>";
            } else {
                 $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mendaftarkan user.</div>";
                // $error_detail = $e->getMessage();
                // $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mendaftarkan user. **Detail Error:** {$error_detail}</div>";
            }
        }

    // Logika 2: REKRUT ANGGOTA LAMA (RECRUIT EXISTING)
    } elseif ($action == 'recruit_existing' && isset($_POST['anggota_rekrut_id'])) {
        $rekrut_id = (int)$_POST['anggota_rekrut_id'];
        
        $sql_cek_cluster = "SELECT cluster FROM users WHERE id = ?";
        $stmt_cek_cluster = $conn->prepare($sql_cek_cluster);
        $stmt_cek_cluster->bind_param("i", $rekrut_id);
        $stmt_cek_cluster->execute();
        $rekrut_cluster = $stmt_cek_cluster->get_result()->fetch_assoc()['cluster'] ?? null;
        $stmt_cek_cluster->close();
        
        if ($rekrut_cluster !== $pembina_cluster) {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal merekrut: Anggota ini berada di cluster **{$rekrut_cluster}**, sementara Anda di cluster **{$pembina_cluster}**.</div>";
        } else {
            $sql = "INSERT INTO bimbingan (anggota_id, pembina_id, is_active) VALUES (?, ?, false)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $rekrut_id, $pembina_id);
            
            if ($stmt->execute()) {
                 $message = "<div class='alert alert-info'><i class='fas fa-handshake'></i> Anggota berhasil direkrut dan masuk ke bimbingan Anda!</div>";
            } else {
                 $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal merekrut anggota. Mungkin sudah aktif dibimbing oleh orang lain atau database bermasalah.</div>";
            }
            $stmt->close();
        }

    // Logika 3: KELUARKAN ANGGOTA (RELEASE MEMBER)
    } elseif ($action == 'release_member' && $anggota_id > 0) {
        $sql = "UPDATE bimbingan SET is_active=0,pembina_id=0, end_date=NOW(), last_pembina_id=? WHERE anggota_id=? AND is_active=1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $pembina_id, $anggota_id);
        if ($stmt->execute()) {
             $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Anggota berhasil dikeluarkan dari bimbingan Anda.</div>";
        } else {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mengeluarkan anggota.</div>";
        }
        $stmt->close();

    // Logika 4: UPGRADE STATUS ANGGOTA (UPGRADE STATUS)
    } elseif ($action == 'upgrade_status' && $anggota_id > 0) {
        $sql_old_status = "SELECT status FROM users WHERE id = ?";
        $stmt_os = $conn->prepare($sql_old_status);
        $stmt_os->bind_param("i", $anggota_id);
        $stmt_os->execute();
        $user_data = $stmt_os->get_result()->fetch_assoc();
        $old_status = $user_data['status'] ?? 'anggota';
        $stmt_os->close();

        $sql_upgrade = "UPDATE users SET status='pembina' WHERE id=?";
        $stmt_upgrade = $conn->prepare($sql_upgrade);
        $stmt_upgrade->bind_param("i", $anggota_id);
        
        $sql_log = "INSERT INTO log_status (user_id, old_status, new_status, changed_by_id) 
                    VALUES (?, ?, 'pembina', ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("isi", $anggota_id, $old_status, $pembina_id);
        
        if ($stmt_upgrade->execute() && $stmt_log->execute()) {
             $message = "<div class='alert alert-success'><i class='fas fa-star'></i> Status anggota berhasil dinaikkan menjadi Pembina!</div>";
        } else {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menaikkan status.</div>";
        }
        $stmt_upgrade->close();
        $stmt_log->close();

    // Logika 5: DOWNGRADE STATUS ANGGOTA (DOWNGRADE STATUS)
    } elseif ($action == 'downgrade_status' && $anggota_id > 0) {
        
        $sql_check = "SELECT full_name, status FROM users WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $anggota_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $user_data = $result_check->fetch_assoc();
        $stmt_check->close();

        if (!$user_data || $user_data['status'] === 'super_admin') {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: User tidak ditemukan atau tidak dapat menurunkan Super Admin.</div>";
        } elseif ($user_data['status'] === 'anggota') {
             $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal: User ini sudah berstatus Anggota.</div>";
        } else {
            // Pengecekan Syarat Tambahan: Pastikan TIDAK membimbing anggota aktif manapun
            $sql_bimbingan_check = "SELECT COUNT(id) AS total_binaan FROM bimbingan WHERE pembina_id = ? AND is_active = TRUE";
            $stmt_bimbingan_check = $conn->prepare($sql_bimbingan_check);
            $stmt_bimbingan_check->bind_param("i", $anggota_id);
            $stmt_bimbingan_check->execute();
            $bimbingan_count = $stmt_bimbingan_check->get_result()->fetch_assoc()['total_binaan'];
            $stmt_bimbingan_check->close();

            if ($bimbingan_count > 0) {
                $message = "<div class='alert alert-danger'><i class='fas fa-user-lock'></i> Gagal menurunkan status. **{$user_data['full_name']}** masih membimbing **{$bimbingan_count}** anggota aktif. Mohon keluarkan anggota binaan tersebut terlebih dahulu.</div>";
            } else {
                $old_status = $user_data['status']; 
                $new_status = 'anggota';

                $sql_downgrade = "UPDATE users SET status=? WHERE id=?";
                $stmt_downgrade = $conn->prepare($sql_downgrade);
                $stmt_downgrade->bind_param("si", $new_status, $anggota_id);
                
                $sql_log = "INSERT INTO log_status (user_id, old_status, new_status, changed_by_id) 
                            VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("issi", $anggota_id, $old_status, $new_status, $pembina_id);
                
                if ($stmt_downgrade->execute() && $stmt_log->execute()) {
                     $message = "<div class='alert alert-warning'><i class='fas fa-user-tag'></i> Status **{$user_data['full_name']}** berhasil diturunkan menjadi Anggota!</div>";
                } else {
                     $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menurunkan status karena masalah database.</div>";
                }
                $stmt_downgrade->close();
                $stmt_log->close();
            }
        }

    // Logika 6: PINDAH CLUSTER (Transfer Member)
    } elseif ($action == 'transfer_cluster') {
        
        $new_cluster_id = (int)($_POST['new_cluster_id'] ?? 0); 
        $new_cluster_name_text = sanitize_input($conn, $_POST['new_cluster_text'] ?? ''); 
        
        $final_new_cluster_name = '';

        $conn->begin_transaction();
        try {
            $cluster_status = " ";
            
            if ($new_cluster_id === -1) { // Opsi "Tambah Cluster Baru" dipilih
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

            } else { // Opsi Cluster yang sudah ada dipilih (ID > 0)
                $final_new_cluster_name = get_cluster_name_by_id($conn, $new_cluster_id);
                if (empty($final_new_cluster_name)) {
                    throw new Exception("ID cluster yang dipilih tidak valid.");
                }
            }

            // 2. Ambil nama anggota
            $sql_name = "SELECT full_name FROM users WHERE id = ?";
            $stmt_name = $conn->prepare($sql_name);
            $stmt_name->bind_param("i", $anggota_id);
            $stmt_name->execute();
            $anggota_nama = $stmt_name->get_result()->fetch_assoc()['full_name'];
            $stmt_name->close();

            // 3. Keluarkan dari bimbingan aktif pembina saat ini (release_member logic)
            $sql_release = "UPDATE bimbingan SET is_active=0,pembina_id = 0, end_date=NOW(), last_pembina_id=? WHERE anggota_id=? AND pembina_id=? ";
            $stmt_release = $conn->prepare($sql_release);
            $stmt_release->bind_param("iii", $pembina_id, $anggota_id, $pembina_id);
            $stmt_release->execute();
            $stmt_release->close();

            // 4. Pindahkan/Update cluster di tabel users (menggunakan NAMA cluster)
            $sql_transfer = "UPDATE users SET cluster=? WHERE id=?";
            $stmt_transfer = $conn->prepare($sql_transfer);
            $stmt_transfer->bind_param("si", $final_new_cluster_name, $anggota_id);
            $stmt_transfer->execute();
            $stmt_transfer->close();

            $conn->commit();
            $message = "<div class='alert alert-warning'><i class='fas fa-exchange-alt'></i> {$cluster_status}Anggota **{$anggota_nama}** berhasil dikeluarkan dari bimbingan Anda dan dipindahkan ke cluster **{$final_new_cluster_name}**!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal memindahkan cluster anggota: {$e->getMessage()}</div>";
        }
    }
}
end_post_logic:

// --- C. AMBIL DATA SEBELUM TAMPILAN (diulang jika ada perubahan di POST) ---

// Ambil ulang daftar cluster yang tersedia (ID dan NAMA)
$sql_clusters = "SELECT id, nama_cluster FROM cluster ORDER BY nama_cluster ASC";
$result_clusters = $conn->query($sql_clusters);
$available_clusters = [];
while ($row = $result_clusters->fetch_assoc()) {
    $available_clusters[] = ['id' => $row['id'], 'nama' => $row['nama_cluster']];
}

// TAMPILKAN DAFTAR ANGGOTA BINAAN AKTIF
$sql_members = "
SELECT
    u.id, u.full_name, u.username, u.status, u.cluster,
    (SELECT COUNT(id) FROM catatan_amalan WHERE anggota_id = u.id AND tanggal = CURDATE()) AS today_amalan_count,
        (SELECT COUNT(id) FROM bimbingan WHERE pembina_id = u.id AND is_active = TRUE) AS active_binaan_count,    
    -- Ini mungkin perlu disesuaikan jika konsep 'binaan' sekarang diwakili oleh 'groups'
    (SELECT COUNT(id) FROM groups WHERE pembina_id = u.id) AS managed_groups_count,g.nama_grup
FROM users u
JOIN bimbingan b ON u.id = b.anggota_id
JOIN groups g ON b.group_id = g.id -- Asumsi tabel bimbingan memiliki group_id
WHERE g.pembina_id = ? -- Filter berdasarkan pembina_id di tabel groups
AND b.is_active = TRUE -- Pertahankan filter keanggotaan aktif jika masih relevan
ORDER BY u.status DESC, u.full_name ASC";
$stmt_members = $conn->prepare($sql_members);
$stmt_members->bind_param("i", $pembina_id);
$stmt_members->execute();
$result_members = $stmt_members->get_result();

// Query untuk modal rekrut anggota lama
$sql_non_bimbingan = "
    SELECT u.id, u.full_name, u.status, u.cluster
    FROM users u
    LEFT JOIN bimbingan b ON u.id = b.anggota_id AND b.is_active = TRUE
    WHERE b.anggota_id IS NULL 
        AND u.status != 'super_admin' 
        AND u.id != ?
        AND u.cluster = ? 
        AND u.status != 'pembina' 
    ORDER BY u.full_name ASC
";
$stmt_non_bimbingan = $conn->prepare($sql_non_bimbingan);
$stmt_non_bimbingan->bind_param("is", $pembina_id, $pembina_cluster);
$stmt_non_bimbingan->execute();
$result_non_bimbingan = $stmt_non_bimbingan->get_result();
?>

<!DOCTYPE html>
<h1 class="mb-4 text-success"><i class="fas fa-users"></i> Manajemen Anggota Binaan (Cluster: <?= htmlspecialchars($pembina_cluster) ?>)</h1>
    <?= $message ?>
    
    <button class="btn btn-success mb-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="fas fa-plus me-1"></i> Tambah / Rekrut Anggota
    </button>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                <input type="text" id="liveSearchInput" class="form-control" placeholder="Cari Anggota (Nama / Username / Cluster)...">
            </div>
        </div>
    </div>
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Anggota Binaan Aktif Anda</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Nama/username</th>
                            <th>Cluster/Grup</th>
                            <th>Status</th>
                            <th>Amalan Hari Ini</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="anggotaTableBody">
                        <?php if ($result_members->num_rows > 0): ?>
                            <?php while($row = $result_members->fetch_assoc()): ?>
                            <tr>
                                <!--<td><?= htmlspecialchars($row['full_name']) ?></td>-->
                                <!--<td><?= htmlspecialchars($row['username']) ?></td>-->
            <td>
                <div class="mb-1">
                    <span class="badge bg-secondary" title="Cluster"><?= htmlspecialchars($row['full_name']) ?></span>
                </div>
                <div>
                    <span class="badge bg-dark" title="Grup"><?= htmlspecialchars($row['username']) ?></span>
                </div>
            </td>                                
                                <!--<td><span class="badge bg-secondary"><?= htmlspecialchars($row['cluster']) ?></span></td>-->
                                <!--<td><span class="badge bg-secondary"><?= htmlspecialchars($row['nama_grup']) ?></span></td>-->
            <td>
                <div class="mb-1">
                    <span class="badge bg-secondary" title="Cluster"><?= htmlspecialchars($row['cluster']) ?></span>
                </div>
                <div>
                    <span class="badge bg-dark" title="Grup"><?= htmlspecialchars($row['nama_grup']) ?></span>
                </div>
            </td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] == 'pembina') ? 'info' : 'primary' ?>"><?= strtoupper($row['status']) ?></span>
                                    <?php 
                                    // TAMBAHAN: Tampilkan jumlah binaan jika anggota ini adalah Pembina
                                    if ($row['status'] == 'pembina' && $row['active_binaan_count'] > 0): ?>
                                        <span class="badge bg-warning text-dark" title="Total Anggota Binaan Aktif"><?= $row['active_binaan_count'] ?> Binaan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($row['today_amalan_count'] > 0) ? 'success' : 'danger' ?>">
                                        <?= ($row['today_amalan_count'] > 0) ? 'Sudah Update' : 'Belum Update' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="report_anggota.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="Lihat Capaian"><i class="fas fa-chart-line"></i></a>
                                    
                                    <?php if ($row['status'] == 'anggota'): ?>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#upgradeModal<?= $row['id'] ?>" title="Jadikan Pembina"><i class="fas fa-arrow-up"></i></button>
                                    <?php elseif ($row['status'] == 'pembina' && $row['id'] != $pembina_id): ?>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#downgradeModal<?= $row['id'] ?>" title="Turunkan Status ke Anggota"><i class="fas fa-arrow-down"></i></button>
                                        
                                        <?php if ($row['active_binaan_count'] > 0): ?>
                                    <a href="report_binaan.php?pembina_id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Lihat Anggota Binaan Aktif">
                                       <i class="fas fa-users"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#transferModal<?= $row['id'] ?>" title="Pindah Cluster"><i class="fas fa-exchange-alt"></i></button>

                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#releaseModal<?= $row['id'] ?>" title="Keluarkan dari Bimbingan"><i class="fas fa-user-minus"></i></button>
                                </td>
                            </tr>
                            
                            <div class="modal fade" id="upgradeModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Konfirmasi Upgrade Status</h5>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="upgrade_status">
                                                <input type="hidden" name="anggota_id" value="<?= $row['id'] ?>">
                                                Apakah Anda yakin ingin menaikkan status **<?= htmlspecialchars($row['full_name']) ?>** menjadi **PEMBINA**?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Ya, Jadikan Pembina</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="downgradeModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title">Konfirmasi Downgrade Status</h5>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="downgrade_status">
                                                <input type="hidden" name="anggota_id" value="<?= $row['id'] ?>">
                                                Apakah Anda yakin ingin **menurunkan** status **<?= htmlspecialchars($row['full_name']) ?>** menjadi **ANGGOTA**?
                                                <p class="text-danger mt-2"><i class="fas fa-exclamation-circle"></i> Perhatian: Downgrade hanya bisa dilakukan jika user ini tidak aktif membimbing anggota lain.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning text-dark">Ya, Turunkan Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="transferModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-secondary text-white">
                                            <h5 class="modal-title">Transfer Cluster & Keluarkan Bimbingan</h5>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="transfer_cluster">
                                                <input type="hidden" name="anggota_id" value="<?= $row['id'] ?>">
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

                                                <p class="text-danger small"><i class="fas fa-exclamation-circle"></i> Tindakan ini otomatis akan mengeluarkan anggota ini dari bimbingan Anda!</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning">Pindah Cluster & Keluarkan Grup</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="releaseModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Konfirmasi Keluarkan Anggota</h5>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="release_member">
                                                <input type="hidden" name="anggota_id" value="<?= $row['id'] ?>">
                                                Apakah Anda yakin ingin **mengeluarkan** **<?= htmlspecialchars($row['full_name']) ?>** dari bimbingan Anda?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger">Ya, Keluarkan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">Anda belum memiliki anggota binaan aktif di cluster **<?= htmlspecialchars($pembina_cluster) ?>**.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addMemberModalLabel">Tambah / Rekrut Anggota Binaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="daftar-tab" data-bs-toggle="tab" data-bs-target="#daftar" type="button" role="tab" aria-controls="daftar" aria-selected="true">1. Daftarkan User Baru</button>
                        </li>
                        <!--<li class="nav-item" role="presentation">-->
                        <!--    <button class="nav-link" id="rekrut-tab" data-bs-toggle="tab" data-bs-target="#rekrut" type="button" role="tab" aria-controls="rekrut" aria-selected="false">2. Rekrut Anggota Lama</button>-->
                        <!--</li>-->
                    </ul>
                    
                    <div class="tab-content" id="myTabContent">
                        
                        <div class="tab-pane fade show active" id="daftar" role="tabpanel" aria-labelledby="daftar-tab">
                            <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="action" value="add_new">
                                <p class="text-muted small">Anggota baru didaftarkan dengan status awal **anggota** dan langsung dibimbing oleh Anda.</p>
                                
                                <div class="mb-3">
                                    <label for="new_cluster_id" class="form-label">Pilih Cluster Anggota Baru</label>
                                    <select name="new_cluster_id" id="new_cluster_id" class="form-select" required>
                                        <option value="">-- Pilih Cluster --</option>
                                        <?php 
                                        foreach ($available_clusters as $cluster): ?>
                                            <option value="<?= $cluster['id'] ?>" 
                                                <?= ($cluster['nama'] == $pembina_cluster) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cluster['nama']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="new_username" class="form-label">Username (unik)</label>
                                    <input type="text" name="new_username" id="new_username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_fullname" class="form-label">Nama Lengkap</label>
                                    <input type="text" name="new_fullname" id="new_fullname" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Password Awal</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="fas fa-user-plus me-1"></i> Daftarkan & Bimbing</button>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade" id="rekrut" role="tabpanel" aria-labelledby="rekrut-tab">
                            <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="action" value="recruit_existing">
                                <p class="text-muted small">Rekrut anggota yang sebelumnya terdaftar, **berada di cluster <?= htmlspecialchars($pembina_cluster) ?>**, bukan Pembina, dan tidak sedang aktif dibimbing.</p>
                                
                                <div class="mb-3">
                                    <label for="anggota_rekrut_id" class="form-label">Pilih Anggota</label>
                                    <select name="anggota_rekrut_id" id="anggota_rekrut_id" class="form-select" required>
                                        <option value="">-- Pilih Anggota --</option>
                                        <?php 
                                        if ($result_non_bimbingan->num_rows > 0) {
                                            $result_non_bimbingan->data_seek(0);
                                            while ($row = $result_non_bimbingan->fetch_assoc()): ?>
                                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?> (Cluster: <?= htmlspecialchars($row['cluster']) ?> | Status: <?= strtoupper($row['status']) ?>)</option>
                                            <?php endwhile;
                                        } else {
                                            echo '<option value="" disabled>Tidak ada user di cluster Anda yang tersedia untuk direkrut.</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary"><i class="fas fa-handshake me-1"></i> Rekrut & Mulai Bimbingan</button>
                            </form>
                        </div>
                    </div>
                </div>
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
$stmt_members->close();
$stmt_non_bimbingan->close();
$conn->close();
require_once '_footer.php';
?>