<?php
require 'config.php';
require 'inc/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo 'Invalid id'; exit; }

// fetch scenario
$sc = mysqli_prepare($conn, 'SELECT * FROM scenarios WHERE id=?');
mysqli_stmt_bind_param($sc, 'i', $id); mysqli_stmt_execute($sc);
$res = mysqli_stmt_get_result($sc);
$s = mysqli_fetch_assoc($res);
mysqli_stmt_close($sc);
if (!$s) { echo 'Scenario not found'; exit; }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? $s['name'];
    $Pm = floatval($_POST['Pm']);
    $Pmax = floatval($_POST['Pmax']);
    $Pmax_fault = floatval($_POST['Pmax_fault']);
    $delta1 = isset($_POST['delta1']) && $_POST['delta1'] !== '' ? floatval($_POST['delta1']) : NULL;
    $H = isset($_POST['H']) && $_POST['H'] !== '' ? floatval($_POST['H']) : 5.0;
    $note = $_POST['note'] ?? '';

    // update scenario
    $up = mysqli_prepare($conn, "UPDATE scenarios SET name=?, pm=?, pmax=?, pmax_fault=?, delta1=?, H=?, note=? WHERE id=?");
    mysqli_stmt_bind_param($up, 'sddddsdi', $name, $Pm, $Pmax, $Pmax_fault, $delta1, $H, $note, $id);
    // Note: PHP doesn't accept 'sddddsdi' if types mismatch for delta1 null; we'll use string binding safely:
    mysqli_stmt_close($up);

    // simpler update using query with proper escaping
    $stmtu = mysqli_prepare($conn, "UPDATE scenarios SET name=?, pm=?, pmax=?, pmax_fault=?, delta1=?, H=?, note=? WHERE id=?");
    mysqli_stmt_bind_param($stmtu, 'sddddsdi', $name, $Pm, $Pmax, $Pmax_fault, $delta1, $H, $note, $id);
    // However binding with null delta1 may fail; we instead prepare dynamic:
    mysqli_stmt_close($stmtu);

    // do update via mysqli_real_escape_string to avoid binding issue with null
    $name_e = mysqli_real_escape_string($conn, $name);
    $note_e = mysqli_real_escape_string($conn, $note);
    $delta1_sql = ($delta1 === null) ? 'NULL' : (float)$delta1;
    $sql_up = "UPDATE scenarios SET
                name='$name_e', pm=". (float)$Pm .", pmax=". (float)$Pmax .", pmax_fault=". (float)$Pmax_fault .",
                delta1={$delta1_sql}, H=".(float)$H .", note='{$note_e}'
               WHERE id=" . (int)$id;
    if (!mysqli_query($conn, $sql_up)) {
        $errors[] = 'Gagal update scenario: ' . mysqli_error($conn);
    } else {
        // recompute
        $delta0 = (abs($Pm) <= $Pmax && $Pmax>0) ? asin($Pm / $Pmax) : null;
        $res_eac = compute_eac($Pm, $Pmax, $Pmax_fault, $delta0);

        // update or insert into results
        $chk = mysqli_prepare($conn, 'SELECT id FROM results WHERE scenario_id=? LIMIT 1');
        mysqli_stmt_bind_param($chk, 'i', $id); mysqli_stmt_execute($chk);
        $rres = mysqli_stmt_get_result($chk); $rowchk = mysqli_fetch_assoc($rres); mysqli_stmt_close($chk);
        $delta0_v = $res_eac['delta0'] ?? NULL;
        $delta_cr = $res_eac['delta_cr'] ?? NULL;
        $delta1_used = $res_eac['delta1'] ?? NULL;
        $A1 = $res_eac['A1'] ?? NULL;
        $A2 = $res_eac['A2'] ?? NULL;
        $is_stable = $res_eac['is_stable'] ?? NULL;

        if ($rowchk) {
            $rid = (int)$rowchk['id'];
            $upd = mysqli_prepare($conn, "UPDATE results SET delta0=?, delta_cr=?, delta1_used=?, A1=?, A2=?, is_stable=? WHERE id=?");
            mysqli_stmt_bind_param($upd, 'dddddii', $delta0_v, $delta_cr, $delta1_used, $A1, $A2, $is_stable, $rid);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO results (scenario_id, delta0, delta_cr, delta1_used, A1, A2, is_stable) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, 'idddddi', $id, $delta0_v, $delta_cr, $delta1_used, $A1, $A2, $is_stable);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }

        // create new simulation entry
        $sim = simulate_smib_rk4($Pm, $Pmax, $Pmax_fault, $delta0, $H, 50.0, 5.0, 0.002, 0.2);
        $sim_json = mysqli_real_escape_string($conn, json_encode($sim['data']));
        $ins2 = mysqli_prepare($conn, "INSERT INTO simulations (scenario_id, tseries, dt, H) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins2, 'isdd', $id, $sim_json, $sim['dt'], $H);
        mysqli_stmt_execute($ins2);
        mysqli_stmt_close($ins2);

        $success = 'Skenario berhasil diperbarui dan dihitung ulang.';
        // reload latest data
        $sc2 = mysqli_prepare($conn, 'SELECT * FROM scenarios WHERE id=?');
        mysqli_stmt_bind_param($sc2, 'i', $id); mysqli_stmt_execute($sc2);
        $res2 = mysqli_stmt_get_result($sc2);
        $s = mysqli_fetch_assoc($res2);
        mysqli_stmt_close($sc2);
    }
}

?>
<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Skenario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container py-4">
  <a class="btn btn-link" href="index.php">← Kembali</a>
  <div class="card p-3 bg-dark text-light">
    <h4>Edit: <?php echo htmlspecialchars($s['name']); ?></h4>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if (!empty($errors)): foreach ($errors as $e): ?><div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div><?php endforeach; endif; ?>
    <form method="post">
      <div class="mb-2"><label class="form-label">Nama</label><input name="name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($s['name']); ?>" required></div>
      <div class="mb-2 row">
        <div class="col"><label class="form-label">Pm (pu)</label><input name="Pm" type="number" step="0.0001" class="form-control form-control-sm" value="<?php echo $s['pm']; ?>" required></div>
        <div class="col"><label class="form-label">Pmax (pu)</label><input name="Pmax" type="number" step="0.0001" class="form-control form-control-sm" value="<?php echo $s['pmax']; ?>" required></div>
      </div>
      <div class="mb-2 row">
        <div class="col"><label class="form-label">Pmax saat fault (pu)</label><input name="Pmax_fault" type="number" step="0.0001" class="form-control form-control-sm" value="<?php echo $s['pmax_fault']; ?>" required></div>
        <div class="col"><label class="form-label">Delta1 (rad) (opsional)</label><input name="delta1" type="number" step="0.0001" class="form-control form-control-sm" value="<?php echo $s['delta1']; ?>"></div>
      </div>
      <div class="mb-2 row"><div class="col"><label class="form-label">H (detik)</label><input name="H" type="number" step="0.1" class="form-control form-control-sm" value="<?php echo $s['H'] ?: 5; ?>"></div></div>
      <div class="mb-2"><label class="form-label">Catatan</label><textarea name="note" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($s['note']); ?></textarea></div>
      <div><button class="btn btn-primary btn-sm">Simpan & Hitung Ulang</button></div>
    </form>
  </div>
</div>
</body>
</html>
