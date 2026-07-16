<?php
// action.php
include '../config.php'; // Pastikan koneksi PDO atau Ki_mysqli sudah benar

$action = $_GET['action'] ?? '';

// 1. PESERTA: Tekan Buzzer
if ($action == 'press_buzzer') {
    $kelompok_id = $_POST['kelompok_id'];
    
    // Gunakan transaksi atau cek kondisi ketat agar tidak keduluan kelompok lain
    $db->query("START TRANSACTION");
    $res = $db->query("SELECT is_locked FROM quiz_buzzer WHERE id = 1 FOR UPDATE")->fetch_assoc();
    
    if ($res['is_locked'] == 0) {
        // Jika belum terkunci, kelompok ini yang pertama!
        $db->query("UPDATE quiz_buzzer SET kelompok_id = $kelompok_id, is_locked = 1 WHERE id = 1");
        $db->query("COMMIT");
        echo json_encode(['status' => 'success', 'message' => 'Anda yang pertama!']);
    } else {
        $db->query("ROLLBACK");
        echo json_encode(['status' => 'failed', 'message' => 'Keduluan kelompok lain!']);
    }
}

// 2. LAYAR & PESERTA: Cek Status Buzzer Saat Ini (Polling)
if ($action == 'get_status') {
    $status = $db->query("SELECT qb.*, qs.nama_kelompok, qs.foto 
                          FROM quiz_buzzer qb 
                          LEFT JOIN quiz_skor qs ON qb.kelompok_id = qs.kelompok_id 
                          WHERE qb.id = 1")->fetch_assoc();
    
    $skor = $db->query("SELECT * FROM quiz_skor ORDER BY kelompok_id ASC")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['buzzer' => $status, 'skor' => $skor]);
}

// 3. ADMIN: Reset Buzzer untuk Pertanyaan Berikutnya
if ($action == 'reset_buzzer') {
    $db->query("UPDATE quiz_buzzer SET kelompok_id = NULL, is_locked = 0 WHERE id = 1");
    echo json_encode(['status' => 'success']);
}

// 4. ADMIN: Update Skor (+ / -)
if ($action == 'update_skor') {
    $kelompok_id = $_POST['kelompok_id'];
    $poin = $_POST['poin']; // bisa positif (100) atau negatif (-50)
    
    $db->query("UPDATE quiz_skor SET skor = skor + ($poin) WHERE kelompok_id = $kelompok_id");
    echo json_encode(['status' => 'success']);
}
?>