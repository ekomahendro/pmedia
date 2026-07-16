<?php
require_once '_header.php';

// Hak Akses: HANYA Anggota, Pembina, Super Admin
if ($_SESSION['status'] !== 'anggota' && $_SESSION['status'] !== 'pembina' && $_SESSION['status'] !== 'super_admin') {
    header("location: dashboard.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$user_status = $_SESSION['status']; // Ambil status pengguna
$message = ''; // Variabel untuk menyimpan pesan notifikasi

// --- DEFINISI VARIABEL PEMBINA (ASUMSI SUDAH ADA DARI SESI/LOAD USER) ---
$pembina_id = ($user_status === 'pembina') ? $user_id : null;
// Asumsi 'cluster' disimpan di sesi untuk Pembina
$pembina_cluster = ($user_status === 'pembina') ? ($_SESSION['cluster'] ?? null) : null; 
// ------------------------------------------------------------------------

// Variabel untuk informasi grup
$group_id = null;
$group_name = null;
$pembina_name = null;

// =========================================================================
// !!! LOGIKA 1: PEMROSESAN FORM REKRUT ANGGOTA LAMA (RECRUIT EXISTING) !!!
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recruit_existing' && $user_status === 'pembina') {
    
    $rekrut_id = (int)$_POST['anggota_rekrut_id'];
    
    // Cari grup aktif Pembina (tempat anggota baru akan dimasukkan)
    // NOTE: Logika ini harus diulang/diperoleh di sini sebelum proses insert
    $sql_pembina_group = "SELECT b.group_id FROM bimbingan b WHERE b.pembina_id = ? AND b.is_active = TRUE LIMIT 1";
    $stmt_pbg = $conn->prepare($sql_pembina_group);
    $stmt_pbg->bind_param("i", $pembina_id);
    $stmt_pbg->execute();
    $pembina_group_id = $stmt_pbg->get_result()->fetch_assoc()['group_id'] ?? null;
    $stmt_pbg->close();

    if (!$pembina_group_id) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal merekrut: Anda (Pembina) tidak memiliki Grup Bimbingan aktif yang dapat diisi.</div>";
        goto end_post_logic;
    }
    
    // Cek Cluster Anggota yang akan direkrut
    $sql_cek_cluster = "SELECT full_name, cluster FROM users WHERE id = ?";
    $stmt_cek_cluster = $conn->prepare($sql_cek_cluster);
    $stmt_cek_cluster->bind_param("i", $rekrut_id);
    $stmt_cek_cluster->execute();
    $result_rekrut = $stmt_cek_cluster->get_result()->fetch_assoc();
    $rekrut_cluster = $result_rekrut['cluster'] ?? null;
    $rekrut_name = $result_rekrut['full_name'] ?? 'Anggota';
    $stmt_cek_cluster->close();
    
    if ($rekrut_cluster !== $pembina_cluster) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal merekrut: Anggota ini berada di cluster **{$rekrut_cluster}**, sementara Grup Anda di cluster **{$pembina_cluster}**.</div>";
    } else {
        // !!! PERBAIKAN UTAMA: Tambahkan group_id ke query INSERT bimbingan !!!
        $sql = "INSERT INTO bimbingan (anggota_id, group_id, pembina_id, is_active) VALUES (?, ?, ?, TRUE)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $rekrut_id, $pembina_group_id, $pembina_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'><i class='fas fa-handshake'></i> Anggota **{$rekrut_name}** berhasil direkrut dan masuk ke grup Anda!</div>";
        } else {
            // Error Code 1062 = Duplicate entry (mungkin sudah ada bimbingan aktif)
            if ($conn->errno == 1062) {
                 $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal merekrut anggota. Anggota ini sudah aktif dibimbing oleh orang lain.</div>";
            } else {
                 $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal merekrut anggota. Database bermasalah.</div>";
            }
        }
        $stmt->close();
    }
    
    end_post_logic:
}
// =========================================================================

// 1. Cari Grup Aktif Anggota yang sedang login (Atau Grup aktif Pembina)
// Jika Pembina yang login, kita tampilkan detail grupnya. Jika Anggota, tampilkan grupnya.
$target_id = ($user_status === 'pembina') ? $pembina_id : $user_id;

$sql_group_info = "
    SELECT 
        b.group_id, 
        g.nama_grup, 
        u_pembina.full_name AS pembina_name
    FROM bimbingan b
    JOIN groups g ON b.group_id = g.id
    JOIN users u_pembina ON g.pembina_id = u_pembina.id
    WHERE " . (($user_status === 'pembina') ? "g.pembina_id = ?" : "b.anggota_id = ?") . " 
      AND b.is_active = TRUE
    LIMIT 1
";
$stmt_group_info = $conn->prepare($sql_group_info);
$stmt_group_info->bind_param("i", $target_id);
$stmt_group_info->execute();
$result_group_info = $stmt_group_info->get_result();

if ($row = $result_group_info->fetch_assoc()) {
    $group_id = $row['group_id'];
    $group_name = htmlspecialchars($row['nama_grup']);
    $pembina_name = htmlspecialchars($row['pembina_name']);
}
$stmt_group_info->close();

