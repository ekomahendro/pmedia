<?php
require_once '../../config.php';
check_login();

$user_id = $_SESSION['user_id'];

// =========================================================================
// 1. VALIDASI LEVEL AKSES USER (BERDASARKAN TABEL htl_user_access)
// =========================================================================
$access_query = mysqli_query($conn, "SELECT level FROM htl_user_access WHERE id_user = $user_id AND id_module = 10 LIMIT 1");
if (mysqli_num_rows($access_query) == 0) {
    echo "<script>alert('Akses Ditolak! Anda tidak memiliki hak akses untuk modul Purchase Request (PR).'); window.location.href='index.php';</script>";
    exit();
}
$access_data = mysqli_fetch_assoc($access_query);
$user_level  = intval($access_data['level']); // Level 1 = Dept Sendiri, Level 2 = Semua Dept

// Tarik data profil & departemen user login
$me_query = mysqli_query($conn, "SELECT u.fullname, u.id_department, d.dept_code, d.dept_name 
                                 FROM htl_users u 
                                 LEFT JOIN htl_departments d ON u.id_department = d.id_department 
                                 WHERE u.id_user = $user_id");
$me = mysqli_fetch_assoc($me_query);
$my_dept_id = intval($me['id_department']);

// =========================================================================
// 2. PROSES BACK-END ACTION (CRUD, ITEM MANAGEMENT, APPROVAL)
// =========================================================================
$msg = ''; $msg_type = 'success';

// Fungsi bantu untuk memeriksa apakah PR sudah dikunci oleh Cost Control
function is_pr_locked($id_pr, $conn) {
    $check = mysqli_query($conn, "SELECT status_cc FROM htl_pur_pr WHERE id_pr = $id_pr");
    $data = mysqli_fetch_assoc($check);
    return ($data && strtolower(trim($data['status_cc'])) === 'approved');
}

// A. TAMBAH HEADER PR BARU
if (isset($_POST['add_pr'])) {
    $pr_number   = 'PR-' . date('Ymd') . '-' . rand(100, 999);
    $dept_id = ($user_level === 2 && !empty($_POST['target_dept'])) ? intval($_POST['target_dept']) : $my_dept_id;
    $date    = mysqli_real_escape_string($conn, $_POST['pr_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $q = mysqli_query($conn, "INSERT INTO htl_pur_pr (pr_number, id_department, pr_date, description, status_approval, status_cc) 
                         VALUES ('$pr_number', $dept_id, '$date', '$description', 'pending', 'pending')");
    if ($q) { $msg = "Header PR #$pr_number Berhasil Dibuat! Silakan isi item barang."; }
}

// B. EDIT HEADER PR
if (isset($_POST['edit_pr'])) {
    $id_pr = intval($_POST['id_pr']);
    if (is_pr_locked($id_pr, $conn)) {
        $msg = "Gagal! Dokumen sudah di-approve oleh Cost Control dan terkunci."; $msg_type = "danger";
    } else {
        $date = mysqli_real_escape_string($conn, $_POST['pr_date']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        mysqli_query($conn, "UPDATE htl_pur_pr SET pr_date='$date', description='$description' WHERE id_pr=$id_pr");
        $msg = "Perubahan informasi PR berhasil disimpan.";
    }
}

// C. HAPUS AKTA PR (CASCADE ITEMS)
if (isset($_GET['delete_pr'])) {
    $id_pr = intval($_GET['delete_pr']);
    if (is_pr_locked($id_pr, $conn)) {
        $msg = "Gagal! Dokumen terikat approval Cost Control tidak boleh dihapus."; $msg_type = "danger";
    } else {
        mysqli_query($conn, "DELETE FROM htl_pur_pr WHERE id_pr=$id_pr");
        mysqli_query($conn, "DELETE FROM htl_pur_pr_detail WHERE id_pr=$id_pr");
        $msg = "Dokumen PR dan seluruh item di dalamnya telah dibersihkan.";
    }
}

// D. CRUD TAMBAH ITEM KE DALAM PR
if (isset($_POST['add_item_to_pr'])) {
    $id_pr     = intval($_POST['id_pr']);
    if (is_pr_locked($id_pr, $conn)) {
        $msg = "Gagal! Item tidak dapat ditambah karena PR telah dikunci oleh Cost Control."; $msg_type = "danger";
    } else {
        $id_item   = intval($_POST['id_item']);
        $qty_requested       = intval($_POST['qty_requested']);
        $est_price = floatval($_POST['estimate_price']);
        
        mysqli_query($conn, "INSERT INTO htl_pur_pr_detail (id_pr, id_item, qty_requested, estimate_price) VALUES ($id_pr, $id_item, $qty_requested, $est_price)");
        $msg = "Item berhasil ditambahkan ke dalam PR.";
    }
}

// E. CRUD HAPUS ITEM DARI PR
if (isset($_GET['delete_item'])) {
    $id_sub = intval($_GET['delete_item']);
    $id_pr  = intval($_GET['pr_ref']);
    if (is_pr_locked($id_pr, $conn)) {
        $msg = "Gagal! Item tidak boleh dihapus karena PR telah dikunci."; $msg_type = "danger";
    } else {
        mysqli_query($conn, "DELETE FROM htl_pur_pr_detail WHERE id_sub_pr=$id_sub");
        $msg = "Item berhasil dikeluarkan dari daftar PR.";
    }
}

// F. PROSES APPROVAL (DEPT HEAD / COST CONTROL)
if (isset($_GET['approve'])) {
    $id_pr = intval($_GET['approve']);
    $layer = $_GET['layer']; // 'dept' atau 'cc'
    
    if ($layer === 'dept') {
        mysqli_query($conn, "UPDATE htl_pur_pr SET status_approval='approved', app_dept_user_id=$user_id WHERE id_pr=$id_pr");
        $msg = "PR berhasil di-approve oleh Kepala Departemen.";
    } elseif ($layer === 'cc') {
        mysqli_query($conn, "UPDATE htl_pur_pr SET status_cc='approved', app_cc_user_id=$user_id WHERE id_pr=$id_pr");
        $msg = "PR dinyatakan VALID dan DI-LOCK oleh Cost Control.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Request (PR) Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .badge-status { font-size: 0.75rem; padding: 5px 10px; border-radius: 50px; }
        @media print { .no-print { display: none !important; } .print-only { display: block !important; } }
        .print-only { display: none; }
    </style>
</head>
<body>

<iframe id="print_frame" name="print_frame" style="display:none;"></iframe>

<div class="container mt-4 no-print">
    <div class="card card-body border-0 shadow-sm mb-3 py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="index.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Modul Utama</a>
                <span class="ms-2 fw-bold text-muted">Akses Level: <span class="badge bg-dark">Level <?= $user_level; ?></span></span>
            </div>
            <span class="small text-muted font-monospace">Operator: <strong><?= $me['fullname']; ?></strong> [<?= $me['dept_code']; ?>]</span>
        </div>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show small py-2" role="alert">
            <?= $msg; ?><button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card card-body border-0 shadow-sm mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-4">
                <h3 class="fw-bold m-0 text-dark"><i class="bi bi-file-earmark-text text-primary"></i> Purchase Request</h3>
                <small class="text-muted">Lembar Pengajuan Permintaan Logistik Barang Properti</small>
            </div>
            <div class="col-md-8 text-md-end">
                <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="collapse" data-bs-target="#boxAddPR"><i class="bi bi-plus-lg"></i> Buat Form PR Baru</button>
                <button class="btn btn-sm btn-outline-dark fw-bold ms-1" data-bs-toggle="collapse" data-bs-target="#boxPrintDate"><i class="bi bi-printer"></i> Print List Per Tanggal</button>
            </div>
        </div>

        <div class="collapse mt-3" id="boxPrintDate">
            <div class="p-3 bg-light rounded border">
                <h6 class="fw-bold mb-2">Cetak Rekap Laporan PR Berdasarkan Tanggal</h6>
                <form action="pr_print_list.php" method="GET" target="print_frame" onsubmit="setTimeout(function(){ window.frames['print_frame'].print(); }, 1000);">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4"><label class="small fw-bold">Dari Tanggal</label><input type="date" name="start_date" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><label class="small fw-bold">Sampai Tanggal</label><input type="date" name="end_date" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><button type="submit" class="btn btn-sm btn-dark w-100 fw-bold"><i class="bi bi-print"></i> Proses Ekskusi Cetak</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="collapse mt-3" id="boxAddPR">
            <div class="p-3 bg-white rounded border border-primary">
                <h5 class="fw-bold text-primary mb-3">Pembuatan Berkas Dokumen Baru</h5>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="small fw-bold">Tanggal Pengajuan</label>
                            <input type="date" name="pr_date" value="<?= date('Y-m-d'); ?>" class="form-control form-control-sm" required>
                        </div>
                        <?php if($user_level === 2): ?>
                        <div class="col-md-3">
                            <label class="small fw-bold">Tujukan Untuk Departemen</label>
                            <select name="target_dept" class="form-select form-select-sm" required>
                                <?php 
                                $depts = mysqli_query($conn, "SELECT * FROM htl_departments ORDER BY dept_name ASC");
                                while($d = mysqli_fetch_assoc($depts)) {
                                    echo "<option value='".$d['id_department']."'>[".$d['dept_code']."] ".$d['dept_name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="small fw-bold">Keperluan / Alasan Pengadaan</label>
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Contoh: Pengadaan bahan buffet breakfast / Buffer stock koridor" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="add_pr" class="btn btn-sm btn-success px-4 fw-bold">Generate Nomor PR</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <form method="GET" class="row mt-4 g-2">
            <?php if ($user_level === 2): ?>
            <div class="col-md-4">
                <select name="filter_dept" class="form-select form-select-sm">
                    <option value="">-- Semua Departemen (Filter Level 2) --</option>
                    <?php 
                    $depts_filter = mysqli_query($conn, "SELECT * FROM htl_departments ORDER BY dept_name ASC");
                    while($d = mysqli_fetch_assoc($depts_filter)) {
                        $sel = (isset($_GET['filter_dept']) && $_GET['filter_dept'] == $d['id_department']) ? 'selected' : '';
                        echo "<option value='".$d['id_department']."' $sel>".$d['dept_name']."</option>";
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <input type="text" name="search" value="<?= $_GET['search'] ?? ''; ?>" class="form-control form-control-sm" placeholder="Cari berdasarkan No. PR atau keterangan...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>No PR / Tanggal</th>
                        <th>Departemen Pemohon</th>
                        <th>Keterangan Keperluan</th>
                        <th>Approval Dept Head</th>
                        <th>Approval Cost Control</th>
                        <th class="text-center">Manajemen Dokumen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where_clause = ($user_level === 1) ? "WHERE p.id_department = $my_dept_id " : "WHERE 1=1 ";
                    
                    if ($user_level === 2 && !empty($_GET['filter_dept'])) {
                        $where_clause .= " AND p.id_department = " . intval($_GET['filter_dept']);
                    }
                    if (!empty($_GET['search'])) {
                        $s = mysqli_real_escape_string($conn, $_GET['search']);
                        $where_clause .= " AND (p.pr_number LIKE '%$s%' OR p.description LIKE '%$s%') ";
                    }

                    $main_sql = "SELECT p.*, d.dept_name, d.dept_code,
                                        u1.fullname as dept_approver,
                                        u2.fullname as cc_approver
                                 FROM htl_pur_pr p
                                 JOIN htl_departments d ON p.id_department = d.id_department
                                 LEFT JOIN htl_users u1 ON p.app_dept_user_id = u1.id_user
                                 LEFT JOIN htl_users u2 ON p.app_cc_user_id = u2.id_user
                                 $where_clause ORDER BY p.id_pr DESC";
                    
                    $res = mysqli_query($conn, $main_sql);
                    while ($pr = mysqli_fetch_assoc($res)):
                        // Gunakan pengecekan yang lebih aman terhadap string kosong atau null data
                        $status_clean = isset($pr['status_cc']) ? strtolower(trim($pr['status_cc'])) : 'pending';
                        $locked = ($status_clean === 'approved');
                    ?>
                    <tr class="<?= $locked ? 'table-light text-muted' : ''; ?>">
                        <td>
                            <span class="fw-bold d-block text-primary font-monospace"><?= $pr['pr_number']; ?></span>
                            <small class="text-muted"><i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($pr['pr_date'])); ?></small>
                        </td>
                        <td><span class="fw-bold">[<?= $pr['dept_code']; ?>]</span> <?= $pr['dept_name']; ?></td>
                        <td><?= htmlspecialchars($pr['description']); ?></td>
                        <td>
                            <?php if(strtolower(trim($pr['status_approval'])) === 'approved'): ?>
                                <span class="badge bg-success-subtle text-success border rounded-pill px-2"><i class="bi bi-check-all"></i> Approved</span>
                                <small class="d-block text-muted" style="font-size:10px;">By: <?= $pr['dept_approver']; ?></small>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border rounded-pill px-2">Pending</span>
                                <?php if(!$locked): ?>
                                    <a href="pr.php?approve=<?= $pr['id_pr']; ?>&layer=dept" class="btn btn-xs btn-success py-0 px-1 font-monospace ms-1" style="font-size: 9px;" onclick="return confirm('Approve PR ini sebagai Kepala Departemen?')">Approve</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($locked): ?>
                                <span class="badge bg-danger text-white border rounded-pill px-2"><i class="bi bi-lock-fill"></i> LOCKED &amp; APV</span>
                                <small class="d-block text-muted" style="font-size:10px;">By: <?= $pr['cc_approver']; ?></small>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border rounded-pill px-2">Pending Verification</span>
                                <?php if(strtolower(trim($pr['status_approval'])) === 'approved'): ?>
                                    <a href="pr.php?approve=<?= $pr['id_pr']; ?>&layer=cc" class="btn btn-xs btn-danger py-0 px-1 font-monospace ms-1" style="font-size: 9px;" onclick="return confirm('PENTING: Menyetujui PR ini akan mengunci seluruh item di dalamnya secara permanen untuk diteruskan ke Purchasing. Lanjutkan?')">Lock &amp; Apv</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-dark py-1" data-bs-toggle="modal" data-bs-target="#modalItems<?= $pr['id_pr']; ?>"><i class="bi bi-box"></i> Items</button>
                                
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1" data-bs-toggle="modal" data-bs-target="#modalEditHeader<?= $pr['id_pr']; ?>" <?= $locked ? 'disabled' : ''; ?>><i class="bi bi-pencil"></i></button>
                                
                                <a href="pr_print_single.php?id=<?= $pr['id_pr']; ?>" target="print_frame" class="btn btn-xs btn-outline-dark py-1" onclick="setTimeout(function(){ window.frames['print_frame'].print(); }, 800);"><i class="bi bi-printer"></i></a>
                                
                                <a href="pr.php?delete_pr=<?= $pr['id_pr']; ?>" class="btn btn-xs btn-outline-danger py-1 <?= $locked ? 'disabled' : ''; ?>" onclick="return confirm('Apakah Anda yakin mau menghapus seluruh berkas dokumen PR ini?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditHeader<?= $pr['id_pr']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-secondary text-white"><h5 class="modal-title fw-bold">Ubah Informasi Header PR</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                <form method="POST">
                                    <div class="modal-body text-start">
                                        <input type="hidden" name="id_pr" value="<?= $pr['id_pr']; ?>">
                                        <div class="mb-3"><label class="small fw-bold">Tanggal Pengajuan</label><input type="date" name="pr_date" value="<?= $pr['pr_date']; ?>" class="form-control form-control-sm" required></div>
                                        <div class="mb-3"><label class="small fw-bold">Tujuan Keterangan Keperluan</label><input type="text" name="description" value="<?= htmlspecialchars($pr['description']); ?>" class="form-control form-control-sm" required></div>
                                    </div>
                                    <div class="modal-footer"><button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Tutup</button><button type="submit" name="edit_pr" class="btn btn-sm btn-success fw-bold">Simpan Atribut</button></div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modalItems<?= $pr['id_pr']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header bg-dark text-white">
                                    <div>
                                        <h5 class="modal-title fw-bold m-0"><i class="bi bi-box-seam text-warning"></i> Item Breakdown: <?= $pr['pr_number']; ?></h5>
                                        <small class="text-white-50">Kondisi Dokumen: <?= $locked ? '🔒 Terkunci (Approved CC)' : '✍️ Terbuka (Bisa Modifikasi)'; ?></small>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body bg-light text-start">
                                    
                                    <?php if (!$locked): ?>
                                    <div class="card card-body border-0 shadow-sm mb-3">
                                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-cart-plus"></i> Tambah Entri Item Baru ke Lembar PR</h6>
                                        <form method="POST">
                                            <input type="hidden" name="id_pr" value="<?= $pr['id_pr']; ?>">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-5">
                                                    <label class="small fw-bold" style="font-size: 10px;">Pilih Katalog Master Barang</label>
                                                    <select name="id_item" class="form-select form-select-sm" required>
                                                        <?php 
                                                        $cat = mysqli_query($conn, "SELECT id_item, item_code, item_name, unit FROM htl_pur_items ORDER BY item_name ASC");
                                                        while($c = mysqli_fetch_assoc($cat)){
                                                            echo "<option value='".$c['id_item']."'>[".$c['item_code']."] ".$c['item_name']." (".$c['unit'].")</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="small fw-bold" style="font-size: 10px;">Kuantitas (qty_requested)</label>
                                                    <input type="number" name="qty_requested" min="1" value="1" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="small fw-bold" style="font-size: 10px;">Estimasi Harga Satuan (Rp)</label>
                                                    <input type="number" name="estimate_price" min="0" value="0" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-12 mt-2"><button type="submit" name="add_item_to_pr" class="btn btn-xs btn-primary w-100 fw-bold py-1">Suntik Masuk Item</button></div>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endif; ?>

                                    <div class="table-responsive rounded bg-white shadow-sm border">
                                        <table class="table table-sm table-hover align-middle mb-0 text-start" style="font-size:12px;">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th>Kode Barang</th>
                                                    <th>Nama Atribut Item</th>
                                                    <th class="text-center">Qty Request</th>
                                                    <th class="text-end">Est Harga Satuan</th>
                                                    <th class="text-end">Subtotal</th>
                                                    <th class="text-center no-print">Opsi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $sub_items = mysqli_query($conn, "SELECT sub.*, it.item_code, it.item_name, it.unit 
                                                                                  FROM htl_pur_pr_detail sub 
                                                                                  JOIN htl_pur_items it ON sub.id_item = it.id_item 
                                                                                  WHERE sub.id_pr = ".$pr['id_pr']);
                                                $grand_total = 0;
                                                if(mysqli_num_rows($sub_items) > 0):
                                                    while($sub = mysqli_fetch_assoc($sub_items)):
                                                        $subtot = $sub['qty_requested'] * $sub['estimate_price'];
                                                        $grand_total += $subtot;
                                                ?>
                                                <tr>
                                                    <td class="font-monospace text-success fw-bold"><?= $sub['item_code']; ?></td>
                                                    <td><strong><?= $sub['item_name']; ?></strong> <span class="text-muted">(<?= $sub['unit']; ?>)</span></td>
                                                    <td class="text-center fw-bold text-dark"><?= $sub['qty_requested']; ?></td>
                                                    <td class="text-end font-monospace">Rp <?= number_format($sub['estimate_price'],0,',','.'); ?></td>
                                                    <td class="text-end font-monospace fw-bold text-primary">Rp <?= number_format($subtot,0,',','.'); ?></td>
                                                    <td class="text-center no-print">
                                                        <a href="pr.php?delete_item=<?= $sub['id_sub_pr']; ?>&pr_ref=<?= $pr['id_pr']; ?>" class="btn btn-xs py-0 px-1 btn-danger" onclick="return confirm('Hapus item ini dari PR?')"><i class="bi bi-trash"></i></a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                else:
                                                    echo "<tr><td colspan='6' class='text-center p-3 text-muted italic'>Belum ada rincian item barang di dokumen ini.</td></tr>";
                                                endif;
                                                ?>
                                                <tr class="table-light fw-bold fs-6">
                                                    <td colspan="4" class="text-end">Akumulasi Nilai Dana PR:</td>
                                                    <td class="text-end text-danger font-monospace">Rp <?= number_format($grand_total,0,',','.'); ?></td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                                <div class="modal-footer bg-light"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup Window</button></div>
                            </div>
                        </div>
                    </div>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>