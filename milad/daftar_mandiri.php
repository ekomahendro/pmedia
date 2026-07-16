<?php
require_once 'config.php';

// MASTER PASSWORD UNTUK PILIHAN ALL MT
define('MASTER_PASSWORD', 'milad2026');

// -------------------------------------------------------------------------
// 1. INTERN FILTER AJAX: CEK VALIDASI PASSWORD
// -------------------------------------------------------------------------
if (isset($_POST['ajax_check_password'])) {
    header('Content-Type: application/json');
    $mt_id = $_POST['mt_id'] ?? '';
    $pass  = $_POST['password'] ?? '';
    
    if ($mt_id === 'all') {
        // Validasi Master Password untuk semua MT
        if ($pass === MASTER_PASSWORD) {
            echo json_encode(['valid' => true]);
        } else {
            echo json_encode(['valid' => false, 'msg' => 'Password Master Panitia Salah!']);
        }
    } else {
        // Validasi Password Majelis Taklim masing-masing
        $stmt = $pdo->prepare("SELECT password_daftar FROM mld_majelis WHERE id = ?");
        $stmt->execute([$mt_id]);
        $db_password = $stmt->fetchColumn();

        if ($db_password && $pass === $db_password) {
            echo json_encode(['valid' => true]);
        } else {
            echo json_encode(['valid' => false, 'msg' => 'Password salah atau belum diset oleh panitia!']);
        }
    }
    exit;
}

