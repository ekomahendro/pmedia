<?php
session_start();
require_once 'config.php';

// Cek jika user sudah login, redirect ke dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

$username = $password = "";
$login_err = "";

// Proses data form saat POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pastikan koneksi DB tersedia
    if (isset($conn)) {
        // Ambil dan bersihkan input
        $username = sanitize_input($conn, $_POST['username']);
        $password = $_POST['password']; 

        $sql = "SELECT id, username, password, full_name, status FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $full_name, $status);
                    if ($stmt->fetch()) {
                        // Verifikasi password
                        if (password_verify($password, $hashed_password)) {
                            
                            // Login berhasil, buat session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["status"] = $status;
                            
                            // Redirect sesuai status
                            header("location: dashboard.php");
                            exit;
                        } else {
                            $login_err = "Password yang Anda masukkan salah.";
                        }
                    }
                } else {
                    $login_err = "Username tidak ditemukan.";
                }
            } else {
                $login_err = "Oops! Terjadi kesalahan pada server. Silakan coba lagi nanti.";
            }

            $stmt->close();
        }
        $conn->close();
    } else {
        $login_err = "Koneksi database bermasalah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Catatan Amalan Harian </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            background-color: white;
        }
        .login-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .login-header h4 {
            color: #007bff; /* Warna biru Bootstrap */
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h4 class="mb-1">Catatan Amalan Harian </h4>
        <p class="text-muted">Silakan masuk untuk melanjutkan</p>
    </div>

    <?php 
    if(!empty($login_err)){
        echo '<div class="alert alert-danger" role="alert">' . $login_err . '</div>';
    }        
    ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" class="form-control <?php echo (!empty($login_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
            <div class="invalid-feedback">
                <?php echo $login_err; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Masuk</button>
        </div>

    </form>
    
    <p class="mt-4 text-center text-muted">Aplikasi Internal </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>