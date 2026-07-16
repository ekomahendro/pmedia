<?php
require_once '../../config.php';
check_login();

$id_license = $_SESSION['id_license'];
$msg = ''; $msg_type = 'success';

// 1. TANGKAP ARRANGEMENT YANG SEDANG DIPILIH
$target_arr = isset($_GET['target_arr']) ? trim($_GET['target_arr']) : '';

if (empty($target_arr)) {
    $q_default = mysqli_query($conn, "SELECT arr_code FROM htl_arrangements ORDER BY arr_code ASC LIMIT 1");
    if ($row_def = mysqli_fetch_assoc($q_default)) {
        $target_arr = $row_def['arr_code'];
    }
}

// =========================================================================
// 2. HANDLE SIMPAN DATA (TAMBAH & EDIT MODAL)
// =========================================================================
if (isset($_POST['save_breakdown'])) {
    $arr_code      = $_POST['arr_code'];
    $id_article    = intval($_POST['id_article']);
    $value_type    = $_POST['value_type'];
    $amount        = floatval($_POST['amount']);
    $post_type     = $_POST['post_type'];
    $qty_rule      = $_POST['qty_rule']; 
    $specific_date = !empty($_POST['specific_date']) ? $_POST['specific_date'] : NULL;
    $is_edit       = intval($_POST['is_edit']);
    $id_breakdown  = intval($_POST['id_breakdown']);

    if ($is_edit === 1) {
        // Mode Update data yang sudah ada
        $stmt = mysqli_prepare($conn, "UPDATE htl_arrangement_articles SET id_article = ?, value_type = ?, amount = ?, post_type = ?, qty_rule = ?, specific_date = ? WHERE id_breakdown = ? AND id_license = ?");
        mysqli_stmt_bind_param($stmt, "isdsssii", $id_article, $value_type, $amount, $post_type, $qty_rule, $specific_date, $id_breakdown, $id_license);
    } else {
        // Mode Insert data baru
        $stmt = mysqli_prepare($conn, "INSERT INTO htl_arrangement_articles (id_license, arr_code, id_article, value_type, amount, post_type, qty_rule, specific_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isisdsss", $id_license, $arr_code, $id_article, $value_type, $amount, $post_type, $qty_rule, $specific_date);
    }

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Komponen breakdown revenue berhasil disimpan!";
        $target_arr = $arr_code;
    }
}

// Handle hapus breakdown
if (isset($_GET['delete_id'])) {
    $id_del = intval($_GET['delete_id']);
    
    $q_find = mysqli_query($conn, "SELECT arr_code FROM htl_arrangement_articles WHERE id_breakdown = $id_del AND id_license = $id_license");
    if($f = mysqli_fetch_assoc($q_find)) { $target_arr = $f['arr_code']; }
    
    mysqli_query($conn, "DELETE FROM htl_arrangement_articles WHERE id_breakdown = $id_del AND id_license = $id_license");
    $msg = "Komponen breakdown berhasil dihapus."; $msg_type = "warning";
}

// Ambil data komponen dropdown
$arr_opts = mysqli_query($conn, "SELECT * FROM htl_arrangements ORDER BY arr_code ASC");
$art_opts = mysqli_query($conn, "SELECT * FROM htl_articles WHERE id_license = $id_license ORDER BY article_name ASC");

// Ambil list breakdown berdasarkan paket yang dipilih
$target_arr_safe = mysqli_real_escape_string($conn, $target_arr);
$q_list = "SELECT b.*, a.arr_name, art.article_name, art.article_code 
           FROM htl_arrangement_articles b
           JOIN htl_arrangements a ON b.arr_code = a.arr_code
           JOIN htl_articles art ON b.id_article = art.id_article
           WHERE b.id_license = $id_license AND b.arr_code = '$target_arr_safe'
           ORDER BY art.article_code ASC";