// -------------------------------------------------------------------------
// AJAX: Cek kuota grup Cerdas Cermat secara real-time
// -------------------------------------------------------------------------
if (isset($_POST['ajax_check_kuota_cc'])) {
    header('Content-Type: application/json');
    $mt_id      = $_POST['majelis_id'] ?? '';
    $grup       = $_POST['grup_cc'] ?? '';
    $peserta_id = $_POST['peserta_id'] ?? '';
    $cc_id      = $_POST['cc_id'] ?? ''; 

    $query = "SELECT COUNT(*) FROM mld_peserta_lomba pl 
              JOIN mld_peserta p ON pl.peserta_id = p.id 
              WHERE p.majelis_id = ? AND pl.kategori_id = ? AND pl.grup_cc = ?";
    $params = [$mt_id, $cc_id, $grup];
    
    if (!empty($peserta_id)) {
        $query .= " AND p.id != ?";
        $params[] = $peserta_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $count = $stmt->fetchColumn();

    echo json_encode(['available' => ($count < 3), 'current' => $count]);
    exit;
}

// -------------------------------------------------------------------------
// 2. PROSES REQUEST HAPUS PESERTA (SISI MANDIRI)
// -------------------------------------------------------------------------
if (isset($_GET['action_hapus_mandiri'])) {
    $peserta_id = $_GET['id'] ?? '';
    $selected_mt_id = $_GET['mt'] ?? '';
    $encoded_pass = $_GET['p_v'] ?? '';
    $input_pass = base64_decode($encoded_pass);

    $is_authorized = false;
    if ($selected_mt_id === 'all' && $input_pass === MASTER_PASSWORD) {
        $is_authorized = true;
    } else {
        $stmt_v = $pdo->prepare("SELECT password_daftar FROM mld_majelis WHERE id = ?");
        $stmt_v->execute([$selected_mt_id]);
        $db_pass = $stmt_v->fetchColumn();
        if ($db_pass && $input_pass === $db_pass) {
            $is_authorized = true;
        }
    }

    if ($is_authorized && !empty($peserta_id)) {
        // Cari majelis_id asli dari peserta yang mau dihapus untuk keperluan query lock check
        $stmt_lock = $pdo->prepare("SELECT is_locked, foto, majelis_id FROM mld_peserta WHERE id = ?");
        $stmt_lock->execute([$peserta_id]);
        $peserta = $stmt_lock->fetch(PDO::FETCH_ASSOC);

        if ($peserta && $peserta['is_locked'] == 0) {
            if (!empty($peserta['foto']) && file_exists("uploads/" . $peserta['foto'])) {
                @unlink("uploads/" . $peserta['foto']);
            }
            
            $stmt_del_lomba = $pdo->prepare("DELETE FROM mld_peserta_lomba WHERE peserta_id = ?");
            $stmt_del_lomba->execute([$peserta_id]);

            $stmt_del_pst = $pdo->prepare("DELETE FROM mld_peserta WHERE id = ?");
            $stmt_del_pst->execute([$peserta_id]);

            header("Location: daftar_mandiri.php?status=success_delete&mt=" . $selected_mt_id . "&p_v=" . $encoded_pass);
            exit;
        } else {
            header("Location: daftar_mandiri.php?status=error_locked&mt=" . $selected_mt_id . "&p_v=" . $encoded_pass);
            exit;
        }
    }
}

// -------------------------------------------------------------------------
// 3. BACKEND LOGIKA CRUD MANDIRI (SUBSTITUSI FUNCTIONS_PESERTA)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_peserta'])) {
    $selected_mt_id = $_POST['majelis_id'] ?? '';
    $input_pass     = $_POST['mt_password_verified'] ?? '';
    
    $is_authorized = false;
    if ($selected_mt_id === 'all' && $input_pass === MASTER_PASSWORD) {
        $is_authorized = true;
        // Jika admin panitia menginput, ambil majelis_id tujuan dari form jika ada, atau default ke all
        $target_mt_id = $_POST['target_majelis_id'] ?? 'all'; 
    } else {
        $stmt_v = $pdo->prepare("SELECT password_daftar FROM mld_majelis WHERE id = ?");
        $stmt_v->execute([$selected_mt_id]);
        $db_pass = $stmt_v->fetchColumn();
        if ($db_pass && $input_pass === $db_pass) {
            $is_authorized = true;
            $target_mt_id = $selected_mt_id;
        }
    }

    if ($is_authorized) {
        $id            = $_POST['id'] ?? '';
        $nama_peserta  = $_POST['nama_peserta'];
        $no_hp         = $_POST['no_hp'];
        $kategori_ids  = $_POST['kategori_ids'] ?? []; 
        $grup_cc       = $_POST['grup_cc'] ?? null;

        if ($target_mt_id === 'all' || empty($target_mt_id)) {
            die("Akses ditolak: Pilih Majelis Taklim spesifik untuk menyimpan data peserta.");
        }

        $stmt_cc_id = $pdo->query("SELECT id FROM mld_kategori WHERE LOWER(nama_kategori) LIKE '%cerdas cermat%' LIMIT 1");
        $cc_kategori_id = $stmt_cc_id->fetchColumn();

        if (!empty($id)) {
            $stmt_lock = $pdo->prepare("SELECT is_locked FROM mld_peserta WHERE id = ?");
            $stmt_lock->execute([$id]);
            if ($stmt_lock->fetchColumn() == 1) {
                header("Location: daftar_mandiri.php?status=error_locked&mt=" . $selected_mt_id . "&p_v=" . base64_encode($input_pass));
                exit;
            }
        }

        // VALIDASI KUOTA CC PER MAJELIS TARGET
        if ($cc_kategori_id && in_array($cc_kategori_id, $kategori_ids)) {
            if (empty($grup_cc)) {
                die("Akses ditolak: Grup Cerdas Cermat wajib dipilih.");
            }
            
            $query_check = "SELECT COUNT(*) FROM mld_peserta_lomba pl 
                            JOIN mld_peserta p ON pl.peserta_id = p.id 
                            WHERE p.majelis_id = ? AND pl.kategori_id = ? AND pl.grup_cc = ?";
            $params_check = [$target_mt_id, $cc_kategori_id, $grup_cc];
            if (!empty($id)) {
                $query_check .= " AND p.id != ?";
                $params_check[] = $id;
            }
            $stmt_check = $pdo->prepare($query_check);
            $stmt_check->execute($params_check);
            if ($stmt_check->fetchColumn() >= 3) {
                die("Akses ditolak: Kuota Grup " . $grup_cc . " untuk cabang Cerdas Cermat di Majelis tersebut sudah penuh (Maksimal 3 peserta).");
            }
        }

        $foto_name = $_POST['old_foto'] ?? '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            if (!empty($_POST['old_foto']) && file_exists($target_dir . $_POST['old_foto'])) {
                @unlink($target_dir . $_POST['old_foto']);
            }
            $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
            $foto_name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto_name);
        }

        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO mld_peserta (majelis_id, nama_peserta, no_hp, foto, is_locked) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$target_mt_id, $nama_peserta, $no_hp, $foto_name]);
            $peserta_id = $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare("UPDATE mld_peserta SET nama_peserta = ?, no_hp = ?, foto = ? WHERE id = ?");
            $stmt->execute([$nama_peserta, $no_hp, $foto_name, $id]);
            $peserta_id = $id;

            $stmt_del = $pdo->prepare("DELETE FROM mld_peserta_lomba WHERE peserta_id = ?");
            $stmt_del->execute([$peserta_id]);
        }

        if (!empty($kategori_ids) && $peserta_id) {
            $stmt_lomba = $pdo->prepare("INSERT INTO mld_peserta_lomba (peserta_id, kategori_id, grup_cc) VALUES (?, ?, ?)");
            foreach ($kategori_ids as $kat_id) {
                $grup_input = ($kat_id == $cc_kategori_id) ? $grup_cc : null;
                $stmt_lomba->execute([$peserta_id, $kat_id, $grup_input]);
            }
        }

        header("Location: daftar_mandiri.php?status=success&mt=" . $selected_mt_id . "&p_v=" . base64_encode($input_pass));
        exit;
    } else {
        die("Akses ditolak: Verifikasi Identitas Gagal.");
    }
}

