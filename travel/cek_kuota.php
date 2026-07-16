<?php
include 'koneksi.php';

header('Content-Type: application/json');

$id_paket = isset($_GET['id_paket']) ? intval($_GET['id_paket']) : 0;
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';

if ($id_paket == 0 || empty($tanggal)) {
    echo json_encode(['sisa_kuota' => 0]);
    exit;
}

// 1. Ambil batasan kuota maksimal untuk paket ini
$query_paket = mysqli_query($conn, "SELECT kuota_maksimal FROM tra_paket WHERE id_paket = $id_paket");
$data_paket = mysqli_fetch_assoc($query_paket);
$kuota_maksimal = isset($data_paket['kuota_maksimal']) ? intval($data_paket['kuota_maksimal']) : 15; // default 15 jika kolom belum ada

// 2. Hitung jumlah total kuota terpakai (SUM jumlah orang) pada tanggal keberangkatan terkait
// Sesuaikan "tra_booking", "id_paket", "tgl_keberangkatan", "jumlah_peserta" dengan kolom tabel booking milik Anda
$query_booked = mysqli_query($conn, "SELECT SUM(jumlah_peserta) as total_terisi FROM tra_pesanan 
                                      WHERE id_paket = $id_paket AND tgl_keberangkatan = '$tanggal'");
$data_booked = mysqli_fetch_assoc($query_booked);
$total_terisi = isset($data_booked['total_terisi']) ? intval($data_booked['total_terisi']) : 0;

// 3. Kalkulasi sisa slot kuota saat ini
$sisa_kuota = $kuota_maksimal - $total_terisi;
if ($sisa_kuota < 0) $sisa_kuota = 0;

echo json_encode([
    'total_kuota_paket' => $kuota_maksimal,
    'terisi' => $total_terisi,
    'sisa_kuota' => $sisa_kuota
]);