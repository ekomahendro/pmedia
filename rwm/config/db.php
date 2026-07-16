<?php
$host = "localhost";
$user = "u1775096_um2";
$pass = "Admin_local";
$db   = "u1775096_m2";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}

function base_url($path = '') {
    return "http://localhost/siak_rw/" . $path;
}
?>