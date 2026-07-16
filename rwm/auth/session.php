<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Timeout 5 menit
if (time() - $_SESSION['last_activity'] > 300) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();
?>