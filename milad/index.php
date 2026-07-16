<?php
require_once 'config.php';

// Proteksi halaman login
if (!isset($_SESSION['login_milad'])) {
    header("Location: login.php");
    exit;
}

// Proses Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Proses Hapus Data (Delete)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?status=deleted");
    exit;
}

// Proses Tambah / Ubah Data (Create & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $jenis = $_POST['jenis'];
    $nominal = $_POST['nominal'];
    $tanggal = $_POST['tanggal'];
    $keperluan = $_POST['keperluan'];
    $sumber_dana = ($jenis === 'masuk') ? ($_POST['sumber_dana'] ?? null) : null;

    if (empty($id)) {
        $nomor_kuitansi = ($jenis === 'masuk') ? generateNoKuitansi($pdo) : null;
        $stmt = $pdo->prepare("INSERT INTO transaksi (nomor_kuitansi, jenis, sumber_dana, keperluan, nominal, tanggal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nomor_kuitansi, $jenis, $sumber_dana, $keperluan, $nominal, $tanggal]);
    } else {
        $stmt = $pdo->prepare("UPDATE transaksi SET jenis=?, sumber_dana=?, keperluan=?, nominal=?, tanggal=? WHERE id=?");
        $stmt->execute([$jenis, $sumber_dana, $keperluan, $nominal, $tanggal, $id]);
    }
    header("Location: index.php?status=success");
    exit;
}

// Parameter Pencarian & Filter
$search = $_GET['search'] ?? '';
$filter_jenis = $_GET['filter_jenis'] ?? '';

// ==========================================
// FITUR SORTING SEMUA KOLOM (FIXED)
// ==========================================
$sort_column = $_GET['sort'] ?? 'tanggal';
$sort_order = $_GET['order'] ?? 'desc';

// Validasi whitelist nama kolom agar aman dari SQL Injection
$allowed_columns = ['tanggal', 'nomor_kuitansi', 'jenis', 'sumber_dana', 'keperluan', 'nominal'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'tanggal';
}

// Atur arah urutan
$sort_order = (strtolower($sort_order) === 'asc') ? 'asc' : 'desc';
$next_order = ($sort_order === 'asc') ? 'desc' : 'asc';

// Fungsi Render Judul Kolom + Link Sortir Otomatis
function renderSortableHeader($column_name, $label, $current_sort, $current_order, $next_order, $search, $filter_jenis) {
    // Tentukan icon berdasarkan status sort aktif saat ini
    if ($current_sort === $column_name) {
        $icon = ($current_order === 'asc') ? ' <i class="bi bi-arrow-up-short text-warning"></i>' : ' <i class="bi bi-arrow-down-short text-warning"></i>';
        $target_order = $next_order; // Jika kolom sama diklik lagi, balikkan arahnya
    } else {
        $icon = ' <i class="bi bi-arrow-down-up text-muted" style="font-size: 0.75rem;"></i>';
        $target_order = 'asc'; // Jika pindah kolom, mulai dari asc dulu
    }
    
    // Bangun URL Query String dengan mempertahankan state filter/search
    $url = "?sort=" . $column_name . "&order=" . $target_order;
    if ($search !== '') $url .= "&search=" . urlencode($search);
    if ($filter_jenis !== '') $url .= "&filter_jenis=" . urlencode($filter_jenis);
    
    return '<a href="' . $url . '" class="text-white text-decoration-none d-inline-block w-100">' . $label . $icon . '</a>';
}

// Membangun query SQL data
$query = "SELECT * FROM transaksi WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (keperluan LIKE ? OR sumber_dana LIKE ? OR nomor_kuitansi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_jenis !== '') {
    $query .= " AND jenis = ?";
    $params[] = $filter_jenis;
}

