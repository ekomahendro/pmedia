<?php
require_once 'config.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $license  = trim($_POST['license_code']);

    if (!empty($username) && !empty($password) && !empty($license)) {
        // Query cek user sekaligus join ke lisensi untuk memastikan kode lisensi valid & aktif
        $query = "SELECT u.*, l.license_code, l.status as license_status, l.hotel_name 
                  FROM htl_users u 
                  JOIN htl_licenses l ON u.id_license = l.id_license 
                  WHERE u.username = ? AND l.license_code = ? LIMIT 1";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $license);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if ($user['license_status'] !== 'active') {
                $error = "Lisensi software Anda sudah tidak aktif / expired!";
            } elseif ($user['status'] !== 'active') {
                $error = "Akun user Anda telah dinonaktifkan!";
            } else {
                // Verifikasi password dengan Hashing
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id']     = $user['id_user'];
                    $_SESSION['username']    = $user['username'];
                    $_SESSION['fullname']    = $user['fullname'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['id_license']  = $user['id_license'];
                    $_SESSION['hotel_name']  = $user['hotel_name'];
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Username atau Password salah!";
                }
            }
        } else {
            $error = "Kombinasi User dan Kode Lisensi tidak ditemukan!";
        }
    } else {
        $error = "Semua field wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; display: flex; align-items: center; }
        .login-card { border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card p-4 bg-white">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-building-gear text-primary" style="font-size: 3rem;"></i>
                        <h4 class="mt-2 fw-bold text-dark">Core Hotel System</h4>
                        <p class="text-muted small">Silahkan masuk dengan kredensial & lisensi Anda</p>
                    </div>
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= $error; ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-array form-control" placeholder="Masukkan username" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold">Kode Lisensi Sistem</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="text" name="license_code" class="form-control" placeholder="PMEDIA-XXXX-XXXX" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold rounded-pill shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Log In System
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>