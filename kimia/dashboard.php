<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Kimia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h3 class="mb-4">🧪 Dashboard Sistem Kimia Terpadu</h3>

    <div class="row g-3">
        <div class="col-md-4">
            <a href="kimia_dasar/stoikiometri.php" class="btn btn-primary w-100 p-3">
                Kimia Dasar – Stoikiometri
            </a>
        </div>
        <div class="col-md-4">
            <a href="kimia_analitik/titrasi.php" class="btn btn-success w-100 p-3">
                Kimia Analitik – Titrasi
            </a>
        </div>
        <div class="col-md-4">
            <a href="grafik/grafik_praktikum.php" class="btn btn-warning w-100 p-3">
                Grafik Hasil Praktikum
            </a>
        </div>
    </div>
</div>

</body>
</html>
