<?php include "../config/db.php"; ?>

<?php
$hasil = null;
if (isset($_POST['hitung'])) {
    $massa = $_POST['massa'];
    $mm_r  = $_POST['mm_r'];
    $mm_p  = $_POST['mm_p'];
    $k_r   = $_POST['k_r'];
    $k_p   = $_POST['k_p'];

    $mol_r = $massa / $mm_r;
    $mol_p = ($mol_r / $k_r) * $k_p;
    $massa_p = $mol_p * $mm_p;

    $hasil = number_format($massa_p, 2);

    $pdo->prepare(
        "INSERT INTO hasil_praktikum (id_praktikum, parameter, nilai, satuan)
         VALUES (1,'Massa Produk',?, 'gram')"
    )->execute([$massa_p]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stoikiometri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h4>⚗️ Stoikiometri – Massa Produk</h4>

    <form method="post" class="row g-3">
        <div class="col-md-3">
            <label>Massa Reaktan (g)</label>
            <input type="number" step="0.01" name="massa" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label>MM Reaktan</label>
            <input type="number" step="0.01" name="mm_r" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label>MM Produk</label>
            <input type="number" step="0.01" name="mm_p" class="form-control" required>
        </div>
        <div class="col-md-1">
            <label>K Reaktan</label>
            <input type="number" name="k_r" class="form-control" required>
        </div>
        <div class="col-md-1">
            <label>K Produk</label>
            <input type="number" name="k_p" class="form-control" required>
        </div>

        <div class="col-12">
            <button name="hitung" class="btn btn-primary">Hitung</button>
            <a href="../dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>
    </form>

    <?php if ($hasil): ?>
        <div class="alert alert-success mt-3">
            Massa produk teoritis = <strong><?= $hasil ?> gram</strong>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
