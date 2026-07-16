<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['user_id']) && isset($_GET['action'])) {
    $user_id = $_GET['user_id'];
    $action = $_GET['action'];
    $is_blocked = $action == 'block' ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_blocked, $user_id);
    $stmt->execute();
}

header('Location: dashboard.php');
exit;
?>