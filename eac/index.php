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

    // compute delta0 first (safe)
    $delta0 = (abs($Pm) <= $Pmax && $Pmax>0) ? asin($Pm / $Pmax) : NULL;
    $res = compute_eac($Pm, $Pmax, $Pmax_fault, $delta0);

    $stmt2 = mysqli_prepare($conn, "INSERT INTO results (scenario_id, delta0, delta_cr, delta1_used, A1, A2, is_stable) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $delta0_v = $res['delta0'] ?? NULL;
    $delta_cr = $res['delta_cr'] ?? NULL;
    $delta1_used = $res['delta1'] ?? NULL;
    $A1 = $res['A1'] ?? NULL;
    $A2 = $res['A2'] ?? NULL;
    $is_stable = $res['is_stable'] ?? NULL;
    mysqli_stmt_bind_param($stmt2, 'idddddi', $sid, $delta0_v, $delta_cr, $delta1_used, $A1, $A2, $is_stable);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    $sim = simulate_smib_rk4($Pm, $Pmax, $Pmax_fault, $delta0_v, $H, 50.0, 5.0, 0.002, 0.2);
    $sim_json = json_encode($sim['data']);
    $stmt3 = mysqli_prepare($conn, "INSERT INTO simulations (scenario_id, tseries, dt, H) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt3, 'isdd', $sid, $sim_json, $sim['dt'], $H);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);

    header('Location: index.php'); exit;
}

// Fetch scenarios and results (no column case-sensitivity issues)
$q = "SELECT s.id, s.name, s.pm, s.pmax, s.pmax_fault, s.delta1, s.H, s.note, s.created_at,
             r.delta0, r.delta_cr, r.delta1_used, r.A1, r.A2, r.is_stable
      FROM scenarios s
      LEFT JOIN results r ON r.scenario_id = s.id
      ORDER BY s.created_at DESC";
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
  <style>
    /* ensure visible text in dark tables */
    table.table-dark tbody tr td, table.table-dark tbody tr th { color: #e8eef6 !important; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="row g-3">
    <div class="col-md-5">
      <div class="card p-3 bg-dark text-light border-secondary">
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

      <div class="card p-3 mt-3 bg-dark text-light border-secondary">
        <h6>Petunjuk Singkat</h6>
        <ul>
          <li>Isi Pm, Pmax, Pmax saat fault, H (untuk simulasi), dan (opsional) delta1 clearing.</li>
          <li>App akan menyimpan skenario & menghitung hasil EAC, lalu menjalankan simulasi SMIB (RK4) dan menyimpan time-series.</li>
          <li>Klik "Lihat" untuk menampilkan grafik daya-sudut & δ(t).</li>
        </ul>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card p-3 bg-dark text-light border-secondary">
        <h5>Daftar Skenario</h5>
        <div class="table-responsive">
        <table class="table table-striped table-dark table-hover">
          <thead><tr><th>#</th><th>Nama</th><th>Pm</th><th>Pmax</th><th>Fault</th><th>Delta0</th><th>Stabil</th><th>Aksi</th></tr></thead>
          <tbody>
<?php foreach ($scenarios as $row): ?>
  <tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo $row['pm']; ?></td>
    <td><?php echo $row['pmax']; ?></td>
    <td><?php echo $row['pmax_fault']; ?></td>
    <td><?php echo isset($row['delta0']) ? number_format($row['delta0'],4) : '-'; ?></td>
    <td><?php
         if (!isset($row['is_stable']) || $row['is_stable']===null) echo '-';
         else echo ($row['is_stable'] ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-danger">Tidak</span>');
       ?></td>
    <td>
      <a href="scenario_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
      <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus skenario ini?');">Hapus</a>
    </td>
  </tr>
<?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

  </div>
</div>
</body>
</html>
