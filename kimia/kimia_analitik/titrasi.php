<?php include "../config/db.php"; ?>

<?php
$hasil = null;
if (isset($_POST['hitung'])) {
    $M_titran = $_POST['M_titran'];
    $V_titran = $_POST['V_titran'];
    $V_analit = $_POST['V_analit'];

    // M1V1 = M2V2
    $M_analit = ($M_titran * $V_titran) / $V_analit;

    $hasil = number_format($M_analit, 4);

    $stmt = $pdo->prepare(
        "INSERT INTO hasil_praktikum (id_praktikum, parameter, nilai, satuan)
         VALUES (1,'Konsentrasi Analit',?, 'M')"
    );
    $stmt->execute([$M_analit]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Titrasi Asam Basa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h4>🔬 Simulasi Titrasi Asam Basa</h4>

    <form method="post" class="row g-3 mt-2">
        <div class="col-md-4">
            <label>M Titran (M)</label>
            <input type="number" step="0.0001" name="M_titran" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label>V Titran (mL)</label>
            <input type="number" step="0.01" name="V_titran" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label>V Analit (mL)</label>
            <input type="number" step="0.01" name="V_analit" class="form-control" required>
        </div>

        <div class="col-12">
            <button name="hitung" class="btn btn-success">Hitung</button>
            <a href="../dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>
    </form>

    <?php if ($hasil): ?>
        <div class="alert alert-info mt-3">
            Konsentrasi Analit = <strong><?= $hasil ?> M</strong>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
