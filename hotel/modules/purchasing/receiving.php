<?php
require_once '../../config.php';
check_login();

if (isset($_GET['receive_po'])) {
    $po_id = intval($_GET['receive_po']);
    $recv_num = "RCV-" . date("Ymd") . "-" . rand(100, 999);
    $uid = $_SESSION['user_id'];
    $today = date("Y-m-d");
    
    mysqli_query($conn, "INSERT INTO htl_pur_receiving (recv_number, id_po, id_user_received, received_date) VALUES ('$recv_num', $po_id, $uid, '$today')");
    $id_recv = mysqli_insert_id($conn);
    
    $po_items = mysqli_query($conn, "SELECT * FROM htl_pur_po_detail WHERE id_po = $po_id");
    while($item = mysqli_fetch_assoc($po_items)) {
        $id_item = $item['id_item'];
        $qty_incoming = $item['qty_ordered'];
        $price_incoming = $item['unit_price'];
        
        // 1. Catat detail log receiving
        mysqli_query($conn, "INSERT INTO htl_pur_receiving_detail (id_receiving, id_item, qty_received, final_price) 
                             VALUES ($id_recv, $id_item, $qty_incoming, $price_incoming)");
        
        // 2. Ambil data master barang lama untuk kalkulasi moving average price
        $m_res = mysqli_query($conn, "SELECT stock_qty, actual_price FROM htl_pur_items WHERE id_item = $id_item");
        $master = mysqli_fetch_assoc($m_res);
        $old_stock = $master['stock_qty'];
        $old_actual_price = $master['actual_price'];
        
        // RUMUS MOVING AVERAGE PRICE (Kalkulasi Nilai Aset Aktual)
        $total_stok_baru = $old_stock + $qty_incoming;
        $new_actual_price = (($old_stock * $old_actual_price) + ($qty_incoming * $price_incoming)) / $total_stok_baru;
        
        // 3. Update database master barang (Stok + Last Price + Actual Price)
        mysqli_query($conn, "UPDATE htl_pur_items SET 
                             stock_qty = $total_stok_baru, 
                             last_price = $price_incoming, 
                             actual_price = $new_actual_price 
                             WHERE id_item = $id_item");
    }
    
    mysqli_query($conn, "UPDATE htl_pur_po SET status_approval = 'Received & Closed' WHERE id_po = $po_id");
    header("Location: receiving.php?success=1"); exit();
}

$po_active = mysqli_query($conn, "SELECT * FROM htl_pur_po WHERE status_approval = 'Approved'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Receiving & Pricing Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <a href="index.php" class="btn btn-sm btn-secondary mb-3">Menu Utama</a>
    <h2 class="fw-bold mb-4">3. Receiving (Update Stok & Rekam Histori Harga)</h2>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold">Daftar PO yang Berlayar/Menunggu Kedatangan Truk</div>
        <div class="card-body">
            <?php if(mysqli_num_rows($po_active) == 0): ?>
                <p class="text-muted small mb-0">Semua pesanan aman. Belum ada PO baru yang siap dikirim vendor.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0 text-center">
                        <thead><tr><th>Nomor PO</th><th>Aksi Penerimaan Dokumen & Fisik</th></tr></thead>
                        <tbody>
                            <?php while($p = mysqli_fetch_assoc($po_active)): ?>
                            <tr>
                                <td class="fw-bold font-monospace"><?= $p['po_number']; ?></td>
                                <td><a href="receiving.php?receive_po=<?= $p['id_po']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Barang masuk akan otomatis merubah nominal Actual Price & Last Price di modul Master. Lanjutkan?')">Terima & Update Rekor Harga</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>