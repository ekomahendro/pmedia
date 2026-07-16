<?php
include 'db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['part_id']) && is_numeric($_GET['part_id'])) {
    $part_id = (int)$_GET['part_id'];
    
    $sql = "SELECT unit_price FROM spare_parts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $part_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $part = $result->fetch_assoc();
        echo json_encode(['success' => true, 'price' => $part['unit_price']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Part tidak ditemukan.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID Part tidak valid.']);
}
?>