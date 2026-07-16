<?php
session_start();
require_once 'config.php';

// Proses Login
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT password, status FROM pdfuser WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && password_verify($pass, $row['password'])) {
        if ($row['status'] === 'active') {
            $_SESSION['user'] = $user;
            header("Location: index.php");
            exit();
        } else {
            $error = "Akun Anda masih dalam antrean aktivasi Admin.";
        }
    } else {
        $error = "Username atau Password salah.";
    }
}

// Proses Register
if (isset($_POST['register'])) {
    $user = $_POST['reg_username'];
    $wa = $_POST['reg_wa'];
    $pass = password_hash($_POST['reg_password'], PASSWORD_DEFAULT); // Hash password langsung

    // Cek apakah username sudah ada
    $check = $conn->prepare("SELECT id FROM pdfuser WHERE username = ?");
    $check->bind_param("s", $user);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Username sudah digunakan!";
    } else {
        $stmt = $conn->prepare("INSERT INTO pdfuser (username, password, no_wa, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("sss", $user, $pass, $wa);
        $stmt->execute();
        $msg = "Registrasi sukses! Silakan menunggu Admin untuk aktivasi.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="manifest" href="manifest.json">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Ebook Dashboard">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { 
        background: #0f172a; 
        color: white; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        min-height: 100vh; 
        margin:0; 
        font-family: 'Inter', sans-serif;
    }
    .auth-card { 
        background: #1e293b; 
        padding: 30px; 
        border-radius: 28px; 
        width: 100%; 
        max-width: 400px; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); 
        border: 1px solid rgba(255,255,255,0.1); 
    }
    
    /* TULISAN LABEL: Dibuat lebih terang dan tebal */
    .form-label-custom { 
        color: #f1f5f9; /* Warna Putih Keabu-abuan (Sangat Jelas) */
        font-weight: 600; 
        font-size: 14px; 
        margin-bottom: 8px; 
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* INPUT FIELD: Dipertegas garis bordernya */
    .form-control { 
        background: #0f172a !important; 
        border: 2px solid #334155 !important; 
        color: #ffffff !important; 
        border-radius: 12px; 
        padding: 14px; 
        transition: all 0.3s ease;
    }
    
    /* Efek saat input diklik */
    .form-control:focus {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.2);
        outline: none;
    }

    .nav-pills .nav-link { color: #94a3b8; font-weight: 500; }
    .nav-pills .nav-link.active { background: #38bdf8; color: #0f172a; font-weight: 700; }
    
    .btn-primary { 
        background: #38bdf8; 
        border: none; 
        font-weight: 800; 
        color: #0f172a; 
        padding: 14px;
        border-radius: 12px;
        text-transform: uppercase;
    }
</style>
</head>
<body>
<div class="auth-card mx-3">
    <h4 class="text-center mb-4 fw-bold">PDF Library</h4>
    <ul class="nav nav-pills nav-justified mb-4" id="pills-tab">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-login">Login</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-reg">Register</button></li>
    </ul>

    <?php if(isset($error)) echo "<div class='alert alert-danger py-2 small border-0'>$error</div>"; ?>
    <?php if(isset($msg)) echo "<div class='alert alert-success py-2 small border-0'>$msg</div>"; ?>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-login">
            <form method="POST">
                <div class="mb-3">
                    <label class="small mb-1 text-muted">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
                <div class="mb-4">
                    <label class="small mb-1 text-muted">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 py-3">MASUK SEKARANG</button>
            </form>
        </div>
        
        <div class="tab-pane fade" id="tab-reg">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label-custom">Username Baru</label>
                    <input type="text" name="reg_username" class="form-control" placeholder="Contoh: budi_sanjaya" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">Nomor WhatsApp</label>
                    <input type="number" name="reg_wa" class="form-control" placeholder="0812xxxxxxxx" required>
                </div>
                <div class="mb-4">
                    <label class="form-label-custom">Password</label>
                    <input type="password" name="reg_password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>
                <button type="submit" name="register" class="btn btn-primary w-100 py-3">DAFTAR AKUN</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('Service Worker terdaftar dengan aman!', reg))
        .catch(err => console.error('Gagal mendaftarkan Service Worker:', err));
    });
  }
</script>
</body>
</html>