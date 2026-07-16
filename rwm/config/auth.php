session_start();

// Cek jika user sudah login
if (isset($_SESSION['last_activity'])) {
    // Hitung selisih waktu (dalam detik)
    $idle_time = time() - $_SESSION['last_activity'];
    if ($idle_time > 300) { // 300 detik = 5 menit
        session_unset();
        session_destroy();
        header("Location: login.php?msg=timeout");
        exit;
    }
}
$_SESSION['last_activity'] = time(); // Update aktivitas terakhir