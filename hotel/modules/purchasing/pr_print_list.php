<?php
require_once '../../config.php';
check_login();

$start = mysqli_real_escape_string($conn, $_GET['start_date']);
$end   = mysqli_real_escape_string($conn, $_GET['end_date']);

$sql = "SELECT p.*, d.dept_name, u1.fullname as name_dept, u2.fullname as name_cc 
        FROM htl_pur_pr p 
        JOIN htl_departments d ON p.id_department = d.id_department
        LEFT JOIN htl_users u1 ON p.app_dept_user_id = u1.id_user
        LEFT JOIN htl_users u2 ON p.app_cc_user_id = u2.id_user
        WHERE p.pr_date BETWEEN '$start' AND '$end' ORDER BY p.pr_date ASC";
$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rekapitulasi PR</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background: #e3e3e3; }
    </style>
</head>
<body>
    <h3 style="margin:0; text-align:center;"><?= $_SESSION['hotel_name']; ?></h3>
    <h4 style="margin:5px 0; text-align:center;">LAPORAN REKAPITULASI PURCHASE REQUEST (PR) PERIODE <?= date('d/M/Y', strtotime($start)); ?> s/d <?= date('d/M/Y', strtotime($end)); ?></h4>
    <table>
        <thead>
            <tr>
                <th>No</th><th>No PR</th><th>Tanggal</th><th>Departemen</th><th>Alasan/Remarks</th><th>Dept Head Approval</th><th>Cost Control Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; while($r = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?= $no++; ?></td>
                <td style="font-family:monospace; font-weight:bold;"><?= $r['pr_no']; ?></td>
                <td><?= date('d-m-Y', strtotime($r['pr_date'])); ?></td>
                <td><?= $r['dept_name']; ?></td>
                <td><?= $r['remarks']; ?></td>
                <td><?= ($r['status_dept']=='approved') ? '✔️ APV ['.$r['name_dept'].']' : '❌ Pending'; ?></td>
                <td><?= ($r['status_cc']=='approved') ? '🔒 LOCKED ['.$r['name_cc'].']' : '❌ Pending'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>