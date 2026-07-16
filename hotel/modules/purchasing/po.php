<?php
require_once '../../config.php';
check_login();
$user_role = $_SESSION['role'];

// Proses Pembuatan PO Multi-Item dari PR Terpilih
if (isset($_POST['create_po_final'])) {
    $pr_id = intval($_POST['id_pr']);
    $supplier_id = intval($_POST['id_supplier']);
    $po_num = "PO-" . date("Ymd") . "-" . rand(100, 999);
    $uid = $_SESSION['user_id'];
    
    // 1. Masukkan data Master PO dengan data supplier pemenang
    mysqli_query($conn, "INSERT INTO htl_pur_po (po_number, id_pr, id_supplier, id_user_created, status_approval) 
                         VALUES ('$po_num', $pr_id, $supplier_id, $uid, 'Pending')");
    $po_id = mysqli_insert_id($conn);
    
    // 2. Loop insert harga deals vendor per item
    if (!empty($_POST['prices'])) {
        foreach ($_POST['prices'] as $item_id => $data) {
            $qty = intval($data['qty']);
            $unit_price = floatval($data['price']);
            $remark = mysqli_real_escape_string($conn, $data['remark']);
            
            mysqli_query($conn, "INSERT INTO htl_pur_po_detail (id_po, id_item, qty_ordered, unit_price, item_remark) 
                                 VALUES ($po_id, $item_id, $qty, $unit_price, '$remark')");
        }
    }
    
    // Tandai PR telah diproses
    mysqli_query($conn, "UPDATE htl_pur_pr SET status_approval = 'PO Created' WHERE id_pr = $pr_id");
    header("Location: po.php?success=1");
    exit();
}

// Handler Approval PO
if (isset($_GET['approve_po']) && isset($_GET['id'])) {
    $id = intval($_GET['id']); $act = $_GET['approve_po'];
    if ($act == 'purchasing') mysqli_query($conn, "UPDATE htl_pur_po SET status_approval = 'Approved Purchasing' WHERE id_po = $id");
    if ($act == 'finance') mysqli_query($conn, "UPDATE htl_pur_po SET status_approval = 'Approved Finance' WHERE id_po = $id");
    if ($act == 'gm') mysqli_query($conn, "UPDATE htl_pur_po SET status_approval = 'Approved' WHERE id_po = $id");
    header("Location: po.php"); exit();
}

// Ambil List Supplier untuk Dropdown Pemilihan Vendor
$suppliers_list = [];
$s_res = mysqli_query($conn, "SELECT id_supplier, supplier_name FROM htl_pur_suppliers");
while($s = mysqli_fetch_assoc($s_res)) { $suppliers_list[] = $s; }

// Ambil PR yang statusnya disetujui penuh (Siap beli)
$pr_ready = mysqli_query($conn, "SELECT p.*, d.dept_name FROM htl_pur_pr p LEFT JOIN htl_departments d ON p.id_department = d.id_department WHERE p.status_approval = 'Approved'");

// Ambil Monitoring PO Terbit
$query_po = "SELECT o.*, s.supplier_name FROM htl_pur_po o LEFT JOIN htl_pur_suppliers s ON o.id_supplier = s.id_supplier ORDER BY o.id_po DESC";
$po_list = mysqli_query($conn, $query_po);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>PO Multi-Item & Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <a href="index.php" class="btn btn-sm btn-secondary mb-3">Menu Utama</a>
    <h2 class="fw-bold mb-3">2. Purchase Order (PO Multi-Item & Pilihan Supplier)</h2>

    <?php while($pr = mysqli_fetch_assoc($pr_ready)): ?>
        <div class="card border-primary mb-4 shadow-sm">
            <div class="card-header bg-primary text-white font-monospace small">Tarik PR No: <strong><?= $pr['pr_number']; ?></strong> (<?= $pr['dept_name']; ?>)</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id_pr" value="<?= $pr['id_pr']; ?>">
                    <div class="col-md-5 mb-3">
                        <label class="small fw-bold text-danger">Tentukan Pemenang Supplier / Vendor:</label>
                        <select name="id_supplier" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Vendor Master --</option>
                            <?php foreach($suppliers_list as $sup): ?><option value="<?= $sup['id_supplier']; ?>"><?= $sup['supplier_name']; ?></option><?php endforeach; ?>
                        </select>
                    </div>

                    <table class="table table-sm table-bordered align-middle small mb-2">
                        <thead class="table-light"><tr><th>Nama Barang</th><th>Qty Minta</th><th>Isi Harga Beli Satuan Resmi (Rp)</th></tr></thead>
                        <tbody>
                            <?php 
                            $pr_it = mysqli_query($conn, "SELECT pd.*, i.item_name FROM htl_pur_pr_detail pd JOIN htl_pur_items i ON pd.id_item = i.id_item WHERE pd.id_pr = " . $pr['id_pr']);
                            while($it = mysqli_fetch_assoc($pr_it)):
                            ?>
                            <tr>
                                <td><?= $it['item_name']; ?></td>
                                <td class="fw-bold"><?= $it['qty_requested']; ?></td>
                                <input type="hidden" name="prices[<?= $it['id_item']; ?>][qty]" value="<?= $it['qty_requested']; ?>">
                                <input type="hidden" name="prices[<?= $it['id_item']; ?>][remark]" value="<?= $it['item_remark']; ?>">
                                <td><input type="number" name="prices[<?= $it['id_item']; ?>][price]" class="form-control form-control-sm" placeholder="Contoh: 125000" required></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="create_po_final" class="btn btn-sm btn-success fw-bold">Terbitkan Lembar Kerja PO</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold py-2">Daftar PO Aktif Hotel</div>
        <table class="table align-middle small mb-0 table-striped">
            <thead class="table-light">
                <tr><th>No PO</th><th>Supplier Terpilih</th><th>Rincian Item & Harga Sepakat</th><th>Status Alur</th><th>Otorisasi</th></tr>
            </thead>
            <tbody>
                <?php while($po = mysqli_fetch_assoc($po_list)): ?>
                <tr>
                    <td class="fw-bold font-monospace text-primary"><?= $po['po_number']; ?></td>
                    <td><span class="badge bg-secondary"><?= $po['supplier_name']; ?></span></td>
                    <td>
                        <ul class="list-unstyled mb-0 px-0 font-monospace" style="font-size: 0.75rem;">
                        <?php 
                        $po_det = mysqli_query($conn, "SELECT od.*, i.item_name FROM htl_pur_po_detail od JOIN htl_pur_items i ON od.id_item = i.id_item WHERE od.id_po = " . $po['id_po']);
                        while($od = mysqli_fetch_assoc($po_det)):
                        ?>
                            <li>• <?= $od['item_name']; ?> (x<?= $od['qty_ordered']; ?>) @ Rp <?= number_format($od['unit_price'],0,',','.'); ?></li>
                        <?php endwhile; ?>
                        </ul>
                    </td>
                    <td><span class="badge bg-warning text-dark"><?= $po['status_approval']; ?></span></td>
                    <td>
                        <?php if($po['status_approval'] == 'Pending'): ?>
                            <a href="po.php?approve_po=purchasing&id=<?= $po['id_po']; ?>" class="btn btn-xs btn-outline-warning py-0 px-1">Approve Purc</a>
                        <?php elseif($po['status_approval'] == 'Approved Purchasing'): ?>
                            <a href="po.php?approve_po=finance&id=<?= $po['id_po']; ?>" class="btn btn-xs btn-outline-info py-0 px-1">Approve Finance</a>
                        <?php elseif($po['status_approval'] == 'Approved Finance'): ?>
                            <a href="po.php?approve_po=gm&id=<?= $po['id_po']; ?>" class="btn btn-xs btn-outline-danger py-0 px-1">Approve GM</a>
                        <?php else: ?>
                            <span class="text-success fw-bold">✓ Valid Termandat</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>