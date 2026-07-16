<?php
$host = "localhost";
$db   = "u1775096_pmed";
$user = "u1775096_upmed";
$pass = "Admin_local";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
