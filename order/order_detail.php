<?php
session_start();
include 'config/db_connect.php';

// Pastikan ada order_id
$order_id = (int)($_GET['id'] ?? 0);
if ($order_id === 0) {
    header("Location: index.php"); // Atau halaman daftar order
    exit;
}

// Cek Keamanan: Pastikan user login atau admin login yang melihat
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$user_id = $_SESSION['user_id'] ?? null;
$access_granted = false;

$order = [];
$details = [];

try {
    // 1. Ambil Data Header Order
    $sql_order = "SELECT o.*, u.nama, u.email 
                  FROM t_orders o 
                  LEFT JOIN t_users u ON o.user_id = u.user_id
                  WHERE o.order_id = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Cek Izin Akses
        if ($is_admin || ($user_id !== null && $order['user_id'] == $user_id)) {
            $access_granted = true;

            // 2. Ambil Data Detail Item Order
            $sql_details = "SELECT od.*, mi.nama_item 
                            FROM t_order_details od 
                            JOIN t_menu_items mi ON od.item_id = mi.item_id
                            WHERE od.order_id = ?";
            $stmt_details = $pdo->prepare($sql_details);
            $stmt_details->execute([$order_id]);
            $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $error_message = "Gagal memuat detail order: " . $e->getMessage();
}

if (!$access_granted) {
    $error_message = "Anda tidak memiliki izin untuk melihat pesanan ini atau pesanan tidak ditemukan.";
}

$total_amount = array_sum(array_column($details, 'subtotal'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Order #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Detail Pesanan #<?php echo $order_id; ?></h2>
        <?php if ($is_admin): ?>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Kembali ke Admin Dashboard</a>
        <?php else: ?>
             <a href="index.php" class="btn btn-secondary">← Kembali ke Beranda</a>
        <?php endif; ?>
    </div>
    <hr>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php elseif (!empty($order)): ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">Informasi Dasar</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Status:</strong> <span class="badge bg-<?php 
                            if ($order['status'] == 'Pending') echo 'warning text-dark';
                            elseif ($order['status'] == 'Received') echo 'info';
                            elseif ($order['status'] == 'Completed') echo 'success';
                            else echo 'danger';
                        ?>"><?php echo $order['status']; ?></span></li>
                        <li class="list-group-item"><strong>Waktu Order Dibuat:</strong> <?php echo date('d M Y H:i:s', strtotime($order['order_date'])); ?></li>
                        <li class="list-group-item"><strong>Waktu Diminta:</strong> <?php echo date('d M Y H:i:s', strtotime($order['order_time'])); ?></li>
                        <li class="list-group-item"><strong>Layanan:</strong> <?php echo $order['is_takeaway'] ? 'Take Away' : 'Makan di Tempat (Meja ' . $order['table_number'] . ')'; ?></li>
                        <li class="list-group-item"><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></li>
                        <li class="list-group-item"><strong>Permintaan Khusus:</strong> <?php echo nl2br(htmlspecialchars($order['special_request'] ?? '-')); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-success text-white">Informasi Pelanggan</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Nama:</strong> <?php echo htmlspecialchars($order['nama'] ?? 'Tamu'); ?></li>
                        <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? '-'); ?></li>
                        <li class="list-group-item"><strong>User ID:</strong> <?php echo $order['user_id'] ?? '-'; ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <h4 class="mt-4">Item Pesanan</h4>
        <div class="table-responsive mb-5">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Nama Item</th>
                        <th>Harga Satuan</th>
                        <th>Kuantitas</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detail['nama_item']); ?></td>
                        <td>Rp <?php echo number_format($detail['price_at_order']); ?></td>
                        <td><?php echo $detail['quantity']; ?></td>
                        <td>Rp <?php echo number_format($detail['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary fw-bold">
                        <td colspan="3" class="text-end">Total Pembayaran</td>
                        <td>Rp <?php echo number_format($total_amount); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>