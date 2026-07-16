<?php
require_once '_header.php';

// Hak Akses: Hanya Pembina dan Super Admin
if ($_SESSION['status'] !== 'pembina' && $_SESSION['status'] !== 'super_admin') {
    header("location: dashboard.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$sender_id = $_SESSION['id'];
$message = '';

// Query untuk mengambil daftar anggota binaan aktif (sebagai penerima)
$sql_recipients = "
    SELECT u.id, u.full_name 
    FROM users u
    JOIN bimbingan b ON u.id = b.anggota_id
    WHERE b.pembina_id = ? AND b.is_active = TRUE
    ORDER BY u.full_name ASC
";
$stmt_recipients = $conn->prepare($sql_recipients);
$stmt_recipients->bind_param("i", $sender_id);
$stmt_recipients->execute();
$result_recipients = $stmt_recipients->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_id = (int)$_POST['receiver_id'];
    $pesan = sanitize_input($conn, $_POST['pesan']);

    $sql_insert = "INSERT INTO pesan (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iis", $sender_id, $receiver_id, $pesan);
    
    if ($stmt_insert->execute()) {
        $message = "<div class='alert alert-success'><i class='fas fa-paper-plane'></i> Pesan berhasil dikirim!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mengirim pesan.</div>";
    }
    $stmt_insert->close();
}
?>

    <h1 class="mb-4 text-info"><i class="fas fa-paper-plane"></i> Kirim Pesan ke Anggota</h1>
    <?= $message ?>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Formulir Pesan Baru</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="receiver_id" class="form-label">Penerima</label>
                    <select name="receiver_id" id="receiver_id" class="form-select" required>
                        <option value="">-- Pilih Anggota Binaan --</option>
                        <?php while($row = $result_recipients->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="pesan" class="form-label">Isi Pesan</label>
                    <textarea name="pesan" id="pesan" class="form-control" rows="6" required></textarea>
                </div>

                <button type="submit" class="btn btn-info text-white"><i class="fas fa-paper-plane me-1"></i> Kirim Pesan</button>
            </form>
        </div>
    </div>

<?php 
$stmt_recipients->close();
$conn->close();
require_once '_footer.php';
?>