// --- 2. Ambil Daftar Anggota Grup (Tetap sama) ---
$group_members = [];
if ($group_id) {
    // Ambil daftar Anggota lain dalam grup yang sama (kecuali diri sendiri)
    $sql_members = "
        SELECT 
            u.full_name, 
            u.username,
            CASE WHEN u.id = ? THEN 'Anda' ELSE 'Anggota' END AS role_display
        FROM bimbingan b
        JOIN users u ON b.anggota_id = u.id
        WHERE b.group_id = ? 
          AND b.is_active = TRUE
        ORDER BY u.full_name ASC
    ";
    $stmt_members = $conn->prepare($sql_members);
    // Bind $user_id (untuk penanda 'Anda') dan $group_id
    $stmt_members->bind_param("ii", $user_id, $group_id); 
    $stmt_members->execute();
    $result_members = $stmt_members->get_result();
    $group_members = $result_members->fetch_all(MYSQLI_ASSOC);
    $stmt_members->close();
}

// =========================================================================
// !!! LOGIKA 2: PENGAMBILAN DATA ANGGOTA TERSEDIA (HANYA UNTUK PEMBINA) !!!
// =========================================================================
$anggota_available = [];
if ($user_status === 'pembina' && $pembina_cluster) {
    // Cari anggota yang:
    // 1. Berstatus 'anggota'
    // 2. Cluster-nya sama dengan cluster pembina
    // 3. TIDAK memiliki bimbingan yang AKTIF (is_active = TRUE)
    
    // Gunakan LEFT JOIN untuk mencari user yang TIDAK ada di bimbingan aktif
    $sql_available = "
        SELECT u.id, u.full_name
        FROM users u
        LEFT JOIN bimbingan b ON u.id = b.anggota_id AND b.is_active = TRUE
        WHERE u.status = 'anggota' 
          AND u.cluster = ? 
          AND b.id IS NULL 
          AND u.id != ? -- Pastikan bukan Pembina itu sendiri
        ORDER BY u.full_name ASC
    ";
    $stmt_available = $conn->prepare($sql_available);
    $stmt_available->bind_param("si", $pembina_cluster, $pembina_id);
    $stmt_available->execute();
    $result_available = $stmt_available->get_result();
    $anggota_available = $result_available->fetch_all(MYSQLI_ASSOC);
    $stmt_available->close();
}
// =========================================================================
?>

    <h1 class="mb-4 text-info"><i class="fas fa-users"></i> Anggota Grup Binaan</h1>
    
    <?php echo $message; ?>

    <?php if (!$group_id): ?>
        <div class="alert alert-warning shadow-sm">
            <i class="fas fa-info-circle"></i> Anda saat ini **belum tergabung** dalam grup binaan aktif manapun.
            <?php if($user_status === 'pembina'): ?>
                <br>Sebagai Pembina, pastikan Anda juga sudah terdaftar sebagai anggota pembimbing di tabel `bimbingan` untuk grup Anda.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Detail Grup Anda</h5>
            </div>
            <div class="card-body">
                <p class="mb-1">**Nama Grup:** <span class="fw-bold text-primary"><?= $group_name ?></span></p>
                <p class="mb-0">**Pembina:** <span class="fw-bold text-success"><?= $pembina_name ?></span></p>
                <p class="mb-0">**ID Grup:** <span class="fw-bold text-muted"><?= $group_id ?></span></p>
            </div>
        </div>
        
        <?php if ($user_status === 'pembina'): ?>
        <div class="card shadow-lg mt-4 mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Rekrut Anggota Lama (Cluster: <?= $pembina_cluster ?>)</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="recruit_existing">
                    <input type="hidden" name="pembina_id" value="<?= $pembina_id ?>">
                    <div class="mb-3">
                        <label for="anggota_rekrut_id" class="form-label">Pilih Anggota dari Cluster Anda yang Belum Terbimbing</label>
                        <select class="form-select" id="anggota_rekrut_id" name="anggota_rekrut_id" required>
                            <option value="">-- Pilih Anggota --</option>
                            <?php if (!empty($anggota_available)): ?>
                                <?php foreach ($anggota_available as $anggota): ?>
                                    <option value="<?= $anggota['id'] ?>"><?= htmlspecialchars($anggota['full_name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada anggota yang tersedia/belum dibimbing di cluster Anda.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" 
                        <?php if (empty($anggota_available)) echo 'disabled'; ?>>
                        <i class="fas fa-handshake"></i> Rekrut dan Tambahkan ke Grup
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <div class="card shadow-lg">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Daftar Rekan Grup (<?= count($group_members) ?> Orang)</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($group_members)): ?>
                        <?php foreach ($group_members as $member): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($member['full_name']) ?></h6>
                                    <small class="text-muted"><i class="fas fa-user me-1"></i> @<?= htmlspecialchars($member['username']) ?></small>
                                </div>
                                <span class="badge bg-<?= ($member['role_display'] == 'Anda' ? 'primary' : 'secondary') ?>"><?= $member['role_display'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fas fa-users-slash"></i> Belum ada rekan anggota lain di grup ini selain Anda.
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

<?php 
$conn->close();
require_once '_footer.php';
?>