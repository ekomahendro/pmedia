<?php
include 'config/db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password akan di-hash

    // Validasi dasar
    if (empty($nama) || empty($email) || empty($password)) {
        $message = '<div class="alert alert-danger">Semua field harus diisi.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Format email tidak valid.</div>';
    } else {
        // Hash password menggunakan algoritma yang aman
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Cek apakah email sudah terdaftar
            $stmt = $pdo->prepare("SELECT user_id FROM t_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $message = '<div class="alert alert-warning">Email sudah terdaftar. Silakan login.</div>';
            } else {
                // Insert data baru
                $sql = "INSERT INTO t_users (nama, email, password_hash) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama, $email, $password_hash]);
                
                $message = '<div class="alert alert-success">Pendaftaran berhasil! Silakan login atau mulai memesan.</div>';
                // Anda bisa mengarahkan user ke halaman menu atau login
                // header("Location: menu.php"); 
                // exit();
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error: Gagal mendaftar.</div>';
            // Debugging: echo $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Resto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4>Pendaftaran Akun Pelanggan</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <form action="signup.php" method="POST">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Valid</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <small class="form-text text-muted">Kami akan menggunakan email ini untuk komunikasi.</small>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Daftar Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>