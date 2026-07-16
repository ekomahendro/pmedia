<?php
require_once 'config.php';
require_once 'functions_peserta.php';

// Pastikan session sudah dimulai (jika belum di config.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman login
if (!isset($_SESSION['login_milad'])) {
    header("Location: login.php");
    exit;
}

// API internal untuk mengambil ID kategori lomba saat edit peserta (via JavaScript AJAX)
if (isset($_GET['ajax_get_lomba'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT kategori_id FROM mld_peserta_lomba WHERE peserta_id = ?");
    $stmt->execute([$_GET['peserta_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

$tab = $_GET['tab'] ?? 'peserta';
$search = $_GET['search'] ?? '';

// 1. LOGIKA FIX SINKRONISASI SELEKSI LOMBA VIA SESSION
if (isset($_GET['kat_id'])) {
    $_SESSION['last_kat_id'] = $_GET['kat_id'];
}
// Ambil kategori terpilih dari session jika ada, jika tidak kosongkan
$selected_kat = $_SESSION['last_kat_id'] ?? '';

// Default display mode jika belum ada
if (!isset($_SESSION['display_mode'])) {
    $_SESSION['display_mode'] = 'lomba';
}

// Eksekusi fungsi pengolah form berdasarkan tab aktif
handleKategori($pdo);
handleMajelis($pdo);
handlePeserta($pdo);
handlePanitia($pdo);
handleDisplayKontrol($pdo); // Pastikan di fungsi ini $_SESSION['display_mode'] = 'lomba' diatur saat tombol "Tampilkan Lomba ke Layar" ditekan

// Ambil data referensi global untuk komponen dropdown/checkbox modal
$list_kategori = $pdo->query("SELECT * FROM mld_kategori ORDER BY nama_kategori ASC")->fetchAll();
$list_majelis = $pdo->query("SELECT * FROM mld_majelis ORDER BY nama_mt ASC")->fetchAll();

// Logic update urutan panitia langsung dari baris table jika disubmit
if (isset($_POST['update_urutan_panitia'])) {
    if (!empty($_POST['urutan'])) {
        foreach ($_POST['urutan'] as $pnt_id => $no_urut) {
            $stmt = $pdo->prepare("UPDATE mld_panitia SET urutan = ? WHERE id = ?");
            $stmt->execute([$no_urut, $pnt_id]);
        }
    }
    header("Location: peserta.php?tab=panitia&status=updated");
    exit;
}

// Logic mengaktifkan tampilan display panitia / balik ke lomba
if (isset($_POST['action_display_panitia'])) {
    // Matikan status tampil semua peserta & majelis terlebih dahulu
    $pdo->query("UPDATE mld_peserta SET is_tampil = 0");
    $pdo->query("UPDATE mld_majelis SET is_tampil = 0");
    
    $_SESSION['display_mode'] = $_POST['mode_display_khusus']; 
    // session_write_close(); // <-- TAMBAHKAN BARIS INI AGAR SESSION LANGSUNG TERKUNCI & TEROPTIMALISASI
    header("Location: peserta.php?tab=kontrol_display&status=display_updated");
    exit;
}

// Routing pengambilan data view berdasarkan tab
if ($tab == 'kategori') {
    $stmt = $pdo->prepare("SELECT * FROM mld_kategori WHERE nama_kategori LIKE ? ORDER BY nama_kategori ASC");
    $stmt->execute(["%$search%"]); $data_kategori = $stmt->fetchAll();
} elseif ($tab == 'majelis') {
    $stmt = $pdo->prepare("SELECT * FROM mld_majelis WHERE nama_mt LIKE ? OR pic LIKE ? ORDER BY nama_mt ASC");
    $stmt->execute(["%$search%", "%$search%"]); $data_majelis = $stmt->fetchAll();
} elseif ($tab == 'panitia') {
    $stmt = $pdo->prepare("SELECT * FROM mld_panitia WHERE nama_panitia LIKE ? OR jabatan LIKE ? ORDER BY urutan ASC, id DESC");
    $stmt->execute(["%$search%", "%$search%"]); $data_panitia = $stmt->fetchAll();
} elseif ($tab == 'kontrol_display') {
    // Jika belum memilih lomba, default pakai lomba pertama yang tersedia
    if (empty($selected_kat) && !empty($list_kategori)) {
        $selected_kat = $list_kategori[0]['id'];
        $_SESSION['last_kat_id'] = $selected_kat;
    }
    
    // Cek tipe kategori aktif apakah termasuk Cerdas Cermat / LCC
    $nama_kategori_aktif = '';
    foreach($list_kategori as $k) {
        if($k['id'] == $selected_kat) $nama_kategori_aktif = $k['nama_kategori'];
    }
    $is_cerdas_cermat = (stripos($nama_kategori_aktif, 'cerdas cermat') !== false || stripos($nama_kategori_aktif, 'lcc') !== false);

    $peserta_opsi = [];
    if ($selected_kat) {
        if ($is_cerdas_cermat) {
            $stmt = $pdo->prepare("SELECT DISTINCT m.* FROM mld_majelis m 
                                  JOIN mld_peserta p ON p.majelis_id = m.id
                                  JOIN mld_peserta_lomba pl ON pl.peserta_id = p.id
                                  WHERE pl.kategori_id = ? ORDER BY m.nama_mt ASC");
            $stmt->execute([$selected_kat]);
            $peserta_opsi = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("SELECT p.*, m.nama_mt FROM mld_peserta p 
                                  JOIN mld_majelis m ON p.majelis_id = m.id 
                                  JOIN mld_peserta_lomba pl ON pl.peserta_id = p.id
                                  WHERE pl.kategori_id = ? ORDER BY p.nama_peserta ASC");
            $stmt->execute([$selected_kat]);
            $peserta_opsi = $stmt->fetchAll();
        }
    }
} else {
    $stmt = $pdo->prepare("SELECT p.*, m.nama_mt, GROUP_CONCAT(k.nama_kategori SEPARATOR ', ') AS list_lomba 
          FROM mld_peserta p 
          JOIN mld_majelis m ON p.majelis_id = m.id 
          LEFT JOIN mld_peserta_lomba pl ON p.id = pl.peserta_id
          LEFT JOIN mld_kategori k ON pl.kategori_id = k.id
          WHERE p.nama_peserta LIKE ? OR m.nama_mt LIKE ? 
          GROUP BY p.id ORDER BY p.id DESC");
    $stmt->execute(["%$search%", "%$search%"]); $data_peserta = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Gebyar Milad XV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .thumb-foto { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 1px solid #ddd; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Gebyar Milad MT.Muallaf Taufiqiyah XV</a>
        <div class="d-flex gap-2">
            <a href="tampil.php" target="_blank" class="btn btn-sm btn-warning fw-bold"><i class="bi bi-tv"></i> Layar</a>
            <a href="index.php" class="btn btn-sm btn-outline-light"><i class="bi bi-cash-coin"></i> Keuangan</a>
            <a href="password_mt.php" class="btn btn-sm btn-outline-light"><i class="bi bi-cash-coin"></i> MT</a>
            <a href="daftar_mandiri.php" class="btn btn-sm btn-outline-light"><i class="bi bi-cash-coin"></i> Mandiri</a>
            <a href="index.php?logout=true" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-right"></i> Keluar</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        <div>
            <h3 class="fw-bold text-dark mb-0 text-capitalize">Kelola Data <?= htmlspecialchars($tab == 'kontrol_display' ? 'Layar Panggung' : $tab) ?></h3>
            <small class="text-muted">Gebyar Milad MT. Muallaf Taufiqiyah XV</small>
        </div>
        <div>
            <?php if($tab == 'peserta'): ?>
                <button class="btn btn-success fw-bold shadow-sm" onclick="openPesertaModal()"><i class="bi bi-plus-circle"></i> Tambah Peserta Baru</button>
            <?php elseif($tab == 'panitia'): ?>
                <button class="btn btn-success fw-bold shadow-sm" onclick="openPanitiaModal()"><i class="bi bi-plus-circle"></i> Tambah Panitia Baru</button>
            <?php elseif($tab == 'majelis'): ?>
                <button class="btn btn-success fw-bold shadow-sm" onclick="openMajelisModal()"><i class="bi bi-plus-circle"></i> Tambah Majelis Taklim</button>
            <?php elseif($tab == 'kategori'): ?>
                <button class="btn btn-success fw-bold shadow-sm" onclick="openKategoriModal()"><i class="bi bi-plus-circle"></i> Tambah Kategori Lomba</button>
            <?php endif; ?>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4 bg-white p-2 rounded shadow-sm">
        <li class="nav-item">
            <a class="nav-link fw-bold text-success <?= $tab == 'peserta' ? 'active' : '' ?>" href="peserta.php?tab=peserta"><i class="bi bi-people-fill"></i> Data Peserta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold text-success <?= $tab == 'kontrol_display' ? 'active' : '' ?>" href="peserta.php?tab=kontrol_display"><i class="bi bi-sliders"></i> Kontrol Layar</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold text-success <?= $tab == 'panitia' ? 'active' : '' ?>" href="peserta.php?tab=panitia"><i class="bi bi-person-badge-fill"></i> Data Panitia</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold text-success <?= $tab == 'majelis' ? 'active' : '' ?>" href="peserta.php?tab=majelis"><i class="bi bi-building"></i> Majelis Taklim</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold text-success <?= $tab == 'kategori' ? 'active' : '' ?>" href="peserta.php?tab=kategori"><i class="bi bi-tags-fill"></i> Kategori Lomba</a>
        </li>
    </ul>

    <?php if($tab != 'kontrol_display'): ?>
    <form method="GET" class="mb-4">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="input-group bg-white shadow-sm rounded">
            <input type="text" name="search" class="form-control border-0 px-3" placeholder="Cari data berdasarkan kata kunci..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-success px-4" type="submit"><i class="bi bi-search"></i> Cari</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($tab == 'kontrol_display'): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="fw-bold text-dark mb-3"><i class="bi bi-broadcast text-danger"></i> Atur Peserta & Live Score</h4>
                <hr>
                
                <form method="GET" class="mb-4">
                    <input type="hidden" name="tab" value="kontrol_display">
                    <label class="form-label fw-bold">1. Pilih Cabang Perlombaan</label>
                    <select name="kat_id" class="form-select form-select-lg border-primary" onchange="this.form.submit()">
                        <option value="">-- Pilih Perlombaan --</option>
                        <?php foreach($list_kategori as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $selected_kat == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php 
                $current_scores = [1 => 0, 2 => 0, 3 => 0];
                $active_majelis = [1 => '', 2 => '', 3 => ''];
                
                if($is_cerdas_cermat) {
                    $mt_scores = $pdo->query("SELECT id, is_tampil, skor FROM mld_majelis WHERE is_tampil > 0")->fetchAll();
                    foreach($mt_scores as $ms) {
                        $current_scores[$ms['is_tampil']] = $ms['skor'] ?? 0;
                        $active_majelis[$ms['is_tampil']] = $ms['id'];
                    }
                } else {
                    $pst_active = $pdo->query("SELECT id, is_tampil FROM mld_peserta WHERE is_tampil > 0")->fetch();
                }
                ?>

                <form method="POST" action="peserta.php?tab=kontrol_display">
                    <input type="hidden" name="kategori_id" value="<?= htmlspecialchars($selected_kat) ?>">
                    
                    <?php if($is_cerdas_cermat): ?>
                        <div class="alert alert-info py-2"><i class="bi bi-info-circle"></i> Mode Cerdas Cermat Aktif.</div>
                        
                        <div class="card p-3 mb-3 border-start border-primary border-4 bg-light">
                            <label class="form-label fw-bold text-primary">Regu A (Majelis Taklim)</label>
                            <div class="row g-2">
                                <div class="col-sm-8">
                                    <select name="majelis_id_a" class="form-select">
                                        <option value="">-- Pilih Majelis Regu A --</option>
                                        <?php foreach($peserta_opsi as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $active_majelis[1] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mt']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" name="skor_a" class="form-control text-center fw-bold text-primary" value="<?= $current_scores[1] ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card p-3 mb-3 border-start border-success border-4 bg-light">
                            <label class="form-label fw-bold text-success">Regu B (Majelis Taklim)</label>
                            <div class="row g-2">
                                <div class="col-sm-8">
                                    <select name="majelis_id_b" class="form-select">
                                        <option value="">-- Pilih Majelis Regu B --</option>
                                        <?php foreach($peserta_opsi as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $active_majelis[2] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mt']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" name="skor_b" class="form-control text-center fw-bold text-success" value="<?= $current_scores[2] ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card p-3 mb-3 border-start border-danger border-4 bg-light">
                            <label class="form-label fw-bold text-danger">Regu C (Majelis Taklim)</label>
                            <div class="row g-2">
                                <div class="col-sm-8">
                                    <select name="majelis_id_c" class="form-select">
                                        <option value="">-- Pilih Majelis Regu C --</option>
                                        <?php foreach($peserta_opsi as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $active_majelis[3] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mt']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" name="skor_c" class="form-control text-center fw-bold text-danger" value="<?= $current_scores[3] ?>">
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Peserta yang Sedang Tampil</label>
                            <select name="peserta_id" class="form-select form-select-lg border-success">
                                <option value="">-- Kosongkan Layar / Istirahat --</option>
                                <?php foreach($peserta_opsi as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= isset($pst_active['id']) && $pst_active['id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_peserta']) ?> (<?= htmlspecialchars($p['nama_mt']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid mt-4">
                        <button type="submit" name="action_display" class="btn btn-lg btn-success shadow-sm">
                            <i class="bi bi-arrow-repeat"></i> Tampilkan Lomba ke Layar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 mb-4 bg-white">
                <h4 class="fw-bold text-dark mb-3"><i class="bi bi-person-video2 text-primary"></i> Atur Tampilan Struktur Panitia</h4>
                <hr>
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-info-circle-fill"></i> Mengaktifkan opsi ini akan mengalihkan Layar Panggung utama untuk menampilkan kredit bergulir seluruh panitia (urut sesuai nomor urut).
                </div>

                <form method="POST" action="peserta.php?tab=kontrol_display">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mode Tampilan Khusus</label>
                        <select name="mode_display_khusus" class="form-select border-primary" required>
                            <option value="panitia" <?= ($_SESSION['display_mode'] ?? 'lomba') == 'panitia' ? 'selected' : '' ?>>Tampilkan Slide-Up Struktur Panitia</option>
                            <option value="lomba" <?= ($_SESSION['display_mode'] ?? 'lomba') == 'lomba' ? 'selected' : '' ?>>Kembali ke Mode Perlombaan Utama</option>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" name="action_display_panitia" class="btn btn-lg btn-primary shadow-sm">
                            <i class="bi bi-play-circle-fill"></i> Terapkan Mode Layar Panitia
                        </button>
                    </div>
                </form>
                
                <div class="mt-4 pt-2 border-top">
                    <span class="badge bg-<?= ($_SESSION['display_mode'] ?? 'lomba') == 'panitia' ? 'primary' : 'secondary' ?> py-2 px-3 w-100 fs-6">
                        Status Saat Ini: Mode <?= ($_SESSION['display_mode'] ?? 'lomba') == 'panitia' ? 'Tampilan Panitia Aktif' : 'Perlombaan Utama' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($tab == 'peserta'): ?>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Foto</th>
                        <th>Nama Peserta</th>
                        <th>No HP</th>
                        <th>Kategori Lomba Diikuti</th>
                        <th>Asal Majelis Taklim</th>
                        <th class="text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($data_peserta) == 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada peserta terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach($data_peserta as $row): ?>
                        <tr class="<?= $row['is_tampil'] ? 'table-warning border-start border-warning border-4' : '' ?>">
                            <td class="ps-3">
                                <?php if($row['foto']): ?>
                                    <img src="uploads/<?= $row['foto'] ?>" class="thumb-foto" onclick="viewFoto('uploads/<?= $row['foto'] ?>')">
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">No Foto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_peserta']) ?></strong>
                                <?php if($row['is_tampil']): ?>
                                    <span class="badge bg-danger ms-2"><i class="bi bi-broadcast"></i> Sedang Tampil</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                            <td><span class="badge bg-primary text-wrap text-start d-inline-block" style="max-width: 250px;"><?= htmlspecialchars($row['list_lomba'] ?: 'Belum Pilih Lomba') ?></span></td>
                            <td><i class="bi bi-building"></i> <?= htmlspecialchars($row['nama_mt']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="openPesertaModal(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="bi bi-pencil"></i></button>
                                <a href="peserta.php?tab=peserta&hapus_peserta=<?= $row['id'] ?>" onclick="return confirm('Hapus peserta ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($tab == 'panitia'): ?>
    <form method="POST" action="peserta.php?tab=panitia">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-secondary"><i class="bi bi-info-circle"></i> Isikan nomor urut untuk menyusun urutan tampilan panitia di panggung.</span>
                <button type="submit" name="update_urutan_panitia" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-save"></i> Simpan Semua Urutan</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 90px;">No Urut</th>
                            <th>Foto</th>
                            <th>Nama Lengkap</th>
                            <th>Jabatan Struktur</th>
                            <th>No HP / WhatsApp</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($data_panitia) == 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada staf panitia yang diinput.</td></tr>
                        <?php else: ?>
                            <?php foreach($data_panitia as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="number" name="urutan[<?= $row['id'] ?>]" class="form-control form-control-sm text-center fw-bold" value="<?= htmlspecialchars($row['urutan'] ?? 0) ?>" min="0" style="max-width: 70px; margin: 0 auto;">
                                </td>
                                <td>
                                    <?php if($row['foto']): ?>
                                        <img src="uploads/<?= $row['foto'] ?>" class="thumb-foto" onclick="viewFoto('uploads/<?= $row['foto'] ?>')">
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">No Foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($row['nama_panitia']) ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['jabatan']) ?></span></td>
                                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openPanitiaModal(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="bi bi-pencil"></i></button>
                                    <a href="peserta.php?tab=panitia&hapus_panitia=<?= $row['id'] ?>" onclick="return confirm('Hapus data panitia ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <?php if($tab == 'majelis'): ?>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Nama Majelis Taklim</th>
                        <th>Nama PIC / Kontak</th>
                        <th>No HP / WhatsApp</th>
                        <th class="text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($data_majelis) == 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Data Majelis Taklim kosong.</td></tr>
                    <?php else: ?>
                        <?php foreach($data_majelis as $row): ?>
                        <tr>
                            <td class="ps-3"><strong><?= htmlspecialchars($row['nama_mt']) ?></strong></td>
                            <td><?= htmlspecialchars($row['pic']) ?></td>
                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="openMajelisModal(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="bi bi-pencil"></i></button>
                                <a href="peserta.php?tab=majelis&hapus_majelis=<?= $row['id'] ?>" onclick="return confirm('Hapus majelis ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($tab == 'kategori'): ?>
    <div class="card border-0 shadow-sm overflow-hidden" style="max-width: 600px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Nama Kategori Perlombaan</th>
                        <th class="text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($data_kategori) == 0): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">Kategori lomba belum ada.</td></tr>
                    <?php else: ?>
                        <?php foreach($data_kategori as $row): ?>
                        <tr>
                            <td class="ps-3 dat-nama"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="openKategoriModal(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="bi bi-pencil"></i></button>
                                <a href="peserta.php?tab=kategori&hapus_kategori=<?= $row['id'] ?>" onclick="return confirm('Hapus kategori ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalPanitia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="peserta.php?tab=panitia" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titlePanitia">Data Anggota Panitia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="pntId">
                <input type="hidden" name="old_foto" id="pntOldFoto">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Lengkap Panitia</label>
                    <input type="text" name="nama_panitia" id="pntNama" class="form-control" required placeholder="Nama Lengkap">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Jabatan / Sie Panitia</label>
                    <input type="text" name="jabatan" id="pntJabatan" class="form-control" required placeholder="Contoh: Ketua Sie Lomba">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" id="pntHp" class="form-control" required placeholder="08XXXXXXXXX">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Foto Resmi <small class="text-muted">(Opsional)</small></label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="action_panitia" class="btn btn-success">Simpan Data Panitia</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="peserta.php?tab=kategori" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titleKategori">Kategori Lomba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="katId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Kategori Perlombaan</label>
                    <input type="text" name="nama_kategori" id="katNama" class="form-control" required placeholder="Contoh: Lomba Qasidah">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="action_kategori" class="btn btn-success">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalMajelis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="peserta.php?tab=majelis" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titleMajelis">Data Majelis Taklim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="mjId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Majelis Taklim (MT)</label>
                    <input type="text" name="nama_mt" id="mjNama" class="form-control" required placeholder="Contoh: MT Taufiqiyah">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama PIC / Penanggung Jawab</label>
                    <input type="text" name="pic" id="mjPic" class="form-control" required placeholder="Nama Kontak">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" id="mjHp" class="form-control" required placeholder="Nomor Telepon">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="action_majelis" class="btn btn-success">Simpan Data MT</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPeserta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="peserta.php?tab=peserta" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titlePeserta">Registrasi Peserta Lomba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="pstId">
                <input type="hidden" name="old_foto" id="pstOldFoto">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Lengkap Peserta</label>
                    <input type="text" name="nama_peserta" id="pstNama" class="form-control" required placeholder="Nama Lengkap">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nomor HP Aktif</label>
                    <input type="text" name="no_hp" id="pstHp" class="form-control" required placeholder="Nomor Telepon">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Kategori Lomba yang Diikuti (Bisa Pilih > 1)</label>
                    <div class="p-3 border rounded bg-light" style="max-height: 170px; overflow-y: auto;">
                        <?php foreach($list_kategori as $k): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input chk-lomba" type="checkbox" name="kategori_ids[]" value="<?= $k['id'] ?>" id="chk_kat_<?= $k['id'] ?>">
                                <label class="form-check-label" for="chk_kat_<?= $k['id'] ?>">
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Asal Kafilah / Majelis Taklim</label>
                    <select name="majelis_id" id="pstMajelis" class="form-select" required>
                        <option value="">-- Pilih Asal MT --</option>
                        <?php foreach($list_majelis as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mt']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Foto Peserta <small class="text-muted">(Opsional)</small></label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="action_peserta" class="btn btn-success">Simpan Data Peserta</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0 text-center">
            <img src="" id="imgZoom" style="max-width: 100%; max-height: 80vh; border-radius: 8px;" class="shadow">
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let mPanitia, mKategori, mMajelis, mPeserta, mFoto;
    document.addEventListener("DOMContentLoaded", function() {
        mPanitia = new bootstrap.Modal(document.getElementById('modalPanitia'));
        mKategori = new bootstrap.Modal(document.getElementById('modalKategori'));
        mMajelis = new bootstrap.Modal(document.getElementById('modalMajelis'));
        mPeserta = new bootstrap.Modal(document.getElementById('modalPeserta'));
        mFoto = new bootstrap.Modal(document.getElementById('modalFoto'));
    });

    function openPanitiaModal(data = null) {
        if(data) {
            document.getElementById('pntId').value = data.id;
            document.getElementById('pntNama').value = data.nama_panitia;
            document.getElementById('pntJabatan').value = data.jabatan;
            document.getElementById('pntHp').value = data.no_hp;
            document.getElementById('pntOldFoto').value = data.foto || "";
            document.getElementById('titlePanitia').innerText = "Ubah Data Staf Panitia";
        } else {
            document.getElementById('pntId').value = "";
            document.getElementById('pntNama').value = "";
            document.getElementById('pntJabatan').value = "";
            document.getElementById('pntHp').value = "";
            document.getElementById('pntOldFoto').value = "";
            document.getElementById('titlePanitia').innerText = "Tambah Staf Panitia";
        }
        mPanitia.show();
    }

    function openKategoriModal(data = null) {
        if(data) {
            document.getElementById('katId').value = data.id;
            document.getElementById('katNama').value = data.nama_kategori;
            document.getElementById('titleKategori').innerText = "Ubah Kategori Lomba";
        } else {
            document.getElementById('katId').value = "";
            document.getElementById('katNama').value = "";
            document.getElementById('titleKategori').innerText = "Tambah Kategori Lomba";
        }
        mKategori.show();
    }

    function openMajelisModal(data = null) {
        if(data) {
            document.getElementById('mjId').value = data.id;
            document.getElementById('mjNama').value = data.nama_mt;
            document.getElementById('mjPic').value = data.pic;
            document.getElementById('mjHp').value = data.no_hp;
            document.getElementById('titleMajelis').innerText = "Ubah Data Majelis Taklim";
        } else {
            document.getElementById('mjId').value = "";
            document.getElementById('mjNama').value = "";
            document.getElementById('mjPic').value = "";
            document.getElementById('mjHp').value = "";
            document.getElementById('titleMajelis').innerText = "Tambah Majelis Taklim";
        }
        mMajelis.show();
    }

    function openPesertaModal(data = null) {
        document.querySelectorAll('.chk-lomba').forEach(el => el.checked = false);

        if(data) {
            document.getElementById('pstId').value = data.id;
            document.getElementById('pstNama').value = data.nama_peserta;
            document.getElementById('pstHp').value = data.no_hp;
            document.getElementById('pstMajelis').value = data.majelis_id;
            document.getElementById('pstOldFoto').value = data.foto || "";
            document.getElementById('titlePeserta').innerText = "Ubah Data Peserta";

            fetch('peserta.php?ajax_get_lomba=1&peserta_id=' + data.id)
                .then(res => res.json())
                .then(ids => {
                    ids.forEach(id => {
                        let chk = document.getElementById('chk_kat_' + id);
                        if(chk) chk.checked = true;
                    });
                });
        } else {
            document.getElementById('pstId').value = "";
            document.getElementById('pstNama').value = "";
            document.getElementById('pstHp').value = "";
            document.getElementById('pstMajelis').value = "";
            document.getElementById('pstOldFoto').value = "";
            document.getElementById('titlePeserta').innerText = "Registrasi Peserta Baru";
        }
        mPeserta.show();
    }

    function viewFoto(src) {
        document.getElementById('imgZoom').src = src;
        mFoto.show();
    }
</script>
</body>
</html>