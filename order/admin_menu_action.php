<?php
session_start();
include 'config/db_connect.php'; 

// Cek Keamanan
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Fungsi untuk menangani upload gambar
function handle_upload($file_array, $current_image_path = null) {
    if ($file_array['error'] == UPLOAD_ERR_NO_FILE) {
        return ['status' => 'success', 'path' => $current_image_path];
    }
    
    $target_dir = "uploads/";
    $file_extension = pathinfo($file_array["name"], PATHINFO_EXTENSION);
    $new_file_name = uniqid('menu_') . "." . $file_extension;
    $target_file = $target_dir . $new_file_name;
    
    // Check file size and type
    if ($file_array["size"] > 5000000) { // Max 5MB
        return ['status' => 'error', 'message' => 'Ukuran file terlalu besar. (Max 5MB)'];
    }
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        return ['status' => 'error', 'message' => 'Hanya file JPG, JPEG, PNG, & GIF yang diperbolehkan.'];
    }
    
    if (move_uploaded_file($file_array["tmp_name"], $target_file)) {
        // Hapus gambar lama jika ada
        if ($current_image_path && file_exists($target_dir . $current_image_path)) {
             // unlink($target_dir . $current_image_path); // Hati-hati saat menghapus file
        }
        return ['status' => 'success', 'path' => $new_file_name];
    } else {
        return ['status' => 'error', 'message' => 'Gagal mengupload file.'];
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action == 'add') {
            $nama_item = $_POST['nama_item'];
            $description = $_POST['description'] ?? null;
            $price_reguler = $_POST['price_reguler'];
            $price_diskon = $_POST['price_diskon'] ?? null;
            $type = $_POST['type'];
            $category_id = $_POST['category_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle Upload Gambar
            $upload_result = handle_upload($_FILES['image_file']);
            if ($upload_result['status'] == 'error') {
                 $_SESSION['status_message'] = '<div class="alert alert-warning">Gagal Tambah Menu: ' . $upload_result['message'] . '</div>';
                 header("Location: admin_menu.php"); exit;
            }
            $image_path = $upload_result['path'] ?? null;


            $sql = "INSERT INTO t_menu_items (category_id, nama_item, description, price_reguler, price_diskon, type, is_active, image_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category_id, $nama_item, $description, $price_reguler, $price_diskon, $type, $is_active, $image_path]);
            
            $_SESSION['status_message'] = '<div class="alert alert-success">Item menu **' . htmlspecialchars($nama_item) . '** berhasil ditambahkan.</div>';
            
        } elseif ($action == 'edit') {
            $item_id = $_POST['item_id'];
            $nama_item = $_POST['nama_item'];
            $description = $_POST['description'] ?? null;
            $price_reguler = $_POST['price_reguler'];
            $price_diskon = $_POST['price_diskon'] ?? null;
            $type = $_POST['type'];
            $category_id = $_POST['category_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $current_image = $_POST['current_image'] ?? null;

            // Handle Upload Gambar
            $upload_result = handle_upload($_FILES['image_file'], $current_image);
            if ($upload_result['status'] == 'error') {
                 $_SESSION['status_message'] = '<div class="alert alert-warning">Gagal Update Menu: ' . $upload_result['message'] . '</div>';
                 header("Location: admin_menu.php"); exit;
            }
            $image_path = $upload_result['path']; // Path baru atau path lama

            $sql = "UPDATE t_menu_items SET category_id = ?, nama_item = ?, description = ?, price_reguler = ?, price_diskon = ?, type = ?, is_active = ?, image_path = ?
                    WHERE item_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category_id, $nama_item, $description, $price_reguler, $price_diskon, $type, $is_active, $image_path, $item_id]);

            $_SESSION['status_message'] = '<div class="alert alert-info">Item menu **' . htmlspecialchars($nama_item) . '** berhasil diupdate.</div>';

        } elseif ($action == 'delete') {
            // (Logika delete menu tetap sama, hanya perlu ditambahkan logika penghapusan file gambar jika diperlukan)
            $item_id = $_POST['item_id'];
            // Logika untuk menghapus gambar dari folder 'uploads' di sini
            
            $sql = "DELETE FROM t_menu_items WHERE item_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$item_id]);

            $_SESSION['status_message'] = '<div class="alert alert-danger">Item menu berhasil dihapus.</div>';
        }

    } catch (PDOException $e) {
        $_SESSION['status_message'] = '<div class="alert alert-danger">Error saat memproses data: ' . $e->getMessage() . '</div>';
    }
}

header("Location: admin_menu.php");
exit;
?>