<?php
session_start();
include 'db_connect.php'; // Koneksi database
// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data POST
    $location = sanitize_input($conn, $_POST['location']);
    $case_type = sanitize_input($conn, $_POST['case_type']);
    $request_details = sanitize_input($conn, $_POST['request_details']);
    $date_created = date('Y-m-d H:i:s');
    
    // Status awal: pending (menunggu persetujuan supervisor)
    $sql = "INSERT INTO maintenance_orders (order_by_user_id, location, case_type, request_details, date_created, status)
            VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $location, $case_type, $request_details, $date_created);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Maintenance Order berhasil dibuat! Menunggu persetujuan Supervisor.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Maintenance Order</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Form Permintaan Maintenance Order (MO)</h2>
    <p>Dipesan oleh: **<?php echo htmlspecialchars($_SESSION['full_name']); ?>**</p>
    <?php echo $message; ?>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        
        <div class="form-group">
            <label for="location">Lokasi Kerusakan:</label>
            <input type="text" class="form-control" id="location" name="location" required>
        </div>
        
        <div class="form-group">
            <label for="case_type">Tipe Kasus (Misal: AC, Plumbing, Electrical):</label>
            <input type="text" class="form-control" id="case_type" name="case_type" required>
        </div>
        
        <div class="form-group">
            <label for="request_details">Detail Permintaan / Kerusakan:</label>
            <textarea class="form-control" id="request_details" name="request_details" rows="4" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Submit MO</button>
        <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>