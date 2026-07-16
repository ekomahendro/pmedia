<?php
session_start();

// Validasi keamanan: Pastikan hanya admin yang sudah login yang bisa mengakses file proses ini
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Memastikan data dikirim melalui metode POST dari form admin_bookings.php
if (isset($_POST['id_pesanan']) && isset($_POST['status_baru'])) {
    
    // Mengamankan data input untuk mencegah celah SQL Injection
    $id_pesanan  = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);

    // Validasi tambahan untuk memastikan status yang dimasukkan sesuai dengan struktur ENUM database
    $status_valid = ['Pending', 'Confirmed', 'Cancelled'];
    
    if (in_array($status_baru, $status_valid)) {
        
        // Eksekusi query pembaruan data status pesanan
        $query_update = "UPDATE tra_pesanan SET status = '$status_baru' WHERE id_pesanan = '$id_pesanan'";
        
        if (mysqli_query($conn, $query_update)) {
            // Jika berhasil, arahkan kembali ke dashboard pemesanan dengan pesan sukses via JavaScript
            echo "<script>
                    alert('Status pesanan berhasil diperbarui menjadi " . $status_baru . "!');
                    window.location.href = 'admin_bookings.php';
                  </script>";
        } else {
            // Penanganan jika terjadi kegagalan sistem database
            echo "<script>
                    alert('Gagal memperbarui status. Terjadi kesalahan pada sistem database.');
                    window.location.href = 'admin_bookings.php';
                  </script>";
        }
    } else {
        // Penanganan jika ada manipulasi nilai status di luar opsi yang valid
        echo "<script>
                alert('Pilihan status tidak valid!');
                window.location.href = 'admin_bookings.php';
              </script>";
    }
} else {
    // Jika file ini diakses secara langsung tanpa melalui form POST, kunci akses dan kembalikan ke dashboard
    header("Location: admin_bookings.php");
    exit;
}
?>