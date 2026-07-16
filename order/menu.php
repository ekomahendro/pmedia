<?php
session_start();
include 'config/db_connect.php';

// ----------------------------------------------------
// Logika Filter dan Pencarian
// ----------------------------------------------------
$search = $_GET['search'] ?? '';
$filter_category = $_GET['category'] ?? '';

$where_clauses = ["m.is_active = TRUE"]; // Hanya tampilkan yang aktif
$params = [];

if ($search) {
    $where_clauses[] = "(m.nama_item LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_category) {
    $where_clauses[] = "m.category_id = ?";
    $params[] = $filter_category;
}

$where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

// Ambil semua item menu yang aktif dan difilter
$menu_items = [];
try {
    $sql_menu = "SELECT m.*, c.nama_kategori 
                 FROM t_menu_items m 
                 JOIN t_categories c ON m.category_id = c.category_id 
                 " . $where_sql . "
                 ORDER BY m.type, c.nama_kategori, m.nama_item";
    
    $stmt_menu = $pdo->prepare($sql_menu);
    $stmt_menu->execute($params);
    $all_items = $stmt_menu->fetchAll(PDO::FETCH_ASSOC);

    // Pisahkan menu berdasarkan tipe untuk Tab
    $food_items = array_filter($all_items, fn($item) => $item['type'] == 'Food');
    $beverage_items = array_filter($all_items, fn($item) => $item['type'] == 'Beverage');

} catch (PDOException $e) {
    $error_message = "Gagal memuat menu: " . $e->getMessage();
}

// Ambil semua kategori untuk filter
$stmt_cat = $pdo->query("SELECT * FROM t_categories ORDER BY nama_kategori");
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// Inisialisasi keranjang belanja
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$total_cart_items = array_sum(array_column($_SESSION['cart'], 'quantity'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Online Menu Restoran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .discount-price { color: #dc3545; font-weight: bold; font-size: 1.2em; }
        .regular-price { color: #6c757d; text-decoration: line-through; margin-right: 10px; }
        .menu-img { height: 200px; object-fit: cover; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">[Nama Restoran]</a>
        <a href="cart.php" class="btn btn-warning position-relative">
            🛒 Keranjang
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $total_cart_items; ?>
                <span class="visually-hidden">items in cart</span>
            </span>
        </a>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="text-center mb-4">Daftar Menu Kami</h2>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="GET" class="row mb-4 g-2 justify-content-center">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Cari menu (Nama/Deskripsi)..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">Filter Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($filter_category == $cat['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Cari & Filter</button>
        </div>
    </form>

    <ul class="nav nav-pills mb-3 justify-content-center" id="menuTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="food-tab" data-bs-toggle="tab" data-bs-target="#food" type="button" role="tab">🍽️ Food (Makanan)</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="beverage-tab" data-bs-toggle="tab" data-bs-target="#beverage" type="button" role="tab">🍹 Beverage (Minuman)</button>
        </li>
    </ul>

    <div class="tab-content" id="menuTabContent">
        <div class="tab-pane fade show active" id="food" role="tabpanel" aria-labelledby="food-tab">
            <?php if (empty($food_items)): ?>
                <div class="alert alert-info text-center">Menu makanan tidak ditemukan sesuai kriteria.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($food_items as $item): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if ($item['image_path']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top menu-img" alt="<?php echo htmlspecialchars($item['nama_item']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-info text-dark mb-2"><?php echo htmlspecialchars($item['nama_kategori']); ?></span>
                                <h5 class="card-title"><?php echo htmlspecialchars($item['nama_item']); ?></h5>
                                <p class="card-text text-muted small" style="min-height: 40px;"><?php echo htmlspecialchars($item['description']); ?></p>
                                
                                <div class="price-section mb-3">
                                    <?php 
                                    $price_reg = $item['price_reguler'];
                                    $price_disc = $item['price_diskon'];
                                    ?>
                                    <?php if ($price_disc && $price_disc < $price_reg): ?>
                                        <span class="regular-price">Rp <?php echo number_format($price_reg); ?></span>
                                        <span class="discount-price">Rp <?php echo number_format($price_disc); ?></span>
                                    <?php else: ?>
                                        <span class="discount-price">Rp <?php echo number_format($price_reg); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <button class="btn btn-primary w-100 add-to-cart" 
                                    data-id="<?php echo $item['item_id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($item['nama_item']); ?>">
                                    Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="beverage" role="tabpanel" aria-labelledby="beverage-tab">
             <?php if (empty($beverage_items)): ?>
                <div class="alert alert-info text-center">Menu minuman tidak ditemukan sesuai kriteria.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($beverage_items as $item): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if ($item['image_path']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top menu-img" alt="<?php echo htmlspecialchars($item['nama_item']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-info text-dark mb-2"><?php echo htmlspecialchars($item['nama_kategori']); ?></span>
                                <h5 class="card-title"><?php echo htmlspecialchars($item['nama_item']); ?></h5>
                                <p class="card-text text-muted small" style="min-height: 40px;"><?php echo htmlspecialchars($item['description']); ?></p>
                                
                                <div class="price-section mb-3">
                                    <?php 
                                    $price_reg = $item['price_reguler'];
                                    $price_disc = $item['price_diskon'];
                                    ?>
                                    <?php if ($price_disc && $price_disc < $price_reg): ?>
                                        <span class="regular-price">Rp <?php echo number_format($price_reg); ?></span>
                                        <span class="discount-price">Rp <?php echo number_format($price_disc); ?></span>
                                    <?php else: ?>
                                        <span class="discount-price">Rp <?php echo number_format($price_reg); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <button class="btn btn-primary w-100 add-to-cart" 
                                    data-id="<?php echo $item['item_id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($item['nama_item']); ?>">
                                    Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-5 mb-5">
        <a href="cart.php" class="btn btn-lg btn-success">Lanjut ke Keranjang (<?php echo $total_cart_items; ?> item)</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemName = this.getAttribute('data-name');
            
            // Logika AJAX/Fetch API untuk menambahkan item ke session/keranjang (cart_action.php)
            fetch('cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(`${itemName} telah ditambahkan ke keranjang!`);
                    // Update tampilan jumlah item di keranjang
                    document.querySelector('.btn-lg.btn-success').innerHTML = `Lanjut ke Keranjang (${data.count} item)`;
                    document.querySelector('.position-absolute.bg-danger').textContent = data.count;
                } else {
                    alert('Gagal menambahkan item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat berkomunikasi dengan server.');
            });
        });
    });
</script>
</body>
</html>