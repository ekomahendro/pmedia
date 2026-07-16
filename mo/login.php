<?php
session_start();
include 'db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($conn, $_POST['username']);
    $password = $_POST['password']; // Password tidak perlu di-sanitize
    
    $sql = "SELECT u.id, u.password_hash, u.level, u.full_name, d.name AS dept_name
            FROM users u
            JOIN departments d ON u.department_id = d.id
            WHERE u.username = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi Password
        if (password_verify($password, $user['password_hash'])) {
            // Login Berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_level'] = $user['level'];
            $_SESSION['department_name'] = $user['dept_name'];
            
            // Redirect ke Dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Username atau Password salah.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Username atau Password salah.</div>";
    }
    $stmt->close();
}
// Tambahkan Form HTML untuk input username dan password di sini
?>