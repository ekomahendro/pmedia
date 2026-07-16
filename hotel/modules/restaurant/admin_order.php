<?php
require_once '../../config.php';

// Logika Split Bill
if (isset($_POST['split_bill'])) {
    $old_order = $_POST['id_order'];
    $new_order_id = rand(1000,9999); // ID bill baru
    foreach ($_POST['items_to_split'] as $item_id) {
        $conn->query("UPDATE htl_restaurant_order_items SET id_order = $new_order_id WHERE id_item = $item_id");
    }
    echo "<script>alert('Bill berhasil dipecah!');</script>";
}

$orders = $conn->query("SELECT * FROM htl_restaurant_orders WHERE status != 'paid'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Dashboard Admin Resto</title>
</head>
<body class="bg-light">
<div class="container py-4">
    <h3>Monitor Pesanan</h3>
    <table class="table table-hover bg-white shadow-sm rounded">
        <thead><tr><th>Tamu</th><th>Meja</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php while($o = $orders->fetch_assoc()): ?>
            <tr>
                <td><?= $o['guest_name'] ?></td>
                <td><?= $o['table_number'] ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="id_order" value="<?= $o['id_order'] ?>">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-info dropdown-toggle" data-bs-toggle="dropdown">Pilih Split</button>
                            <div class="dropdown-menu p-2">
                                <?php 
                                $items = $conn->query("SELECT * FROM htl_restaurant_order_items WHERE id_order = ".$o['id_order']);
                                while($i = $items->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="items_to_split[]" value="<?= $i['id_item'] ?>">
                                        <label>Item ID: <?= $i['id_menu'] ?></label>
                                    </div>
                                <?php endwhile; ?>
                                <button type="submit" name="split_bill" class="btn btn-sm btn-warning mt-2 w-100">Proses</button>
                            </div>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>