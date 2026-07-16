<?php
session_start();
require_once 'config.php';

$stmt = $conn->prepare("SELECT u.id, u.status, a.nama FROM users u JOIN alumni a ON u.id = a.user_id WHERE u.last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND u.is_blocked = 0");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<div class='mb-2'>";
    echo "<strong>" . htmlspecialchars($row['nama']) . "</strong>";
    if ($row['status']) {
        echo ": " . htmlspecialchars($row['status']);
    }
    if ($row['id'] == $_SESSION['user_id']) {
        echo " <a href='edit_status.php' class='btn btn-sm btn-primary'>Update Status</a>";
    }
    echo "</div>";
}
?>