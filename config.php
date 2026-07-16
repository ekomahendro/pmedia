<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u1775096_uinvit');
define('DB_PASS', 'Admin_local');
define('DB_NAME', 'u1775096_dinvit');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' https://cdn.jsdelivr.net; script-src \'self\' https://cdn.jsdelivr.net;');
?>