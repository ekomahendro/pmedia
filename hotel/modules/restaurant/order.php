<?php
require_once '../../config.php';
$outlet_id = $_GET['outlet'] ?? 1; // Default outlet
$table = $_GET['table'] ?? 'A1';

// Proses Order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("INSERT INTO htl_restaurant_orders (id_outlet, guest_name, guest_contact, table_number) VALUES (?, ?, ?, ?)");
    $stmt->execute([$outlet_id, $_POST['name'], $_POST['phone'], $table]);
    $order_id = $conn->insert_id;

    foreach ($_POST['items'] as $id_menu => $qty) {
        if ($qty > 0) {
            $conn->query("INSERT INTO htl_restaurant_order_items (id_order, id_menu, qty) VALUES ($order_id, $id_menu, $qty)");
        }
    }
    $success = "Pesanan berhasil dikirim!";
}
$menus = $conn->query("SELECT * FROM htl_menu_items WHERE id_outlet = $outlet_id AND status = 'active'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Menu Restaurant</title>
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-4">Menu Resto - Meja <?= htmlspecialchars($table) ?></h3>
    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <form method="POST">
        <div class="card p-3 mb-3">
            <input type="text" name="name" class="form-control mb-2" placeholder="Nama Anda" required>
            <input type="text" name="phone" class="form-control" placeholder="No HP/Email" required>
        </div>
        <div class="row">
            <?php while($m = $menus->fetch_assoc()): ?>
            <div class="col-md-6 mb-2">
                <div class="card p-2 d-flex flex-row justify-content-between align-items-center">
                    <span><?= $m['name'] ?> (Rp <?= number_format($m['price']) ?>)</span>
                    <input type="number" name="items[<?= $m['id_menu'] ?>]" class="form-control w-25" value="0" min="0">
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <button type="submit" class="btn btn-primary mt-3 w-100">Kirim Pesanan</button>
    </form>
</div>
</body>
</html>