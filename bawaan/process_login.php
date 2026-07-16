<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];

    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['loggedin'] = true;
        header("location: index.php");
        exit;
    } else {
        $_SESSION['login_error'] = "Password salah!";
        header("location: login.php");
        exit;
    }
}
?>