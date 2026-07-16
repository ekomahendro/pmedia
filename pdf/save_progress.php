<?php
session_start();
if (!isset($_SESSION['user'])) exit("Unauthorized");

require_once 'config.php';
$file = $_POST['file'];
$page = $_POST['page'];
$username = $_SESSION['user'];

// Logika: Jika kombinasi username + file_name sudah ada, update. Jika belum, insert.
// Ini dilakukan tanpa foreign key.
$query = "INSERT INTO bookmarks (file_name, username, last_page) 
          VALUES (?, ?, ?) 
          ON DUPLICATE KEY UPDATE last_page = VALUES(last_page)";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $file, $username, $page);
$stmt->execute();

echo "Progress saved for $username";
?>