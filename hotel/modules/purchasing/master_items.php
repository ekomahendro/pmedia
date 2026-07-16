<?php
require_once '../../config.php';
check_login();

// 1. PROSES TAMBAH ITEM BARANG
if (isset($_POST['add_item'])) {
    $code = mysqli_real_escape_string($conn, $_POST['item_code']);
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $stock = intval($_POST['stock_qty']);
    $price = floatval($_POST['actual_price']);
    
    $img_name = 'default.png';
    if (!empty($_FILES['image']['name'])) {
        $img_name = time() . '_' . $_FILES['image']['name'];
        // Pastikan folder 'uploads' sudah dibuat di direktori yang sama
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $img_name);
    }

    // Saat pertama kali ditambah, actual_price dan last_price bernilai sama
    mysqli_query($conn, "INSERT INTO htl_pur_items (item_code, item_name, unit, stock_qty, actual_price, last_price, image_path) 
                         VALUES ('$code', '$name', '$unit', $stock, $price, $price, '$img_name')");
    header("Location: master_items.php?msg=added");
    exit();
}

// 2. PROSES EDIT/UPDATE ITEM BARANG
if (isset($_POST['edit_item'])) {
    $id = intval($_POST['id_item']);
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $stock = intval($_POST['stock_qty']);
    
    if (!empty($_FILES['image']['name'])) {
        $img_name = time() . '_' . $_FILES['image']['name'];
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $img_name);
        mysqli_query($conn, "UPDATE htl_pur_items SET item_name='$name', unit='$unit', stock_qty=$stock, image_path='$img_name' WHERE id_item=$id");
    } else {
        mysqli_query($conn, "UPDATE htl_pur_items SET item_name='$name', unit='$unit', stock_qty=$stock WHERE id_item=$id");
    }
    header("Location: master_items.php?msg=updated");
    exit();
}

$items = mysqli_query($conn, "SELECT * FROM htl_pur_items ORDER BY item_code ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Master Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    
    <div class="card card-body border-0 shadow-sm mb-3 py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="index.php" class="btn btn-sm btn-secondary"><i class="bi bi-house"></i> Dashboard</a>
                <a href="suppliers.php" class="btn btn-sm btn-outline-dark ms-1"><i class="bi bi-truck"></i> Ke Master Supplier</a>
            </div>
            <span class="fw-bold text-success">Gudang Utama Terintegrasi</span>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
            Data master berhasil diperbarui!
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-box-seam text-dark"></i> Master Katalog Barang (Inventory)</h3>
        <button class="btn btn-sm btn-dark" data-bs-toggle="collapse" data-bs-target="#formTambah" aria-expanded="false" aria-controls="formTambah">
            <i class="bi bi-plus-lg"></i> Tambah Item Katalog
        </button>
    </div>

    <div class="collapse mb-4" id="formTambah">
        <div class="card card-body border-0 shadow-sm bg-white">
            <h5 class="fw-bold mb-3 text-secondary">Form Input Master Barang Baru</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="small fw-bold">Kode Barang</label>
                        <input type="text" name="item_code" class="form-control form-control-sm" placeholder="Contoh: INV-004" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Nama Barang</label>
                        <input type="text" name="item_name" class="form-control form-control-sm" placeholder="Nama barang spesifik" required>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Satuan (Unit)</label>
                        <input type="text" name="unit" class="form-control form-control-sm" placeholder="Contoh: Pcs / Kg / Rim" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Stok Gudang Awal</label>
                        <input type="number" name="stock_qty" value="0" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Harga Awal / Average Price (Rp)</label>
                        <input type="number" name="actual_price" value="0" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold">Foto Fisik Barang</label>
                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" name="add_item" class="btn btn-sm btn-primary w-100 fw-bold">Simpan Katalog</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>Visual</th>
                        <th>Kode</th>
                        <th>Nama Item</th>
                        <th>Satuan</th>
                        <th>Stok Aktif</th>
                        <th>Average Price (Moving Avg)</th>
                        <th>Last Price (PO Terakhir)</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td>
                            <img src="uploads/<?= $row['image_path']; ?>" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='https://placehold.co/40'">
                        </td>
                        <td class="fw-bold font-monospace text-success"><?= $row['item_code']; ?></td>
                        <td class="fw-bold"><?= $row['item_name']; ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $row['unit']; ?></span></td>
                        <td class="fw-bold fs-6"><?= $row['stock_qty']; ?></td>
                        <td class="text-primary fw-bold">Rp <?= number_format($row['actual_price'], 0, ',', '.'); ?></td>
                        <td class="text-danger">Rp <?= number_format($row['last_price'], 0, ',', '.'); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-outline-dark py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#editItem<?= $row['id_item']; ?>">
                                <i class="bi bi-pencil"></i> Ubah
                            </button>
                        </td>
                    </tr>

                    <div class="modal fade" id="editItem<?= $row['id_item']; ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['id_item']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-dark text-white">
                                    <h5 class="modal-title fw-bold" id="modalLabel<?= $row['id_item']; ?>">Ubah Atribut: <?= $row['item_code']; ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="modal-body text-start">
                                        <input type="hidden" name="id_item" value="<?= $row['id_item']; ?>">
                                        
                                        <div class="mb-2">
                                            <label class="small fw-bold">Nama Barang</label>
                                            <input type="text" name="item_name" value="<?= $row['item_name']; ?>" class="form-control form-control-sm" required>
                                        </div>
                                        
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="small fw-bold">Satuan (Unit)</label>
                                                <input type="text" name="unit" value="<?= $row['unit']; ?>" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="small fw-bold">Koreksi Stok Gudang</label>
                                                <input type="number" name="stock_qty" value="<?= $row['stock_qty']; ?>" class="form-control form-control-sm" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="small fw-bold">Ganti Foto Barang</label>
                                            <input type="file" name="image" class="form-control form-control-sm mb-1" accept="image/*">
                                            <small class="text-muted" style="font-size: 10px;">*Kosongkan jika tidak ingin mengubah foto visual barang saat ini.</small>
                                        </div>
                                        
                                        <div class="alert alert-warning p-2 mb-0" style="font-size: 11px;">
                                            <i class="bi bi-info-circle-fill"></i> <strong>Sistem Kunci Nilai:</strong> Nilai <em>Average Price</em> &amp; <em>Last Price</em> sengaja tidak dibuka di form ini guna menghindari manipulasi internal dan otomatis terhitung lewat pintu masuk modul <strong>Receiving</strong>.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <button type="submit" name="edit_item" class="btn btn-sm btn-success fw-bold">Simpan Pembaruan</button>
                                    </div>
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