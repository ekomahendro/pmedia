<?php
session_start();
// Pastikan path koneksi sudah benar
include('config/koneksi.php'); 

// Cek apakah user sudah login, jika ya redirect ke dashboard
if (isset($_SESSION['level'])) {
    // *** PERBAIKAN DI SINI: Redirect ke file PHP di dalam folder 'page' ***
    if ($_SESSION['level'] == 'admin') {
        header('Location: page/dashboard_admin.php');
    } else {
        header('Location: page/dashboard_kasir.php');
    }
    exit();
}

$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = MD5($_POST['password']); 
    $level    = $_POST['level']; 

    $query = "SELECT * FROM userchasier WHERE username='$username' AND password='$password' AND level='$level'";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);

    if (mysqli_num_rows($result) == 1) {
        // ... (setting session seperti sebelumnya) ...
        $_SESSION['id_user']    = $data['id_user'];
        $_SESSION['username']   = $data['username'];
        $_SESSION['level']      = $data['level'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        
        // *** PERBAIKAN DI SINI: Redirect ke file PHP di dalam folder 'page' ***
        if ($data['level'] == 'admin') {
            header('Location: page/dashboard_admin.php');
        } else {
            header('Location: page/dashboard_kasir.php');
        }
        exit();
    } else {
        $error = "Username, password, atau level salah!";
    }
}
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Kasir Minimarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 100px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-lg login-container mx-auto">
            <div class="card-header text-center bg-primary text-white">
                <h4>Login Kasir Minimarket</h4>
            </div>
            <div class="card-body">
                <?php if ($error) { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } ?>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">Level Akses</label>
                        <select class="form-select" id="level" name="level" required>
                            <option value="">Pilih Level</option>
                            <option value="admin">Admin</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="login" class="btn btn-primary">LOGIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>