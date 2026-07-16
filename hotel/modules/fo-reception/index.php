<?php
require_once '../../config.php';
check_login();

// Deteksi mode akses dari modul Housekeeping
$is_hk_mode = (isset($_GET['source']) && $_GET['source'] === 'hk');
$msg = $_GET['msg'] ?? '';
$msg_type = $_GET['msg_type'] ?? 'success';

// Advanced Search & Date Filter Logic
$search   = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$start_dt = $_GET['start_date'] ?? '';
$end_dt   = $_GET['end_date'] ?? '';
$type_dt  = $_GET['date_type'] ?? 'arrival';

$where_clauses = ["1=1"];
if (!empty($search)) {
    $where_clauses[] = "(g.guest_name LIKE '%$search%' OR r.room_number LIKE '%$search%' OR g.phone_number LIKE '%$search%')";
}

// Bikin salinan clause khusus filter tanggal agar tidak merusak pencarian berkas Cancel/No-Show
$date_where_clauses = $where_clauses;
if (!empty($start_dt) && !empty($end_dt)) {
    $field_target = ($type_dt === 'departure') ? 'r.departure_date' : 'r.arrival_date';
    $date_where_clauses[] = "($field_target BETWEEN '$start_dt' AND '$end_dt')";
}

$where_query_with_date = implode(" AND ", $date_where_clauses);
$where_query_basic     = implode(" AND ", $where_clauses);

// Pull Parameters & Master Rooms
$guests_datalist = mysqli_query($conn, "SELECT * FROM htl_guests ORDER BY guest_name ASC");
$arr_opts = mysqli_query($conn, "SELECT * FROM htl_arrangements ORDER BY arr_code ASC");
$seg_opts = mysqli_query($conn, "SELECT * FROM htl_segments ORDER BY seg_code ASC");
$src_opts = mysqli_query($conn, "SELECT * FROM htl_sources ORDER BY src_code ASC");
$rooms_available = mysqli_query($conn, "SELECT * FROM htl_rooms ORDER BY room_number ASC");

// Base Query String Blueprint
$base_select = "SELECT r.*, g.id_guest, g.guest_name, g.identity_number, g.phone_number, g.photo_profile, g.photo_identity,
                (SELECT IFNULL(SUM(amount), 0) FROM htl_deposits WHERE registration_id = r.id_reg) as total_deposit
                FROM htl_registrations r 
                JOIN htl_guests g ON r.id_guest = g.id_guest WHERE ";

// Pembagian Tab List Berdasarkan Status & Filter yang Sesuai
$res_arrival   = mysqli_query($conn, $base_select . "$where_query_with_date AND r.status = 'arrival' ORDER BY r.arrival_date ASC");
$res_inhouse   = mysqli_query($conn, $base_select . "$where_query_with_date AND r.status = 'inhouse' ORDER BY r.room_number ASC");
$res_departure = mysqli_query($conn, $base_select . "$where_query_with_date AND r.status = 'departure' ORDER BY r.actual_checkout DESC");

