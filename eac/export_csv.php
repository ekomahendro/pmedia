<?php
require 'config.php'; require 'inc/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$scq = mysqli_prepare($conn, 'SELECT * FROM scenarios WHERE id=?'); mysqli_stmt_bind_param($scq, 'i', $id); mysqli_stmt_execute($scq); $rs = mysqli_stmt_get_result($scq); $s = mysqli_fetch_assoc($rs);
if (!$s) { echo 'Scenario not found'; exit; }
$res = compute_eac($s['pm'], $s['pmax'], $s['pmax_fault'], isset($s['pmax']) && $s['pmax']>0 ? asin($s['pm']/$s['pmax']) : null);
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
if (is_array($tseries)) {
    foreach ($tseries as $row) { fputcsv($out, array($row['t'], $row['delta'], $row['omega'])); }
}
fclose($out);
exit;
?>
