<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u1775096_uinvit'); // Ganti dengan username database Anda
define('DB_PASSWORD', 'Admin_local');     // Ganti dengan password database Anda
define('DB_NAME', 'u1775096_dinvit'); // Ganti dengan nama database Anda

// Encrypted password for 'pibtabanan'
define('ADMIN_PASSWORD_HASH', password_hash('pibtabanan', PASSWORD_DEFAULT));

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>