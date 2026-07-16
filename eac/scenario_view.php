<?php
require 'config.php'; require 'inc/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sc = mysqli_prepare($conn, 'SELECT * FROM scenarios WHERE id=?');
mysqli_stmt_bind_param($sc, 'i', $id);
mysqli_stmt_execute($sc);
$res_s = mysqli_stmt_get_result($sc);
$s = mysqli_fetch_assoc($res_s);
if (!$s) { echo 'Scenario not found'; exit; }

// compute delta0 safely
$delta0 = null;
if ($s['pmax'] > 0 && abs($s['pm'] / $s['pmax']) <= 1) {
    $delta0 = asin($s['pm'] / $s['pmax']);
}
$res = compute_eac($s['pm'], $s['pmax'], $s['pmax_fault'], $delta0);

// get latest simulation if exists
$simq = mysqli_prepare($conn, 'SELECT * FROM simulations WHERE scenario_id=? ORDER BY created_at DESC LIMIT 1');
mysqli_stmt_bind_param($simq, 'i', $id);
mysqli_stmt_execute($simq);
$res_sim = mysqli_stmt_get_result($simq);
$simr = mysqli_fetch_assoc($res_sim);

$tseries = array();
$dt = 0.002;
if ($simr) {
    $tseries = json_decode($simr['tseries'], true);
    $dt = $simr['dt'] ?? $dt;
} else {
    $simdata = simulate_smib_rk4($s['pm'], $s['pmax'], $s['pmax_fault'], $delta0, $s['H']?:5.0, 50.0, 5.0, 0.002, 0.2);
    $tseries = $simdata['data'];
    $dt = $simdata['dt'];
}

$points = array();
$step = 300;
for ($i=0;$i<=$step;$i++){
    $d = $i * pi() / $step;
    $points[] = array('d'=>$d,'Pe_normal'=>$s['pmax'] * sin($d),'Pe_fault'=>$s['pmax_fault'] * sin($d));
}
?>
<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Scenario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-dark text-light">
<div class="container py-4">
  <a class="btn btn-link" href="index.php">← Kembali</a>
  <div class="card p-3 bg-dark text-light">
    <h4><?php echo htmlspecialchars($s['name']); ?> <small class="text-muted">(dibuat: <?php echo $s['created_at']; ?>)</small></h4>
    <div class="row">
      <div class="col-md-6">
        <ul class="list-unstyled">
          <li><strong>Pm:</strong> <?php echo $s['pm']; ?></li>
          <li><strong>Pmax:</strong> <?php echo $s['pmax']; ?></li>
          <li><strong>Pmax fault:</strong> <?php echo $s['pmax_fault']; ?></li>
          <li><strong>delta0:</strong> <?php echo $delta0!==null?number_format($delta0,4):'-'; ?></li>
          <li><strong>delta_cr:</strong> <?php echo isset($res['delta_cr'])?number_format($res['delta_cr'],4):'-'; ?></li>
          <li><strong>A1:</strong> <?php echo isset($res['A1'])?number_format($res['A1'],6):'-'; ?></li>
          <li><strong>A2max:</strong> <?php echo isset($res['A2max'])?number_format($res['A2max'],6):'-'; ?></li>
          <li><strong>Stabil:</strong> <?php echo isset($res['is_stable'])?($res['is_stable']?'<span class="badge bg-success">Ya</span>':'<span class="badge bg-danger">Tidak</span>'):'-';?></li>
        </ul>
        <div class="mt-2">
          <a href="export_csv.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-success">Download CSV (input + hasil + time-series)</a>
        </div>
      </div>
      <div class="col-md-6">
        <canvas id="eacChart"></canvas>
      </div>
    </div>

    <hr>
    <h6>Simulasi δ(t) & ω(t)</h6>
    <div class="row">
      <div class="col-md-12">
        <canvas id="simChart"></canvas>
      </div>
    </div>
    <div class="mt-2">
      <button id="downloadSimPng" class="btn btn-outline-primary btn-sm">Download Grafik (PNG)</button>
    </div>
  </div>
</div>

<script>
const data = <?php echo json_encode($points); ?>;
const labels = data.map(p => p.d.toFixed(3));
const peNormal = data.map(p => p.Pe_normal);
const peFault = data.map(p => p.Pe_fault);
const pmLine = new Array(labels.length).fill(<?php echo json_encode($s['pm']); ?>);

const ctx = document.getElementById('eacChart').getContext('2d');
const eacChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{ label: 'Pe (normal) = Pmax*sinδ', data: peNormal, borderWidth: 2, tension: 0.2, borderColor: '#4fc3f7' },
                   { label: 'Pe (fault) = Pmax_fault*sinδ', data: peFault, borderDash: [6,4], borderWidth: 2, tension: 0.2, borderColor: '#f48fb1' },
                   { label: 'Pm (konstan)', data: pmLine, borderDash: [2,4], borderWidth: 1, borderColor: '#cfd8dc' }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

const tseries = <?php echo json_encode($tseries); ?>;
const tlabels = tseries.map(p => p.t.toFixed(3));
const deltas = tseries.map(p => p.delta);
const omegas = tseries.map(p => p.omega);
const ctx2 = document.getElementById('simChart').getContext('2d');
const simChart = new Chart(ctx2, {
    type: 'line',
    data: {
        labels: tlabels,
        datasets: [{ label: 'δ (rad)', data: deltas, borderWidth: 1, tension: 0.1, borderColor: '#aed581' }, { label: 'ω (pu)', data: omegas, borderWidth: 1, tension: 0.1, yAxisID: 'y1', borderColor: '#ffb74d' }]
    },
    options: {
        scales: { y: { beginAtZero: false }, y1: { position: 'right' } },
        interaction: { mode: 'index', intersect: false },
        responsive: true
    }
});

document.getElementById('downloadSimPng').addEventListener('click', function(){
    const a = document.createElement('a');
    a.href = simChart.toBase64Image('image/png', 1);
    a.download = 'simulation_<?php echo $s['id']; ?>.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
});
</script>
</body>
</html>
