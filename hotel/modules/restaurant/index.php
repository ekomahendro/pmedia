<?php
require_once '../../config.php';
check_login(); // Pastikan user sudah login

// Ambil daftar outlet untuk ditampilkan
$outlets = $conn->query("SELECT * FROM htl_outlets WHERE status = 'active'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management - Core Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-hover:hover { transform: translateY(-5px); transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../dashboard.php"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        <span class="navbar-text">Restaurant Management</span>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Pilih Outlet</h3>
        <a href="admin_order.php" class="btn btn-warning"><i class="bi bi-receipt"></i> Monitor Pesanan Aktif</a>
    </div>

    <div class="row">
        <?php while($o = $outlets->fetch_assoc()): ?>
        <div class="col-md-4 mb-3">
            <div class="card card-hover shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i class="bi bi-shop"></i> <?= $o['outlet_name'] ?></h5>
                    <p class="text-muted small">Kode: <?= $o['outlet_code'] ?></p>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="menu_setup.php?id=<?= $o['id_outlet'] ?>" class="btn btn-outline-primary btn-sm">Setup Menu</a>
                        <a href="table_setup.php?id=<?= $o['id_outlet'] ?>" class="btn btn-outline-secondary btn-sm">Setup Meja & QR</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>