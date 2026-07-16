<?php
require_once 'config.php';

// 1. Ambil semua cabang lomba (kategori) untuk filter drop-down
$list_kategori = $pdo->query("SELECT * FROM mld_kategori ORDER BY nama_kategori ASC")->fetchAll();

// 2. Ambil filter kategori dari URL jika ada
$filter_kategori = $_GET['kategori_id'] ?? '';

// Identifikasi ID Kategori untuk Cerdas Cermat secara otomatis
$cc_id = 0;
foreach($list_kategori as $kat) {
    if(str_contains(strtolower($kat['nama_kategori']), 'cerdas cermat')) {
        $cc_id = $kat['id'];
        break;
    }
}

// 3. STATISTIK GLOBAL: Hitung jumlah fisik orang RIIL (Unik) yang terdaftar (tidak dobel walau ikut banyak lomba)
$total_riil_peserta = $pdo->query("SELECT COUNT(DISTINCT peserta_id) FROM mld_peserta_lomba")->fetchColumn() ?: 0;

// STATISTIK PER CABANG: Hitung jumlah peserta di tiap-tiap cabang lomba
$query_resume_cabang = "SELECT k.nama_kategori, COUNT(pl.peserta_id) AS jumlah_peserta
                        FROM mld_kategori k
                        LEFT JOIN mld_peserta_lomba pl ON k.id = pl.kategori_id
                        GROUP BY k.id
                        ORDER BY k.nama_kategori ASC";
$resume_cabang_data = $pdo->query($query_resume_cabang)->fetchAll(PDO::FETCH_ASSOC);

// 4. DATA GRAFIK: Hitung total peserta (porsi kontribusi entri) dan cabang unik per Majelis Taklim
$query_chart = "SELECT 
                    m.nama_mt, 
                    COUNT(pl.peserta_id) AS total_peserta,
                    COUNT(DISTINCT pl.kategori_id) AS total_cabang
                FROM mld_majelis m 
                JOIN mld_peserta p ON m.id = p.majelis_id 
                JOIN mld_peserta_lomba pl ON p.id = pl.peserta_id
                GROUP BY m.id 
                ORDER BY total_peserta DESC";
$raw_chart_data = $pdo->query($query_chart)->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data array untuk dilempar ke JavaScript Chart.js
$chart_labels = [];
$chart_values = [];
$chart_cabang = [];

foreach ($raw_chart_data as $row) {
    $chart_labels[] = $row['nama_mt'];
    $chart_values[] = (int)$row['total_peserta'];
    $chart_cabang[] = (int)$row['total_cabang'];
}

// 5. Query mengambil relasi detail peserta dengan tambahan data field grup_cc[cite: 2]
$query_str = "SELECT 
                k.id AS kategori_id,
                k.nama_kategori,
                p.nama_peserta,
                p.no_hp,
                p.foto,
                m.nama_mt,
                pl.grup_cc
              FROM mld_peserta_lomba pl
              JOIN mld_peserta p ON pl.peserta_id = p.id
              JOIN mld_kategori k ON pl.kategori_id = k.id
              JOIN mld_majelis m ON p.majelis_id = m.id";

$params = [];
if (!empty($filter_kategori)) {
    $query_str .= " WHERE k.id = ?";
    $params[] = $filter_kategori;
}

// Disortir berdasarkan Nama Majelis Taklim (MT) dan Grup CC jika ada
$query_str .= " ORDER BY k.nama_kategori ASC, pl.grup_cc ASC, m.nama_mt ASC, p.nama_peserta ASC";

