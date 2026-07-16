<?php
session_start();

include_once 'config.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['password']) && $_POST['password'] === PASSWORD_ADMIN) {
        $_SESSION['loggedin'] = true;
        $_SESSION['is_admin'] = true;
        header("location: dashboard.php");
        exit;
    } elseif (isset($_POST['no_password_login'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['is_admin'] = false;
        header("location: dashboard.php");
        exit;
    } else {
        $login_error = "Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Data 7B</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Login Data 7B</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="password" class="form-label">Password Admin</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary w-100 mb-2">Login dengan Password</button>
            </form>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!--<button type="submit" name="no_password_login" class="btn btn-secondary w-100">Masuk Tanpa Password</button>-->
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>