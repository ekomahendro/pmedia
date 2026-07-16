<?php
// installer_eac.php (perbaikan)
// Installer interaktif untuk men-deploy aplikasi EAC ke folder "eac/" di public_html
// Pilihan: mengisi config.php otomatis; tidak menghapus installer setelah selesai.
//
// Cara pakai:
// 1) Upload file ini ke public_html (langsung di root) melalui cPanel File Manager.
// 2) Buka https://yourdomain.com/installer_eac.php
// 3) Isi DB Host, User, Pass, DB Name lalu klik Install Now.
//
// NOTE: jika PHP menunjukkan error, laporkan pesan error & barisnya.

if (php_sapi_name() === 'cli') {
    echo "Please run installer via web browser.\n";
    exit;
}

// Utility
function write_file($path, $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) return false;
    }
    return file_put_contents($path, $content) !== false;
}

$target_folder = __DIR__ . DIRECTORY_SEPARATOR . 'eac'; // akan membuat public_html/eac jika diupload di public_html

// --- File templates (string literals safe) ---
$files = array();



// README
$files['README.txt'] = <<< 'TXT'
EAC Web App (cPanel-ready)

Deployment steps (cPanel)
1. Upload the 'eac' folder to public_html (e.g. public_html/eac/).
2. Import the SQL file (db.sql) into your MySQL via phpMyAdmin. Default DB name suggested: eac_db
3. Update config.php with your database credentials (installer can already do this).
4. Open index.php in browser.

Notes:
- PHP: tested on PHP 7.4+ (MySQLi). No external composer packages required.
- Chart.js and Bootstrap loaded from CDN.
- Theme: Dark Elegant (A) - CSS in assets/css/style.css
- CSV export and chart PNG download are included.
TXT;

