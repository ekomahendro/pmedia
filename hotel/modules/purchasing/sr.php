<?php
require_once '../../config.php';
check_login();
$user_role = $_SESSION['role'];

$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$depts = mysqli_query($conn, "SELECT * FROM htl_departments");

// Submit Pengeluaran Multi-Item Gudang
if (isset($_POST['submit_sr'])) {
    $sr_num = "SR-" . date("Ymd") . "-" . rand(100, 999);
    $dept_id = intval($_POST['id_department']);
    $uid = $_SESSION['user_id'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    mysqli_query($conn, "INSERT INTO htl_pur_sr (sr_number, id_department, id_user_created, description, status_approval) 
                         VALUES ('$sr_num', $dept_id, $uid, '$desc', 'Pending')");
    $id_sr = mysqli_insert_id($conn);
    
    if (!empty($_POST['sr_items'])) {
        foreach ($_POST['sr_items'] as $sit) {
            $id_item = intval($sit['id_item']);
            $qty = intval($sit['qty']);
            $remark = mysqli_real_escape_string($conn, $sit['remark']);
            
            if ($qty > 0) {
                mysqli_query($conn, "INSERT INTO htl_pur_sr_detail (id_sr, id_item, qty_requested, item_remark) 
                                     VALUES ($id_sr, $id_item, $qty, '$remark')");
            }
        }
    }
    header("Location: sr.php");
    exit();
}

// Approval & Eksekusi Potong Stok Masal
if (isset($_GET['approve_sr']) && isset($_GET['id'])) {
    $id = intval($_GET['id']); $act = $_GET['approve_sr'];
    
    if ($act == 'cost_control') {
        mysqli_query($conn, "UPDATE htl_pur_sr SET status_approval = 'Approved Cost Control' WHERE id_sr = $id");
    } elseif ($act == 'release') {
        $sr_items = mysqli_query($conn, "SELECT * FROM htl_pur_sr_detail WHERE id_sr = $id");
        while($stk = mysqli_fetch_assoc($sr_items)) {
            mysqli_query($conn, "UPDATE htl_pur_items SET stock_qty = stock_qty - " . $stk['qty_requested'] . " WHERE id_item = " . $stk['id_item']);
            mysqli_query($conn, "UPDATE htl_pur_sr_detail SET qty_issued = " . $stk['qty_requested'] . " WHERE id_sr_detail = " . $stk['id_sr_detail']);
        }
        mysqli_query($conn, "UPDATE htl_pur_sr SET status_approval = 'Released' WHERE id_sr = $id");
    }
    header("Location: sr.php"); exit();
}

// Load Dropdown Master Items
$master_items = [];
$mi_res = mysqli_query($conn, "SELECT id_item, item_name, stock_qty, unit FROM htl_pur_items");
while($row = mysqli_fetch_assoc($mi_res)) { $master_items[] = $row; }

// Filter Clause
$where_clauses = ["1=1"];
if (!empty($search)) $where_clauses[] = "(s.sr_number LIKE '%$search%' OR s.description LIKE '%$search%')";
if (!empty($start_date) && !empty($end_date)) $where_clauses[] = "DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'";
$where_str = implode(" AND ", $where_clauses);

$sr_list = mysqli_query($conn, "SELECT s.*, d.dept_name FROM htl_pur_sr s 
                                LEFT JOIN htl_departments d ON s.id_department = d.id_department 
                                WHERE $where_str ORDER BY s.id_sr DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Store Request Multi-Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><a href="index.php" class="btn btn-sm btn-secondary">Menu Utama</a><h2 class="fw-bold mt-1">4. Store Request (SR Multi-Item)</h2></div>
        <button class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#formSR">Buat SR Multi-Item</button>
    </div>

    <div class="collapse mb-4" id="formSR">
        <div class="card card-body border-0 shadow-sm">
            <form method="POST">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="small fw-bold">Departemen Peminta</label>
                        <select name="id_department" class="form-select form-select-sm" required>
                            <?php mysqli_data_seek($depts, 0); while($dp = mysqli_fetch_assoc($depts)): ?><option value="<?= $dp['id_department']; ?>"><?= $dp['dept_name']; ?></option><?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold">Tujuan / Alasan Pemakaian Barang</label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Contoh: Penggantian sabun amenity kamar atau operasional laundry" required>
                    </div>
                </div>

                <h6 class="fw-bold text-muted border-bottom pb-1">Daftar Barang yang Diambil dari Gudang</h6>
                <div id="wrapper-sr">
                    <div class="row g-2 align-items-center mb-2 sr-row">
                        <div class="col-md-4">
                            <select name="sr_items[0][id_item]" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Akses Gudang Utama --</option>
                                <?php foreach($master_items as $mi): ?><option value="<?= $mi['id_item']; ?>"><?= $mi['item_name']; ?> (Sisa Stok: <?= $mi['stock_qty']; ?> <?= $mi['unit']; ?>)</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><input type="number" name="sr_items[0][qty]" min="1" placeholder="Qty Keluar" class="form-control form-control-sm" required></div>
                        <div class="col-md-6"><input type="text" name="sr_items[0][remark]" placeholder="Alasan spesifik / No. Kamar jika ada" class="form-control form-control-sm"></div>
                    </div>
                </div>
                <button type="button" id="add-sr-row" class="btn btn-xs btn-outline-secondary mt-1">+ Tambah Baris Pengambilan</button>
                <hr>
                <button type="submit" name="submit_sr" class="btn btn-sm btn-danger fw-bold">Ajukan Pengambilan Barang</button>
            </form>
        </div>
    </div>

    <div class="card card-body border-0 shadow-sm mb-3">
        <form method="GET" class="row g-2 small align-items-end">
            <div class="col-md-4"><label class="fw-bold">Cari No SR / Alasan</label><input type="text" name="search" value="<?= $search; ?>" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="fw-bold">Mulai Tanggal</label><input type="date" name="start_date" value="<?= $start_date; ?>" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="fw-bold">Hingga Tanggal</label><input type="date" name="end_date" value="<?= $end_date; ?>" class="form-control form-control-sm"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-sm btn-dark w-100">Filter</button></div>
        </form>
    </div>

    <?php while($sr = mysqli_fetch_assoc($sr_list)): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2">
                <div><span class="fw-bold font-monospace"><?= $sr['sr_number']; ?></span> <span class="badge bg-dark ms-2"><?= $sr['dept_name']; ?></span></div>
                <div>
                    <span class="badge bg-light text-dark me-2"><?= $sr['status_approval']; ?></span>
                    <?php if($sr['status_approval'] == 'Pending'): ?>
                        <a href="sr.php?approve_sr=cost_control&id=<?= $sr['id_sr']; ?>" class="btn btn-xs btn-info text-white py-0">Approve Cost Control</a>
                    <?php elseif($sr['status_approval'] == 'Approved Cost Control'): ?>
                        <a href="sr.php?approve_sr=release&id=<?= $sr['id_sr']; ?>" class="btn btn-xs btn-danger py-0" onclick="return confirm('Kurangi stok total gudang secara otomatis?')">Release & Handover</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 table-hover font-monospace small">
                    <tbody>
                        <?php 
                        $srd = mysqli_query($conn, "SELECT sd.*, i.item_name, i.unit FROM htl_pur_sr_detail sd JOIN htl_pur_items i ON sd.id_item = i.id_item WHERE sd.id_sr = " . $sr['id_sr']);
                        while($d = mysqli_fetch_assoc($srd)):
                        ?>
                        <tr><td class="ps-3">• <?= $d['item_name']; ?></td><td class="fw-bold">Qty Minta: <?= $d['qty_requested']; ?> <?= $d['unit']; ?></td><td><small class="text-muted">Note: <?= $d['item_remark']; ?></small></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script>
let srIndex = 1;
document.getElementById('add-sr-row').addEventListener('click', function() {
    let container = document.getElementById('wrapper-sr');
    let row = container.querySelector('.sr-row').cloneNode(true);
    row.querySelector('select').name = `sr_items[${srIndex}][id_item]`;
    row.querySelector('input[type="number"]').name = `sr_items[${srIndex}][qty]`;
    row.querySelector('input[type="text"]').name = `sr_items[${srIndex}][remark]`;
    row.querySelector('select').value = "";
    row.querySelector('input[type="number"]').value = "";
    row.querySelector('input[type="text"]').value = "";
    container.appendChild(row);
    srIndex++;
});
</script>
</body>
</html>