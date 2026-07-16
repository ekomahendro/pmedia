<?php
session_start();
include 'config/db_connect.php'; 

// Cek Keamanan
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// ----------------------------------------------------
// 1. Ambil Filter dan Pencarian
// ----------------------------------------------------
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';

$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(m.nama_item LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_type) {
    $where_clauses[] = "m.type = ?";
    $params[] = $filter_type;
}

if ($filter_category) {
    $where_clauses[] = "m.category_id = ?";
    $params[] = $filter_category;
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';


// ----------------------------------------------------
// 2. Ambil Data Menu Sesuai Filter
// ----------------------------------------------------
$menu_items = [];
try {
    $sql_menu = "SELECT m.*, c.nama_kategori 
                 FROM t_menu_items m 
                 JOIN t_categories c ON m.category_id = c.category_id 
                 " . $where_sql . "
                 ORDER BY m.type, m.is_active DESC, m.nama_item";
    
    $stmt_menu = $pdo->prepare($sql_menu);
    $stmt_menu->execute($params);
    $menu_items = $stmt_menu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_menu = "Gagal mengambil data menu: " . $e->getMessage();
}

// 3. Ambil semua kategori untuk filter dan form
$categories = [];
try {
    $stmt_cat = $pdo->query("SELECT * FROM t_categories ORDER BY nama_kategori");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_cat = "Gagal mengambil data kategori: " . $e->getMessage();
}

$status_message = $_SESSION['status_message'] ?? '';
unset($_SESSION['status_message']); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Menu - Admin Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
        <div class="collapse navbar-collapse" id="navbarNavAdmin">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_menu.php">Manajemen Menu</a>
                </li>
            </ul>
        </div>
        <a class="btn btn-outline-light btn-sm" href="admin_logout.php">Logout</a>
    </div>
</nav>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Manajemen Item Menu 🍔🍹</h2>
    <?php echo $status_message; ?>

    <div class="row mb-3">
        <div class="col-md-3">
            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                ➕ Tambah Item Baru
            </button>
        </div>
        <div class="col-md-9">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari Nama/Deskripsi..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="filter_category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($filter_category == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="filter_type" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="Food" <?php echo ($filter_type == 'Food') ? 'selected' : ''; ?>>Food</option>
                        <option value="Beverage" <?php echo ($filter_type == 'Beverage') ? 'selected' : ''; ?>>Beverage</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Item (Deskripsi)</th>
                            <th>Tipe/Kategori</th>
                            <th>Harga (Disc)</th>
                            <th>Gambar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($menu_items)): ?>
                            <tr><td colspan="7" class="text-center">Data menu tidak ditemukan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($menu_items as $item): ?>
                            <tr class="<?php echo $item['is_active'] ? '' : 'table-secondary'; ?>">
                                <td><?php echo $item['item_id']; ?></td>
                                <td>
                                    <span class="badge <?php echo $item['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $item['is_active'] ? 'Aktif' : 'Non-Aktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-primary"><?php echo htmlspecialchars($item['nama_item']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['type']); ?>
                                    <br><span class="badge bg-info text-dark"><?php echo htmlspecialchars($item['nama_kategori']); ?></span>
                                </td>
                                <td>
                                    <del class="text-muted">Rp <?php echo number_format($item['price_reguler']); ?></del>
                                    <br><strong class="text-danger">Rp <?php echo number_format($item['price_diskon'] ?? $item['price_reguler']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($item['image_path']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" alt="Img" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editMenuModal"
                                            data-id="<?php echo $item['item_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['nama_item']); ?>"
                                            data-desc="<?php echo htmlspecialchars($item['description']); ?>"
                                            data-reg="<?php echo $item['price_reguler']; ?>"
                                            data-disc="<?php echo $item['price_diskon']; ?>"
                                            data-type="<?php echo $item['type']; ?>"
                                            data-cat="<?php echo $item['category_id']; ?>"
                                            data-active="<?php echo $item['is_active']; ?>"
                                            data-img="<?php echo htmlspecialchars($item['image_path']); ?>">
                                        Edit
                                    </button>
                                    <form action="admin_menu_action.php" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus menu <?php echo htmlspecialchars($item['nama_item']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMenuModal" tabindex="-1" aria-labelledby="addMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="admin_menu_action.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addMenuModalLabel">Tambah Item Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="nama_item" class="form-label">Nama Item</label>
                        <input type="text" class="form-control" id="nama_item" name="nama_item" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi Menu</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price_reguler" class="form-label">Harga Reguler</label>
                            <input type="number" class="form-control" id="price_reguler" name="price_reguler" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price_diskon" class="form-label">Harga Diskon (Coret)</label>
                            <input type="number" class="form-control" id="price_diskon" name="price_diskon" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="image_file" class="form-label">Gambar Menu</label>
                        <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Tipe</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="Food">Food</option>
                                <option value="Beverage">Beverage</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active_add" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active_add">Aktif (Tampilkan di Menu)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Simpan Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editMenuModal" tabindex="-1" aria-labelledby="editMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="admin_menu_action.php" method="POST" id="editMenuForm" enctype="multipart/form-data">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editMenuModalLabel">Edit Item Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <input type="hidden" name="current_image" id="edit_current_image">

                    <div class="mb-3">
                        <label for="edit_nama_item" class="form-label">Nama Item</label>
                        <input type="text" class="form-control" id="edit_nama_item" name="nama_item" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Deskripsi Menu</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_price_reguler" class="form-label">Harga Reguler</label>
                            <input type="number" class="form-control" id="edit_price_reguler" name="price_reguler" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_price_diskon" class="form-label">Harga Diskon</label>
                            <input type="number" class="form-control" id="edit_price_diskon" name="price_diskon" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image_file" class="form-label">Ganti Gambar Menu</label>
                        <input type="file" class="form-control" id="edit_image_file" name="image_file" accept="image/*">
                        <small class="text-muted" id="current_image_info"></small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_type" class="form-label">Tipe</label>
                            <select class="form-select" id="edit_type" name="type" required>
                                <option value="Food">Food</option>
                                <option value="Beverage">Beverage</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_category_id" class="form-label">Kategori</label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Aktif (Tampilkan di Menu)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-info">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript untuk mengisi modal Edit saat tombol Edit diklik
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const desc = this.getAttribute('data-desc');
            const reg = this.getAttribute('data-reg');
            const disc = this.getAttribute('data-disc');
            const type = this.getAttribute('data-type');
            const cat = this.getAttribute('data-cat');
            const active = this.getAttribute('data-active');
            const img = this.getAttribute('data-img');
            
            // Isi form di modal Edit
            document.getElementById('edit_item_id').value = id;
            document.getElementById('edit_nama_item').value = name;
            document.getElementById('edit_description').value = desc;
            document.getElementById('edit_price_reguler').value = reg;
            document.getElementById('edit_price_diskon').value = disc;
            document.getElementById('edit_type').value = type; 
            document.getElementById('edit_category_id').value = cat; 
            document.getElementById('edit_current_image').value = img;

            // Handle Checkbox Status Aktif
            document.getElementById('edit_is_active').checked = (active === '1');

            // Tampilkan info gambar saat ini
            const imgInfo = document.getElementById('current_image_info');
            if (img) {
                imgInfo.innerHTML = `**Gambar saat ini:** <a href="uploads/${img}" target="_blank">${img}</a>. Kosongkan field di atas untuk mempertahankan gambar ini.`;
            } else {
                imgInfo.textContent = 'Belum ada gambar yang diupload.';
            }
        });
    });
</script>
</body>
</html>