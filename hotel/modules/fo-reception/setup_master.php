<?php
require_once '../../config.php';
check_login();

$msg = '';

// =========================================================================
// PROSES POST: HANDLER INTEGRASI TAMBAH & EDIT DATA (8 MODUL GLOBAL)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_edit = intval($_POST['is_edit']);

    // 1. Arrangement
    if (isset($_POST['save_arrangement'])) {
        $code = trim($_POST['arr_code']); $name = trim($_POST['arr_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_arrangements SET arr_name = ? WHERE arr_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_arrangements (arr_code, arr_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Arrangement berhasil disimpan!";
    }
    // 2. Market Segment
    if (isset($_POST['save_segment'])) {
        $code = trim($_POST['seg_code']); $name = trim($_POST['seg_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_segments SET seg_name = ? WHERE seg_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_segments (seg_code, seg_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Market Segment berhasil disimpan!";
    }
    // 3. Booking Source
    if (isset($_POST['save_source'])) {
        $code = trim($_POST['src_code']); $name = trim($_POST['src_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_sources SET src_name = ? WHERE src_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_sources (src_code, src_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Booking Source berhasil disimpan!";
    }
    // 4. Department Definition
    if (isset($_POST['save_dept'])) {
        $code = trim($_POST['dept_code']); $name = trim($_POST['dept_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_departments SET dept_name = ? WHERE dept_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_departments (dept_code, dept_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Department berhasil disimpan!";
    }
    // 5. Outlet Definition
    if (isset($_POST['save_outlet'])) {
        $code = trim($_POST['outlet_code']); $name = trim($_POST['outlet_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_outlets SET outlet_name = ? WHERE outlet_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_outlets (outlet_code, outlet_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Outlet berhasil disimpan!";
    }
    // 6. Region
    if (isset($_POST['save_region'])) {
        $code = trim($_POST['region_code']); $name = trim($_POST['region_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_regions SET region_name = ? WHERE region_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_regions (region_code, region_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Region berhasil disimpan!";
    }
    // 7. Nationality
    if (isset($_POST['save_nation'])) {
        $code = trim($_POST['nat_code']); $name = trim($_POST['nat_name']);
        if ($is_edit === 1) { $stmt = mysqli_prepare($conn, "UPDATE htl_nationalities SET nat_name = ? WHERE nat_code = ?"); mysqli_stmt_bind_param($stmt, "ss", $name, $code); }
        else { $stmt = mysqli_prepare($conn, "INSERT INTO htl_nationalities (nat_code, nat_name) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt, "ss", $code, $name); }
        if(mysqli_stmt_execute($stmt)) $msg = "Nationality berhasil disimpan!";
    }
    // 8. Room Administration
    if (isset($_POST['save_room'])) {
        $room_number       = trim($_POST['room_number']);
        $room_type     = trim($_POST['room_type']);
        $is_connecting = isset($_POST['is_connecting']) ? 1 : 0;
        $connecting_to = ($is_connecting === 1 && !empty($_POST['connecting_to'])) ? trim($_POST['connecting_to']) : null;

        if ($is_edit === 1) {
            $stmt = mysqli_prepare($conn, "UPDATE htl_rooms SET room_type = ?, is_connecting = ?, connecting_to = ? WHERE room_number = ?");
            mysqli_stmt_bind_param($stmt, "siss", $room_type, $is_connecting, $connecting_to, $room_number);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO htl_rooms (room_number, room_type, is_connecting, connecting_to) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssis", $room_number, $room_type, $is_connecting, $connecting_to);
        }
        if(mysqli_stmt_execute($stmt)) $msg = "Data Kamar berhasil disimpan!";
    }
}

// =========================================================================
// PROSES GET: HANDLER HAPUS DATA (DELETE)
// =========================================================================
if (isset($_GET['del_type']) && isset($_GET['code'])) {
    $code = mysqli_real_escape_string($conn, $_GET['code']);
    switch ($_GET['del_type']) {
        case 'arr': mysqli_query($conn, "DELETE FROM htl_arrangements WHERE arr_code = '$code'"); mysqli_query($conn, "DELETE FROM htl_arrangement_articles WHERE arr_code = '$code'"); break;
        case 'seg': mysqli_query($conn, "DELETE FROM htl_segments WHERE seg_code = '$code'"); break;
        case 'src': mysqli_query($conn, "DELETE FROM htl_sources WHERE src_code = '$code'"); break;
        case 'dept': mysqli_query($conn, "DELETE FROM htl_departments WHERE dept_code = '$code'"); break;
        case 'outlet': mysqli_query($conn, "DELETE FROM htl_outlets WHERE outlet_code = '$code'"); break;
        case 'region': mysqli_query($conn, "DELETE FROM htl_regions WHERE region_code = '$code'"); break;
        case 'nation': mysqli_query($conn, "DELETE FROM htl_nationalities WHERE nat_code = '$code'"); break;
        case 'room': mysqli_query($conn, "DELETE FROM htl_rooms WHERE room_number = '$code'"); break;
    }
    header("Location: setup_master.php"); exit();
}

// AMBIL SEMUA DATA UNTUK DITAMPILKAN DI TABEL
$arr_list    = mysqli_query($conn, "SELECT * FROM htl_arrangements ORDER BY arr_code ASC");
$seg_list    = mysqli_query($conn, "SELECT * FROM htl_segments ORDER BY seg_code ASC");
$src_list    = mysqli_query($conn, "SELECT * FROM htl_sources ORDER BY src_code ASC");
$dept_list   = mysqli_query($conn, "SELECT * FROM htl_departments ORDER BY dept_code ASC");
$outlet_list = mysqli_query($conn, "SELECT * FROM htl_outlets ORDER BY outlet_code ASC");
$region_list = mysqli_query($conn, "SELECT * FROM htl_regions ORDER BY region_code ASC");
$nat_list    = mysqli_query($conn, "SELECT * FROM htl_nationalities ORDER BY nat_code ASC");
$room_list   = mysqli_query($conn, "SELECT * FROM htl_rooms ORDER BY room_number ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Setup Parameter Master Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #f4f6f9; }
        .card-param { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); min-height: 400px; }
        .table-responsive { max-height: 250px; overflow-y: auto; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="bi bi-shield-lock-fill text-dark me-2"></i>Sistem Kontrol Setup Parameter</h4>
            <p class="text-secondary small mb-0">Kelola pemetaan konfigurasi database operasional front office global, departemen, kamar dan kebangsaan.</p>
        </div>
        <a href="index.php" class="btn btn-sm btn-outline-dark rounded-pill px-3"><i class="bi bi-arrow-left"></i> Meja Resepsionis</a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?= $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        
        <!-- CARD 1: ARRANGEMENT -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-primary"><i class="bi bi-box me-1"></i> 1. Arrangement</h6>
                    <button class="btn btn-xs btn-primary py-0 px-2 small rounded-pill text-white" onclick="openFormModal('arr', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-arr', this.value)" placeholder="Cari arrangement...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-arr">
                        <tbody>
                            <?php while($r = mysqli_fetch_assoc($arr_list)): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= $r['arr_code']; ?></span></td>
                                    <td class="target-name"><strong><?= htmlspecialchars($r['arr_name']); ?></strong></td>
                                    <td class="text-end">
                                        <a href="setup_arrangement.php?target_arr=<?= urlencode($r['arr_code']); ?>" class="btn btn-sm text-info p-1"><i class="bi bi-sliders"></i></a>
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('arr', 1, '<?= $r['arr_code']; ?>', '<?= htmlspecialchars($r['arr_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=arr&code=<?= $r['arr_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 2: MARKET SEGMENT -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-success"><i class="bi bi-pie-chart me-1"></i> 2. Market Segment</h6>
                    <button class="btn btn-xs btn-success py-0 px-2 small rounded-pill text-white" onclick="openFormModal('seg', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-seg', this.value)" placeholder="Cari segment...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-seg">
                        <tbody>
                            <?php while($s = mysqli_fetch_assoc($seg_list)): ?>
                                <tr>
                                    <td><span class="badge bg-success"><?= $s['seg_code']; ?></span></td>
                                    <td class="target-name"><?= htmlspecialchars($s['seg_name']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('seg', 1, '<?= $s['seg_code']; ?>', '<?= htmlspecialchars($s['seg_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=seg&code=<?= $s['seg_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 3: BOOKING SOURCE -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-warning"><i class="bi bi-globe me-1"></i> 3. Booking Source</h6>
                    <button class="btn btn-xs btn-warning py-0 px-2 small rounded-pill text-white" onclick="openFormModal('src', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-src', this.value)" placeholder="Cari source...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-src">
                        <tbody>
                            <?php while($c = mysqli_fetch_assoc($src_list)): ?>
                                <tr>
                                    <td><span class="badge bg-warning text-dark"><?= $c['src_code']; ?></span></td>
                                    <td class="target-name"><?= htmlspecialchars($c['src_name']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('src', 1, '<?= $c['src_code']; ?>', '<?= htmlspecialchars($c['src_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=src&code=<?= $c['src_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 4: DEPARTMENT DEFINITION -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-danger"><i class="bi bi-building me-1"></i> 4. Department Definition</h6>
                    <button class="btn btn-xs btn-danger py-0 px-2 small rounded-pill text-white" onclick="openFormModal('dept', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-dept', this.value)" placeholder="Cari departemen...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-dept">
                        <tbody>
                            <?php while($d = mysqli_fetch_assoc($dept_list)): ?>
                                <tr>
                                    <td><span class="badge bg-danger"><?= $d['dept_code']; ?></span></td>
                                    <td class="target-name"><?= htmlspecialchars($d['dept_name']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('dept', 1, '<?= $d['dept_code']; ?>', '<?= htmlspecialchars($d['dept_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=dept&code=<?= $d['dept_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 5: OUTLET DEFINITION -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-info"><i class="bi bi-cart4 me-1"></i> 5. Outlet Definition</h6>
                    <button class="btn btn-xs btn-info py-0 px-2 small rounded-pill text-white" onclick="openFormModal('outlet', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-outlet', this.value)" placeholder="Cari outlet...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-outlet">
                        <tbody>
                            <?php while($o = mysqli_fetch_assoc($outlet_list)): ?>
                                <tr>
                                    <td><span class="badge bg-info text-white"><?= $o['outlet_code']; ?></span></td>
                                    <td class="target-name"><?= htmlspecialchars($o['outlet_name']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('outlet', 1, '<?= $o['outlet_code']; ?>', '<?= htmlspecialchars($o['outlet_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=outlet&code=<?= $o['outlet_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 6: REGION -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-secondary"><i class="bi bi-map me-1"></i> 6. Region / Wilayah</h6>
                    <button class="btn btn-xs btn-secondary py-0 px-2 small rounded-pill text-white" onclick="openFormModal('region', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-region', this.value)" placeholder="Cari wilayah...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-region">
                        <tbody>
                            <?php while($rg = mysqli_fetch_assoc($region_list)): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= $rg['region_code']; ?></span></td>
                                    <td class="target-name"><?= htmlspecialchars($rg['region_name']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('region', 1, '<?= $rg['region_code']; ?>', '<?= htmlspecialchars($rg['region_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=region&code=<?= $rg['region_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 7: NATIONALITY -->
        <div class="col-xl-4 col-md-6">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-dark"><i class="bi bi-flag me-1"></i> 7. Nationality / Kebangsaan</h6>
                    <button class="btn btn-xs btn-dark py-0 px-2 small rounded-pill text-white" onclick="openFormModal('nation', 0)"><i class="bi bi-plus"></i> Tambah</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-nation', this.value)" placeholder="Cari kebangsaan...">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small" id="table-nation">
                        <tbody>
                            <?php while($n = mysqli_fetch_assoc($nat_list)): ?>
                                <tr>
                                    <td><span class="badge bg-dark"><?= $n['nat_code']; ?></span></td>
                                    <td class="target-name"><?= htmlspecialchars($n['nat_name']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openFormModal('nation', 1, '<?= $n['nat_code']; ?>', '<?= htmlspecialchars($n['nat_name'], ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=nation&code=<?= $n['nat_code']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CARD 8: ROOM ADMINISTRATION -->
        <div class="col-xl-8 col-md-12">
            <div class="card card-param bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0 text-dark"><i class="bi bi-door-open-fill me-1"></i> 8. Room Administration & Connecting Setup</h6>
                    <button class="btn btn-xs btn-dark py-0 px-2 small rounded-pill" onclick="openRoomModal(0)"><i class="bi bi-plus"></i> Tambah Kamar</button>
                </div>
                <input type="text" class="form-control form-control-sm mb-2" onkeyup="searchTable('table-rooms', this.value)" placeholder="Cari nomor atau tipe kamar...">
                <div class="table-responsive" style="max-height:250px;">
                    <table class="table table-sm table-hover align-middle small" id="table-rooms">
                        <thead class="table-light sticky-top">
                            <tr><th>No. Kamar</th><th>Tipe Kamar</th><th>Status Connecting Room</th><th class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php while($rm = mysqli_fetch_assoc($room_list)): ?>
                                <tr>
                                    <td class="target-name"><strong>Kamar <?= $rm['room_number']; ?></strong></td>
                                    <td><?= htmlspecialchars($rm['room_type']); ?></td>
                                    <td>
                                        <?php if ($rm['is_connecting'] == 1): ?>
                                            <span class="badge bg-info"><i class="bi bi-link-45deg"></i> Terhubung dgn Kamar: <?= htmlspecialchars($rm['connecting_to']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Bukan Kamar Connecting</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm text-dark p-1" onclick="openRoomModal(1, '<?= $rm['room_number']; ?>', '<?= htmlspecialchars($rm['room_type'], ENT_QUOTES); ?>', <?= $rm['is_connecting']; ?>, '<?= $rm['connecting_to']; ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="setup_master.php?del_type=room&code=<?= $rm['room_number']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus kamar ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL PARAMETER (KODE & NAMA) -->
<div class="modal fade" id="modalParamForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 text-white" id="modal-header-bg">
                <h6 class="modal-title fw-bold" id="modalParamTitle">Form Parameter</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMasterParam" action="" method="POST">
                <input type="hidden" name="is_edit" id="param_is_edit" value="0">
                <div class="modal-body p-3 row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Kode Parameter</label>
                        <input type="text" name="" id="param_code" class="form-control form-control-sm text-uppercase" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Deskripsi / Nama</label>
                        <input type="text" name="" id="param_name" class="form-control form-control-sm" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer py-1 bg-light">
                    <button type="button" class="btn btn-xs btn-secondary small px-2 py-1" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="param_submit_name" name="" class="btn btn-xs btn-dark small px-3 py-1">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ADMINISTRASI KAMAR -->
<div class="modal fade" id="modalRoomForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white">
                <h6 class="modal-title fw-bold" id="modalRoomTitle">Tambah Kamar</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="is_edit" id="room_is_edit" value="0">
                <div class="modal-body p-3 row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Nomor Kamar</label>
                        <input type="text" name="room_number" id="room_number" class="form-control form-control-sm text-uppercase" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Tipe Kamar</label>
                        <input type="text" name="room_type" id="room_type" class="form-control form-control-sm" placeholder="Contoh: Deluxe, Suite" required>
                    </div>
                    <div class="col-12 pt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_connecting" id="is_connecting" onchange="toggleConnectingRow(this.checked)">
                            <label class="form-check-label small fw-bold text-info" for="is_connecting">Kamar Connecting?</label>
                        </div>
                    </div>
                    <div class="col-12 d-none" id="row_connecting_to">
                        <label class="form-label small mb-0 fw-bold text-secondary">Terhubung dengan Kamar No.</label>
                        <input type="text" name="connecting_to" id="connecting_to" class="form-control form-control-sm text-uppercase" placeholder="Misal: 102">
                    </div>
                </div>
                <div class="modal-footer py-1 bg-light">
                    <button type="button" class="btn btn-xs btn-secondary small px-2 py-1" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="save_room" class="btn btn-xs btn-dark small px-3 py-1">Simpan Kamar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const modalParam = new bootstrap.Modal(document.getElementById('modalParamForm'));
const modalRoom = new bootstrap.Modal(document.getElementById('modalRoomForm'));

function searchTable(tableId, query) {
    const filter = query.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
        const nameCell = row.querySelector('.target-name');
        if (nameCell) {
            const textValue = nameCell.textContent || nameCell.innerText;
            row.style.display = textValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        }
    });
}

function openFormModal(type, isEdit, code = '', name = '') {
    const title = document.getElementById('modalParamTitle');
    const header = document.getElementById('modal-header-bg');
    const inputCode = document.getElementById('param_code');
    const inputName = document.getElementById('param_name');
    const inputSubmit = document.getElementById('param_submit_name');
    const flagEdit = document.getElementById('param_is_edit');

    flagEdit.value = isEdit; inputCode.value = code; inputName.value = name;
    inputCode.readOnly = (isEdit === 1);

    if (type === 'arr') {
        title.innerText = isEdit ? "Edit Arrangement" : "Tambah Arrangement"; header.className = "modal-header py-2 bg-primary text-white";
        inputCode.name = "arr_code"; inputName.name = "arr_name"; inputSubmit.name = "save_arrangement";
    } else if (type === 'seg') {
        title.innerText = isEdit ? "Edit Market Segment" : "Tambah Market Segment"; header.className = "modal-header py-2 bg-success text-white";
        inputCode.name = "seg_code"; inputName.name = "seg_name"; inputSubmit.name = "save_segment";
    } else if (type === 'src') {
        title.innerText = isEdit ? "Edit Booking Source" : "Tambah Booking Source"; header.className = "modal-header py-2 bg-warning text-dark";
        inputCode.name = "src_code"; inputName.name = "src_name"; inputSubmit.name = "save_source";
    } else if (type === 'dept') {
        title.innerText = isEdit ? "Edit Department" : "Tambah Department"; header.className = "modal-header py-2 bg-danger text-white";
        inputCode.name = "dept_code"; inputName.name = "dept_name"; inputSubmit.name = "save_dept";
    } else if (type === 'outlet') {
        title.innerText = isEdit ? "Edit Outlet" : "Tambah Outlet"; header.className = "modal-header py-2 bg-info text-white";
        inputCode.name = "outlet_code"; inputName.name = "outlet_name"; inputSubmit.name = "save_outlet";
    } else if (type === 'region') {
        title.innerText = isEdit ? "Edit Region" : "Tambah Region"; header.className = "modal-header py-2 bg-secondary text-white";
        inputCode.name = "region_code"; inputName.name = "region_name"; inputSubmit.name = "save_region";
    } else if (type === 'nation') {
        title.innerText = isEdit ? "Edit Nationality" : "Tambah Nationality"; header.className = "modal-header py-2 bg-dark text-white";
        inputCode.name = "nat_code"; inputName.name = "nat_name"; inputSubmit.name = "save_nation";
    }
    modalParam.show();
}

function openRoomModal(isEdit, rNo = '', rType = '', isConnect = 0, connectTo = '') {
    document.getElementById('room_is_edit').value = isEdit;
    const rNoInput = document.getElementById('room_number');
    rNoInput.value = rNo;
    rNoInput.readOnly = (isEdit === 1);
    document.getElementById('room_type').value = rType;
    
    const checkbox = document.getElementById('is_connecting');
    checkbox.checked = (isConnect === 1);
    
    document.getElementById('modalRoomTitle').innerText = isEdit ? "Edit Administrasi Kamar" : "Tambah Kamar Baru";
    document.getElementById('connecting_to').value = connectTo;
    
    toggleConnectingRow(isConnect === 1);
    modalRoom.show();
}

function toggleConnectingRow(isChecked) {
    const row = document.getElementById('row_connecting_to');
    if(isChecked) {
        row.classList.remove('d-none');
    } else {
        row.classList.add('d-none');
        document.getElementById('connecting_to').value = '';
    }
}
</script>
</body>
</html>