// Ambil data referensi master
$list_kategori = $pdo->query("SELECT * FROM mld_kategori ORDER BY nama_kategori ASC")->fetchAll();
$list_majelis  = $pdo->query("SELECT * FROM mld_majelis ORDER BY nama_mt ASC")->fetchAll();

$cc_id_js = 0;
foreach($list_kategori as $kat) {
    if(str_contains(strtolower($kat['nama_kategori']), 'cerdas cermat')) {
        $cc_id_js = $kat['id'];
        break;
    }
}

// Ambil list peserta milik MT terpilih / Semua MT
$selected_mt = $_GET['mt'] ?? '';
$data_peserta = [];
if (!empty($selected_mt)) {
    $query_p = "SELECT p.*, m.nama_mt,
                GROUP_CONCAT(CONCAT(k.nama_kategori, IF(pl.grup_cc IS NOT NULL, CONCAT(' (Grup ', pl.grup_cc, ')'), '')) SEPARATOR ', ') as list_lomba,
                GROUP_CONCAT(pl.kategori_id SEPARATOR ',') as array_kat_ids,
                GROUP_CONCAT(IFNULL(pl.grup_cc, 0) SEPARATOR ',') as array_grup_cc
                FROM mld_peserta p 
                JOIN mld_majelis m ON p.majelis_id = m.id
                LEFT JOIN mld_peserta_lomba pl ON p.id = pl.peserta_id
                LEFT JOIN mld_kategori k ON pl.kategori_id = k.id";
    
    if ($selected_mt === 'all') {
        $query_p .= " GROUP BY p.id ORDER BY m.nama_mt ASC, p.id DESC";
        $stmt_p = $pdo->prepare($query_p);
        $stmt_p->execute();
    } else {
        $query_p .= " WHERE p.majelis_id = ? GROUP BY p.id ORDER BY p.id DESC";
        $stmt_p = $pdo->prepare($query_p);
        $stmt_p->execute([$selected_mt]);
    }
    $data_peserta = $stmt_p->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Mandiri - Milad XV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #e9ecef; }
        .hero-section { background: linear-gradient(135deg, #198754, #0d5031); color: white; padding: 40px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; }
        .card-peserta { border: none; border-radius: 15px; transition: transform 0.2s; }
        .card-peserta:hover { transform: translateY(-5px); }
        .locked-label { font-size: 0.75rem; color: #dc3545; font-weight: bold; }
        .grup-title { border-left: 5px solid #ffc107; padding-left: 10px; margin-top: 20px; font-weight: bold; color: #333; }
    </style>
</head>
<body>

<div class="hero-section text-center shadow">
    <div class="container">
        <h2 class="fw-bold"><i class="bi bi-person-plus"></i> Pendaftaran Peserta Mandiri</h2>
        <p class="mb-0">Gebyar Milad MT. Muallaf Taufiqiyah XV</p>
    </div>
</div>

<div class="container mb-5">
    
    <?php if(($_GET['status'] ?? '') == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill"></i> Data peserta dan pilihan cabang lomba berhasil disimpan ke dalam sistem sistem.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif(($_GET['status'] ?? '') == 'success_delete'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill"></i> Data peserta telah berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif(($_GET['status'] ?? '') == 'error_locked'): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> Operasi ditolak! Data ini telah dikunci oleh panitia dan tidak bisa diubah kembali.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock text-success"></i> Verifikasi Identitas Kafilah</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">1. Pilih Majelis Taklim Anda</label>
                    <select id="mt_selector" class="form-select form-select-lg" onchange="resetAuth()">
                        <option value="">-- Pilih Nama MT --</option>
                        <option value="all" <?= $selected_mt == 'all' ? 'selected' : '' ?>>-- SEMUA MAJELIS (PANITIA) --</option>
                        <?php foreach($list_majelis as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $selected_mt == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mt']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4" id="pass_box">
                    <label class="form-label small fw-bold">2. Masukkan Password Rahasia</label>
                    <input type="password" id="mt_password" class="form-control form-select-lg" placeholder="Sandi Majelis / Sandi Panitia">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-success btn-lg w-100 fw-bold" onclick="verifyAccess()">Buka Data</button>
                </div>
            </div>
        </div>
    </div>

    <?php if(!empty($selected_mt)): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-dark">Daftar Peserta Terdaftar <?= $selected_mt === 'all' ? '(Semua MT)' : '' ?></h4>
        <button class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" onclick="openModalPeserta()"><i class="bi bi-plus-lg"></i> Tambah Peserta</button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <label class="form-label small fw-bold text-muted mb-2 d-block"><i class="bi bi-filter"></i> Filter Berdasarkan Cabang Lomba:</label>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 filter-btn active" onclick="filterCabang('all')">Semua Cabang</button>
                <?php foreach($list_kategori as $k): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" id="btn_filter_<?= $k['id'] ?>" onclick="filterCabang('<?= $k['id'] ?>')">
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="container_peserta">
        <?php if(count($data_peserta) == 0): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted"></i>
                <p class="text-muted mt-2">Belum ada data peserta. Silakan klik Tambah Peserta.</p>
            </div>
        <?php else: ?>
            
            <!-- VIEW NORMAL -->
            <div id="view_normal_lomba" class="row g-3">
                <?php foreach($data_peserta as $p): ?>
                    <div class="col-md-6 item-peserta" data-kategori="<?= $p['array_kat_ids'] ?>">
                        <div class="card card-peserta shadow-sm">
                            <div class="card-body d-flex align-items-center p-3">
                                <img src="<?= $p['foto'] ? 'uploads/'.$p['foto'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>" 
                                     class="rounded-circle shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                                <div class="ms-3 flex-grow-1">
                                    <h6 class="fw-bold mb-0 text-success"><?= htmlspecialchars($p['nama_peserta']) ?></h6>
                                    <small class="text-dark fw-semibold d-block mt-1" style="font-size:0.8rem;"><i class="bi bi-house-door text-muted"></i> MT: <?= htmlspecialchars($p['nama_mt']) ?></small>
                                    <small class="text-muted d-block mb-1"><?= htmlspecialchars($p['list_lomba'] ?: 'Belum pilih lomba') ?></small>
                                    
                                    <?php if($p['is_locked']): ?>
                                        <span class="locked-label"><i class="bi bi-lock-fill"></i> DATA DIKUNCI PANITIA</span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-1">
                                    <?php if(!$p['is_locked'] || $selected_mt === 'all'): ?>
                                        <button class="btn btn-sm btn-outline-primary border-0" title="Edit Data" onclick="openModalPeserta(<?= htmlspecialchars(json_encode($p)) ?>)">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger border-0" title="Hapus Peserta" onclick="pemicuHapus('<?= $p['id'] ?>', '<?= htmlspecialchars($p['nama_peserta'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-trash3 fs-5"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12 text-center py-4 d-none" id="empty_filter_msg">
                    <p class="text-muted italic">Tidak ada peserta yang terdaftar pada cabang lomba ini.</p>
                </div>
            </div>

            <!-- VIEW CERDAS CERMAT GROUP -->
            <div id="view_cerdas_cermat_group" class="d-none">
                <?php for($g = 1; $g <= 5; $g++): ?>
                    <div class="grup-container mb-4" id="box_tampilan_grup_<?= $g ?>">
                        <h5 class="grup-title text-success"><i class="bi bi-boxes"></i> Kelompok Cerdas Cermat - Grup <?= $g ?></h5>
                        <div class="row g-3">
                            <?php 
                            $has_member_in_group = false;
                            foreach($data_peserta as $p) {
                                $kat_array = explode(',', $p['array_kat_ids']);
                                $grup_array = explode(',', $p['array_grup_cc']);
                                
                                $key = array_search($cc_id_js, $kat_array);
                                if($key !== false && isset($grup_array[$key]) && $grup_array[$key] == $g) {
                                    $has_member_in_group = true;
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card card-peserta shadow-sm border border-warning border-opacity-20">
                                            <div class="card-body d-flex align-items-center p-3">
                                                <img src="<?= $p['foto'] ? 'uploads/'.$p['foto'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>" 
                                                     class="rounded-circle shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div class="ms-3 flex-grow-1">
                                                    <h6 class="fw-bold mb-0 text-success"><?= htmlspecialchars($p['nama_peserta']) ?></h6>
                                                    <small class="text-dark fw-semibold d-block mt-1" style="font-size:0.8rem;"><i class="bi bi-house-door text-muted"></i> MT: <?= htmlspecialchars($p['nama_mt']) ?></small>
                                                    <small class="text-muted d-block mb-1"><span class="badge bg-warning text-dark">Cerdas Cermat (Grup <?= $g ?>)</span></small>
                                                    <?php if($p['is_locked']): ?>
                                                        <span class="locked-label"><i class="bi bi-lock-fill"></i> DATA DIKUNCI PANITIA</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <?php if(!$p['is_locked'] || $selected_mt === 'all'): ?>
                                                        <button class="btn btn-sm btn-outline-primary border-0" title="Edit Data" onclick="openModalPeserta(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                            <i class="bi bi-pencil-square fs-5"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger border-0" title="Hapus Peserta" onclick="pemicuHapus('<?= $p['id'] ?>', '<?= htmlspecialchars($p['nama_peserta'], ENT_QUOTES) ?>')">
                                                            <i class="bi bi-trash3 fs-5"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            if(!$has_member_in_group): ?>
                                <div class="col-12">
                                    <div class="bg-white p-3 rounded-3 text-muted text-center small border border-dashed">Belum ada anggota kelompok yang didaftarkan di Grup <?= $g ?>.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalPesertaMandiri" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formInputPeserta" method="POST" action="daftar_mandiri.php" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <input type="hidden" name="action_peserta" value="1">
            <input type="hidden" name="id" id="pstId">
            <input type="hidden" name="majelis_id" id="pstMajelisId">
            <input type="hidden" name="old_foto" id="pstOldFoto">
            <input type="hidden" name="mt_password_verified" id="pstPassVerified">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalTitle">Form Pendaftaran Peserta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 row g-3">
                <!-- Dropdown Pilihan MT Baru di dalam Form jika Login menggunakan Akses ALL (Panitia) -->
                <div class="col-12 d-none" id="box_select_target_mt">
                    <label class="form-label fw-bold small text-danger">Daftarkan ke Majelis Taklim:</label>
                    <select name="target_majelis_id" id="pstTargetMajelis" class="form-select border-danger">
                        <option value="">-- Pilih Target MT Asal Peserta --</option>
                        <?php foreach($list_majelis as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mt']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">Nama Lengkap Peserta</label>
                    <input type="text" name="nama_peserta" id="pstNama" class="form-control" required placeholder="Sesuai KTP/Kartu Keluarga">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" id="pstHp" class="form-control" placeholder="Contoh: 08123456789">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small">Pilih Bidang Lomba yang Diikuti:</label>
                    <div class="row g-2 px-2 bg-light p-3 rounded border">
                        <?php foreach($list_kategori as $k): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="form-check">
                                    <input class="form-check-input chk-lomba" type="checkbox" name="kategori_ids[]" value="<?= $k['id'] ?>" id="chk_kat_<?= $k['id'] ?>" onchange="toggleGrupCC()">
                                    <label class="form-check-label small" style="cursor:pointer" for="chk_kat_<?= $k['id'] ?>">
                                        <?= htmlspecialchars($k['nama_kategori']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 d-none" id="box_grup_cc">
                    <div class="bg-warning bg-opacity-10 p-3 rounded border border-warning">
                        <label class="form-label fw-bold small text-dark"><i class="bi bi-info-circle-fill text-warning"></i> Pilih Grup Cerdas Cermat</label>
                        <select name="grup_cc" id="pstGrupCC" class="form-select">
                            <option value="">-- Pilih Grup --</option>
                            <option value="1">Grup 1</option>
                            <option value="2">Grup 2</option>
                            <option value="3">Grup 3</option>
                            <option value="4">Grup 4</option>
                            <option value="5">Grup 5</option>
                        </select>
                        <div class="form-text text-muted small mt-1">Setiap Majelis Taklim maksimal mengirimkan 3 peserta untuk setiap kelompok grup.</div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold small">Upload Foto Profil <small class="text-muted">(Opsional)</small></label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSimpan" class="btn btn-success px-5 fw-bold shadow-sm">Simpan Pendaftaran</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let mPeserta = new bootstrap.Modal(document.getElementById('modalPesertaMandiri'));
    const ID_CERDAS_CERMAT = <?= $cc_id_js ?>;

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const p_v = urlParams.get('p_v');
        if(p_v) {
            document.getElementById('mt_password').value = atob(p_v);
        }
    });

    function filterCabang(kategoriId) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('btn-success', 'active');
            btn.classList.add('btn-outline-secondary');
        });
        
        if(kategoriId === 'all') {
            event.target.classList.add('btn-success', 'active');
        } else {
            document.getElementById('btn_filter_' + kategoriId).classList.add('btn-success', 'active');
        }

        const viewNormal = document.getElementById('view_normal_lomba');
        const viewCCGroup = document.getElementById('view_cerdas_cermat_group');
        const emptyMsg = document.getElementById('empty_filter_msg');

        if(kategoriId == ID_CERDAS_CERMAT) {
            viewNormal.classList.add('d-none');
            viewCCGroup.classList.remove('d-none');
            if(emptyMsg) emptyMsg.classList.add('d-none');
        } else {
            viewCCGroup.classList.add('d-none');
            viewNormal.classList.remove('d-none');
            
            let visibleCount = 0;
            document.querySelectorAll('.item-peserta').forEach(item => {
                const kats = item.getAttribute('data-kategori').split(',');
                if(kategoriId === 'all' || kats.includes(kategoriId.toString())) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            });

            if(visibleCount === 0) {
                if(emptyMsg) emptyMsg.classList.remove('d-none');
            } else {
                if(emptyMsg) emptyMsg.classList.add('d-none');
            }
        }
    }

    function toggleGrupCC() {
        const chkCC = document.getElementById('chk_kat_' + ID_CERDAS_CERMAT);
        const boxGrup = document.getElementById('box_grup_cc');
        const selectGrup = document.getElementById('pstGrupCC');

        if (chkCC && chkCC.checked) {
            boxGrup.classList.remove('d-none');
            selectGrup.setAttribute('required', 'required');
        } else {
            boxGrup.classList.add('d-none');
            selectGrup.removeAttribute('required');
            selectGrup.value = "";
        }
    }

    document.getElementById('formInputPeserta').addEventListener('submit', function(e) {
        const btn = document.getElementById('btnSimpan');
        const checkedCount = document.querySelectorAll('.chk-lomba:checked').length;
        
        if (checkedCount === 0) {
            e.preventDefault(); 
            alert('Silakan pilih minimal satu cabang lomba yang diikuti!');
            return false;
        }

        const mtIdSelector = document.getElementById('mt_selector').value;
        if (mtIdSelector === 'all') {
            const targetMt = document.getElementById('pstTargetMajelis').value;
            if(!targetMt) {
                e.preventDefault();
                alert('Sebagai Panitia, Anda wajib menentukan Majelis Taklim asal peserta ini!');
                return false;
            }
        }

        const chkCC = document.getElementById('chk_kat_' + ID_CERDAS_CERMAT);
        if (chkCC && chkCC.checked) {
            e.preventDefault(); 
            
            const mtId = (mtIdSelector === 'all') ? document.getElementById('pstTargetMajelis').value : mtIdSelector;
            const grup = document.getElementById('pstGrupCC').value;
            const pesertaId = document.getElementById('pstId').value;

            let checkData = new FormData();
            checkData.append('ajax_check_kuota_cc', '1');
            checkData.append('majelis_id', mtId);
            checkData.append('grup_cc', grup);
            checkData.append('peserta_id', pesertaId);
            checkData.append('cc_id', ID_CERDAS_CERMAT);

            fetch('daftar_mandiri.php', { method: 'POST', body: checkData })
                .then(res => res.json())
                .then(data => {
                    if(!data.available) {
                        alert(`Gagal menyimpan! Kuota Grup ${grup} untuk cabang Cerdas Cermat pada Majelis Taklim tujuan sudah penuh (Maksimal 3 peserta).`);
                    } else {
                        btn.disabled = true;
                        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;
                        document.getElementById('formInputPeserta').submit();
                    }
                }).catch(() => {
                    alert('Terjadi kesalahan koneksi saat memverifikasi kuota grup.');
                });
            return false;
        }

        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;
    });

    function verifyAccess() {
        const mtId = document.getElementById('mt_selector').value;
        const pass = document.getElementById('mt_password').value;

        if(!mtId || !pass) {
            alert('Pilih Majelis dan isi Password terlebih dahulu!');
            return;
        }

        let formData = new FormData();
        formData.append('ajax_check_password', '1');
        formData.append('mt_id', mtId);
        formData.append('password', pass);

        fetch('daftar_mandiri.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.valid) {
                window.location.href = 'daftar_mandiri.php?mt=' + mtId + '&p_v=' + btoa(pass);
            } else {
                alert(data.msg);
            }
        });
    }

    function pemicuHapus(id, nama) {
        const mtId = document.getElementById('mt_selector').value;
        const pass = document.getElementById('mt_password').value || atob("<?= $_GET['p_v'] ?? '' ?>");
        
        if (confirm(`Apakah Anda yakin ingin menghapus peserta bernama "${nama}"? Data yang terhapus tidak dapat dikembalikan.`)) {
            window.location.href = `daftar_mandiri.php?action_hapus_mandiri=1&id=${id}&mt=${mtId}&p_v=${btoa(pass)}`;
        }
    }

    function openModalPeserta(data = null) {
        const mtId = document.getElementById('mt_selector').value;
        const pass = document.getElementById('mt_password').value || atob("<?= $_GET['p_v'] ?? '' ?>");

        if(!mtId) {
            alert('Silakan verifikasi identitas Kafilah Anda terlebih dahulu.');
            return;
        }

        document.getElementById('pstId').value = "";
        document.getElementById('pstNama').value = "";
        document.getElementById('pstHp').value = "";
        document.getElementById('pstOldFoto').value = "";
        document.querySelectorAll('.chk-lomba').forEach(el => el.checked = false);
        
        document.getElementById('pstMajelisId').value = mtId;
        document.getElementById('pstPassVerified').value = pass;
        
        document.getElementById('pstGrupCC').value = "";
        document.getElementById('box_grup_cc').classList.add('d-none');

        // Logic UI tambahan jika login sebagai ALL (Panitia)
        const boxSelectTarget = document.getElementById('box_select_target_mt');
        const selectTarget = document.getElementById('pstTargetMajelis');
        if (mtId === 'all') {
            boxSelectTarget.classList.remove('d-none');
            selectTarget.setAttribute('required', 'required');
            selectTarget.value = "";
            selectTarget.disabled = false;
        } else {
            boxSelectTarget.classList.add('d-none');
            selectTarget.removeAttribute('required');
        }

        if(data) {
            document.getElementById('modalTitle').innerText = "Edit Data Peserta";
            document.getElementById('pstId').value = data.id;
            document.getElementById('pstNama').value = data.nama_peserta;
            document.getElementById('pstHp').value = data.no_hp;
            document.getElementById('pstOldFoto').value = data.foto || "";

            if(mtId === 'all') {
                selectTarget.value = data.majelis_id;
                selectTarget.disabled = true; // Kunci target MT saat mode edit biar konsisten
            }

            fetch('peserta.php?ajax_get_lomba=1&peserta_id=' + data.id)
                .then(res => res.json())
                .then(ids => {
                    if(Array.isArray(ids)) {
                        ids.forEach(id => {
                            let chk = document.getElementById('chk_kat_' + id);
                            if(chk) chk.checked = true;
                        });
                        toggleGrupCC();
                        
                        const katArray = data.array_kat_ids.split(',');
                        const grupArray = data.array_grup_cc.split(',');
                        const idx = katArray.indexOf(ID_CERDAS_CERMAT.toString());
                        if(idx !== -1 && grupArray[idx] != '0') {
                            document.getElementById('pstGrupCC').value = grupArray[idx];
                        }
                    }
                }).catch(err => console.log("Gagal memuat list kategori lama."));
        } else {
            document.getElementById('modalTitle').innerText = "Daftarkan Peserta Baru";
            document.getElementById('btnSimpan').disabled = false;
            document.getElementById('btnSimpan').innerHTML = "Simpan Pendaftaran";
        }

        mPeserta.show();
    }

    function resetAuth() {
        document.getElementById('mt_password').value = "";
    }
</script>
</body>
</html>