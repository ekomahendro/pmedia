<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u1775096_uinvit');
define('DB_PASS', 'Admin_local');
define('DB_NAME', 'u1775096_dinvit');

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Google OAuth configuration
define('GOOGLE_CLIENT_ID', 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', 'http://pmediaku.my.id/google_login.php');
?>