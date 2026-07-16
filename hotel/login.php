// login.php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $key = $_POST['license'];

    // Query untuk cek user dan license
    $query = "SELECT * FROM htl_users u 
              JOIN htl_licenses l ON u.license_id = l.license_id 
              WHERE u.username = '$user' AND l.license_key = '$key'";
    
    // Proses verifikasi password
    if (password_verify($pass, $row['password_hash'])) {
        $_SESSION['user'] = $row;
        header("Location: dashboard.php");
    }
}
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Login Hotel System</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card p-4 shadow">
                    <h3 class="text-center">Hotel Login</h3>
                    <form method="POST" action="auth.php">
                        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
                        <input type="text" name="license" class="form-control mb-3" placeholder="License Key" required>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>