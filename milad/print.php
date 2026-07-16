<?php
require_once 'config.php';

if (!isset($_SESSION['login_milad'])) {
    die("Akses ditolak!");
}

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ? AND jenis = 'masuk'");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Data transaksi kuitansi tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php 
        // Mengubah karakter yang tidak aman untuk nama file (seperti /) menjadi tanda hubung (-)
        $safe_no_kuitansi = str_replace('/', '-', $trx['nomor_kuitansi']);
        $safe_sumber_dana = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $trx['sumber_dana']);
        $filename = $safe_no_kuitansi . '_' . $safe_sumber_dana;
    ?>
    <title><?= $filename ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; padding: 20px; background: #fff; }
        .kuitansi-box { width: 800px; padding: 30px; border: 3px double #2e7d32; position: relative; margin: 0 auto; background-color: #fafdfa; }
        
        .header { display: flex; align-items: center; border-bottom: 2px solid #2e7d32; padding-bottom: 15px; margin-bottom: 20px; }
        .logo { width: 90px; height: 90px; object-fit: contain; margin-right: 20px; }
        .header-text { flex-grow: 1; }
        .header-text h2 { margin: 0; color: #2e7d32; font-size: 22px; font-weight: bold; }
        .header-text p { margin: 4px 0 0 0; font-size: 12px; color: #555; }
        
        .title-kuitansi { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; color: #111; }
        .nomor-kuitansi { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 25px; }

        table.content-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.content-table td { padding: 12px 4px; vertical-align: top; }
        table.content-table td.label { width: 180px; font-style: italic; color: #444; }
        table.content-table td.colon { width: 15px; text-align: center; }
        table.content-table td.value { border-bottom: 1px dashed #ccc; font-weight: bold; }
        
        .terbilang-box { background: #e8f5e9; padding: 10px; font-style: italic; font-weight: bold; border-left: 5px solid #2e7d32; margin-top: 5px; }

        .footer-section { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; }
        .nominal-box { font-size: 22px; font-weight: bold; background: #2e7d32; color: #fff; padding: 12px 25px; border-radius: 4px; display: inline-block; }
        
        .ttd-area { text-align: center; width: 220px; position: relative; }
        .ttd-space { height: 100px; position: relative; display: flex; align-items: center; justify-content: center; }
        
        .img-stempel { position: absolute; width: 110px; left: 15px; top: 5px; opacity: 0.85; z-index: 2; }
        .img-ttd { position: absolute; width: 130px; z-index: 1; }
        
        .border-nama { border-bottom: 1px solid #333; font-weight: bold; padding-bottom: 2px; }

        @media print {
            body { padding: 0; background: #fff; }
            .kuitansi-box { border: 2px solid #000; background: #fff; box-shadow: none; }
            .terbilang-box { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .nominal-box { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="kuitansi-box">
    <div class="header">
        <img src="logo.jpeg" alt="Logo MT" class="logo" onerror="this.style.display='none'">
        <div class="header-text">
            <h2>PANITIA GEBYAR MILAD MT. MUALLAF TAUFIQIYAH XV</h2>
            <p>Sekretariat: Jl Tukad Ayung No 2 Kediri, Tabanan, Bali</p>
            <p>Hubungi Kontak Telp/WA: 081371578332</p>
        </div>
    </div>

    <div class="title-kuitansi">KUITANSI BUKTI PENERIMAAN</div>
    <div class="nomor-kuitansi">Nomor: &nbsp;<?= htmlspecialchars($trx['nomor_kuitansi']) ?></div>

    <table class="content-table">
        <tr>
            <td class="label">Telah Diterima Dari</td>
            <td class="colon">:</td>
            <td class="value" style="font-size: 16px; text-transform: uppercase;"><?= htmlspecialchars($trx['sumber_dana']) ?></td>
        </tr>
        <tr>
            <td class="label">Uang Sejumlah</td>
            <td class="colon">:</td>
            <td class="value">
                <div class="terbilang-box"># <?= terbilang($trx['nominal']) ?> #</div>
            </td>
        </tr>
        <tr>
            <td class="label">Untuk Pembayaran</td>
            <td class="colon">:</td>
            <td class="value" style="font-weight: normal; font-style: italic;"><?= htmlspecialchars($trx['keperluan']) ?></td>
        </tr>
    </table>

    <div class="footer-section">
        <div>
            <div class="nominal-box">
                Rp <?= number_format($trx['nominal'], 0, ',', '.') ?>,-
            </div>
        </div>
        <div class="ttd-area">
            <div>Tabanan, <?= date('d F 2026', strtotime($trx['tanggal'])) ?></div>
            <div class="text-muted" style="font-size: 11px; margin-top:2px;">Bendahara Panitia</div>
            
            <div class="ttd-space">
                <img src="stemp.png" alt="Stempel" class="img-stempel" onerror="this.style.display='none'">
                <img src="ttb2.png" alt="Tanda Tangan" class="img-ttd" onerror="this.style.display='none'">
            </div>
            
            <div class="border-nama">Sulihati</div>
        </div>
    </div>
</div>

<script>
    window.print();
</script>
</body>
</html>