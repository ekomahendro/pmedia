<?php
include "../config/db.php";
$data = $pdo->query("SELECT parameter, nilai FROM hasil_praktikum")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Grafik Praktikum</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container mt-4">
    <h4>📊 Grafik Hasil Praktikum</h4>
    <canvas id="grafik"></canvas>
    <br>
    <a href="../dashboard.php">⬅ Kembali</a>
</div>

<script>
const data = {
    labels: <?= json_encode(array_column($data,'parameter')) ?>,
    datasets: [{
        data: <?= json_encode(array_column($data,'nilai')) ?>
    }]
};

new Chart(document.getElementById('grafik'), {
    type: 'bar',
    data: data
});
</script>

</body>
</html>
