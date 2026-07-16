<?php
session_start();
require_once 'config.php';

$stmt = $conn->prepare("SELECT c.message, c.created_at, a.nama FROM chats c JOIN alumni a ON c.user_id = a.user_id ORDER BY c.created_at ASC");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $time = date('H:i', strtotime($row['created_at']));
    echo "<div class='mb-2'>";
    echo "<strong>" . htmlspecialchars($row['nama']) . "</strong> ($time): ";
    echo htmlspecialchars($row['message']);
    echo "</div>";
}
?>