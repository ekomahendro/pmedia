<?php
require_once '_header.php';

// Hak Akses: HANYA Super Admin
if ($_SESSION['status'] !== 'super_admin') {
    header("location: dashboard.php");
    exit;
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

$user_id = $_SESSION['id'];
$message = '';

// --- LOGIKA HAPUS LOG (CLEAR LOG) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'clear_log') {
    $sql_clear = "TRUNCATE TABLE log_status"; // TRUNCATE lebih cepat daripada DELETE FROM
    
    if ($conn->query($sql_clear)) {
        $message = "<div class='alert alert-success'><i class='fas fa-trash-alt'></i> Semua riwayat log berhasil dihapus!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menghapus log.</div>";
    }
    // Redirect untuk membersihkan POST dan menampilkan hasil
    header("location: audit_log.php?msg=" . urlencode(strip_tags($message)));
    exit;
}

// Cek pesan dari redirect setelah clear log
if (isset($_GET['msg'])) {
    $message = "<div class='alert alert-success'>" . htmlspecialchars($_GET['msg']) . "</div>";
}

// --- TAMPILKAN AUDIT LOG ---
$sql_log = "
    SELECT 
        ls.timestamp, 
        u.full_name AS user_affected, 
        ls.old_status, 
        ls.new_status, 
        uc.full_name AS changed_by 
    FROM log_status ls
    JOIN users u ON ls.user_id = u.id
    JOIN users uc ON ls.changed_by_id = uc.id
    ORDER BY ls.timestamp DESC
";
$result_log = $conn->query($sql_log);
$log_data = $result_log ? $result_log->fetch_all(MYSQLI_ASSOC) : [];
$total_logs = count($log_data);

?>

    <h1 class="mb-4 text-warning"><i class="fas fa-history"></i> Audit Log Status Pengguna</h1>
    
    <?= $message ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="input-group" style="max-width: 350px;">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" id="liveSearchInput" class="form-control" placeholder="Cari berdasarkan nama atau status...">
        </div>
        
        <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#clearLogModal">
            <i class="fas fa-trash-alt me-1"></i> Clear Semua Log (<?= $total_logs ?>)
        </button>
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Riwayat Perubahan Status</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover table-striped table-sm" id="logTable">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User Terdampak</th>
                        <th>Status Lama</th>
                        <th>Status Baru</th>
                        <th>Diubah Oleh</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    <?php if ($total_logs > 0): ?>
                        <?php foreach($log_data as $row): ?>
                        <tr>
                            <td><?= date('d M Y H:i', strtotime($row['timestamp'])) ?></td>
                            <td><?= htmlspecialchars($row['user_affected']) ?></td>
                            <td><span class="badge bg-secondary"><?= strtoupper($row['old_status']) ?></span></td>
                            <td><span class="badge bg-success"><?= strtoupper($row['new_status']) ?></span></td>
                            <td><?= htmlspecialchars($row['changed_by']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noResultsRow"><td colspan="5" class="text-center text-muted">Belum ada riwayat perubahan status.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal fade" id="clearLogModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus Log</h5>
                </div>
                <form method="post" action="audit_log.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="clear_log">
                        <p>Apakah Anda yakin ingin menghapus **SEMUA (<?= $total_logs ?>)** riwayat audit log status pengguna?</p>
                        <div class="alert alert-warning">
                            Aksi ini tidak dapat dibatalkan! Log akan dihapus secara permanen.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Ya, Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<?php 
$conn->close();
require_once '_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('liveSearchInput');
    const tableBody = document.getElementById('logTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');
    let noResultsRow = document.getElementById('noResultsRow');

    if (!noResultsRow) {
        // Buat baris "tidak ada hasil" jika belum ada (hanya jika ada data awal)
        noResultsRow = document.createElement('tr');
        noResultsRow.id = 'noResultsRow';
        noResultsRow.style.display = 'none';
        noResultsRow.innerHTML = '<td colspan="5" class="text-center text-muted">Tidak ada riwayat yang cocok dengan pencarian.</td>';
        tableBody.appendChild(noResultsRow);
    }
    
    searchInput.addEventListener('keyup', function () {
        const filter = searchInput.value.toLowerCase();
        let found = false;
        
        for (let i = 0; i < tableRows.length; i++) {
            const row = tableRows[i];
            // Lewati baris "tidak ada hasil"
            if (row.id === 'noResultsRow') continue; 

            // Dapatkan teks dari semua kolom di baris (kecuali kolom Waktu)
            const cells = row.getElementsByTagName('td');
            let rowText = '';
            for (let j = 1; j < cells.length; j++) { // Mulai dari index 1 (skip Waktu)
                rowText += cells[j].textContent || cells[j].innerText;
            }
            rowText = rowText.toLowerCase();

            if (rowText.includes(filter)) {
                row.style.display = "";
                found = true;
            } else {
                row.style.display = "none";
            }
        }
        
        // Tampilkan/sembunyikan pesan "tidak ada hasil"
        if (noResultsRow) {
            noResultsRow.style.display = found ? 'none' : 'table-row';
        }
    });
});
</script>