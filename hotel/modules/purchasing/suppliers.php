<?php
require_once '../../config.php';
check_login();

// 1. PROSES TAMBAH SUPPLIER
if (isset($_POST['add_supplier'])) {
    $code = mysqli_real_escape_string($conn, $_POST['supplier_code']);
    $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    mysqli_query($conn, "INSERT INTO htl_pur_suppliers (supplier_code, supplier_name, phone, address) VALUES ('$code', '$name', '$phone', '$address')");
    header("Location: suppliers.php?msg=added");
    exit();
}

// 2. PROSES EDIT/UPDATE SUPPLIER
if (isset($_POST['edit_supplier'])) {
    $id = intval($_POST['id_supplier']);
    $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    mysqli_query($conn, "UPDATE htl_pur_suppliers SET supplier_name='$name', phone='$phone', address='$address' WHERE id_supplier=$id");
    header("Location: suppliers.php?msg=updated");
    exit();
}

$suppliers = mysqli_query($conn, "SELECT * FROM htl_pur_suppliers ORDER BY supplier_code ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Kelola Master Supplier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="card card-body border-0 shadow-sm mb-3 py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="index.php" class="btn btn-sm btn-secondary"><i class="bi bi-house"></i> Dashboard</a>
                <a href="master_items.php" class="btn btn-sm btn-outline-primary ms-1"><i class="bi bi-box"></i> Ke Master Barang</a>
            </div>
            <span class="fw-bold text-muted">ERP Purchasing System v2.0</span>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-truck text-dark"></i> Database Master Supplier (Vendor)</h3>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#formSupp"><i class="bi bi-plus-lg"></i> Registrasi Supplier Baru</button>
    </div>

    <div class="collapse mb-4" id="formSupp">
        <div class="card card-body border-0 shadow-sm bg-white">
            <h5 class="fw-bold text-primary mb-3">Form Input Supplier</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-3"><label class="small fw-bold">Kode Supplier (Unique)</label><input type="text" name="supplier_code" class="form-control form-control-sm" placeholder="Contoh: SPL-003" required></div>
                    <div class="col-md-5"><label class="small fw-bold">Nama Perusahaan/Vendor</label><input type="text" name="supplier_name" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="small fw-bold">No. Telepon / Sales</label><input type="text" name="phone" class="form-control form-control-sm" placeholder="081xxx"></div>
                    <div class="col-md-12"><label class="small fw-bold">Alamat Kantor/Gudang Vendor</label><textarea name="address" class="form-control form-control-sm" rows="2" required></textarea></div>
                    <div class="col-12"><button type="submit" name="add_supplier" class="btn btn-sm btn-success fw-bold px-4">Simpan Data Vendor</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Supplier</th>
                        <th>No Telp</th>
                        <th>Alamat</th>
                        <th class="text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($s = mysqli_fetch_assoc($suppliers)): ?>
                    <tr>
                        <td class="fw-bold text-primary font-monospace"><?= $s['supplier_code']; ?></td>
                        <td class="fw-bold"><?= $s['supplier_name']; ?></td>
                        <td><?= $s['phone'] ?: '-'; ?></td>
                        <td><?= $s['address']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-warning py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id_supplier']; ?>"><i class="bi bi-pencil-square"></i> Edit</button>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?= $s['id_supplier']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-dark"><h5 class="modal-title fw-bold">Edit Vendor: <?= $s['supplier_code']; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <form method="POST">
                                    <div class="modal-body text-start">
                                        <input type="hidden" name="id_supplier" value="<?= $s['id_supplier']; ?>">
                                        <div class="mb-2"><label class="small fw-bold">Nama Supplier</label><input type="text" name="supplier_name" value="<?= $s['supplier_name']; ?>" class="form-control form-control-sm" required></div>
                                        <div class="mb-2"><label class="small fw-bold">No. Telepon</label><input type="text" name="phone" value="<?= $s['phone']; ?>" class="form-control form-control-sm"></div>
                                        <div class="mb-2"><label class="small fw-bold">Alamat Kantor</label><textarea name="address" class="form-control form-control-sm" rows="3" required><?= $s['address']; ?></textarea></div>
                                    </div>
                                    <div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_supplier" class="btn btn-sm btn-warning fw-bold">Simpan Perubahan</button></div>
                                </form>
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