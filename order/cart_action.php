<?php
session_start();
include 'config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['item_id'])) {
    $action = $_POST['action'];
    $itemId = (int)$_POST['item_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Ambil harga item dari database
    try {
        $stmt = $pdo->prepare("SELECT item_id, price_reguler, price_diskon FROM t_menu_items WHERE item_id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $response = ['status' => 'error', 'message' => 'Item tidak ditemukan.'];
            echo json_encode($response);
            exit;
        }

        // Tentukan harga yang digunakan (harga diskon jika ada)
        $price_to_use = $item['price_diskon'] > 0 && $item['price_diskon'] < $item['price_reguler'] ? $item['price_diskon'] : $item['price_reguler'];

        if ($action == 'add') {
            if (isset($_SESSION['cart'][$itemId])) {
                // Jika sudah ada, tambahkan kuantitas
                $_SESSION['cart'][$itemId]['quantity'] += $quantity;
            } else {
                // Jika belum ada, tambahkan item baru
                $_SESSION['cart'][$itemId] = [
                    'item_id' => $itemId,
                    'price' => $price_to_use,
                    'quantity' => $quantity
                ];
            }
            $response = ['status' => 'success', 'message' => 'Item berhasil ditambahkan.', 'count' => count($_SESSION['cart'])];
        } 
        // Logika untuk remove dan update quantity bisa ditambahkan di sini

    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Database error.'];
    }
}

echo json_encode($response);
?>