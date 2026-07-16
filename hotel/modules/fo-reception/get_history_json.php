<?php
require_once '../../config.php';
header('Content-Type: application/json');

$id_reg = intval($_GET['id'] ?? 0);

$moves = [];
$res_moves = mysqli_query($conn, "SELECT * FROM htl_room_moves WHERE registration_id = $id_reg ORDER BY moved_at DESC");
while($m = mysqli_fetch_assoc($res_moves)) {
    $moves[] = $m;
}

$deposits = [];
$res_deps = mysqli_query($conn, "SELECT * FROM htl_deposits WHERE registration_id = $id_reg ORDER BY received_at DESC");
while($d = mysqli_fetch_assoc($res_deps)) {
    $deposits[] = $d;
}

echo json_encode([
    'moves' => $moves,
    'deposits' => $deposits
]);