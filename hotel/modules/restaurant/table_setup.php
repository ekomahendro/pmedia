<?php
require_once '../../config.php';
$id_outlet = $_GET['id'] ?? 0;

if(isset($_POST['add_table'])){
    $conn->query("INSERT INTO htl_restaurant_tables (id_outlet, table_name, table_number, capacity) 
                  VALUES ($id_outlet, '{$_POST['name']}', '{$_POST['number']}', {$_POST['cap']})");
}
$tables = $conn->query("SELECT * FROM htl_restaurant_tables WHERE id_outlet = $id_outlet");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Setup Meja</title>
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="index.php" class="btn btn-sm btn-secondary mb-3">Kembali</a>
    <div class="card p-4 shadow-sm mb-4">
        <h5>Tambah Meja Baru</h5>
        <form method="POST" class="row g-2">
            <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nama Meja (Contoh: VIP 1)" required></div>
            <div class="col-md-2"><input type="text" name="number" class="form-control" placeholder="No Meja (Contoh: A1)" required></div>
            <div class="col-md-2"><input type="number" name="cap" class="form-control" placeholder="Kapasitas"></div>
            <div class="col-md-2"><button type="submit" name="add_table" class="btn btn-primary w-100">Simpan</button></div>
        </form>
    </div>
    
    <div class="row">
        <?php while($t = $tables->fetch_assoc()): 
            $url = "http://".$_SERVER['HTTP_HOST']."/modules/restaurant/order.php?outlet=$id_outlet&table=".$t['table_number'];
        ?>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6><?= $t['table_name'] ?> (<?= $t['table_number'] ?>)</h6>
                <div class="p-2 bg-light border my-2">
                    <small>QR Link:<br> <?= $url ?></small>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>