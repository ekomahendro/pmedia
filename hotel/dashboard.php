<?php
require_once 'config.php';
check_login();

$id_user    = $_SESSION['user_id'];
$id_license = $_SESSION['id_license'];

/**
 * KUERI FILTER GANDA (JOIN MATRIX):
 * Hanya menampilkan modul utama (parent_id = 0) yang:
 * 1. Diizinkan untuk akun USER ini (htl_user_access)
 * 2. DAN AKTIF pada paket LISENSI hotel ini (htl_license_access)
 */
$query_modul = "SELECT m.* FROM htl_modules m 
                JOIN htl_user_access ua ON m.id_module = ua.id_module 
                JOIN htl_license_access la ON m.id_module = la.id_module
                WHERE m.parent_id = 0 
                AND ua.id_user = ? 
                AND la.id_license = ?
                ORDER BY m.id_module ASC";

$stmt_modul = mysqli_prepare($conn, $query_modul);
mysqli_stmt_bind_param($stmt_modul, "ii", $id_user, $id_license);
mysqli_stmt_execute($stmt_modul);
$res_modul = mysqli_stmt_get_result($stmt_modul);

$allowed_modules = [];
while($row = mysqli_fetch_assoc($res_modul)) {
    $allowed_modules[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Core Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-custom { background: #1e3c72; }
        /* Style Kotak Elips Menarik sesuai Request */
        .ellipse-card {
            border-radius: 50px; /* Membuat bentuk elips/kapsul melengkung manis */
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 15px 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .ellipse-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(30, 60, 114, 0.15);
            border-color: #1e3c72;
            background: linear-gradient(135deg, #ffffff 0%, #f0f4f8 100%);
        }
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e3faf2;
            color: #0ca678;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 15px;
            flex-shrink: 0;
        }
        /* Variasi warna icon acak agar dashboard tampak hidup */
        .ellipse-card:nth-child(2n) .icon-circle { background-color: #edf2ff; color: #4c6ef5; }
        .ellipse-card:nth-child(3n) .icon-circle { background-color: #fff4e6; color: #fd7e14; }
        .ellipse-card:nth-child(4n) .icon-circle { background-color: #f3f0ff; color: #7048e8; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
            <i class="bi bi-building me-2"></i> <?= $_SESSION['hotel_name']; ?>
        </a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 small d-none d-md-inline">Halo, <strong><?= $_SESSION['fullname']; ?></strong> (<?= ucfirst($_SESSION['role']); ?>)</span>
            
            <?php if($_SESSION['role'] == 'superadmin' || $_SESSION['role'] == 'admin'): ?>
                <a href="users_management.php" class="btn btn-sm btn-info me-2 fw-bold text-dark rounded-pill">
                    <i class="bi bi-person-gear"></i> Kelola User
                </a>
                <a href="access_control.php" class="btn btn-sm btn-warning me-2 fw-bold rounded-pill">
                    <i class="bi bi-sliders"></i> Hak Akses
                </a>
            <?php endif; ?>
            
            <a href="logout.php" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Log out dari sistem?')"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="fw-bold text-dark mb-1">Main Dashboard Directory</h4>
            <p class="text-secondary small">Silahkan pilih modul operasional kerja Anda di bawah ini.</p>
        </div>
    </div>

    <!-- Grid Folder Modul Utama -->
    <div class="row g-3">
        <?php if(count($allowed_modules) > 0): ?>
            <?php foreach($allowed_modules as $mod): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                    <a href="modules/<?= $mod['module_slug']; ?>/index.php" class="ellipse-card">
                        <div class="icon-circle">
                            <i class="bi <?= $mod['icon']; ?>"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;"><?= $mod['module_name']; ?></div>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Buka Modul <i class="bi bi-arrow-right small"></i></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center p-4">
                    <i class="bi bi-shield-lock-fill text-warning fs-1 d-block mb-2"></i>
                    <h5>Akses Diblokir / Belum Diatur</h5>
                    <p class="small text-muted mb-0">Akun Anda belum memiliki izin akses ke modul manapun. Hubungi Super Admin untuk konfigurasi.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>