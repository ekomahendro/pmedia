<?php
session_start();
include 'db_connect.php';

// Cek akses: hanya Supervisor yang bisa
if ($_SESSION['user_level'] !== 'supervisor') {
    die("Akses ditolak.");
}

if (isset($_GET['mo_id'])) {
    $mo_id = (int)$_GET['mo_id'];
    $supervisor_id = $_SESSION['user_id'];
    $date_approve = date('Y-m-d H:i:s');
    
    // Query untuk mengambil MO (untuk memastikan Supervisor dari Dept yang Benar, atau Superadmin)
    // Di contoh ini, kita asumsikan Supervisor bisa approve MO yang muncul di list mereka.
    
    $sql = "UPDATE maintenance_orders 
            SET status = 'approved', 
                supervisor_approve_by_user_id = ?, 
                date_supervisor_approve = ?
            WHERE id = ? AND status = 'pending'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $supervisor_id, $date_approve, $mo_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = "<div class='alert alert-success'>MO #{$mo_id} berhasil disetujui. Siap diproses Engineering.</div>";
    } else {
        $_SESSION['flash_message'] = "<div class='alert alert-danger'>Gagal menyetujui MO. Mungkin MO sudah diproses atau tidak ditemukan.</div>";
    }
    $stmt->close();
    header("Location: list_mo_supervisor.php"); // Redirect kembali ke daftar MO
    exit;
}
?>