// inc/functions.php
$files['inc/functions.php'] = <<< 'PHP'
<?php
// inc/functions.php - numerical functions & simulation (no DB dependency)
function integrate_trap($func, $a, $b, $n=10000) {
    if ($b <= $a) return 0.0;
    $h = ($b - $a) / $n;
    $s = 0.5 * ($func($a) + $func($b));
    for ($i=1; $i<$n; $i++) {
        $x = $a + $i * $h;
        $s += $func($x);
    }
    return $s * $h;
}
function bisection($f, $a, $b, $tol=1e-6, $maxIter=100) {
    $fa = $f($a); $fb = $f($b);
    if (!is_numeric($fa) || !is_numeric($fb) || $fa * $fb > 0) return null;
    $i=0;
    while (($b - $a) / 2 > $tol && $i < $maxIter) {
        $c = ($a + $b) / 2;
        $fc = $f($c);
        if ($fc == 0) return $c;
        if ($fa * $fc < 0) {
            $b = $c; $fb = $fc;
        } else {
            $a = $c; $fa = $fc;
        }
        $i++;
    }
    return ($a + $b) / 2;
}
function compute_eac($Pm, $Pmax, $Pmax_fault, $delta1_assumed=null) {
    if ($Pmax <= 0) return array('error'=>'Pmax harus > 0');
    if (abs($Pm) > $Pmax) return array('error'=>'|Pm| > Pmax (tidak valid)');
    $delta0 = asin($Pm / $Pmax);
    $res = array('delta0'=>$delta0);
    if ($delta1_assumed !== null && $delta1_assumed !== '') {
        if ($delta1_assumed <= $delta0) {
            $res['error']='delta1 harus > delta0 untuk percepatan';
            return $res;
        }
        $A1 = integrate_trap(function($d) use ($Pm, $Pmax_fault) {
            return max(0, $Pm - $Pmax_fault * sin($d));
        }, $delta0, $delta1_assumed);
        $A2max = integrate_trap(function($d) use ($Pm, $Pmax) {
            return max(0, $Pmax * sin($d) - $Pm);
        }, $delta1_assumed, pi());
        $res['delta1']=$delta1_assumed;
        $res['A1']=$A1;
        $res['A2max']=$A2max;
        $res['is_stable'] = ($A2max >= $A1) ? 1 : 0;
        if ($res['is_stable']) {
            $f = function($d) use ($Pm, $Pmax, $delta1_assumed, $A1) {
                $val = integrate_trap(function($x) use ($Pm, $Pmax) {
                    return $Pmax * sin($x) - $Pm;
                }, $delta1_assumed, $d);
                return $val - $A1;
            };
            $root = bisection($f, $delta1_assumed, pi()-1e-6);
            $res['delta_max']=$root;
            if ($root !== null) {
                $A2 = integrate_trap(function($d) use ($Pm, $Pmax) {
                    return max(0, $Pmax * sin($d) - $Pm);
                }, $delta1_assumed, $root);
                $res['A2']=$A2;
            }
        }
    }
    $f_cr = function($d) use ($Pm, $Pmax, $Pmax_fault) {
        if ($d <= 0) return 1;
        $delta0 = asin($Pm / $Pmax);
        $A1 = integrate_trap(function($x) use ($Pm, $Pmax_fault) { return max(0, $Pm - $Pmax_fault * sin($x)); }, $delta0, $d);
        $A2max = integrate_trap(function($x) use ($Pm, $Pmax) { return max(0, $Pmax * sin($x) - $Pm); }, $d, pi());
        return $A2max - $A1;
    };
    $fa = $f_cr(asin($Pm/$Pmax) + 1e-6);
    $fb = $f_cr(pi() - 1e-6);
    if (is_numeric($fa) && is_numeric($fb) && $fa * $fb <= 0) {
        $delta_cr = bisection($f_cr, asin($Pm/$Pmax) + 1e-6, pi() - 1e-6);
        $res['delta_cr']=$delta_cr;
    } else {
        $res['delta_cr']=null;
    }
    return $res;
}
function simulate_smib_rk4($Pm, $Pmax, $Pmax_fault, $delta0, $H=5.0, $f=50.0, $tmax=5.0, $dt=0.002, $fault_duration=0.2) {
    $ws = 2 * pi() * $f;
    $M = 2 * $H / $ws;
    $delta = $delta0;
    $omega = 0.0;
    $nsteps = (int)ceil($tmax / $dt);
    $t = 0.0;
    $data = array();
    for ($i=0; $i<=$nsteps; $i++) {
        $Pmax_cur = ($t <= $fault_duration) ? $Pmax_fault : $Pmax;
        $Pe = $Pmax_cur * sin($delta);
        $acc = ($Pm - $Pe) / $M;
        $k1d = $omega; $k1w = $acc;
        $d2 = $delta + 0.5 * $dt * $k1d; $w2 = $omega + 0.5 * $dt * $k1w;
        $Pcur2 = ($t + 0.5*$dt <= $fault_duration) ? $Pmax_fault : $Pmax;
        $acc2 = ($Pm - $Pcur2 * sin($d2)) / $M; $k2d = $w2; $k2w = $acc2;
        $d3 = $delta + 0.5 * $dt * $k2d; $w3 = $omega + 0.5 * $dt * $k2w;
        $Pcur3 = ($t + 0.5*$dt <= $fault_duration) ? $Pmax_fault : $Pmax;
        $acc3 = ($Pm - $Pcur3 * sin($d3)) / $M; $k3d = $w3; $k3w = $acc3;
        $d4 = $delta + $dt * $k3d; $w4 = $omega + $dt * $k3w;
        $Pcur4 = ($t + $dt <= $fault_duration) ? $Pmax_fault : $Pmax;
        $acc4 = ($Pm - $Pcur4 * sin($d4)) / $M; $k4d = $w4; $k4w = $acc4;
        $delta_next = $delta + ($dt/6.0) * ($k1d + 2*$k2d + 2*$k3d + $k4d);
        $omega_next = $omega + ($dt/6.0) * ($k1w + 2*$k2w + 2*$k3w + $k4w);
        $data[] = array('t'=>round($t,6), 'delta'=>$delta, 'omega'=>$omega);
        $delta = $delta_next; $omega = $omega_next; $t += $dt;
        if (abs($delta) > 50) break;
    }
    return array('data'=>$data, 'dt'=>$dt, 'H'=>$H);
}
PHP;