// Sisipkan perintah urut kolom secara dinamis ke SQL query
$query .= " ORDER BY $sort_column $sort_order, id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Rekap Saldo Dashboard
$total_masuk = $pdo->query("SELECT SUM(nominal) FROM transaksi WHERE jenis='masuk'")->fetchColumn() ?? 0;
$total_keluar = $pdo->query("SELECT SUM(nominal) FROM transaksi WHERE jenis='keluar'")->fetchColumn() ?? 0;
$saldo_akhir = $total_masuk - $total_keluar;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="manifest" href="manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Milad">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Keuangan - Gebyar Milad XV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .table-sortable th { padding: 0 !important; }
        .table-sortable th a { display: block; padding: 12px 8px; width: 100%; height: 100%; transition: background 0.2s; }
        .table-sortable th a:hover { background-color: #2c3034 !important; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Gebyar Milad MT.Muallaf Taufiqiyah XV</a>
        <div class="d-flex gap-2">
            <a href="peserta.php" class="btn btn-sm btn-outline-light"><i class="bi bi-people-fill"></i> Data Peserta & Panitia</a>
<a href="report.php" target="_blank" class="btn btn-dark">
    <i class="bi bi-printer"></i> Cetak Laporan
</a>
            <a href="?logout=true" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-right"></i> Keluar</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <!-- Info Alamat -->
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-geo-alt-fill fs-3 me-3"></i>
        <div>
            <strong>Sekretariat Panitia:</strong> Jl Tukad Ayung No 2 Kediri Tabanan | <i class="bi bi-telephone-fill"></i> Telp: 081371578332
        </div>
    </div>

    <!-- Ringkasan Kas Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4">
                <small class="text-muted text-uppercase fw-bold">Total Uang Masuk</small>
                <h3 class="text-success fw-bold mt-1">Rp <?= number_format($total_masuk, 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-danger border-4">
                <small class="text-muted text-uppercase fw-bold">Total Uang Keluar</small>
                <h3 class="text-danger fw-bold mt-1">Rp <?= number_format($total_keluar, 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4">
                <small class="text-muted text-uppercase fw-bold">Saldo Akhir Sisa Kas</small>
                <h3 class="text-primary fw-bold mt-1">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <!-- Kontrol Form Cari & Filter -->
    <div class="card border-0 shadow-sm p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari Keperluan/Sumber Dana/No Kwt..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="filter_jenis" class="form-select">
                    <option value="">-- Semua Jenis Alur Kas --</option>
                    <option value="masuk" <?= $filter_jenis == 'masuk' ? 'selected' : '' ?>>Uang Masuk</option>
                    <option value="keluar" <?= $filter_jenis == 'keluar' ? 'selected' : '' ?>>Uang Keluar</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 d-grid">
                <button type="button" class="btn btn-success" onclick="openModalTambah()"><i class="bi bi-plus-circle"></i> Tambah Transaksi</button>
            </div>
        </form>
    </div>

    <!-- TABEL DATA TRANSAKSI -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sortable mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3" style="min-width: 130px;">
                            <?= renderSortableHeader('tanggal', 'Tanggal', $sort_column, $sort_order, $next_order, $search, $filter_jenis) ?>
                        </th>
                        <th style="min-width: 150px;">
                            <?= renderSortableHeader('nomor_kuitansi', 'No. Kuitansi', $sort_column, $sort_order, $next_order, $search, $filter_jenis) ?>
                        </th>
                        <th style="min-width: 110px;">
                            <?= renderSortableHeader('jenis', 'Jenis', $sort_column, $sort_order, $next_order, $search, $filter_jenis) ?>
                        </th>
                        <th style="min-width: 160px;">
                            <?= renderSortableHeader('sumber_dana', 'Sumber Dana', $sort_column, $sort_order, $next_order, $search, $filter_jenis) ?>
                        </th>
                        <th>
                            <?= renderSortableHeader('keperluan', 'Keterangan / Keperluan', $sort_column, $sort_order, $next_order, $search, $filter_jenis) ?>
                        </th>
                        <th class="text-end" style="min-width: 140px;">
                            <?= renderSortableHeader('nominal', 'Nominal', $sort_column, $sort_order, $next_order, $search, $filter_jenis) ?>
                        </th>
                        <!-- Kolom Aksi Tetap Statis Normal (Tidak Bisa Disortir) -->
                        <th class="text-center py-3" style="width: 140px; color: #fff; padding-left: 8px !important; font-weight: 500;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data) == 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data transaksi yang ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td class="ps-3"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                            <td><span class="badge bg-secondary"><?= $row['nomor_kuitansi'] ?: '-' ?></span></td>
                            <td>
                                <span class="badge bg-<?= $row['jenis'] == 'masuk' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($row['jenis']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['jenis'] == 'masuk'): ?>
                                    <strong><?= htmlspecialchars($row['sumber_dana']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['keperluan']) ?></td>
                            <td class="text-end fw-bold text-<?= $row['jenis'] == 'masuk' ? 'success' : 'danger' ?>">
                                 <?= $row['jenis'] == 'masuk' ?> Rp <?= number_format($row['nominal'], 0, ',', '.') ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['jenis'] == 'masuk'): ?>
                                    <a href="print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak Kuitansi"><i class="bi bi-printer"></i></a>
                                <?php endif; ?>
                                <button
                                class="btn btn-sm btn-outline-primary"
                                onclick='openModalEdit(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>                                
                                <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Dialog Transaksi -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formTransaksi" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="formId">
                <input type="hidden" name="jenis" id="formJenisHidden" disabled>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Arus Uang</label>
                    <select name="jenis" id="formJenis" class="form-select" onchange="toggleFormInputs()" required>
                        <option value="masuk">Uang Masuk</option>
                        <option value="keluar">Uang Keluar</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tanggal</label>
                        <input type="date" name="tanggal" id="formTanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nominal (Rp)</label>
                        <input type="number" name="nominal" id="formNominal" class="form-control" placeholder="Contoh: 500000" required>
                    </div>
                </div>

                <div id="sectionUangMasuk">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sumber Dana / Diterima Dari</label>
                        <input type="text" name="sumber_dana" id="formSumber" class="form-control" placeholder="Contoh: Donatur H. Ahmad, Kas Majelis">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" id="labelKeperluan">Keperluan / Keterangan</label>
                    <textarea name="keperluan" id="formKeperluan" class="form-control" rows="3" placeholder="Deskripsi transaksi..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan Data</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pastikan modal diinisialisasi setelah DOM sepenuhnya siap
    let modalElement;
    document.addEventListener("DOMContentLoaded", function() {
        modalElement = new bootstrap.Modal(document.getElementById('modalTransaksi'));
    });

    function toggleFormInputs() {
        const jenis = document.getElementById('formJenis').value;
        const sectionMasuk = document.getElementById('sectionUangMasuk');
        const labelKeperluan = document.getElementById('labelKeperluan');

        if (jenis === 'masuk') {
            sectionMasuk.style.display = 'block';
            labelKeperluan.innerText = "Untuk Pembayaran / Keperluan";
            document.getElementById('formSumber').required = true;
        } else {
            sectionMasuk.style.display = 'none';
            labelKeperluan.innerText = "Keterangan Pengeluaran Alokasi";
            document.getElementById('formSumber').required = false;
        }
    }

    function openModalTambah() {
        // Reset form secara menyeluruh
        document.getElementById('formTransaksi').reset();
        document.getElementById('formId').value = '';
        document.getElementById('formJenisHidden').disabled = true;
        document.getElementById('formJenis').disabled = false;
        document.getElementById('modalTitle').innerText = 'Tambah Data Transaksi';
        
        // Atur ulang tanggal default ke hari ini
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('formTanggal').value = today;

        toggleFormInputs();
        
        // Proteksi jika modalElement belum terisi saat tombol diklik cepat
        if(!modalElement) {
            modalElement = new bootstrap.Modal(document.getElementById('modalTransaksi'));
        }
        modalElement.show();
    }

    function openModalEdit(data) {
        document.getElementById('formId').value = data.id;
        document.getElementById('formJenis').value = data.jenis;
        
        document.getElementById('formJenisHidden').value = data.jenis;
        document.getElementById('formJenisHidden').disabled = false;
        
        document.getElementById('formTanggal').value = data.tanggal;
        document.getElementById('formNominal').value = parseInt(data.nominal);
        document.getElementById('formSumber').value = data.sumber_dana || '';
        document.getElementById('formKeperluan').value = data.keperluan;
        
        document.getElementById('modalTitle').innerText = 'Ubah Data Transaksi';
        document.getElementById('formJenis').disabled = true;
        
        toggleFormInputs();

        if(!modalElement) {
            modalElement = new bootstrap.Modal(document.getElementById('modalTransaksi'));
        }
        modalElement.show();
    }
</script>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('Service Worker terdaftar dengan aman!', reg))
        .catch(err => console.error('Gagal mendaftarkan Service Worker:', err));
    });
  }
</script>
</body>
</html>