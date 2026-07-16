<?php
session_start();
include 'config/db_connect.php'; 

// Cek Keamanan: Jika admin belum login, arahkan ke halaman login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Logika untuk Mengubah Status Order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $sql = "UPDATE t_orders SET status = ? WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_status, $order_id]);
        $status_message = '<div class="alert alert-success">Status Order #' . htmlspecialchars($order_id) . ' berhasil diupdate menjadi **' . htmlspecialchars($new_status) . '**</div>';
    } catch (PDOException $e) {
        $status_message = '<div class="alert alert-danger">Gagal mengupdate status: ' . $e->getMessage() . '</div>';
    }
}

// ----------------------------------------------------
// 1. Ambil Data Pesanan Terbaru (Pending/Received)
// ----------------------------------------------------
$orders = [];
try {
    $sql_orders = "SELECT o.*, u.nama, u.email 
                   FROM t_orders o 
                   LEFT JOIN t_users u ON o.user_id = u.user_id
                   WHERE o.status IN ('Pending', 'Received')
                   ORDER BY o.order_date ASC";
    $stmt_orders = $pdo->query($sql_orders);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_orders = "Gagal mengambil data pesanan: " . $e->getMessage();
}

// ----------------------------------------------------
// 2. Ambil Data Pelanggan Baru (Profile Email)
// ----------------------------------------------------
$users = [];
try {
    $sql_users = "SELECT nama, email, created_at FROM t_users ORDER BY created_at DESC LIMIT 10";
    $stmt_users = $pdo->query($sql_users);
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_users = "Gagal mengambil data pengguna: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Resto Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
        <div class="collapse navbar-collapse" id="navbarNavAdmin">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_menu.php">Manajemen Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_about.php">About</a>
                </li>
            </ul>
        </div>
        <span class="navbar-text ms-auto me-3">
            Selamat datang, Admin!
        </span>
        <a class="btn btn-outline-light btn-sm" href="admin_logout.php">Logout</a>
    </div>
</nav>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Admin Dashboard</h2>
    <?php echo $status_message ?? ''; ?>

    ---

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5>🔥 Pesanan Masuk & Proses (Pending & Received)</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error_orders)): ?>
                        <div class="alert alert-danger"><?php echo $error_orders; ?></div>
                    <?php elseif (empty($orders)): ?>
                        <div class="alert alert-info">Tidak ada pesanan yang sedang diproses saat ini.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Waktu Masuk</th>
                                        <th>Pelanggan</th>
                                        <th>Meja/Takeaway</th>
                                        <th>Request</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <?php 
                                        $is_takeaway = $order['is_takeaway'] ? 'Take Away' : 'Meja ' . htmlspecialchars($order['table_number']);
                                        $customer_info = $order['nama'] ? htmlspecialchars($order['nama']) . ' (' . htmlspecialchars($order['email']) . ')' : 'Tamu (ID: ' . $order['user_id'] . ')';
                                        
                                        // Badge Status
                                        $status_badge = '';
                                        if ($order['status'] == 'Pending') $status_badge = 'bg-warning text-dark';
                                        elseif ($order['status'] == 'Received') $status_badge = 'bg-primary';
                                        elseif ($order['status'] == 'Completed') $status_badge = 'bg-success';
                                    ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo date('H:i:s / d M', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo $customer_info; ?></td>
                                        <td><?php echo $is_takeaway; ?></td>
                                        <td><small><?php echo nl2br(htmlspecialchars($order['special_request'] ?? '-')); ?></small></td>
                                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo $order['status']; ?></span></td>
                                        <td>
                                            <form method="POST" class="d-flex" onsubmit="return confirm('Yakin ingin mengubah status pesanan #<?php echo $order['order_id']; ?>?');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <select name="new_status" class="form-select form-select-sm me-2">
                                                    <option value="Pending" <?php echo ($order['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Received" <?php echo ($order['status'] == 'Received') ? 'selected' : ''; ?>>Received</option>
                                                    <option value="Completed">Completed</option>
                                                    <option value="Canceled">Canceled</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-info">Update</button>
                                            </form>
                                            <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-secondary mt-1 w-100">Detail</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    ---

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>👥 10 Pendaftar Akun Terbaru</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error_users)): ?>
                        <div class="alert alert-danger"><?php echo $error_users; ?></div>
                    <?php elseif (empty($users)): ?>
                        <div class="alert alert-info">Belum ada pelanggan yang mendaftar.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email Valid</th>
                                        <th>Tanggal Daftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('d M Y H:i:s', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>