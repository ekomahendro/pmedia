<?php
// test_password.php - File untuk mengecek Hashing Password

// ----------------------
// BAGIAN 1: GENERATE HASH BARU
// ----------------------

// 1. Definisikan password yang ingin Anda gunakan (misalnya: 123456)
$password_anda = 'Girh2025'; 

// 2. Buat Hash menggunakan algoritma BCRYPT (standar dan aman)
// Hasilnya akan menjadi string panjang (misalnya: $2y$10$Q7Y...)
$hashed_password_baru = password_hash($password_anda, PASSWORD_BCRYPT);

echo "<h2>1. Generate Password Hash Baru</h2>";
echo "<p>Password Asli: <strong>" . htmlspecialchars($password_anda) . "</strong></p>";
echo "<p>Hasil Hash (MySQL): <strong style='color: green;'>" . $hashed_password_baru . "</strong></p>";
echo "<p style='color: red;'>*** Salin Hash di atas dan perbarui kolom <code>password_hash</code> di tabel <code>users</code> untuk user 'admin' Anda. ***</p>";

echo "<hr>";

// ----------------------
// BAGIAN 2: VERIFIKASI HASH
// ----------------------

// Ganti nilai ini dengan hash yang SEBENARNYA tersimpan di database Anda
// Jika Anda belum mengganti di database, gunakan $hashed_password_baru
$hash_dari_database = $hashed_password_baru; 

// Password yang dimasukkan oleh pengguna saat mencoba login (misalnya: '123456')
$password_input_saat_login = '123456'; 

// Coba verifikasi
if (password_verify($password_input_saat_login, $hash_dari_database)) {
    $status = "<strong style='color: blue;'>Cocok! Login seharusnya berhasil.</strong>";
} else {
    $status = "<strong style='color: red;'>TIDAK COCOK! Cek kembali password dan hash di database.</strong>";
}

echo "<h2>2. Verifikasi Password</h2>";
echo "<p>Hash dari Database: " . htmlspecialchars($hash_dari_database) . "</p>";
echo "<p>Input Password User: <strong>" . htmlspecialchars($password_input_saat_login) . "</strong></p>";
echo "<p>Status Verifikasi: " . $status . "</p>";

echo "<hr>";

// ----------------------
// BAGIAN 3: TES GAGAL LOGIN
// ----------------------
$salah_password = 'salahpassword';
if (!password_verify($salah_password, $hash_dari_database)) {
    $status_gagal = "<strong style='color: blue;'>Benar! Password salah terdeteksi.</strong>";
} else {
    $status_gagal = "<strong style='color: red;'>Salah! Password salah malah dianggap benar. Ada masalah pada hash.</strong>";
}
echo "<h2>3. Tes Password Salah</h2>";
echo "<p>Input Password Salah: <strong>" . htmlspecialchars($salah_password) . "</strong></p>";
echo "<p>Status Verifikasi: " . $status_gagal . "</p>";


?>