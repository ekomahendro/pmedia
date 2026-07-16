<?php
session_start();
include 'db_connect.php';
// Cek akses: hanya Engineering yang bisa
if ($_SESSION['user_level'] !== 'engineering') {
    die("Akses ditolak.");
}

// Ambil ID Staff Engineering yang login
$user_id = $_SESSION['user_id'];
// Query untuk mendapatkan staff_engineering_id
$sql_staff = "SELECT id FROM staff_engineering WHERE user_id = ?";
$stmt_staff = $conn->prepare($sql_staff);
$stmt_staff->bind_param("i", $user_id);
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
if ($result_staff->num_rows > 0) {
    $staff = $result_staff->fetch_assoc();
    $staff_incharge_id = $staff['id'];

    // Ambil MO yang sedang dikerjakan staff ini
    $sql_mo = "SELECT id, location, case_type, date_start, date_estimate_finish 
               FROM maintenance_orders 
               WHERE staff_incharge_id = ? AND status IN ('approved', 'progress')
               ORDER BY date_start ASC";
               
    $stmt_mo = $conn->prepare($sql_mo);
    $stmt_mo->bind_param("i", $staff_incharge_id);
    $stmt_mo->execute();
    $schedule_data = $stmt_mo->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_mo->close();
} else {
    $schedule_data = [];
}
$stmt_staff->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Jadwal Tugas Engineering</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Jadwal Tugas Maintenance (<?php echo $_SESSION['full_name']; ?>)</h2>
    
    <?php if (!empty($schedule_data)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>MO ID</th>
                    <th>Lokasi</th>
                    <th>Tipe Kasus</th>
                    <th>Tgl Mulai</th>
                    <th>Est. Selesai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedule_data as $mo): ?>
                    <tr>
                        <td><?php echo $mo['id']; ?></td>
                        <td><?php echo htmlspecialchars($mo['location']); ?></td>
                        <td><?php echo htmlspecialchars($mo['case_type']); ?></td>
                        <td><?php echo date('d M Y', strtotime($mo['date_start'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($mo['date_estimate_finish'])); ?></td>
                        <td>
                            <a href="mo_followup.php?id=<?php echo $mo['id']; ?>" class="btn btn-sm btn-info">Detail & Follow Up</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">Tidak ada tugas maintenance yang dijadwalkan saat ini.</div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
</body>
</html>