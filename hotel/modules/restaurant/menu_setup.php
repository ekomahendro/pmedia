<?php
require_once '../../config.php';
$id_outlet = $_GET['id'] ?? 0;

// Handle Aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_menu'])) {
        $conn->query("INSERT INTO htl_menu_items (id_outlet, name, price, status) VALUES ($id_outlet, '{$_POST['name']}', '{$_POST['price']}', 'active')");
    } elseif (isset($_POST['delete_menu'])) {
        $conn->query("DELETE FROM htl_menu_items WHERE id_menu = {$_POST['id_menu']}");
    } elseif (isset($_POST['copy_menu'])) {
        $conn->query("INSERT INTO htl_menu_items (id_outlet, name, price, status) SELECT {$_POST['target_outlet']}, name, price, 'active' FROM htl_menu_items WHERE id_menu = {$_POST['source_id']}");
    }
}
$menus = $conn->query("SELECT * FROM htl_menu_items WHERE id_outlet = $id_outlet");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container bg-white p-4 shadow-sm rounded">
        <h4>Manajemen Menu</h4>
        <form method="POST" class="row mb-4">
            <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Nama Menu" required></div>
            <div class="col-md-3"><input type="number" name="price" class="form-control" placeholder="Harga" required></div>
            <div class="col-md-2"><button name="save_menu" class="btn btn-success w-100">Tambah</button></div>
        </form>
        <table class="table table-hover">
            <thead><tr><th>Menu</th><th>Harga</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php while($m = $menus->fetch_assoc()): ?>
                <tr>
                    <td><?= $m['name'] ?></td>
                    <td>Rp <?= number_format($m['price']) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                            <button name="delete_menu" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>