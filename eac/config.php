<?php
// config.php - MySQLi procedural (will be created by installer)
define('DB_HOST', 'localhost');
define('DB_USER', 'u1775096_um2');
define('DB_PASS', 'Admin_local');
define('DB_NAME', 'u1775096_m2');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die('DB connect error: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>