// FIX: Untuk No-Show dan Cancel, kita gunakan basic search query tanpa membatasi range tanggal agar riwayat pembatalan tetap terlihat jelas
$res_noshow    = mysqli_query($conn, $base_select . "$where_query_basic AND r.status = 'noshow' ORDER BY r.arrival_date DESC");
$res_cancel = mysqli_query($conn,
    $base_select . "$where_query_basic 
    AND LOWER(r.status) IN ('cancel','cancelled','canceled') 
    ORDER BY r.id_reg DESC"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>FO Reception Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-size: 0.9rem; }
        .navbar-custom { background: #1e3c72; }
        .thumb-img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; cursor: pointer; }
        .grid-matrix { display: grid; grid-template-columns: repeat(11, 1fr); gap: 2px; background: #e0e0e0; padding: 4px; border-radius: 6px; }
        .cell-head { background: #343a40; color: white; font-weight: bold; text-align: center; padding: 5px; font-size: 11px; }
        .cell-room { background: #f8f9fa; font-weight: bold; padding: 6px; border: 1px solid #dee2e6; text-align: center; display: flex; align-items: center; justify-content: center; }
        .cell-status { text-align: center; padding: 4px 2px; font-size: 10px; color: white; font-weight: bold; min-height: 38px; display: flex; flex-direction: column; justify-content: center; line-height: 1.1; }
        .guest-name-matrix { font-size: 9px; font-weight: normal; opacity: 0.9; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 75px; margin: 0 auto; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-3">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="../../dashboard.php"><i class="bi bi-building me-2"></i> Front Office Desk</a>
        <div>
            <?php if(!$is_hk_mode): ?>
                <a href="guest_cards.php" class="btn btn-sm btn-info rounded-pill px-3 me-2 text-white"><i class="bi bi-card-id"></i> Guest Card</a>
                <a href="setup_master.php" class="btn btn-sm btn-warning rounded-pill px-3 me-2"><i class="bi bi-sliders"></i> Parameter</a>
            <?php else: ?>
                <span class="badge bg-danger p-2 rounded-pill px-3 me-2"><i class="bi bi-eye-fill"></i> Housekeeping View Only</span>
            <?php endif; ?>
            <a href="../../dashboard.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-dark m-0"><?= $is_hk_mode ? 'Housekeeping Information Hub' : 'Front Office Reception Operasional'; ?></h4>
            <p class="text-secondary small mb-0">Manajemen Pemesanan Terintegrasi Dokumen Lampiran Foto Tamu & Kontrol Status Hunian.</p>
        </div>
        <?php if(!$is_hk_mode): ?>
            <button type="button" class="btn btn-primary rounded-pill shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalReservation">
                <i class="bi bi-calendar-plus-fill me-1"></i> Buat Reservasi Baru
            </button>
        <?php endif; ?>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= htmlspecialchars($msg_type); ?> alert-dismissible fade show py-2 small" role="alert">
            <?= htmlspecialchars($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- PENGEMBANGAN 1: DIAGRAM MATRIX TOGGLE TOGETHER WITH GUEST NAME DISPLAY -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0 text-uppercase text-secondary small">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Diagram Matrix Status Kamar (10 Hari Kedepan)
            </h6>
            <button class="btn btn-sm btn-outline-secondary py-0 px-2 small text-xs" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMatrix" aria-expanded="false" aria-controls="collapseMatrix">
                <i class="bi bi-eye-slash"></i> Toggle Hide/Show Matrix
            </button>
        </div>
        
        <div class="collapse show mt-2" id="collapseMatrix">
            <div class="grid-matrix text-center">
                <div class="cell-head">Room</div>
                <?php 
                $dates_range = [];
                for($i=0; $i<10; $i++) {
                    $d = date('Y-m-d', strtotime("+$i days"));
                    $dates_range[] = $d;
                    echo "<div class='cell-head'>".date('d/m', strtotime($d))."</div>";
                }
                mysqli_data_seek($rooms_available, 0);
                while($rm = mysqli_fetch_assoc($rooms_available)) {
                    echo "<div class='cell-room'>".$rm['room_number']."</div>";
                    foreach($dates_range as $date) {
                        $r_num = $rm['room_number'];
                        // Ambil status kamar beserta nama tamu pendukung
                        $check_booking = mysqli_query($conn, "
    SELECT r.status, g.guest_name 
    FROM htl_registrations r 
    JOIN htl_guests g ON r.id_guest = g.id_guest 
    WHERE r.room_number = '$r_num'
    AND '$date' >= r.arrival_date
    AND '$date' < r.departure_date
    AND r.status IN ('arrival','inhouse','OO')
    LIMIT 1
");
                        if($book = mysqli_fetch_assoc($check_booking)) {
                            if ($book['status'] == 'OO') {
                                $status_badge = 'bg-dark';
                                $status_text  = 'OO';
                                $g_display    = 'Maintenance';
                            } else {
                                $status_badge = ($book['status'] == 'arrival') ? 'bg-warning text-dark' : 'bg-danger';
                                $status_text  = ($book['status'] == 'arrival') ? 'RES' : 'OCC';
                                $g_display    = htmlspecialchars($book['guest_name']);
                            }
                            echo "<div class='cell-status $status_badge'>
                                    <span>$status_text</span>
                                    <span class='guest-name-matrix' title='$g_display'>$g_display</span>
                                  </div>";
                        } else {
                            echo "<div class='cell-status bg-success'><span>VAC</span><span class='guest-name-matrix text-white-50'>-</span></div>";
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- ADVANCED FILTER FORM -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
        <form method="GET" action="index.php" class="row g-2 align-items-end small">
            <?php if($is_hk_mode): ?><input type="hidden" name="source" value="hk"><?php endif; ?>
            <div class="col-md-3">
                <label class="fw-bold text-muted small">Cari Nama / Kamar / No. HP</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($search); ?>" placeholder="Ketik pencarian...">
            </div>
            <div class="col-md-3">
                <label class="fw-bold text-muted small">Berdasarkan Parameter</label>
                <select name="date_type" class="form-select form-select-sm">
                    <option value="arrival" <?= $type_dt == 'arrival' ? 'selected':''; ?>>Tanggal Arrival (Kedatangan)</option>
                    <option value="departure" <?= $type_dt == 'departure' ? 'selected':''; ?>>Tanggal Departure (Keberangkatan)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-bold text-muted small">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_dt; ?>">
            </div>
            <div class="col-md-2">
                <label class="fw-bold text-muted small">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_dt; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>

    <!-- TABEL MANIFEST LIST (DENGAN TAMBAHAN NO SHOW & CANCEL LIST) -->
    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
        <ul class="nav nav-tabs mb-3" id="foTabs">
            <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#arrival-pane"><i class="bi bi-box-arrow-in-right text-success"></i> Arrival List</button></li>
            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#inhouse-pane"><i class="bi bi-door-open-fill text-primary"></i> Inhouse Guest</button></li>
            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#departure-pane"><i class="bi bi-box-arrow-left text-secondary"></i> History Departure</button></li>
            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#noshow-pane"><i class="bi bi-exclamation-octagon-fill text-warning"></i> No-Show Guest List</button></li>
            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#cancel-pane"><i class="bi bi-x-circle-fill text-danger"></i> Cancel List</button></li>
        </ul>

        <div class="tab-content">
            <?php 
            $panes = [
                'arrival-pane' => $res_arrival, 
                'inhouse-pane' => $res_inhouse, 
                'departure-pane' => $res_departure,
                'noshow-pane' => $res_noshow,
                'cancel-pane' => $res_cancel
            ];
            foreach ($panes as $pane_id => $resource):
            ?>
            <div class="tab-pane fade <?= $pane_id === 'arrival-pane'?'show active':''; ?>" id="<?= $pane_id; ?>">
                <table class="table table-sm table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Lampiran</th><th>Kode</th><th>Nama Tamu / No HP</th><th>No Kamar</th><th>CI - CO Period</th><th>ETA</th><th>Total Deposit</th><th>Stay</th><th>Aksi Manifes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($resource) === 0): ?>
                            <tr><td colspan="9" class="text-center text-muted py-3">Tidak ada data manifes pada kategori ini.</td></tr>
                        <?php endif; ?>
                        <?php while($row = mysqli_fetch_assoc($resource)): ?>
                            <tr>
                                <td>
                                    <?php if($row['photo_profile']): ?><img src="../../uploads/guests/<?= $row['photo_profile']; ?>" class="thumb-img me-1" data-bs-toggle="modal" data-bs-target="#viewPhotoModal" onclick="showBigPhoto('../../uploads/guests/<?= $row['photo_profile']; ?>')"><?php endif; ?>
                                    <?php if($row['photo_identity']): ?><img src="../../uploads/guests/<?= $row['photo_identity']; ?>" class="thumb-img" data-bs-toggle="modal" data-bs-target="#viewPhotoModal" onclick="showBigPhoto('../../uploads/guests/<?= $row['photo_identity']; ?>')"><?php endif; ?>
                                </td>
                                <td><strong class="text-primary"><?= $row['booking_code']; ?></strong></td>
                                <td><strong><?= htmlspecialchars($row['guest_name']); ?></strong><br><span class="text-muted small"><?= $row['phone_number']; ?></span></td>
                                <td><span class="badge bg-primary fs-6">#<?= $row['room_number']; ?></span></td>
                                <td><span class="text-success fw-bold"><?= date('d/m/Y', strtotime($row['arrival_date'])); ?></span> s/d <span class="text-danger fw-bold"><?= date('d/m/Y', strtotime($row['departure_date'])); ?></span></td>
                                <td><i class="bi bi-clock me-1"></i><?= !empty($row['eta']) ? date('H:i', strtotime($row['eta'])) : '-'; ?></td>
                                <td><strong>Rp <?= number_format($row['total_deposit'], 0, ',', '.'); ?></strong></td>
                                <td><span class="badge bg-secondary"><?= $row['history_stay_count']; ?>x</span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2 small btn-view-detail" data-json='<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><i class="bi bi-info-circle"></i> Detail</button>
                                    
                                    <?php if(!$is_hk_mode): ?>
                                        <?php if(in_array($row['status'], ['arrival', 'noshow'])): ?>
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2 small btn-edit-res" data-json='<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><i class="bi bi-pencil"></i> Edit</button>
                                            <a href="action_handler.php?action=checkin&id=<?= $row['id_reg']; ?>&room=<?= $row['room_number']; ?>" class="btn btn-sm btn-success py-0 px-2 fw-bold" onclick="return confirm('Proses Check-In?')">Check-In</a>
                                            <a href="action_handler.php?action=cancel&id=<?= $row['id_reg']; ?>" class="btn btn-sm btn-outline-danger py-0 px-2 small" onclick="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')"><i class="bi bi-x-circle"></i> Cancel</a>
                                        <?php elseif($row['status'] == 'inhouse'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 small" onclick="openDepositModal(<?= $row['id_reg']; ?>)"><i class="bi bi-cash"></i> +Dep</button>
                                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1 text-dark small" onclick="openMoveRoomModal(<?= $row['id_reg']; ?>, '<?= $row['room_number']; ?>')"><i class="bi bi-arrow-left-right"></i> Move</button>
                                            <a href="action_handler.php?action=checkout&id=<?= $row['id_reg']; ?>&room=<?= $row['room_number']; ?>" class="btn btn-sm btn-danger py-0 px-2 fw-bold" onclick="return confirm('Proses Check-Out?')">Check-Out</a>
                                        <?php elseif(in_array(strtolower($row['status']), ['cancel','cancelled','canceled'])): ?>
                                            <a href="action_handler.php?action=reactivate&id=<?= $row['id_reg']; ?>" class="btn btn-sm btn-success py-0 px-2 fw-bold" onclick="return confirm('Kembalikan data ini menjadi Reservasi Aktif?')">
                                                <i class="bi bi-arrow-counterclockwise"></i> Reactivate
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small text-capitalize">Archived (<?= $row['status']; ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'modals_view.php'; ?>

<script>
// Penataan Fleksibel Aman Mengikuti Gaya DOMContentLoaded Rekomendasi
let modalDetail = null;
let modalDeposit = null;
let modalMove = null;
let modalEdit = null;

function openDepositModal(id) { 
    document.getElementById('dep_reg_id').value = id; 
    if(modalDeposit) modalDeposit.show(); 
}

function openMoveRoomModal(id, currentRoom) {
    document.getElementById('move_reg_id').value = id;
    document.getElementById('move_old_room').value = currentRoom;
    document.getElementById('display_old_room').value = "Kamar " + currentRoom;
    if(modalMove) modalMove.show();
}

function showBigPhoto(src) { 
    document.getElementById('bigPhotoPreview').src = src; 
}

function viewDetailedManifest(data) {
    document.getElementById('det_guest_name').innerText = data.guest_name;
    document.getElementById('det_phone').innerText = data.phone_number ? data.phone_number : '-';
    document.getElementById('det_room').innerText = "Kamar " + data.room_number;
    document.getElementById('det_eta').innerText = data.eta ? data.eta : '-';
    document.getElementById('det_history').innerText = data.history_stay_count + " Kali Menginap";
    document.getElementById('det_notes').innerText = data.notes ? data.notes : '-';
    if(document.getElementById('det_rate')) {
        document.getElementById('det_rate').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(data.room_rate ?? 0);
    }
    
    fetch(`get_history_json.php?id=${data.id_reg}`)
        .then(res => res.json())
        .then(history => {
            let moveHtml = history.moves.length === 0 ? '<span class="text-muted small">Belum pernah pindah kamar.</span>' : '';
            history.moves.forEach(m => { moveHtml += `<div class="small border-bottom py-1"><b>${m.moved_at}</b>: Kamar ${m.from_room} &rarr; ${m.to_room} (${m.reason})</div>`; });
            document.getElementById('container_move_history').innerHTML = moveHtml;

            let depHtml = history.deposits.length === 0 ? '<span class="text-muted small">Belum ada deposit.</span>' : '';
            history.deposits.forEach(d => { depHtml += `<div class="small border-bottom py-1 text-success"><b>${d.received_at}</b>: Rp ${new Intl.NumberFormat('id-ID').format(d.amount)} (${d.deposit_notes})</div>`; });
            document.getElementById('container_deposit_history').innerHTML = depHtml;
        })
        .catch(err => {
            document.getElementById('container_move_history').innerHTML = '<span class="text-danger small">Gagal memuat log.</span>';
        });
    if(modalDetail) modalDetail.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const elDetail = document.getElementById('modalDetailManifest');
    const elDeposit = document.getElementById('modalDeposit');
    const elMove = document.getElementById('modalMoveRoom');
    const elEdit = document.getElementById('modalEditReservation');

    if (elDetail)  modalDetail = new bootstrap.Modal(elDetail);
    if (elDeposit) modalDeposit = new bootstrap.Modal(elDeposit);
    if (elMove)    modalMove = new bootstrap.Modal(elMove);
    if (elEdit)    modalEdit = new bootstrap.Modal(elEdit);

    // Click trigger detail manifest
    document.querySelectorAll('.btn-view-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            viewDetailedManifest(JSON.parse(this.dataset.json));
        });
    });

    // Autofill Form Edit Kamar & Reservasi
    document.querySelectorAll('.btn-edit-res').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.json);
            
            document.getElementById('edit_id_reg').value = data.id_reg;
            document.getElementById('edit_guest_id').value = data.id_guest;
            document.getElementById('edit_old_room').value = data.room_number;
            
            const selectGuest = document.getElementById('edit_guest_select');
            if(selectGuest) { selectGuest.value = data.id_guest; }
            
            document.getElementById('edit_room_number').value = data.room_number;
            document.getElementById('edit_room_rate').value = data.room_rate;
            document.getElementById('edit_arrangement').value = data.arrangement;
            document.getElementById('edit_segment').value = data.segment;
            document.getElementById('edit_source').value = data.source;
            document.getElementById('edit_arrival').value = data.arrival_date;
            document.getElementById('edit_departure').value = data.departure_date;
            document.getElementById('edit_eta').value = data.eta;
            document.getElementById('edit_notes').value = data.notes;
            document.getElementById('edit_history_stay').value = data.history_stay_count;

            if(modalEdit) modalEdit.show();
        });
    });

    // Form Pintasan Cepat +Guest Card Baru via AJAX
    const btnSaveFastGuest = document.getElementById('btnSubmitFastGuest');
    if(btnSaveFastGuest) {
        btnSaveFastGuest.addEventListener('click', function() {
            const name = document.getElementById('fast_guest_name').value;
            const identity = document.getElementById('fast_identity').value;
            const phone = document.getElementById('fast_phone').value;

            if(!name) { alert('Nama tamu wajib diisi!'); return; }

            const formData = new FormData();
            formData.append('ajax_add_guest', '1');
            formData.append('guest_name', name);
            formData.append('identity_number', identity);
            formData.append('phone_number', phone);

            fetch('action_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) {
                    const selectRes = document.getElementById('res_guest_select');
                    const selectEdit = document.getElementById('edit_guest_select');
                    
                    selectRes.add(new Option(`${name} - KTP: ${identity} (Telp: ${phone})`, resData.id_guest), undefined);
                    if(selectEdit) { selectEdit.add(new Option(`${name} [KTP: ${identity}]`, resData.id_guest), undefined); }
                    
                    selectRes.value = resData.id_guest;
                    document.getElementById('formFastGuest').reset();
                    bootstrap.Modal.getInstance(document.getElementById('modalFastAddGuest')).hide();
                } else {
                    alert('Gagal menyimpan: ' + resData.message);
                }
            });
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>