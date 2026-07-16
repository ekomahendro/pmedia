<?php
require_once 'config.php';
check_login();

// Proteksi tingkat tinggi: Hanya superadmin atau admin yang boleh mengatur hak akses
if ($_SESSION['role'] !== 'superadmin' && $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// -------------------------------------------------------------------------
// ANTARMUKA API (AJAX) - UNTUK MENGAMBIL DATA HAK AKSES SECARA REALTIME
// -------------------------------------------------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // API 1: Ambil modul yang aktif berdasarkan ID Lisensi
    if ($_GET['action'] == 'get_license_modules' && isset($_GET['id_license'])) {
        $id_lic = intval($_GET['id_license']);
        $q = "SELECT id_module FROM htl_license_access WHERE id_license = ?";
        $stmt = mysqli_prepare($conn, $q);
        mysqli_stmt_bind_param($stmt, "i", $id_lic);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        $mods = [];
        while($r = mysqli_fetch_assoc($res)) { $mods[] = $r['id_module']; }
        echo json_encode($mods);
        exit();
    }
    
    // API 2: Ambil modul yang diizinkan untuk User, sekaligus list modul induk lisensinya
    if ($_GET['action'] == 'get_user_modules' && isset($_GET['id_user'])) {
        $id_usr = intval($_GET['id_user']);
        
        // Cari tahu dulu user ini terikat ke lisensi apa
        $q_user = mysqli_query($conn, "SELECT id_license FROM htl_users WHERE id_user = $id_usr");
        $user_data = mysqli_fetch_assoc($q_user);
        $id_lic = $user_data['id_license'];
        
        // Ambil list modul yang dibeli oleh lisensi si user
        $q_lic_mods = "SELECT m.id_module, m.module_name, m.icon FROM htl_modules m 
                       JOIN htl_license_access la ON m.id_module = la.id_module 
                       WHERE la.id_license = ? AND m.parent_id = 0";
        $stmt_l = mysqli_prepare($conn, $q_lic_mods);
        mysqli_stmt_bind_param($stmt_l, "i", $id_lic);
        mysqli_stmt_execute($stmt_l);
        $res_l = mysqli_stmt_get_result($stmt_l);
        
        $available_modules = [];
        while($r = mysqli_fetch_assoc($res_l)) { $available_modules[] = $r; }
        
        // Ambil hak akses aktif milik user saat ini
        $q_usr_mods = "SELECT id_module FROM htl_user_access WHERE id_user = ?";
        $stmt_u = mysqli_prepare($conn, $q_usr_mods);
        mysqli_stmt_bind_param($stmt_u, "i", $id_usr);
        mysqli_stmt_execute($stmt_u);
        $res_u = mysqli_stmt_get_result($stmt_u);
        
        $user_allowed = [];
        while($r = mysqli_fetch_assoc($res_u)) { $user_allowed[] = $r['id_module']; }
        
        echo json_encode([
            'available' => $available_modules,
            'allowed' => $user_allowed
        ]);
        exit();
    }
}

// -------------------------------------------------------------------------
// PROSES PENYIMPANAN DATA (POST FORM SUBMIT)
// -------------------------------------------------------------------------
$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Simpan Akses Lisensi
    if (isset($_POST['save_license_access'])) {
        $target_license = intval($_POST['id_license']);
        
        $del = mysqli_prepare($conn, "DELETE FROM htl_license_access WHERE id_license = ?");
        mysqli_stmt_bind_param($del, "i", $target_license);
        mysqli_stmt_execute($del);

        if (!empty($_POST['lic_modules'])) {
            foreach ($_POST['lic_modules'] as $mod_id) {
                $ins = mysqli_prepare($conn, "INSERT INTO htl_license_access (id_license, id_module) VALUES (?, ?)");
                $m_id = intval($mod_id);
                mysqli_stmt_bind_param($ins, "ii", $target_license, $m_id);
                mysqli_stmt_execute($ins);
            }
        }
        $msg = "Hak akses paket Lisensi berhasil diperbarui!";
    }

    // 2. Simpan Akses User
    if (isset($_POST['save_user_access'])) {
        $target_user = intval($_POST['id_user']);

        $del = mysqli_prepare($conn, "DELETE FROM htl_user_access WHERE id_user = ?");
        mysqli_stmt_bind_param($del, "i", $target_user);
        mysqli_stmt_execute($del);

        if (!empty($_POST['user_modules'])) {
            foreach ($_POST['user_modules'] as $mod_id) {
                $ins = mysqli_prepare($conn, "INSERT INTO htl_user_access (id_user, id_module) VALUES (?, ?)");
                $m_id = intval($mod_id);
                mysqli_stmt_bind_param($ins, "ii", $target_user, $m_id);
                mysqli_stmt_execute($ins);
            }
        }
        $msg = "Otoritas kerja akun User berhasil disesuaikan!";
    }
}

