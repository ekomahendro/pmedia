<?php
session_start();
include 'config/db_connect.php'; 

$message = '';

// Kredensial Admin Hardcode (Ganti dengan DB di Real App)
$ADMIN_EMAIL = "admin@resto.com";
$ADMIN_PASS = "admin123"; 
$ADMIN_PASS_HASH = password_hash($ADMIN_PASS, PASSWORD_DEFAULT); // Hash untuk perbandingan

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === $ADMIN_EMAIL) {
        // Pada aplikasi nyata, Anda akan mengambil hash dari DB dan memverifikasinya
        if (password_verify($password, $ADMIN_PASS_HASH) || $password === $ADMIN_PASS) {
            
            // Login Admin Berhasil
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $ADMIN_EMAIL;
            
            header("Location: admin_dashboard.php"); 
            exit();
        } else {
            $message = '<div class="alert alert-danger">Email atau Password salah.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Email atau Password salah.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Resto Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-dark text-white text-center">
                    <h4>Admin Panel Login</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <form action="admin_login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Admin</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>