$res_breakdown = mysqli_query($conn, $q_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Setup Revenue Breakdown Arrangement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="bi bi-sliders text-primary me-2"></i>Konfigurasi Revenue Breakdown Arrangement</h4>
            <p class="text-muted small mb-0">Tentukan bagaimana harga reservasi dipecah ke masing-masing departemen pendapatan berdasarkan waktu posting.</p>
        </div>
        <div>
            <a href="setup_article.php" class="btn btn-sm btn-primary rounded-pill px-3 me-2"><i class="bi bi-plus-circle"></i> Tambah Master Artikel</a>
            <a href="setup_master.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show" role="alert">
            <?= $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold mb-3 small text-uppercase text-secondary">Tambah Aturan Artikel</h5>
                <form action="setup_arrangement.php?target_arr=<?= urlencode($target_arr); ?>" method="POST" class="row g-3">
                    <input type="hidden" name="is_edit" value="0">
                    <input type="hidden" name="id_breakdown" value="0">
                    <input type="hidden" name="arr_code" value="<?= htmlspecialchars($target_arr); ?>">
                    
                    <div class="col-12">
                        <label class="form-label small fw-bold">Arrancement Paket Terpilih</label>
                        <select class="form-select form-select-sm fw-bold text-primary" onchange="window.location.href='setup_arrangement.php?target_arr=' + this.value">
                            <?php mysqli_data_seek($arr_opts, 0); ?>
                            <?php while($ar = mysqli_fetch_assoc($arr_opts)): ?>
                                <option value="<?= $ar['arr_code']; ?>" <?= ($ar['arr_code'] == $target_arr) ? 'selected' : ''; ?>>
                                    <?= $ar['arr_code']; ?> - <?= $ar['arr_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Artikel / Alokasi Revenue</label>
                        <select name="id_article" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Artikel --</option>
                            <?php mysqli_data_seek($art_opts, 0); ?>
                            <?php while($art = mysqli_fetch_assoc($art_opts)): ?>
                                <option value="<?= $art['id_article']; ?>">[<?= $art['article_code']; ?>] <?= $art['article_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tipe Nilai</label>
                        <select name="value_type" class="form-select form-select-sm" required>
                            <option value="fixed">Nominal (Rp)</option>
                            <option value="percentage">Persentase (%)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nilai / Angka</label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Waktu Posting (*Timing*)</label>
                        <select name="post_type" class="form-select form-select-sm post_type_trigger" required>
                            <option value="daily">Daily (Setiap Malam Audit)</option>
                            <option value="checkin">Hanya Diawal Check-In</option>
                            <option value="checkout">Hanya Saat Check-Out</option>
                            <option value="specific_date">Pada Tanggal Tertentu</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Aturan Multiplier Kuantitas</label>
                        <select name="qty_rule" class="form-select form-select-sm" required>
                            <option value="always_1">Selalu Hitung 1x (Flat per Kamar)</option>
                            <option value="per_guest">Kalikan dengan Jumlah Pax / Total Tamu Kamar</option>
                        </select>
                    </div>

                    <div class="col-12 d-none specific_date_container">
                        <label class="form-label small fw-bold">Pilih Tanggal Spesifik</label>
                        <input type="date" name="specific_date" class="form-control form-control-sm">
                    </div>

                    <div class="col-12 pt-2">
                        <button type="submit" name="save_breakdown" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold">Simpan Aturan Alokasi</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold mb-3 small text-uppercase text-secondary">
                    Daftar Breakdown Mapping Paket: <span class="text-primary"><?= htmlspecialchars($target_arr); ?></span>
                </h5>
                <table class="table table-sm table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Artikel Revenue</th>
                            <th>Alokasi Nilai</th>
                            <th>Aturan Schedular</th>
                            <th>Qty Rule</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_breakdown) == 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada aturan pecahan artikel untuk paket <strong><?= htmlspecialchars($target_arr); ?></strong>.</td></tr>
                        <?php endif; ?>
                        <?php while($b = mysqli_fetch_assoc($res_breakdown)): ?>
                            <tr>
                                <td><strong>[<?= $b['article_code']; ?>]</strong> <?= $b['article_name']; ?></td>
                                <td>
                                    <?= $b['value_type'] == 'percentage' ? $b['amount'].' %' : 'Rp '.number_format($b['amount'], 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <?php if($b['post_type'] == 'daily'): ?>
                                        <span class="badge bg-primary">Daily Post</span>
                                    <?php elseif($b['post_type'] == 'checkin'): ?>
                                        <span class="badge bg-success">On Check-In</span>
                                    <?php elseif($b['post_type'] == 'checkout'): ?>
                                        <span class="badge bg-danger">On Check-Out</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Tgl: <?= date('d/m/Y', strtotime($b['specific_date'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $b['qty_rule'] == 'always_1' ? '1x Flat' : 'x Qty Guest'; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm text-dark p-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($b)); ?>)"><i class="bi bi-pencil-square"></i></button>
                                    <a href="setup_arrangement.php?delete_id=<?= $b['id_breakdown']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus aturan breakdown ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditBreakdown" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white">
                <h6 class="modal-title fw-bold">Edit Breakdown Rule</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="setup_arrangement.php?target_arr=<?= urlencode($target_arr); ?>" method="POST">
                <input type="hidden" name="is_edit" value="1">
                <input type="hidden" name="id_breakdown" id="edit_id_breakdown">
                <input type="hidden" name="arr_code" id="edit_arr_code">
                
                <div class="modal-body p-3 row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Artikel Revenue</label>
                        <select name="id_article" id="edit_id_article" class="form-select form-select-sm" required>
                            <?php mysqli_data_seek($art_opts, 0); ?>
                            <?php while($art = mysqli_fetch_assoc($art_opts)): ?>
                                <option value="<?= $art['id_article']; ?>">[<?= $art['article_code']; ?>] <?= $art['article_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-0 fw-bold">Tipe Nilai</label>
                        <select name="value_type" id="edit_value_type" class="form-select form-select-sm" required>
                            <option value="fixed">Nominal (Rp)</option>
                            <option value="percentage">Persentase (%)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-0 fw-bold">Nilai</label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Waktu Posting</label>
                        <select name="post_type" id="edit_post_type" class="form-select form-select-sm post_type_trigger" required>
                            <option value="daily">Daily</option>
                            <option value="checkin">Hanya Diawal Check-In</option>
                            <option value="checkout">Hanya Saat Check-Out</option>
                            <option value="specific_date">Pada Tanggal Tertentu</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0 fw-bold">Aturan Multiplier</label>
                        <select name="qty_rule" id="edit_qty_rule" class="form-select form-select-sm" required>
                            <option value="always_1">Selalu Hitung 1x (Flat)</option>
                            <option value="per_guest">Kalikan dengan Qty Guest</option>
                        </select>
                    </div>
                    <div class="col-12 d-none specific_date_container" id="edit_specific_date_row">
                        <label class="form-label small mb-0 fw-bold">Tanggal Spesifik</label>
                        <input type="date" name="specific_date" id="edit_specific_date" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer py-1 bg-light">
                    <button type="button" class="btn btn-xs btn-secondary small px-2 py-1" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="save_breakdown" class="btn btn-xs btn-success small px-3 py-1">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Logic Menampilkan kolom tanggal spesifik untuk form utama & form modal edit
document.querySelectorAll('.post_type_trigger').forEach(selectElement => {
    selectElement.addEventListener('change', function() {
        const parentForm = this.closest('form');
        const dateContainer = parentForm.querySelector('.specific_date_container');
        if (this.value === 'specific_date') {
            dateContainer.classList.remove('d-none');
        } else {
            dateContainer.classList.add('d-none');
        }
    });
});

// Instance modal edit
const editModal = new bootstrap.Modal(document.getElementById('modalEditBreakdown'));

// Fungsi mempassing data baris tabel ke elemen input modal edit
function openEditModal(data) {
    document.getElementById('edit_id_breakdown').value = data.id_breakdown;
    document.getElementById('edit_arr_code').value = data.arr_code;
    document.getElementById('edit_id_article').value = data.id_article;
    document.getElementById('edit_value_type').value = data.value_type;
    document.getElementById('edit_amount').value = data.amount;
    document.getElementById('edit_post_type').value = data.post_type;
    document.getElementById('edit_qty_rule').value = data.qty_rule;
    
    const dateRow = document.getElementById('edit_specific_date_row');
    if (data.post_type === 'specific_date') {
        dateRow.classList.remove('d-none');
        document.getElementById('edit_specific_date').value = data.specific_date;
    } else {
        dateRow.classList.add('d-none');
        document.getElementById('edit_specific_date').value = '';
    }
    
    editModal.show();
}
</script>
</body>
</html>