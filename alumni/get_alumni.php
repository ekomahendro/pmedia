<?php
session_start();
require_once 'config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : '';

$query = "SELECT a.*, u.is_blocked FROM alumni a JOIN users u ON a.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND a.nama LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($angkatan) {
    $query .= " AND a.angkatan = ?";
    $params[] = $angkatan;
    $types .= "i";
}

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<div class='card mb-2'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>" . htmlspecialchars($row['nama']) . "</h5>";
    echo "<p class='card-text'>Angkatan: " . htmlspecialchars($row['angkatan']) . "<br>";
    echo "Perguruan Tinggi: " . htmlspecialchars($row['perguruan_tinggi']) . "<br>";
    echo "Jurusan: " . htmlspecialchars($row['jurusan_kuliah']) . "</p>";
    if ($row['user_id'] == $_SESSION['user_id']) {
        echo "<a href='edit_profile.php' class='btn btn-sm btn-primary'>Edit Profile</a>";
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin' && $row['user_id'] != $_SESSION['user_id']) {
        $block_text = $row['is_blocked'] ? 'Unblock' : 'Block';
        echo "<a href='admin_block.php?user_id=" . $row['user_id'] . "&action=" . ($row['is_blocked'] ? 'unblock' : 'block') . "' class='btn btn-sm btn-" . ($row['is_blocked'] ? 'success' : 'danger') . " ms-2'>$block_text</a>";
    }
    echo "</div></div>";
}
?>