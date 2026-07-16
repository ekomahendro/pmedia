<?php
require_once 'config.php';

if (!isset($_SESSION['login_milad'])) {
    die("Akses ditolak");
}

$sql = "SELECT * FROM transaksi ORDER BY tanggal ASC,id ASC";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll();

$total_masuk = 0;
$total_keluar = 0;

foreach($data as $row){
    if($row['jenis']=='masuk'){
        $total_masuk += $row['nominal'];
    }else{
        $total_keluar += $row['nominal'];
    }
}

$saldo = $total_masuk - $total_keluar;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan Keuangan Gebyar Milad XV</title>

<style>

body{
    font-family: Arial, Helvetica, sans-serif;
    font-size:12px;
    color:#000;
}

.header{
    text-align:center;
    margin-bottom:15px;
}

.header h2{
    margin:0;
}

.header h3{
    margin:5px 0;
}

.info{
    margin-bottom:15px;
}

.summary{
    width:100%;
    margin-bottom:15px;
}

.summary td{
    padding:5px;
    border:1px solid #000;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#eaeaea;
}

table th,
table td{
    border:1px solid #000;
    padding:5px;
}

.text-right{
    text-align:right;
}

.text-center{
    text-align:center;
}

.masuk{
    color:green;
    font-weight:bold;
}

.keluar{
    color:red;
    font-weight:bold;
}

@media print{
    @page{
        size:A4 landscape;
        margin:10mm;
    }
}
</style>
</head>
<body onload="window.print()">

<div class="header">
    <h2>GEBYAR MILAD XV</h2>
    <h3>MT. MUALLAF TAUFIQIYAH</h3>
    <div>
        Jl Tukad Ayung No 2 Kediri Tabanan<br>
        Telp : 081371578332
    </div>
</div>

<table class="summary">
<tr>
    <td width="33%">
        <b>Total Uang Masuk</b><br>
        Rp <?= number_format($total_masuk,0,',','.') ?>
    </td>

    <td width="33%">
        <b>Total Uang Keluar</b><br>
        Rp <?= number_format($total_keluar,0,',','.') ?>
    </td>

    <td width="34%">
        <b>Saldo Akhir</b><br>
        Rp <?= number_format($saldo,0,',','.') ?>
    </td>
</tr>
</table>

<table>
<thead>
<tr>
    <th width="4%">No</th>
    <th width="10%">Tanggal</th>
    <th width="12%">No Kuitansi</th>
    <th width="8%">Jenis</th>
    <th width="15%">Sumber Dana</th>
    <th>Keterangan</th>
    <th width="12%">Masuk</th>
    <th width="12%">Keluar</th>
</tr>
</thead>

<tbody>

<?php
$no=1;

foreach($data as $row):
?>

<tr>
    <td class="text-center"><?= $no++ ?></td>

    <td>
        <?= date('d-m-Y',strtotime($row['tanggal'])) ?>
    </td>

    <td>
        <?= $row['nomor_kuitansi'] ?>
    </td>

    <td class="text-center">
        <?= ucfirst($row['jenis']) ?>
    </td>

    <td>
        <?= htmlspecialchars($row['sumber_dana']) ?>
    </td>

    <td>
        <?= htmlspecialchars($row['keperluan']) ?>
    </td>

    <td class="text-right masuk">
        <?php
        if($row['jenis']=='masuk'){
            echo number_format($row['nominal'],0,',','.');
        }
        ?>
    </td>

    <td class="text-right keluar">
        <?php
        if($row['jenis']=='keluar'){
            echo number_format($row['nominal'],0,',','.');
        }
        ?>
    </td>
</tr>

<?php endforeach; ?>

</tbody>

<tfoot>

<tr style="font-weight:bold;background:#f0f0f0;">
    <td colspan="6" class="text-right">
        TOTAL
    </td>

    <td class="text-right">
        <?= number_format($total_masuk,0,',','.') ?>
    </td>

    <td class="text-right">
        <?= number_format($total_keluar,0,',','.') ?>
    </td>
</tr>

<tr style="font-weight:bold;background:#d4edda;">
    <td colspan="6" class="text-right">
        SALDO AKHIR
    </td>

    <td colspan="2" class="text-right">
        Rp <?= number_format($saldo,0,',','.') ?>
    </td>
</tr>

</tfoot>

</table>

<br><br>

<table style="border:none">
<tr>
<td style="border:none;width:60%"></td>

<td style="border:none;text-align:center">
    Tabanan,
    <?= date('d-m-Y') ?>
    <br><br><br><br><br>

    <b>BENDAHARA</b>
</td>
</tr>
</table>

</body>
</html>