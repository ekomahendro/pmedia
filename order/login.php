<?php
session_start();
include 'config/db_connect.php'; // Sertakan file koneksi database

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Validasi Input Dasar
    if (empty($email) || empty($password)) {
        $message = '<div class="alert alert-danger">Email dan Password harus diisi.</div>';
    } else {
        try {
            // 2. Ambil data pengguna berdasarkan email
            $sql = "SELECT user_id, nama, password_hash FROM t_users WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 3. Verifikasi Password
                // Membandingkan password yang diinput dengan hash yang tersimpan di database
                if (password_verify($password, $user['password_hash'])) {
                    
                    // 4. Login Berhasil: Buat Session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['nama'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Arahkan ke halaman menu atau beranda
                    header("Location: menu.php"); 
                    exit();
                } else {
                    // Password tidak cocok
                    $message = '<div class="alert alert-danger">Email atau Password salah.</div>';
                }
            } else {
                // Email tidak ditemukan
                $message = '<div class="alert alert-danger">Email atau Password salah.</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Terjadi kesalahan pada server. Silakan coba lagi.</div>';
            // Debugging: echo $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Pelanggan Restoran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4>Masuk ke Akun Anda</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Masuk</button>
                    </form>
                    <p class="text-center">Belum punya akun? <a href="signup.php">Daftar Sekarang</a></p>
                    <p class="text-center"><a href="index.php">Kembali ke Beranda</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>