$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Proses Pengelompokan (Grouping) data per Cabang Lomba di Sisi PHP[cite: 2]
$grouped_data = [];
foreach ($raw_data as $row) {
    $kat_nama = $row['nama_kategori'];
    if (!isset($grouped_data[$kat_nama])) {
        $grouped_data[$kat_nama] = [];
    }
    $grouped_data[$kat_nama][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap & Grafik Peserta - Milad XV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    
    <style>
        body { background: #f4f6f9; }
        .hero-section { background: linear-gradient(135deg, #0d6efd, #0a4da2); color: white; padding: 35px 0; border-radius: 0 0 25px 25px; margin-bottom: 25px; }
        .lomba-header { background-color: #ffffff; border-left: 5px solid #0d6efd; font-weight: bold; }
        .cc-header { background-color: #fffbeb; border-left: 5px solid #ffc107; font-weight: bold; }
        .table-peserta th { background-color: #f8f9fa; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; color: #495057; }
        
        .img-preview { 
            width: 45px; 
            height: 45px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 2px solid #e9ecef; 
            cursor: pointer; 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .img-preview:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .badge-count { font-size: 0.9rem; }
        .chart-container { position: relative; height: 420px; width: 100%; }
        .modal-body img { max-height: 75vh; object-fit: contain; }
        .box-resume-cabang { background: #fff; border-radius: 10px; border: 1px solid #dee2e6; padding: 12px; height: 100%; }
        .subgrup-title { background-color: #f8f9fa; font-weight: bold; color: #495057; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="hero-section text-center shadow-sm">
    <div class="container">
        <h2 class="fw-bold"><i class="bi bi-trophy"></i> Rekapitulasi Peserta Milad XV</h2>
        <p class="mb-0 text-white-50">Daftar Resmi Peserta Terdaftar Berdasarkan Cabang Lomba</p>
    </div>
</div>

<div class="container mb-5">

    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-3x3-gap-fill text-primary"></i> Resume Kuota per Cabang Lomba</h5>
    <div class="row g-3 mb-4">
        <?php foreach($resume_cabang_data as $cabang): ?>
            <div class="col-md-3 col-sm-6">
                <div class="box-resume-cabang shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size:0.75rem;">Cabang Lomba</small>
                        <span class="fw-bold text-dark small d-block text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($cabang['nama_kategori']) ?>">
                            <?= htmlspecialchars($cabang['nama_kategori']) ?>
                        </span>
                    </div>
                    <div class="text-end">
                        <span class="fs-4 fw-bold text-primary"><?= $cabang['jumlah_peserta'] ?></span>
                        <small class="text-muted d-block" style="font-size:0.7rem;">Peserta</small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if(!empty($chart_values)): ?>
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i> Analisis Kontribusi Majelis Taklim</h5>
            <span class="badge bg-success p-2 fs-6 shadow-sm"><i class="bi bi-people-fill"></i> Total Fisik: <?= $total_riil_peserta ?> Orang (Riil)</span>
        </div>
        <div class="card-body p-4">
            <div class="chart-container">
                <canvas id="mtChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-8 col-sm-7">
                    <select name="kategori_id" class="form-select">
                        <option value="">-- Tampilkan Semua Cabang Lomba --</option>
                        <?php foreach($list_kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= $filter_kategori == $kat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-sm-5 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-filter"></i> Filter</button>
                    <?php if(!empty($filter_kategori)): ?>
                        <a href="rekap_peserta.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($grouped_data)): ?>
        <div class="card shadow-sm border-0 rounded-3 text-center py-5">
            <div class="card-body">
                <i class="bi bi-folder-x text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="mt-3 text-secondary">Tidak ada data peserta ditemukan</h5>
                <p class="text-muted small mb-0">Silakan pilih cabang lomba lain atau pastikan pendaftaran telah diisi.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($grouped_data as $nama_lomba => $pesertas): 
            $is_cc = ($pesertas[0]['kategori_id'] == $cc_id);
        ?>
            <div class="card shadow-sm border-0 rounded-3 mb-4 overflow-hidden">
                <div class="card-header <?= $is_cc ? 'cc-header' : 'lomba-header' ?> p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 <?= $is_cc ? 'text-warning-emphasis' : 'text-primary' ?> fw-bold">
                        <i class="bi <?= $is_cc ? 'bi-boxes text-warning' : 'bi-bookmark-star-fill text-warning' ?> me-1"></i> <?= htmlspecialchars($nama_lomba) ?>
                    </h5>
                    <span class="badge <?= $is_cc ? 'bg-warning text-dark' : 'bg-primary' ?> rounded-pill badge-count">
                        <?= count($pesertas) ?> Pendaftar
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-peserta">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 70px;">No</th>
                                <th style="width: 80px;">Foto</th>
                                <th>Asal Kafilah / Majelis</th>
                                <th>Nama Lengkap Peserta</th>
                                <?php if($is_cc): ?>
                                    <th class="text-center">Grup Kelompok</th>
                                <?php endif; ?>
                                <th class="text-center">Kontak HP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $current_grup = null;
                            
                            foreach ($pesertas as $p): 
                                // Jika mode Cerdas Cermat, buat separator baris penanda grup baru
                                if ($is_cc && $current_grup !== $p['grup_cc']): 
                                    $current_grup = $p['grup_cc'];
                            ?>
                                    <tr class="subgrup-title table-warning table-opacity-25">
                                        <td colspan="6" class="py-2 px-3 fw-bold text-dark">
                                            <i class="bi bi-collection-fill text-warning me-1"></i> GROUP / KELOMPOK <?= $current_grup ?: '-' ?>
                                        </td>
                                    </tr>
                            <?php 
                                endif; 
                                $foto_src = $p['foto'] ? 'uploads/'.$p['foto'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                            ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $no++ ?></td>
                                    <td>
                                        <img src="<?= $foto_src ?>" 
                                             class="img-preview shadow-sm btn-zoom-foto" 
                                             alt="Foto"
                                             data-nama="<?= htmlspecialchars($p['nama_peserta']) ?>">
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border p-2 fw-bold"><i class="bi bi-house-door text-primary me-1"></i> <?= htmlspecialchars($p['nama_mt']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($p['nama_peserta']) ?></span>
                                    </td>
                                    <?php if($is_cc): ?>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark fw-bold px-3 py-1">Grup <?= htmlspecialchars($p['grup_cc']) ?></span>
                                        </td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <?php if (!empty($p['no_hp'])): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_hp']) ?>" target="_blank" class="btn btn-sm btn-light text-success border">
                                                <i class="bi bi-whatsapp"></i> <span class="d-none d-md-inline small ms-1"><?= htmlspecialchars($p['no_hp']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- MODAL ZOOM FOTO -->
<div class="modal fade" id="zoomFotoModal" tabindex="-1" aria-labelledby="zoomFotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="card shadow-lg border-0 overflow-hidden w-100 bg-white" style="border-radius: 15px;">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 border-0">
                <h6 class="modal-title fw-bold text-dark mb-0" id="zoomFotoModalLabel">Foto Peserta</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-dark p-0 d-flex align-items-center justify-content-center" style="min-height: 300px;">
                <img src="" id="modalImgTarget" class="img-fluid w-100" alt="Zoom Foto">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- FITUR ZOOM FOTO INTERAKTIF ---
    const modalZoom = new bootstrap.Modal(document.getElementById('zoomFotoModal'));
    const modalImgTarget = document.getElementById('modalImgTarget');
    const modalTitleTarget = document.getElementById('zoomFotoModalLabel');

    document.querySelectorAll('.btn-zoom-foto').forEach(img => {
        img.addEventListener('click', function() {
            const srcFoto = this.getAttribute('src');
            const namaPeserta = this.getAttribute('data-nama');
            
            modalImgTarget.setAttribute('src', srcFoto);
            modalTitleTarget.textContent = "Foto: " + namaPeserta;
            modalZoom.show();
        });
    });

    // --- INITIALISASI CHART.JS ---
    <?php if(!empty($chart_values)): ?>
    Chart.register(ChartDataLabels);

    const ctx = document.getElementById('mtChart').getContext('2d');
    const labelsMT = <?= json_encode($chart_labels) ?>;
    const dataJumlah = <?= json_encode($chart_values) ?>;
    const dataCabang = <?= json_encode($chart_cabang) ?>;
    const totalGlobal = <?= $total_riil_peserta ?>;

    const kustomWarna = [
        'rgba(255, 99, 132, 0.85)', 'rgba(54, 162, 235, 0.85)', 'rgba(255, 206, 86, 0.85)',
        'rgba(75, 192, 192, 0.85)', 'rgba(153, 102, 255, 0.85)', 'rgba(255, 159, 64, 0.85)',
        'rgba(46, 204, 113, 0.85)', 'rgba(155, 89, 182, 0.85)', 'rgba(22, 160, 133, 0.85)',
        'rgba(211, 84, 0, 0.85)'
    ];

    const backgroundColors = labelsMT.map((_, index) => kustomWarna[index % kustomWarna.length]);
    const borderColors = backgroundColors.map(color => color.replace('0.85', '1'));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsMT,
            datasets: [{
                data: dataJumlah,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.55
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    textAlign: 'center',
                    font: { size: 10 },
                    color: '#495057',
                    formatter: function(value, context) {
                        const idx = context.dataIndex;
                        const cabang = dataCabang[idx];
                        let persentase = 0;
                        if (totalGlobal > 0) {
                            persentase = Math.round((value / totalGlobal) * 100);
                        }
                        return [
                            '👤 ' + value + ' slot',
                            '📊 ' + persentase + '%',
                            '🏆 ' + cabang + ' Cabang'
                        ];
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 25,
                        minRotation: 15,
                        font: { size: 11, weight: 'bold' }
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '25%',
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            }
        }
    });
    <?php endif; ?>
</script>

</body>
</html>