// assets css
$files['assets/css/style.css'] = <<< 'CSS'
/* Dark Elegant theme */
body { background: #0b1020; color: #e6eef6; font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
.card { background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border: 1px solid rgba(255,255,255,0.04); }
.btn-primary { background: #1e88e5; border-color: #1e88e5; }
.table-dark { background: rgba(255,255,255,0.02); }
.table-striped>tbody>tr:nth-of-type(odd)>* { background-color: rgba(255,255,255,0.01); }
a.btn-link { color: #90caf9; }
CSS;

// config template
$files['config.php.template'] = <<< 'CFG'
<?php
// config.php - MySQLi procedural (will be created by installer)
define('DB_HOST', '{{DB_HOST}}');
define('DB_USER', '{{DB_USER}}');
define('DB_PASS', '{{DB_PASS}}');
define('DB_NAME', '{{DB_NAME}}');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die('DB connect error: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>
CFG;

// index.php (compact)
$files['index.php'] = <<< 'IDX'
<?php
require 'config.php';
require 'inc/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['save'])) {
    $name = $_POST['name'] ?? 'scenario';
    $Pm = floatval($_POST['Pm']);
    $Pmax = floatval($_POST['Pmax']);
    $Pmax_fault = floatval($_POST['Pmax_fault']);
    $delta1 = isset($_POST['delta1']) && $_POST['delta1'] !== '' ? floatval($_POST['delta1']) : NULL;
    $H = isset($_POST['H']) && $_POST['H'] !== '' ? floatval($_POST['H']) : 5.0;
    $note = $_POST['note'] ?? '';
    $stmt = mysqli_prepare($conn, "INSERT INTO scenarios (name, pm, pmax, pmax_fault, delta1, H, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sddddds', $name, $Pm, $Pmax, $Pmax_fault, $delta1, $H, $note);
    mysqli_stmt_execute($stmt);
    $sid = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    $res = compute_eac($Pm, $Pmax, $Pmax_fault, $delta1);
    $stmt2 = mysqli_prepare($conn, "INSERT INTO results (scenario_id, delta0, delta_cr, delta1_used, A1, A2, is_stable) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $delta0 = $res['delta0'] ?? NULL; $delta_cr = $res['delta_cr'] ?? NULL; $delta1_used = $res['delta1'] ?? NULL; $A1 = $res['A1'] ?? NULL; $A2 = $res['A2'] ?? NULL; $is_stable = $res['is_stable'] ?? NULL;
    mysqli_stmt_bind_param($stmt2, 'idddddi', $sid, $delta0, $delta_cr, $delta1_used, $A1, $A2, $is_stable);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    $sim = simulate_smib_rk4($Pm, $Pmax, $Pmax_fault, $delta0, $H, 50.0, 5.0, 0.002, 0.2);
    $sim_json = json_encode($sim['data']);
    $stmt3 = mysqli_prepare($conn, "INSERT INTO simulations (scenario_id, tseries, dt, H) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt3, 'isdd', $sid, $sim_json, $sim['dt'], $H);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);
    header('Location: index.php'); exit;
}
$q = "SELECT s.*, r.delta0, r.delta_cr, r.delta1_used, r.A1, r.A2, r.is_stable FROM scenarios s LEFT JOIN results r ON r.scenario_id = s.id ORDER BY s.created_at DESC";
$rs = mysqli_query($conn, $q);
$scenarios = array();
while ($row = mysqli_fetch_assoc($rs)) $scenarios[] = $row;
?>
<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EAC Visualizer - Dark Elegant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container py-4">
  <div class="row g-3">
    <div class="col-md-5">
      <div class="card p-3 bg-dark text-light">
        <h5 class="mb-3">Tambah Skenario EAC</h5>
        <form method="post">
          <div class="mb-2"><label class="form-label">Nama</label><input name="name" class="form-control form-control-sm" value="Skenario " required></div>
          <div class="mb-2 row">
            <div class="col"><label class="form-label">Pm (pu)</label><input name="Pm" type="number" step="0.0001" class="form-control form-control-sm" value="0.8" required></div>
            <div class="col"><label class="form-label">Pmax (pu)</label><input name="Pmax" type="number" step="0.0001" class="form-control form-control-sm" value="1.0" required></div>
          </div>
          <div class="mb-2 row">
            <div class="col"><label class="form-label">Pmax saat fault (pu)</label><input name="Pmax_fault" type="number" step="0.0001" class="form-control form-control-sm" value="0.4" required></div>
            <div class="col"><label class="form-label">Delta1 (rad) (opsional)</label><input name="delta1" type="number" step="0.0001" class="form-control form-control-sm" placeholder="contoh: 1.2"></div>
          </div>
          <div class="mb-2 row"><div class="col"><label class="form-label">H (detik)</label><input name="H" type="number" step="0.1" class="form-control form-control-sm" value="5"></div></div>
          <div class="mb-2"><label class="form-label">Catatan</label><textarea name="note" class="form-control form-control-sm" rows="2"></textarea></div>
          <div class="d-flex gap-2"><button name="save" class="btn btn-primary btn-sm">Simpan, Hitung & Simulasi</button><a class="btn btn-outline-secondary btn-sm" href="#" onclick="document.querySelector('form').reset();return false;">Reset</a></div>
        </form>
      </div>
      <div class="card p-3 mt-3 bg-dark text-light">
        <h6>Petunjuk Singkat</h6>
        <ul>
          <li>Isi Pm, Pmax, Pmax saat fault, H (untuk simulasi), dan (opsional) delta1 clearing.</li>
          <li>App akan menyimpan skenario & menghitung hasil EAC, lalu menjalankan simulasi SMIB (RK4) dan menyimpan time-series.</li>
          <li>Klik "Lihat" untuk menampilkan grafik daya-sudut & δ(t).</li>
        </ul>
      </div>
    </div>
    <div class="col-md-7">
      <div class="card p-3 bg-dark text-light">
        <h5>Daftar Skenario</h5>
        <div class="table-responsive">
        <table class="table table-striped table-dark">
          <thead><tr><th>#</th><th>Nama</th><th>Pm</th><th>Pmax</th><th>Fault</th><th>Delta0</th><th>Stabil</th><th>Aksi</th></tr></thead>
          <tbody>
IDX;

// scenario_view.php
$files['scenario_view.php'] = <<< 'SV'
<?php
require 'config.php'; require 'inc/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sc = mysqli_prepare($conn, 'SELECT * FROM scenarios WHERE id=?'); mysqli_stmt_bind_param($sc, 'i', $id); mysqli_stmt_execute($sc); $res_s = mysqli_stmt_get_result($sc); $s = mysqli_fetch_assoc($res_s);
if (!$s) { echo 'Scenario not found'; exit; }
$res = compute_eac($s['pm'], $s['pmax'], $s['pmax_fault'], $s['delta1']);
$simq = mysqli_prepare($conn, 'SELECT * FROM simulations WHERE scenario_id=? ORDER BY created_at DESC LIMIT 1'); mysqli_stmt_bind_param($simq, 'i', $id); mysqli_stmt_execute($simq); $res_sim = mysqli_stmt_get_result($simq); $simr = mysqli_fetch_assoc($res_sim);
$tseries = array();
if ($simr) {
    $tseries = json_decode($simr['tseries'], true);
    $dt = $simr['dt'];
} else {
    $simdata = simulate_smib_rk4($s['pm'], $s['pmax'], $s['pmax_fault'], $res['delta0'], $s['H']?:5.0, 50.0, 5.0, 0.002, 0.2);
    $tseries = $simdata['data'];
    $dt = $simdata['dt'];
}
$points = array();
$step = 300;
for ($i=0;$i<=$step;$i++){ $d = $i * pi() / $step; $points[] = array('d'=>$d,'Pe_normal'=>$s['pmax'] * sin($d),'Pe_fault'=>$s['pmax_fault'] * sin($d)); }
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
          <li><strong>delta0:</strong> <?php echo number_format($res['delta0'],4); ?></li>
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
SV;

// export_csv.php
$files['export_csv.php'] = <<< 'CSV'
<?php
require 'config.php'; require 'inc/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$scq = mysqli_prepare($conn, 'SELECT * FROM scenarios WHERE id=?'); mysqli_stmt_bind_param($scq, 'i', $id); mysqli_stmt_execute($scq); $rs = mysqli_stmt_get_result($scq); $s = mysqli_fetch_assoc($rs);
if (!$s) { echo 'Scenario not found'; exit; }
$res = compute_eac($s['pm'], $s['pmax'], $s['pmax_fault'], $s['delta1']);
$simq = mysqli_prepare($conn, 'SELECT * FROM simulations WHERE scenario_id=? ORDER BY created_at DESC LIMIT 1'); mysqli_stmt_bind_param($simq, 'i', $id); mysqli_stmt_execute($simq); $rs2 = mysqli_stmt_get_result($simq); $simr = mysqli_fetch_assoc($rs2);
$tseries = array();
if ($simr) $tseries = json_decode($simr['tseries'], true);

$filename = 'eac_scenario_'.$id.'_'.date('Ymd_His').'.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$out = fopen('php://output', 'w');
fputcsv($out, array('field','value'));
fputcsv($out, array('id', $s['id']));
fputcsv($out, array('name', $s['name']));
fputcsv($out, array('Pm', $s['pm']));
fputcsv($out, array('Pmax', $s['pmax']));
fputcsv($out, array('Pmax_fault', $s['pmax_fault']));
fputcsv($out, array('delta1', $s['delta1']));
fputcsv($out, array('H', $s['H']));
fputcsv($out, array());
fputcsv($out, array('Result','value'));
fputcsv($out, array('delta0', $res['delta0']));
fputcsv($out, array('delta_cr', $res['delta_cr']));
fputcsv($out, array('A1', isset($res['A1'])?$res['A1']:''));
fputcsv($out, array('A2max', isset($res['A2max'])?$res['A2max']:''));
fputcsv($out, array('is_stable', $res['is_stable']));
fputcsv($out, array());
fputcsv($out, array('time','delta','omega'));
foreach ($tseries as $row) { fputcsv($out, array($row['t'], $row['delta'], $row['omega'])); }
fclose($out);
exit;
?>
CSV;

// --- Installer processing ---
$step = isset($_POST['step']) ? $_POST['step'] : 'form';
$messages = array();

if ($step === 'install') {
    $dbhost = isset($_POST['dbhost']) ? $_POST['dbhost'] : 'localhost';
    $dbuser = isset($_POST['dbuser']) ? $_POST['dbuser'] : '';
    $dbpass = isset($_POST['dbpass']) ? $_POST['dbpass'] : '';
    $dbname = isset($_POST['dbname']) ? $_POST['dbname'] : 'eac_db';

    // make target folder
    if (!is_dir($target_folder)) {
        if (!mkdir($target_folder, 0755, true)) {
            $messages[] = array('type'=>'error','text'=>'Gagal membuat folder target: ' . $target_folder);
        }
    }

    // write files
    foreach ($files as $rel => $content) {
        $path = $target_folder . DIRECTORY_SEPARATOR . $rel;
        if (!write_file($path, $content)) {
            $messages[] = array('type'=>'error','text'=>"Gagal menulis: $rel");
        } else {
            $messages[] = array('type'=>'ok','text'=>"Tersimpan: $rel");
        }
    }

    // create config.php from template
    $cfg = str_replace(array('{{DB_HOST}}','{{DB_USER}}','{{DB_PASS}}','{{DB_NAME}}'), array($dbhost,$dbuser,$dbpass,$dbname), $files['config.php.template']);
    if (write_file($target_folder . DIRECTORY_SEPARATOR . 'config.php', $cfg)) {
        $messages[] = array('type'=>'ok','text'=>'config.php dibuat.');
    } else {
        $messages[] = array('type'=>'error','text'=>'Gagal menulis config.php');
    }

    // attempt to execute SQL (if DB creds ok)
    $mysqli = @new mysqli($dbhost, $dbuser, $dbpass);
    if ($mysqli->connect_errno) {
        $messages[] = array('type'=>'error','text'=>'Koneksi DB gagal: '.$mysqli->connect_error);
        $messages[] = array('type'=>'info','text'=>'Kamu masih bisa import db.sql secara manual via phpMyAdmin.');
    } else {
        // create database if not exist
        if (!$mysqli->select_db($dbname)) {
            if ($mysqli->query("CREATE DATABASE `".$mysqli->real_escape_string($dbname)."` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
                $messages[] = array('type'=>'ok','text'=>'Database dibuat: '.$dbname);
            } else {
                $messages[] = array('type'=>'error','text'=>'Gagal membuat database: '.$mysqli->error);
            }
        }
        $mysqli->select_db($dbname);
        $sql = file_get_contents($target_folder . DIRECTORY_SEPARATOR . 'db.sql');
        if ($sql !== false) {
            // naive split by semicolon — fine for our generated db.sql
            $parts = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($parts as $p) {
                if ($p === '') continue;
                if (!$mysqli->query($p)) {
                    $messages[] = array('type'=>'warn','text'=>'SQL warning/error: '. $mysqli->error . ' -- query snippet: ' . substr($p,0,120));
                }
            }
            $messages[] = array('type'=>'ok','text'=>'SQL (db.sql) dijalankan (cek pesan di atas untuk error).');
        } else {
            $messages[] = array('type'=>'error','text'=>'Tidak dapat membaca db.sql untuk dieksekusi.');
        }
        $mysqli->close();
    }
    $step = 'done';
}

// --- Installer HTML ---
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installer EAC Webapp</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#0b1020;color:#e6eef6}.card{background:#07101a;border:1px solid rgba(255,255,255,0.03)}.ok{color:#a5d6a7}.error{color:#ef9a9a}.warn{color:#ffcc80}</style>
</head>
<body>
<div class="container py-5">
  <div class="card p-4">
    <h3>Installer EAC Webapp</h3>
    <p>Target install folder: <strong><?php echo htmlspecialchars($target_folder); ?></strong></p>

<?php if ($step === 'form'): ?>
    <p>Silakan masukkan informasi database. Installer akan menulis file aplikasi ke folder <code>eac/</code> di lokasi ini.</p>
    <form method="post">
      <input type="hidden" name="step" value="install">
      <div class="mb-2"><label class="form-label">DB Host</label><input name="dbhost" class="form-control" value="localhost"></div>
      <div class="mb-2"><label class="form-label">DB User</label><input name="dbuser" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">DB Password</label><input name="dbpass" type="password" class="form-control"></div>
      <div class="mb-2"><label class="form-label">DB Name</label><input name="dbname" class="form-control" value="eac_db"></div>
      <div class="mt-3"><button class="btn btn-primary">Install Now</button></div>
    </form>
<?php else: ?>
    <h5>Hasil Instalasi</h5>
    <?php foreach ($messages as $m): ?>
      <div class="mb-1 <?php echo $m['type']; ?>">• <?php echo htmlspecialchars($m['text']); ?></div>
    <?php endforeach; ?>
    <hr>
    <p>Langkah selanjutnya:</p>
    <ol>
      <li>Buka <code>eac/index.php</code> di browser (mis: https://yourdomain.com/eac/index.php)</li>
      <li>Jika ada error DB, buka <code>eac/config.php</code> dan perbaiki kredensial.</li>
      <li>Jika SQL gagal, import <code>eac/db.sql</code> melalui phpMyAdmin.</li>
    </ol>
<?php endif; ?>
  </div>
</div>
</body>
</html>
