<?php
require_once '../config/db.php';
session_start();

// Cek apakah ada cookie "remember_me" untuk mengisi otomatis input username
$saved_username = isset($_COOKIE['remember_user']) ? $_COOKIE['remember_user'] : '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); // Cek apakah checkbox dicentang

    $stmt = $pdo->prepare("SELECT * FROM tr_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set Session
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['level']    = $user['level'];
        $_SESSION['wilayah']  = $user['wilayah'];
        $_SESSION['blok']     = $user['blok'];
        $_SESSION['last_activity'] = time();
        
        // Logika Remember Me (Cookie)
        if ($remember) {
            // Simpan username di cookie selama 30 hari
            setcookie('remember_user', $username, time() + (30 * 24 * 60 * 60), "/");
        } else {
            // Jika tidak dicentang, hapus cookie yang ada
            if (isset($_COOKIE['remember_user'])) {
                setcookie('remember_user', '', time() - 3600, "/");
            }
        }

        header("Location: ../pages/dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RWM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f0f2f5; 
            display: flex; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card { 
            width: 100%; 
            max-width: 420px; 
            padding: 20px; 
            margin: auto; 
        }
        .card { 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05) !important;
        }
        .form-control {
            padding: 12px;
            font-size: 16px; /* Mencegah auto-zoom di iOS */
        }
        .input-group-text {
            cursor: pointer;
            background: white;
        }
        .btn-login {
            padding: 12px;
            font-weight: bold;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary">RUKUN WARGA</h3>
                    <p class="text-muted small">Silakan login untuk akses sistem</p>
                </div>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger p-2 small border-0 text-center"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Masukkan username" 
                           value="<?= htmlspecialchars($saved_username) ?>" required>
                </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                            <span class="input-group-text" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4 d-flex justify-content-between">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">Ingat Saya</label>
                        </div>
                        <a href="#" class="small text-decoration-none">Lupa Password?</a>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-login w-100">Login Ke Sistem</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-4 text-muted small">&copy; 2026 Pengelola RWM - Terintegrasi</p>
        <p class="text-center mt-4 text-muted small">&copy; pmediaku.my.id</p>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const toggleIcon = document.getElementById("toggleIcon");
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.replace("bi-eye", "bi-eye-slash");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.replace("bi-eye-slash", "bi-eye");
            }
        }
    </script>
</body>
</html>