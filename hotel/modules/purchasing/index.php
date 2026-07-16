<?php
require_once '../../config.php';
check_login();

// Ambil data departemen user yang sedang login dari database untuk validasi ekstra
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT u.role, d.dept_code FROM htl_users u 
                                   LEFT JOIN htl_departments d ON u.id_department = d.id_department 
                                   WHERE u.id_user = $user_id");
$user_data = mysqli_fetch_assoc($user_query);

$user_dept = $user_data['dept_code'] ?? '';
$user_role = $user_data['role'] ?? '';

// Array departemen yang diizinkan mengelola data Master & Receiving
$allowed_depts = ['CC', 'PCH', 'COST CONTROL', 'PURCHASING'];

// Flag otorisasi untuk menu khusus (Cost Control & Purchasing)
$is_authorized_special = in_array(strtoupper($user_dept), $allowed_depts) || ($user_role === 'superadmin' || $user_role === 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchasing & Inventory Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .menu-card { transition: all 0.3s ease; border: none; border-radius: 12px; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08)!important; }
        .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container px-4">
        <a class="navbar-brand fw-bold" href="../../dashboard.php"><i class="bi bi-building me-2"></i><?= $_SESSION['hotel_name']; ?></a>
        <div class="d-flex align-items-center text-white small">
            <span class="me-3"><i class="bi bi-person-circle text-warning"></i> <?= $_SESSION['fullname']; ?> (<?= $user_dept ?: 'No Dept'; ?>)</span>
            <a href="../../dashboard.php" class="btn btn-sm btn-outline-light rounded-pill"><i class="bi bi-arrow-left-circle"></i> Main Dashboard</a>
        </div>
    </div>
</nav>

<div class="container px-4">
    <div class="mb-4">
        <h2 class="fw-bold text-dark m-0">Purchasing &amp; Inventory Module</h2>
        <p class="text-secondary small">Sistem pengadaan barang, kontrol logistik gudang, dan manajemen vendor hotel.</p>
    </div>

    <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-journal-text"></i> Dokumen Permintaan &amp; Operasional</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card menu-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-primary-subtle text-primary me-3"><i class="bi bi-file-earmark-text fs-4"></i></div>
                    <div>
                        <a href="pr.php" class="fs-5 fw-bold text-dark text-decoration-none d-block">Purchase Request (PR)</a>
                        <small class="text-muted">Pengajuan pengadaan barang multi-item.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card menu-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-danger-subtle text-danger me-3"><i class="bi bi-box-arrow-up fs-4"></i></div>
                    <div>
                        <a href="sr.php" class="fs-5 fw-bold text-dark text-decoration-none d-block">Store Request (SR)</a>
                        <small class="text-muted">Pengeluaran barang internal dari gudang.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-shield-lock"></i> Kontrol Logistik &amp; Master Data <span class="badge bg-danger text-white fs-6 ms-1" style="font-size: 11px!important; vertical-align: middle;">Restricted</span></h5>
        <div class="col-md-4">
            <div class="card menu-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-success-subtle text-success me-3"><i class="bi bi-file-earmark-check fs-4"></i></div>
                    <div>
                        <a href="po.php" class="fs-5 fw-bold text-dark text-decoration-none d-block">Purchase Order (PO)</a>
                        <small class="text-muted">Persetujuan harga &amp; penunjukan Supplier.</small>
                    </div>
                </div>
            </div>
        </div>
    <div class="row g-3">
        <?php if ($is_authorized_special): ?>
            <div class="col-md-4">
                <div class="card menu-card shadow-sm border-start border-4 border-info h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-box bg-info-subtle text-info me-3"><i class="bi bi-download fs-4"></i></div>
                        <div>
                            <a href="receiving.php" class="fs-5 fw-bold text-dark text-decoration-none d-block">Receiving Log</a>
                            <small class="text-muted">Penerimaan barang dari supplier &amp; kalkulasi HPP Rata-rata.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card menu-card shadow-sm border-start border-4 border-success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-box bg-dark text-white me-3"><i class="bi bi-box-seam fs-4"></i></div>
                        <div>
                            <a href="master_items.php" class="fs-5 fw-bold text-dark text-decoration-none d-block">Master Katalog Barang</a>
                            <small class="text-muted">Kelola stok, satuan unit, dan audit harga logistik gudang.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card menu-card shadow-sm border-start border-4 border-warning h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-box bg-warning text-dark me-3"><i class="bi bi-truck fs-4"></i></div>
                        <div>
                            <a href="suppliers.php" class="fs-5 fw-bold text-dark text-decoration-none d-block">Master Supplier (Vendor)</a>
                            <small class="text-muted">Registrasi kemitraan supplier, telepon sales, dan alamat data.</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light border d-flex align-items-center p-3 mb-0" role="alert">
                    <i class="bi bi-lock-fill text-danger fs-3 me-3"></i>
                    <div>
                        <strong class="text-dark d-block">Akses Menu Logistik &amp; Master Terkunci</strong>
                        <span class="text-muted small">Modul <strong>Receiving</strong>, <strong>Master Katalog</strong>, dan <strong>Master Supplier</strong> hanya diizinkan untuk diakses oleh tim internal departemen <strong>Cost Control</strong> atau <strong>Purchasing</strong>.</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>