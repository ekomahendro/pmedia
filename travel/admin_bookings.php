<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Logika Hapus Pesanan (Jika ada di sistem Anda sebelumnya)
if(isset($_GET['hapus'])){
    if($_SESSION['role'] == 'Super Admin') {
        $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
        mysqli_query($conn, "DELETE FROM tra_pesanan WHERE id_pesanan='$id_hapus'");
        echo "<script>alert('Data pesanan berhasil dihapus!'); window.location='admin_bookings.php';</script>";
        exit;
    } else {
        echo "<script>alert('Akses Ditolak! Hanya Super Admin yang dapat menghapus pesanan.'); window.location='admin_bookings.php';</script>";
        exit;
    }
}

// Mengambil parameter filter jika ada
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search_query = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

// Perhitungan Counter Ringkasan (Total, Pending, Confirmed, Cancelled)
$count_all = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM tra_pesanan"))[0];
$count_pending = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM tra_pesanan WHERE status='Pending'"))[0];
$count_confirmed = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM tra_pesanan WHERE status='Confirmed'"))[0];
$count_cancelled = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM tra_pesanan WHERE status='Cancelled'"))[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - Maluku Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Roboto, sans-serif; }
        .sidebar { background: #1e293b; min-height: 100vh; color: #fff; position: sticky; top: 0; }
        .sidebar .nav-link { color: #94a3b8; border-radius: 8px; margin-bottom: 5px; padding: 10px 15px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #334155; color: #fff; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .counter-card { transition: transform 0.2s; cursor: pointer; text-decoration: none; }
        .counter-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Memanggil Navigasi Sidebar (Mobile & Desktop Support) -->
        <?php include 'sidebar.php'; ?>

        <!-- Kolom Konten Utama -->
        <div class="col-12 col-md-9 col-lg-10 p-3 p-md-4 p-lg-5">
            
            <!-- Top Dashboard Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-0">Pesanan Masuk (Bookings)</h4>
                    <small class="text-muted">Pantau data reservasi masuk dan ubah status pemesanan.</small>
                </div>
                <div class="text-end text-md-start">
                    <span class="badge bg-light text-dark border p-2"><i class="bi bi-clock-history me-1 text-primary"></i> Real-time Manifest</span>
                </div>
            </div>

            <!-- Row Ringkasan / Counter Cards (Responsive Grid) -->
            <div class="row mb-4 g-3">
                <div class="col-6 col-lg-3">
                    <a href="admin_bookings.php" class="card card-custom p-3 bg-white text-dark counter-card d-flex flex-row align-items-center justify-content-between shadow-sm">
                        <div>
                            <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 0.75rem;">Semua Pesanan</small>
                            <span class="fs-3 fw-bold"><?= $count_all ?></span>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 d-none d-sm-block"><i class="bi bi-collection fs-4"></i></div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="admin_bookings.php?status=Pending" class="card card-custom p-3 bg-white text-dark counter-card d-flex flex-row align-items-center justify-content-between shadow-sm">
                        <div>
                            <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 0.75rem;">Pending</small>
                            <span class="fs-3 fw-bold text-warning"><?= $count_pending ?></span>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3 d-none d-sm-block"><i class="bi bi-hourglass-split fs-4"></i></div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="admin_bookings.php?status=Confirmed" class="card card-custom p-3 bg-white text-dark counter-card d-flex flex-row align-items-center justify-content-between shadow-sm">
                        <div>
                            <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 0.75rem;">Confirmed</small>
                            <span class="fs-3 fw-bold text-success"><?= $count_confirmed ?></span>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 d-none d-sm-block"><i class="bi bi-check-circle fs-4"></i></div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="admin_bookings.php?status=Cancelled" class="card card-custom p-3 bg-white text-dark counter-card d-flex flex-row align-items-center justify-content-between shadow-sm">
                        <div>
                            <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 0.75rem;">Cancelled</small>
                            <span class="fs-3 fw-bold text-danger"><?= $count_cancelled ?></span>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-3 d-none d-sm-block"><i class="bi bi-x-circle fs-4"></i></div>
                    </a>
                </div>
            </div>

            <!-- Card Utama List Tabel -->
            <div class="card card-custom border-0 bg-white shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold text-dark m-0">Daftar Manifest Penumpang</h5>
                        <?php if(!empty($filter_status)): ?>
                            <span class="badge bg-secondary mt-1">Filter Status: <?= $filter_status ?> <a href="admin_bookings.php" class="text-white ms-1 text-decoration-none">×</a></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Form Pencarian Nama/ID -->
                    <form method="GET" action="" class="d-flex" style="max-width: 320px;">
                        <?php if(!empty($filter_status)): ?>
                            <input type="hidden" name="status" value="<?= $filter_status ?>">
                        <?php endif; ?>
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" class="form-control bg-light border-end-0" placeholder="Cari nama pemesan..." value="<?= htmlspecialchars($search_query) ?>">
                            <button class="btn btn-light border border-start-0 text-muted" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <!-- Pembungkus Tabel Responsive (Anti-Patah & Support Swipe Horizontal di HP) -->
                <div class="table-responsive shadow-sm rounded-3">
                    <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3" style="width: 100px;">ID Pesanan</th>
                                <th>Data Pemesan</th>
                                <th>Paket Wisata</th>
                                <th>Tgl Berangkat</th>
                                <th class="text-center">Pax</th>
                                <th>Keterangan Tambahan</th>
                                <th>Status Transaksi</th>
                                <?php if($_SESSION['role'] == 'Super Admin'): ?>
                                    <th class="pe-3 text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Membangun Query dinamis berdasarkan Filter pencarian dan Filter status tab
                            $conditions = array();
                            if(!empty($filter_status)) {
                                $conditions[] = "p.status = '$filter_status'";
                            }
                            if(!empty($search_query)) {
                                $conditions[] = "(p.nama LIKE '%$search_query%' OR p.id_pesanan LIKE '%$search_query%')";
                            }
                            
                            $where_clause = "";
                            if(count($conditions) > 0) {
                                $where_clause = "WHERE " . implode(" AND ", $conditions);
                            }
                            
                            $query_string = "SELECT p.*, pkt.nama_paket FROM tra_pesanan p 
                                             JOIN tra_paket pkt ON p.id_paket = pkt.id_paket 
                                             $where_clause 
                                             ORDER BY p.id_pesanan DESC";
                                             
                            $query_booking = mysqli_query($conn, $query_string);
                            
                            if(mysqli_num_rows($query_booking) > 0) {
                                while($b = mysqli_fetch_array($query_booking)) { 
                                    
                                    // Pewarnaan Dinamis Dropdown Status
                                    $status = $b['status'];
                                    $badge_class = 'bg-warning-subtle text-warning border-warning-subtle';
                                    if($status == 'Confirmed') $badge_class = 'bg-success-subtle text-success border-success-subtle';
                                    if($status == 'Cancelled') $badge_class = 'bg-danger-subtle text-danger border-danger-subtle';
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark">#<?= $b['id_pesanan'] ?></span>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;"><?= isset($b['tgl_booking']) ? date('d/m/y H:i', strtotime($b['tgl_booking'])) : '' ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($b['nama_pelanggan']) ?></div>
                                            <small class="text-muted d-block"><i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($b['no_telp']) ?></small>
                                            <small class="text-muted d-block" style="font-size: 0.8rem;"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($b['email']) ?></small>
                                        </td>
                                        <td><div class="text-dark fw-semibold" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($b['nama_paket']) ?></div></td>
                                        <td><span><i class="bi bi-calendar-check me-1 text-primary"></i><?= date('d M Y', strtotime($b['tgl_keberangkatan'])) ?></span></td>
                                        <td class="text-center fw-bold text-dark"><?= $b['jumlah_peserta'] ?> <span class="fw-normal text-muted" style="font-size: 0.8rem;">pax</span></td>
                                        <td>
                                            <!-- Melipat Keterangan panjang agar tabel tidak melar -->
                                            <div class="text-muted text-wrap" style="max-width: 220px; font-size: 0.85rem; max-height: 45px; overflow-y: auto;">
                                                <?= !empty($b['keterangan']) ? htmlspecialchars($b['keterangan']) : '<span class="text-muted small">-</span>' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <!-- Form update status otomatis sewaktu diganti admin -->
                                            <form action="update_status_booking.php" method="POST" class="m-0">
                                                <input type="hidden" name="id_pesanan" value="<?= $b['id_pesanan'] ?>">
                                                <select name="status_baru" onchange="this.form.submit()" class="form-select form-select-sm fw-semibold border <?= $badge_class ?>" style="width: 135px; cursor: pointer;">
                                                    <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                                    <option value="Confirmed" <?= $status == 'Confirmed' ? 'selected' : '' ?>>✅ Confirmed</option>
                                                    <option value="Cancelled" <?= $status == 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <?php if($_SESSION['role'] == 'Super Admin'): ?>
                                            <td class="pe-3 text-center">
                                                <a href="?hapus=<?= $b['id_pesanan'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus permanen data pesanan ini?')" title="Hapus Data"><i class="bi bi-trash"></i></a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="<?= ($_SESSION['role'] == 'Super Admin') ? '8' : '7' ?>" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i> Data pesanan tidak ditemukan.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <!-- End Kolom Konten Utama -->
    </div> <!-- End Row -->
</div> <!-- End Container fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>