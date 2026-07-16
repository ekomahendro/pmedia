<?php
require_once '_header.php';

// Hak Akses: Hanya pengguna yang login (anggota/pembina/super_admin)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Re-establish DB connection after _header
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$message = '';

// --- LOGIKA FORM (Tandai Sudah Dibaca) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'mark_read') {
    $pesan_id = (int)$_POST['pesan_id'];
    
    // Pastikan user adalah penerima pesan tersebut
    $sql_update = "UPDATE pesan SET is_read = TRUE WHERE id = ? AND receiver_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $pesan_id, $user_id);
    $stmt_update->execute();
    $stmt_update->close();
}

// --- LOGIKA PENGAMBILAN DATA PESAN MASUK ---
$sql_inbox = "
    SELECT 
        p.id, 
        p.message, 
        p.timestamp, 
        p.is_read,
        u.full_name AS sender_name,
        u.status AS sender_status
    FROM pesan p
    JOIN users u ON p.sender_id = u.id
    WHERE p.receiver_id = ?
    ORDER BY p.timestamp DESC
";
$stmt_inbox = $conn->prepare($sql_inbox);
$stmt_inbox->bind_param("i", $user_id);
$stmt_inbox->execute();
$result_inbox = $stmt_inbox->get_result();

$inbox_data = [];
while ($row = $result_inbox->fetch_assoc()) {
    $inbox_data[] = $row;
}
$stmt_inbox->close();

// Hitung pesan belum dibaca
$unread_count = array_sum(array_map(function($p) { return $p['is_read'] == 0; }, $inbox_data));

?>

    <h1 class="mb-4 text-primary"><i class="fas fa-envelope"></i> Pesan Masuk (Inbox)</h1>
    
    <?= $message ?>
    
    <div class="alert alert-info shadow-sm mb-4">
        Anda memiliki **<?= count($inbox_data) ?>** total pesan masuk. (**<?= $unread_count ?>** belum dibaca).
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Daftar Pesan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width: 5%"></th>
                            <th style="width: 25%">Pengirim</th>
                            <th style="width: 45%">Subjek / Isi Singkat</th>
                            <th style="width: 25%">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inbox_data)): ?>
                            <?php foreach ($inbox_data as $pesan): 
                                $is_new = $pesan['is_read'] == 0;
                                $row_class = $is_new ? 'fw-bold table-warning' : 'text-muted';
                                $icon = $is_new ? 'fas fa-envelope-open text-danger' : 'fas fa-envelope';
                            ?>
<tr class="<?= $row_class ?>" 
    style="cursor: pointer;"
    onclick="window.location='pesan_detail.php?id=<?= $pesan['id'] ?>';">
    
    <td>
        <i class="<?= $icon ?>"></i>
        <?php if ($is_new): ?>
        <form method="post" class="d-inline" title="Tandai sudah dibaca">
            <input type="hidden" name="action" value="mark_read">
            <input type="hidden" name="pesan_id" value="<?= $pesan['id'] ?>">
            <button type="submit" class="btn btn-sm btn-link p-0"><i class="fas fa-check-double text-success"></i></button>
        </form>
        <?php endif; ?>
    </td>
    </tr>

                            <div class="modal fade" id="readModal<?= $pesan['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-eye me-1"></i> Detail Pesan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted mb-1">Dari: <strong><?= htmlspecialchars($pesan['sender_name']) ?></strong> (<?= strtoupper($pesan['sender_status']) ?>)</p>
                                            <p class="text-muted mb-3">Dikirim: <?= date('d M Y H:i', strtotime($pesan['timestamp'])) ?></p>
                                            
                                            <hr>
                                            <h6>Isi Pesan:</h6>
                                            <div class="alert alert-light border p-3">
                                                <?= nl2br(htmlspecialchars($pesan['message'])) ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <?php if ($is_new): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input type="hidden" name="pesan_id" value="<?= $pesan['id'] ?>">
                                                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Tandai Sudah Dibaca</button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">Kotak masuk Anda kosong.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php 
$conn->close();
require_once '_footer.php';
?>