<?php
session_start();
session_unset();
session_destroy();

// 1. Hapus semua data di $_SESSION
$_SESSION = array();

// 2. Hancurkan cookie session di browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session di server
session_destroy();

// 4. Redirect kembali ke halaman login
header("location: login.php");
exit;
?>