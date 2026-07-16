<?php 
include '../auth/session.php'; 

// 1. Ambil Statistik (Logika menyesuaikan level user)
$where = "";
$params = [];

if ($_SESSION['level'] == 'Kawil') {
    $where = " WHERE wilayah = ?";
    $params = [$_SESSION['wilayah']];
} elseif ($_SESSION['level'] == 'Kablok') {
    $where = " WHERE wilayah = ? AND blok = ?";
    $params = [$_SESSION['wilayah'], $_SESSION['blok']];
}

// Total KK
$stmt1 = $pdo->prepare("SELECT COUNT(*) FROM tr_warga_kk" . $where);
$stmt1->execute($params);
$total_kk = $stmt1->fetchColumn();

// Total Seluruh Warga (KK + Anggota)
// Kita hitung total anggota dari KK yang terfilter
if ($_SESSION['level'] == 'Super Admin') {
    $total_warga = $pdo->query("SELECT (SELECT COUNT(*) FROM tr_warga_kk) + (SELECT COUNT(*) FROM tr_warga_anggota)")->fetchColumn();
} else {
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM tr_warga_anggota WHERE id_kk IN (SELECT id_kk FROM tr_warga_kk $where)");
    $stmt2->execute($params);
    $total_anggota = $stmt2->fetchColumn();
    $total_warga = $total_kk + $total_anggota;
}

// Statistik Gender (Contoh untuk Pie Chart sederhana nanti)
$stmt3 = $pdo->prepare("SELECT jk, COUNT(*) as jumlah,wilayah FROM tr_warga_kk $where GROUP BY jk");
$sql = "SELECT jk, COUNT(*) as jumlah 
        FROM (
            SELECT jk,wilayah FROM tr_warga_kk $where
            UNION ALL
            SELECT jk FROM tr_warga_anggota $where
        ) as gabungan 
        GROUP BY jk";

$stmt3 = $pdo->prepare($sql);

// Gandakan array params agar cukup untuk kedua klausa WHERE
$double_params = array_merge($params, $params);

$stmt3->execute($double_params);
$gender_stats = $stmt3->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bukit Sanggulan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .stat-card { transition: transform 0.2s; border: none; border-radius: 15px; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 15px; bottom: 10px; }
    </style>
</head>
<body class="bg-light">

    <?php include 'navbar.php'; // Sebaiknya navbar dipisah agar rapi ?>

    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4 bg-white shadow-sm rounded-3">
                    <h4 class="mb-1">Selamat Datang, <?= $_SESSION['username'] ?>!</h4>
                    <p class="text-muted mb-0">
                        Akses Anda: <strong><?= $_SESSION['level'] ?></strong> 
                        <?= $_SESSION['wilayah'] ? " - " . $_SESSION['wilayah'] : "" ?>
                        <?= $_SESSION['blok'] ? " (Blok " . $_SESSION['blok'] . ")" : "" ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card stat-card shadow-sm bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small fw-bold">Total Kepala Keluarga</h6>
                        <h2 class="display-5 fw-bold"><?= $total_kk ?></h2>
                        <i class="bi bi-house-door icon-box"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card shadow-sm bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small fw-bold">Total Seluruh Warga</h6>
                        <h2 class="display-5 fw-bold"><?= $total_warga ?></h2>
                        <i class="bi bi-people icon-box"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card shadow-sm bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small fw-bold">Cakupan Data</h6>
                        <p class="mb-0">Menampilkan data untuk:</p>
                        <h4 class="fw-bold"><?= $_SESSION['wilayah'] ?? 'Semua Wilayah' ?></h4>
                        <i class="bi bi-geo-alt icon-box"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white fw-bold py-3">Aksi Cepat</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <a href="warga_input.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="bi bi-person-plus d-block fs-3"></i> Tambah Warga
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="warga_list.php" class="btn btn-outline-secondary w-100 py-3">
                                    <i class="bi bi-table d-block fs-3"></i> Lihat Data
                                </a>
                            </div>
                            <?php if($_SESSION['level'] == 'Super Admin'): ?>
                            <div class="col-6 col-md-3">
                                <a href="user_master.php" class="btn btn-outline-dark w-100 py-3">
                                    <i class="bi bi-shield-lock d-block fs-3"></i> Kelola User
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="col-6 col-md-3">
                                <button onclick="window.print()" class="btn btn-outline-danger w-100 py-3">
                                    <i class="bi bi-printer d-block fs-3"></i> Cetak Laporan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-header bg-white fw-bold py-3">Distribusi Gender (KK)</div>
                    <div class="card-body d-flex align-items-center">
                        <ul class="list-group list-group-flush w-100">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Laki-laki <span class="badge bg-primary rounded-pill"><?= $gender_stats['L'] ?? 0 ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Perempuan <span class="badge bg-danger rounded-pill"><?= $gender_stats['P'] ?? 0 ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>