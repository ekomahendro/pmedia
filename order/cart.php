<?php
session_start();
include 'config/db_connect.php';

// Cek apakah user sudah login (jika diperlukan)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

$message = '';
$cart = $_SESSION['cart'] ?? [];
$total_amount = 0;

// --------------------------------------------------------
// Logika Update Quantity (Tambah/Kurang/Hapus)
// --------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_action'])) {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $action = $_POST['cart_action'];

    if ($itemId > 0 && isset($_SESSION['cart'][$itemId])) {
        if ($action == 'increase') {
            $_SESSION['cart'][$itemId]['quantity'] += 1;
        } elseif ($action == 'decrease') {
            $_SESSION['cart'][$itemId]['quantity'] -= 1;
            if ($_SESSION['cart'][$itemId]['quantity'] <= 0) {
                unset($_SESSION['cart'][$itemId]); // Hapus jika qty <= 0
            }
        } elseif ($action == 'remove') {
            unset($_SESSION['cart'][$itemId]);
        }
    }
    // Redirect untuk menghindari resubmission form saat refresh
    header("Location: cart.php");
    exit;
}

// --------------------------------------------------------
// Logika Pemrosesan Order (Checkout)
// --------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_order'])) {
    if (empty($cart)) {
        $message = '<div class="alert alert-danger">Keranjang Anda kosong. Gagal memproses pesanan.</div>';
    } else {
        $table_number = trim($_POST['table_number'] ?? '');
        $is_takeaway = isset($_POST['is_takeaway']) ? TRUE : FALSE;
        $special_request = trim($_POST['special_request'] ?? '');
        $order_time = date('Y-m-d H:i:s'); // Waktu order dibuat
        $payment_method = $_POST['payment_method'] ?? 'Tunai';

        try {
            $pdo->beginTransaction();

            // 1. Insert ke t_orders
            $sql_order = "INSERT INTO t_orders (user_id, table_number, is_takeaway, special_request, order_time, payment_method) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_order = $pdo->prepare($sql_order);
            $stmt_order->execute([
                $user_id, 
                $table_number, 
                $is_takeaway, 
                $special_request, 
                $order_time, 
                $payment_method
            ]);
            $order_id = $pdo->lastInsertId();

            // 2. Insert ke t_order_details
            $sql_detail = "INSERT INTO t_order_details (order_id, item_id, quantity, price_at_order, subtotal) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $pdo->prepare($sql_detail);
            
            $final_total_amount = 0;

            foreach ($cart as $item_id => $item) {
                $subtotal = $item['quantity'] * $item['price'];
                $stmt_detail->execute([
                    $order_id, 
                    $item_id, 
                    $item['quantity'], 
                    $item['price'],
                    $subtotal
                ]);
                $final_total_amount += $subtotal;
            }

            $pdo->commit();
            
            // Order Berhasil
            unset($_SESSION['cart']); 
            $message = '<div class="alert alert-success">Pesanan Anda **#' . $order_id . '** berhasil dibuat! Total: Rp ' . number_format($final_total_amount) . '. Silakan cek status pesanan Anda.</div>';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Gagal memproses pesanan. Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// --------------------------------------------------------
// Ambil Detail Item dari Database untuk Display
// --------------------------------------------------------
$cart = $_SESSION['cart'] ?? [];
$cart_details = [];
$total_amount = 0;

if (!empty($cart)) {
    $item_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    
    // Ambil nama item dan harga saat ini (untuk cross-check)
    $stmt = $pdo->prepare("SELECT item_id, nama_item FROM t_menu_items WHERE item_id IN ($placeholders)");
    $stmt->execute($item_ids);
    $db_items = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [item_id => nama_item]

    foreach ($cart as $id => $item) {
        $subtotal = $item['quantity'] * $item['price'];
        $cart_details[] = [
            'item_id' => $id,
            'name' => $db_items[$id] ?? 'Item tidak dikenal',
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $subtotal
        ];
        $total_amount += $subtotal;
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout & Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Detail Pesanan & Checkout</h2>
    <hr>
    <?php echo $message; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5>Ringkasan Keranjang</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($cart_details)): ?>
                        <li class="list-group-item text-center">Keranjang belanja Anda kosong.</li>
                    <?php else: ?>
                        <?php foreach ($cart_details as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <br><small class="text-muted">Rp <?php echo number_format($item['price']); ?> / item</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        
                                        <button type="submit" name="cart_action" value="decrease" class="btn btn-sm btn-outline-danger me-1" title="Kurangi Kuantitas">
                                            <i class="fas fa-minus">-</i>
                                        </button>
                                        
                                        <span class="fw-bold mx-2" style="width: 20px; text-align: center;"><?php echo $item['quantity']; ?></span>
                                        
                                        <button type="submit" name="cart_action" value="increase" class="btn btn-sm btn-outline-success me-3" title="Tambah Kuantitas">
                                            <i class="fas fa-plus">+</i>
                                        </button>

                                        <span class="text-nowrap fw-bold me-3">Rp <?php echo number_format($item['subtotal']); ?></span>
                                    </form>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" name="cart_action" value="remove" class="btn btn-sm btn-danger" title="Hapus Item">
                                            <i class="fas fa-trash">X</i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-light fw-bold">
                            Total Pembayaran
                            <span>Rp <?php echo number_format($total_amount); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="text-center mb-4">
                <a href="menu.php" class="btn btn-outline-primary w-100">← Kembali Lihat Menu</a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5>Informasi Pemesanan</h5>
                </div>
                <div class="card-body">
                    <form action="cart.php" method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilihan Layanan</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_takeaway" id="dinein" value="0" checked>
                                <label class="form-check-label" for="dinein">Makan di Tempat (Dine-in)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_takeaway" id="takeaway" value="1">
                                <label class="form-check-label" for="takeaway">Bawa Pulang (Take Away)</label>
                            </div>
                        </div>

                        <div class="mb-3" id="table_number_input">
                            <label for="table_number" class="form-label">Nomor Meja</label>
                            <input type="text" class="form-control" id="table_number" name="table_number" required>
                        </div>

                        <div class="mb-3">
                            <label for="special_request" class="form-label">Permintaan Khusus</label>
                            <textarea class="form-control" id="special_request" name="special_request" rows="3"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Pilihan Pembayaran</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="Tunai">Tunai (Cash)</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="E-Wallet">E-Wallet (OVO/GOPAY/Dll)</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="submit_order" class="btn btn-danger btn-lg w-100" <?php echo empty($cart) ? 'disabled' : ''; ?>>
                            SUBMIT ORDER SEKARANG
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Logic untuk menampilkan/menyembunyikan input Nomor Meja
    document.addEventListener('DOMContentLoaded', function() {
        const dineInRadio = document.getElementById('dinein');
        const takeAwayRadio = document.getElementById('takeaway');
        const tableNumberInputDiv = document.getElementById('table_number_input');
        const tableNumberInput = document.getElementById('table_number');

        function handleServiceChange() {
            if (dineInRadio.checked) {
                tableNumberInputDiv.style.display = 'block';
                tableNumberInput.required = true;
                tableNumberInput.value = '';
                tableNumberInput.placeholder = 'Contoh: 05';
            } else if (takeAwayRadio.checked) {
                tableNumberInputDiv.style.display = 'none';
                tableNumberInput.required = false;
                tableNumberInput.value = 'Take Away'; 
            }
        }
        dineInRadio.addEventListener('change', handleServiceChange);
        takeAwayRadio.addEventListener('change', handleServiceChange);
        handleServiceChange(); 
    });
</script>
</body>
</html>