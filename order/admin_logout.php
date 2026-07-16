<?php
session_start();

// Hapus variabel session admin
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_email']);

// Hancurkan session dan arahkan kembali ke halaman login admin
session_destroy();

header("Location: admin_login.php");
exit;
?>