<?php
require_once '../../config.php';
check_login();

$id_pr = intval($_GET['id']);
$query = mysqli_query($conn, "SELECT p.*, d.dept_name, 
                                     u1.fullname as name_dept, 
                                     u2.fullname as name_cc 
                              FROM htl_pur_pr p
                              JOIN htl_departments d ON p.id_department = d.id_department
                              LEFT JOIN htl_users u1 ON p.app_dept_user_id = u1.id_user
                              LEFT JOIN htl_users u2 ON p.app_cc_user_id = u2.id_user
                              WHERE p.id_pr = $id_pr");
$pr = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print PR</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px double #000; padding-bottom: 10px; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px; text-align: left; }
        .data-table th { background-color: #f2f2f2; }
        .sign-container { width: 100%; margin-top: 40px; display: table; }
        .sign-box { display: table-cell; width: 33%; text-align: center; }
        .space { height: 60px; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;"><?= $_SESSION['hotel_name']; ?></h2>
        <h3 style="margin:5px 0 0 0;">FORMULIR PURCHASE REQUEST (PR)</h3>
    </div>

    <table class="meta-table">
        <tr>
            <td width="15%"><strong>No. Dokumen</strong></td><td>: <?= $pr['pr_no']; ?></td>
            <td width="15%"><strong>Departemen</strong></td><td>: <?= $pr['dept_name']; ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Form</strong></td><td>: <?= date('d-M-Y', strtotime($pr['pr_date'])); ?></td>
            <td><strong>Alasan/Sifat</strong></td><td>: <?= $pr['remarks']; ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>No</th><th>Kode</th><th>Deskripsi Barang</th><th>Kuantitas</th><th>Est. Satuan</th><th>Total Estimasi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items = mysqli_query($conn, "SELECT sub.*, it.item_code, it.item_name, it.unit FROM htl_pur_pr_items sub JOIN htl_pur_items it ON sub.id_item = it.id_item WHERE sub.id_pr = $id_pr");
            $no = 1; $grand = 0;
            while($row = mysqli_fetch_assoc($items)): 
                $subt = $row['qty'] * $row['estimate_price']; $grand += $subt;
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['item_code']; ?></td>
                <td><?= $row['item_name']; ?> (<?= $row['unit']; ?>)</td>
                <td><?= $row['qty']; ?></td>
                <td>Rp <?= number_format($row['estimate_price'],0,',','.'); ?></td>
                <td>Rp <?= number_format($subt,0,',','.'); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="font-weight:bold; background-color:#f9f9f9;">
                <td colspan="5" style="text-align:right;">Total Anggaran Dana Diajukan:</td>
                <td>Rp <?= number_format($grand,0,',','.'); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="sign-container">
        <div class="sign-box">
            <p>Diajukan Oleh (Staff),</p><div class="space"></div>
            <p style="text-decoration:underline;">Mekanisme Sistem</p>
        </div>
        <div class="sign-box">
            <p>Disetujui Dept. Head,</p><div class="space"></div>
            <p style="text-decoration:underline; font-weight:bold;"><?= $pr['name_dept'] ?: '(.......................)'; ?></p>
        </div>
        <div class="sign-box">
            <p>Divalidasi Cost Control,</p><div class="space"></div>
            <p style="text-decoration:underline; font-weight:bold;"><?= $pr['name_cc'] ?: '(.......................)'; ?></p>
        </div>
    </div>
</body>
</html>