// Data awal untuk render Dropdown pilihan
$all_modules  = mysqli_query($conn, "SELECT * FROM htl_modules WHERE parent_id = 0 ORDER BY id_module ASC");
$all_licenses = mysqli_query($conn, "SELECT * FROM htl_licenses ORDER BY id_license DESC");
$all_users    = mysqli_query($conn, "SELECT u.*, l.hotel_name, l.license_code FROM htl_users u JOIN htl_licenses l ON u.id_license = l.id_license ORDER BY u.id_user DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kontrol Akses - Core Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card { border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-radius: 15px; }
        .module-scroll-box { max-height: 350px; overflow-y: auto; padding: 10px; border-radius: 10px; background-color: #f8f9fa; }
        .form-check { padding-left: 2.5em; margin-bottom: 0.7rem; }
        .form-check-input { width: 1.3em; height: 1.3em; margin-left: -2.5em; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Access Control Matrix</h3>
            <p class="text-secondary small mb-0">Atur paket modul untuk lisensi vendor dan batasan otorisasi staf hotel.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark rounded-pill shadow-sm btn-sm px-4 mt-3 mt-md-0">
            <i class="bi bi-arrow-left-circle me-1"></i> Ke Dashboard
        </a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div><?= $msg; ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white p-3 rounded-top-3">
                    <h5 class="mb-0 fw-bold small text-uppercase tracking-wider">
                        <i class="bi bi-key-fill text-warning me-2"></i> 1. Pengaturan Pilihan Modul Per Lisensi
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Pilih Hotel / Lisensi</label>
                            <select name="id_license" id="select_license" class="form-select form-select-lg" required>
                                <option value="">-- Pilih Kode Lisensi --</option>
                                <?php while($lic = mysqli_fetch_assoc($all_licenses)): ?>
                                    <option value="<?= $lic['id_license']; ?>">
                                        <?= $lic['hotel_name']; ?> (<?= $lic['license_code']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text small text-info"><i class="bi bi-info-circle"></i> Memilih lisensi akan memuat konfigurasi modul aktifnya secara realtime.</div>
                        </div>

                        <label class="form-label fw-bold text-muted small">Daftar Modul Sistem (Centang Untuk Mengaktifkan)</label>
                        <div class="module-scroll-box mb-4">
                            <?php while($mod = mysqli_fetch_assoc($all_modules)): ?>
                                <div class="form-check p-2 border-bottom border-1 border-light">
                                    <input class="form-check-input lic-checkbox" type="checkbox" name="lic_modules[]" 
                                           value="<?= $mod['id_module']; ?>" id="lic_mod_<?= $mod['id_module']; ?>">
                                    <label class="form-check-label d-flex align-items-center text-dark" for="lic_mod_<?= $mod['id_module']; ?>">
                                        <i class="bi <?= $mod['icon']; ?> text-primary me-2 fs-5"></i>
                                        <span class="fw-semibold"><?= $mod['module_name']; ?></span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <button type="submit" name="save_license_access" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Modul Lisensi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white p-3 rounded-top-3">
                    <h5 class="mb-0 fw-bold small text-uppercase tracking-wider">
                        <i class="bi bi-person-lines-fill me-2"></i> 2. Hak Akses Modul Akun User / Staff
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Pilih Akun Pengguna</label>
                            <select name="id_user" id="select_user" class="form-select form-select-lg" required>
                                <option value="">-- Pilih Akun User --</option>
                                <?php while($usr = mysqli_fetch_assoc($all_users)): ?>
                                    <option value="<?= $usr['id_user']; ?>">
                                        <?= $usr['fullname']; ?> (@<?= $usr['username']; ?>) - <?= $usr['hotel_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text small text-info"><i class="bi bi-lightning-charge"></i> Pilihan modul user akan otomatis dibatasi mengikuti lisensi hotelnya.</div>
                        </div>

                        <label class="form-label fw-bold text-muted small">Wewenang Otoritas Kerja Modul</label>
                        
                        <div id="user_module_wrapper" class="module-scroll-box mb-4 text-center py-4">
                            <span class="text-muted small"><i class="bi bi-arrow-up-circle d-block fs-3 mb-1"></i> Silahkan pilih akun staff terlebih dahulu untuk melihat modul kerja</span>
                        </div>

                        <button type="submit" name="save_user_access" id="btn_save_user" class="btn btn-success w-100 py-2.5 rounded-pill fw-bold shadow-sm" disabled>
                            <i class="bi bi-check-all me-1"></i> Sinkronisasi Hak Akses User
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- KONTROL DRIVEN UNTUK PANEL LISENSI ---
    const selectLicense = document.getElementById('select_license');
    const licCheckboxes = document.querySelectorAll('.lic-checkbox');

    selectLicense.addEventListener('change', function() {
        const idLicense = this.value;
        
        // Reset semua checkbox ke keadaan kosong dulu
        licCheckboxes.forEach(cb => cb.checked = false);

        if(!idLicense) return;

        // Tarik data hak akses lisensi saat ini ke server via AJAX
        fetch(`access_control.php?action=get_license_modules&id_license=${idLicense}`)
            .then(response => response.json())
            .then(data => {
                // Centang checkbox yang id_module nya ada di database htl_license_access
                data.forEach(modId => {
                    const cb = document.getElementById(`lic_mod_${modId}`);
                    if(cb) cb.checked = true;
                });
            })
            .catch(err => console.error('Gagal mengambil data modul lisensi:', err));
    });


    // --- KONTROL DRIVEN UNTUK PANEL USER (MENGEKOR KAKAK LISENSINYA) ---
    const selectUser = document.getElementById('select_user');
    const userModuleWrapper = document.getElementById('user_module_wrapper');
    const btnSaveUser = document.getElementById('btn_save_user');

    selectUser.addEventListener('change', function() {
        const idUser = this.value;

        if(!idUser) {
            userModuleWrapper.innerHTML = '<span class="text-muted small"><i class="bi bi-arrow-up-circle d-block fs-3 mb-1"></i> Silahkan pilih akun staff terlebih dahulu</span>';
            btnSaveUser.disabled = true;
            return;
        }

        userModuleWrapper.innerHTML = '<div class="spinner-border text-primary my-3" role="status"><span class="visually-hidden">Loading...</span></div>';

        // Tarik data modul lisensi induk & hak akses yang dimiliki user saat ini
        fetch(`access_control.php?action=get_user_modules&id_user=${idUser}`)
            .then(response => response.json())
            .then(data => {
                userModuleWrapper.innerHTML = '';
                btnSaveUser.disabled = false;

                if(data.available.length === 0) {
                    userModuleWrapper.innerHTML = '<div class="alert alert-danger small m-2">Lisensi hotel tempat user ini terdaftar belum memiliki/membeli modul aktif sama sekali!</div>';
                    btnSaveUser.disabled = true;
                    return;
                }

                // Render checkbox secara dinamis. Hanya modul yang terdaftar di lisensi hotelnya yang akan muncul di sini.
                data.available.forEach(mod => {
                    // Cek apakah user sudah punya akses sebelumnya ke modul ini
                    const isChecked = data.allowed.includes(mod.id_module) ? 'checked' : '';

                    const div = document.createElement('div');
                    div.className = 'form-check p-2 border-bottom border-1 border-light text-start';
                    div.innerHTML = `
                        <input class="form-check-input user-checkbox" type="checkbox" name="user_modules[]" 
                               value="${mod.id_module}" id="usr_mod_${mod.id_module}" ${isChecked}>
                        <label class="form-check-label d-flex align-items-center text-dark" for="usr_mod_${mod.id_module}">
                            <i class="bi ${mod.icon} text-success me-2 fs-5"></i>
                            <span class="fw-semibold">${mod.module_name}</span>
                        </label>
                    `;
                    userModuleWrapper.appendChild(div);
                });
            })
            .catch(err => {
                console.error('Gagal mengambil data modul user:', err);
                userModuleWrapper.innerHTML = '<span class="text-danger small">Gagal memuat data.</span>';
            });
    });

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>