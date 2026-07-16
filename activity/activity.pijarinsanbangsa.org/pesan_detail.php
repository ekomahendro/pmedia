<?php
require_once '_header.php';

// Hak Akses: Hanya pengguna yang login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$root_pesan_id = (int)($_GET['id'] ?? 0); // ID pesan awal/root
$message = '';

if ($root_pesan_id <= 0) {
    $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Pesan tidak ditemukan.</div>";
}

// --- LOGIKA FORM (Kirim Balasan) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $balasan_message = sanitize_input($conn, $_POST['balasan_message']);
    $original_sender_id = (int)$_POST['original_sender_id'];
    $original_receiver_id = (int)$_POST['original_receiver_id'];
    $reply_to_id = (int)$_POST['reply_to_id']; // ID pesan root

    // Tentukan penerima balasan (lawan bicara)
    $receiver_id = ($user_id == $original_receiver_id) ? $original_sender_id : $original_receiver_id;

    if (!empty($balasan_message) && $receiver_id > 0) {
        $sql_reply = "INSERT INTO pesan (sender_id, receiver_id, parent_id, message, is_read) VALUES (?, ?, ?, ?, FALSE)";
        $stmt_reply = $conn->prepare($sql_reply);
        $stmt_reply->bind_param("iiss", $user_id, $receiver_id, $reply_to_id, $balasan_message);
        
        if ($stmt_reply->execute()) {
            $message = "<div class='alert alert-success'><i class='fas fa-reply'></i> Balasan berhasil dikirim!</div>";
            // Redirect untuk menghindari pengiriman ulang dan refresh tampilan
            header("location: pesan_detail.php?id=" . $root_pesan_id);
            exit;
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mengirim balasan.</div>";
        }
        $stmt_reply->close();
    }
}

// --- LOGIKA PENGAMBILAN DATA PERCAKAPAN ---
$conversation = [];
$thread_participants = [];
$can_reply = false;

if ($root_pesan_id > 0) {
    // Ambil pesan root dan semua balasannya
    $sql_conversation = "
        SELECT 
            p.id, p.message, p.timestamp, p.is_read, p.sender_id, p.receiver_id, 
            u.full_name AS sender_name, u.status AS sender_status
        FROM pesan p
        JOIN users u ON p.sender_id = u.id
        WHERE (p.id = ? OR p.parent_id = ?)
        ORDER BY p.timestamp ASC
    ";
    $stmt_conv = $conn->prepare($sql_conversation);
    $stmt_conv->bind_param("ii", $root_pesan_id, $root_pesan_id);
    $stmt_conv->execute();
    $result_conv = $stmt_conv->get_result();

    while ($row = $result_conv->fetch_assoc()) {
        $conversation[] = $row;
        $thread_participants[$row['sender_id']] = $row['sender_name'];
        $thread_participants[$row['receiver_id']] = $row['receiver_name'] ?? 'Unknown'; 
        
        // Tandai sebagai sudah dibaca jika user ini adalah penerima
        if ($row['receiver_id'] == $user_id && $row['is_read'] == 0) {
            $sql_mark = "UPDATE pesan SET is_read = TRUE WHERE id = ?";
            $stmt_mark = $conn->prepare($sql_mark);
            $stmt_mark->bind_param("i", $row['id']);
            $stmt_mark->execute();
            $stmt_mark->close();
        }
    }
    $stmt_conv->close();

    // Tentukan apakah user saat ini adalah bagian dari percakapan (sender atau receiver di pesan root)
    if (!empty($conversation)) {
        $root_msg = $conversation[0];
        $original_sender = $root_msg['sender_id'];
        $original_receiver = $root_msg['receiver_id'];
        
        if ($user_id == $original_sender || $user_id == $original_receiver) {
            $can_reply = true;
        }
    }
}

?>

<h1 class="mb-4 text-primary"><i class="fas fa-comments"></i> Detail Percakapan</h1>
<a href="pesan_list.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> Kembali ke Inbox</a>

<?= $message ?>

<?php if (empty($conversation)): ?>
    <div class="alert alert-warning shadow"><i class="fas fa-info-circle"></i> Pesan atau riwayat percakapan tidak ditemukan.</div>
<?php else: 
    $root_msg = $conversation[0]; // Pesan awal
?>
    
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-history me-1"></i> Riwayat Pesan</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($conversation as $index => $msg): 
                    $is_sender = $msg['sender_id'] == $user_id;
                    $chat_class = $is_sender ? 'text-end bg-light-blue' : 'text-start';
                    $align_class = $is_sender ? 'ms-auto' : 'me-auto';
                    $bg_color = $is_sender ? 'bg-primary text-white' : 'bg-light text-dark border';
                ?>
                <div class="list-group-item border-start border-end border-1 py-3 <?= $chat_class ?>" style="border-left: 5px solid <?= $is_sender ? '#0d6efd' : '#28a745' ?> !important;">
                    <div class="card <?= $bg_color ?> shadow-sm mb-1" style="max-width: 80%; width: fit-content;">
                        <div class="card-body p-3">
                            <p class="mb-1 fw-bold fs-6">
                                <?= $is_sender ? 'Anda' : htmlspecialchars($msg['sender_name']) ?>
                                <small class="badge bg-secondary ms-2"><?= strtoupper($msg['sender_status']) ?></small>
                            </p>
                            <p class="card-text mb-0"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        </div>
                        <div class="card-footer p-2 pt-0 border-0 <?= $bg_color ?>">
                             <small class="text-xs fst-italic float-end" style="opacity: 0.8;"><?= date('d M Y H:i', strtotime($msg['timestamp'])) ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php if ($can_reply): ?>
        <div class="card shadow-lg">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-reply me-1"></i> Balas Percakapan</h5>
            </div>
            <div class="card-body">
                <form method="post" action="pesan_detail.php?id=<?= $root_pesan_id ?>">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="reply_to_id" value="<?= $root_pesan_id ?>">
                    <input type="hidden" name="original_sender_id" value="<?= $root_msg['sender_id'] ?>">
                    <input type="hidden" name="original_receiver_id" value="<?= $root_msg['receiver_id'] ?>">
                    
                    <div class="mb-3">
                        <label for="balasan_message" class="form-label">Tulis Balasan Anda:</label>
                        <textarea name="balasan_message" id="balasan_message" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Kirim Balasan</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php 
$conn->close();
require_once '_footer.php';
?>