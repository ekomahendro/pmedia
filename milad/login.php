<?php
require_once 'config.php';
$error = '';

if (isset($_SESSION['login_milad'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === 'milad2026') {
        $_SESSION['login_milad'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panitia Gebyar Milad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="card login-card p-4">
    <div class="text-center mb-4">
        <h5 class="fw-bold text-success">Gebyar Milad MT.Muallaf Taufiqiyah XV</h5>
        <small class="text-muted">Sistem Pencatatan Uang Keluar Masuk</small>
    </div>
    <?php if($error): ?>
        <div class="alert alert-danger text-center py-2"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Password Akses</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password..." required autofocus>
        </div>
        <button type="submit" class="btn btn-success w-100">Masuk Sistem</button>
    </form>
</div>
</body>
</html>