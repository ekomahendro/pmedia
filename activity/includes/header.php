<?php
// Pastikan session_start() dipanggil hanya sekali
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika belum login (contoh sederhana)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koperasi Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="wrapper">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Koperasi System</a>
            </div>
    </nav>
    <div class="container-fluid mt-4">
        ```

## 2. File `'config/db_connect.php'`

Anda bertanya apakah seharusnya `'config.php'` alih-alih `'config/db_connect.php'`.

**Jawaban:** Keduanya dimungkinkan, tergantung bagaimana Anda menyusun proyek Anda.

* **`config/db_connect.php`:** Menunjukkan bahwa file konfigurasi koneksi *database* Anda disimpan dalam *folder* bernama `config`. Ini adalah praktik bagus untuk **organisasi file**.
* **`config.php`:** Lebih umum dan mungkin menampung semua pengaturan, termasuk koneksi DB dan variabel lainnya.

Berdasarkan kode yang Anda berikan (`include '../config/db_connect.php';`), dapat diasumsikan bahwa **Anda telah membuat file tersebut dan menamainya sebagai `'db_connect.php'`** di dalam *folder* `config`.

### Contoh Isi `'config/db_connect.php'`

File ini seharusnya berisi detail koneksi ke *database* dan membuat variabel koneksi `$conn`.

```php
<?php
$servername = "localhost";
$username = "root"; // Ganti dengan username database Anda
$password = ""; // Ganti dengan password database Anda
$dbname = "nama_database_koperasi"; // Ganti dengan nama database Anda

// Membuat koneksi
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>