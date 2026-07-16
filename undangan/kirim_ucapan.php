<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $pesan = mysqli_real_escape_string($conn, $_POST['pesan']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "INSERT INTO comments (guest_name, message, status) VALUES ('$nama', '$pesan', '$status')";
    
    if (mysqli_query($conn, $query)) {
        // Kembali ke halaman utama dengan anchor ke section komentar
        header("Location: index.php?status=success#komentar");
    }
}
?>