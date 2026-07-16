<?php
include 'koneksi.php';

if (isset($_POST['book'])) {
    // Mengamankan input data dari SQL Injection
    $id_paket     = mysqli_real_escape_string($conn, $_POST['id_paket']);
    $nama         = mysqli_real_escape_string($conn, $_POST['nama']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $tgl_berangkat = mysqli_real_escape_string($conn, $_POST['tgl']);
    $jumlah       = mysqli_real_escape_string($conn, $_POST['jml']);
        // Ambil data keterangan tambahan dari form booking
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Status awal diatur sebagai 'Pending' secara default dari struktur tabel
    $query = "INSERT INTO tra_pesanan (id_paket, nama_pelanggan, email, no_telp, tgl_keberangkatan, jumlah_peserta, keterangan, status) 
              VALUES ('$id_paket', '$nama', '$email', '$no_telp', '$tgl_berangkat', '$jumlah', '$keterangan','Pending')";
              
    if (mysqli_query($conn, $query)) {
        // Mengambil data paket untuk keperluan kustomisasi pesan teks WhatsApp
        $query_paket = mysqli_query($conn, "SELECT nama_paket FROM tra_paket WHERE id_paket = '$id_paket'");
        $data_paket  = mysqli_fetch_array($query_paket);
        $nama_paket  = $data_paket['nama_paket'];

        // Membuat format nomor telepon internasional yang valid jika user memakai awalan 0
        if (substr($no_telp, 0, 1) === '0') {
            $no_telp_wa = '62' . substr($no_telp, 1);
        } else {
            $no_telp_wa = $no_telp;
        }

        // Menyusun template pesan otomatis untuk konfirmasi mandiri ke WhatsApp Admin
        // Ganti '628123456789' dengan nomor WhatsApp Admin kantor Maluku Paradise Travel Anda
        $admin_whatsapp = "628123456789"; 
        $text_message = "Halo Maluku Paradise Travel, saya ingin konfirmasi pesanan tour.\n\n"
                      . "*Detail Pesanan:*\n"
                      . "• Nama: " . $nama . "\n"
                      . "• Paket: " . $nama_paket . "\n"
                      . "• Tgl Keberangkatan: " . date('d M Y', strtotime($tgl_berangkat)) . "\n"
                      . "• Jumlah Peserta: " . $jumlah . " Orang\n\n"
                      . "Mohon untuk segera diproses. Terima kasih!";
                      
        $url_wa = "https://wa.me/" . $admin_whatsapp . "?text=" . urlencode($text_message);

        // Notifikasi sukses, lalu mengarahkan tamu langsung ke WhatsApp Admin (opsi praktis agar deal lebih cepat)
        echo "<script>
                alert('Booking berhasil disimpan! Klik OK untuk terhubung dengan CS kami via WhatsApp guna proses verifikasi.');
                window.location.href = '" . $url_wa . "';
              </script>";
    } else {
        // Penanganan jika query gagal dijalankan
        echo "<script>
                alert('Gagal memproses booking. Silakan coba beberapa saat lagi.');
                window.history.back();
              </script>";
    }
} else {
    // Jika file diakses langsung tanpa submit form, kembalikan ke homepage
    header("Location: index.php");
    exit;
}
?>