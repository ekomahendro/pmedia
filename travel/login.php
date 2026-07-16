<?php
session_start();
include 'koneksi.php';

// Jika admin sudah login sebelumnya, langsung lempar ke dashboard pesanan
if (isset($_SESSION['admin'])) {
    header("Location: admin_bookings.php");
    exit;
}

if (isset($_POST['login'])) {
    // Mengamankan input data dari SQL Injection
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']); 

    // Mencari user di tabel tra_admin
    $query = "SELECT * FROM tra_admin WHERE username='$user' AND password='$pass'";
    $res = mysqli_query($conn, $query);

    if (mysqli_num_rows($res) > 0) {
        // PROSES FETCHING DATA YANG SEBELUMNYA TERLEWAT
        $row = mysqli_fetch_array($res);
        
        // Menyimpan data kredensial ke dalam Session internal PHP
        $_SESSION['admin'] = $row['username'];
        $_SESSION['role']  = $row['role']; // Menyimpan 'Super Admin' atau 'Staff'
        
        // Berhasil login, arahkan ke halaman utama pesanan masuk
        header("Location: admin_bookings.php");
        exit;
    } else { 
        $error = "Username atau password yang Anda masukkan salah!"; 
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Maluku Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { 
            background: #0f172a; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            font-family: 'Segoe UI', Roboto, sans-serif;
        }
        .login-card { 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); 
            background: #ffffff;
        }
        .btn-login { 
            border-radius: 10px; 
            padding: 12px; 
            font-weight: 600; 
            background-color: #0284c7;
            border: none;
            transition: 0.2s;
        }
        .btn-login:hover {
            background-color: #0369a1;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4 p-3">
            <div class="card login-card p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="text-primary mb-2"><i class="bi bi-shield-lock-fill fs-1"></i></div>
                    <h3 class="fw-bold text-dark mb-1">Sign In</h3>
                    <p class="text-muted small">Maluku Paradise Travel Panel</p>
                </div>
                
                <!-- Notifikasi Error jika login gagal -->
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">USERNAME</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control bg-light border-start-0" placeholder="Masukkan username" required autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">PASSWORD</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 btn-login text-white">
                        MASUK KE DASHBOARD
                    </button>
                </form>
            </div>
            <div class="text-center mt-3">
                <a href="index.php" class="text-white-50 small text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Kembali ke Website</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>