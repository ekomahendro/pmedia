<?php
require_once '../../config.php';
check_login();

$id_license = $_SESSION['id_license'];
$msg = ''; $msg_type = 'success';

$upload_dir = '../../uploads/guests/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ==========================================
// 1. PROSES SIMPAN GUEST CARD BARU (TANPA RESERVASI)
// ==========================================
if (isset($_POST['add_guest'])) {
    $guest_name  = trim($_POST['guest_name']);
    $identity    = trim($_POST['identity_number']);
    $phone       = trim($_POST['phone_number']);
    $file_profile  = NULL;
    $file_identity = NULL;

    if (!empty($_FILES['photo_profile']['name'])) {
        $ext = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
        $file_profile = "AVATAR_" . time() . "_" . rand(10,99) . "." . $ext;
        move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_dir . $file_profile);
    }
    if (!empty($_FILES['photo_identity']['name'])) {
        $ext = pathinfo($_FILES['photo_identity']['name'], PATHINFO_EXTENSION);
        $file_identity = "KTP_" . time() . "_" . rand(10,99) . "." . $ext;
        move_uploaded_file($_FILES['photo_identity']['tmp_name'], $upload_dir . $file_identity);
    }

    $stmt_g = mysqli_prepare($conn, "INSERT INTO htl_guests (id_license, guest_name, identity_number, phone_number, photo_profile, photo_identity) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_g, "isssss", $id_license, $guest_name, $identity, $phone, $file_profile, $file_identity);
    
    if (mysqli_stmt_execute($stmt_g)) {
        $msg = "Kartu Tamu Baru atas nama <strong>".htmlspecialchars($guest_name)."</strong> berhasil didaftarkan!";
    } else {
        $msg = "Gagal mendaftarkan kartu tamu. No Identitas mungkin sudah terdaftar."; $msg_type = "danger";
    }
}

// ==========================================
// 2. PROSES UPDATE GUEST CARD 
// ==========================================
if (isset($_POST['update_guest'])) {
    $id_guest    = intval($_POST['id_guest']);
    $guest_name  = trim($_POST['guest_name']);
    $identity    = trim($_POST['identity_number']);
    $phone       = trim($_POST['phone_number']);

    // Ambil data lama untuk mempertahankan file gambar lama jika tidak diganti
    $q_old = mysqli_query($conn, "SELECT photo_profile, photo_identity FROM htl_guests WHERE id_guest=$id_guest AND id_license=$id_license");
    $d_old = mysqli_fetch_assoc($q_old);
    $file_profile  = $d_old['photo_profile'];
    $file_identity = $d_old['photo_identity'];

    if (!empty($_FILES['photo_profile']['name'])) {
        $ext = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
        $file_profile = "AVATAR_" . time() . "_" . rand(10,99) . "." . $ext;
        move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_dir . $file_profile);
    }
    if (!empty($_FILES['photo_identity']['name'])) {
        $ext = pathinfo($_FILES['photo_identity']['name'], PATHINFO_EXTENSION);
        $file_identity = "KTP_" . time() . "_" . rand(10,99) . "." . $ext;
        move_uploaded_file($_FILES['photo_identity']['tmp_name'], $upload_dir . $file_identity);
    }

    $stmt_ug = mysqli_prepare($conn, "UPDATE htl_guests SET guest_name=?, identity_number=?, phone_number=?, photo_profile=?, photo_identity=? WHERE id_guest=? AND id_license=?");
    mysqli_stmt_bind_param($stmt_ug, "sssssii", $guest_name, $identity, $phone, $file_profile, $file_identity, $id_guest, $id_license);
    
    if (mysqli_stmt_execute($stmt_ug)) {
        $msg = "Data Kartu Tamu berhasil diperbarui!";
    }
}

// Tarik semua database master kartu tamu milik lisensi hotel ini
$res_guests = mysqli_query($conn, "SELECT * FROM htl_guests WHERE id_license = $id_license ORDER BY guest_name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Master Guest Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-custom { background: #1e3c72; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .thumb-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; cursor: pointer; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="bi bi-building me-2"></i> <?= $_SESSION['hotel_name']; ?> <span class="ms-2 badge bg-info text-white small" style="font-size:0.65rem;">Guest Cards</span>
        </a>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left"></i> Kembali ke Front Office</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="bi bi-card-heading text-info me-1"></i> Master Guest Cards (Buku Tamu Utama)</h3>
            <p class="text-secondary small mb-0">Kelola berkas identitas profil tamu tetap sebelum kedatangan reservasi.</p>
        </div>
        <button type="button" class="btn btn-info text-white rounded-pill shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAddGuest">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Tamu Baru
        </button>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?= $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card card-custom bg-white p-4">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Foto Profil</th>
                    <th>Foto KTP / ID</th>
                    <th>Nama Lengkap Tamu</th>
                    <th>Nomor Identitas</th>
                    <th>Nomor Telepon</th>
                    <th>Tanggal Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($res_guests) == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data kartu tamu terdaftar.</td></tr>
                <?php endif; ?>
                <?php while($g = mysqli_fetch_assoc($res_guests)): ?>
                    <tr>
                        <td>
                            <?php if($g['photo_profile']): ?>
                                <img src="../../uploads/guests/<?= $g['photo_profile']; ?>" class="thumb-img" data-bs-toggle="modal" data-bs-target="#viewPhotoModal" onclick="showBigPhoto('../../uploads/guests/<?= $g['photo_profile']; ?>')">
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border p-2"><i class="bi bi-person fs-5"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($g['photo_identity']): ?>
                                <img src="../../uploads/guests/<?= $g['photo_identity']; ?>" class="thumb-img" data-bs-toggle="modal" data-bs-target="#viewPhotoModal" onclick="showBigPhoto('../../uploads/guests/<?= $g['photo_identity']; ?>')">
                            <?php else: ?>
                                <span class="badge bg-light text-danger border p-2"><i class="bi bi-card-image fs-5"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($g['guest_name']); ?></strong></td>
                        <td><code class="text-dark"><?= htmlspecialchars($g['identity_number']); ?></code></td>
                        <td><?= htmlspecialchars($g['phone_number']); ?></td>
                        <td class="text-muted"><?= date('d M Y H:i', strtotime($g['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-dark rounded-pill btn-edit-guest" data-json='<?= json_encode($g); ?>' data-bs-toggle="modal" data-bs-target="#modalEditGuest">
                                <i class="bi bi-pencil"></i> Edit Profil
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: TAMBAH GUEST CARD MANDIRI -->
<div class="modal fade" id="modalAddGuest" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-vcard"></i> Registrasi Kartu Tamu Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" class="p-3 row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Nama Lengkap Sesuai KTP</label>
                    <input type="text" name="guest_name" class="form-control" required autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">No. KTP / Paspor</label>
                    <input type="text" name="identity_number" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">No. Telepon / WhatsApp</label>
                    <input type="text" name="phone_number" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted"><i class="bi bi-camera"></i> Foto Wajah Tamu (Profil)</label>
                    <input type="file" name="photo_profile" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted"><i class="bi bi-card-image"></i> Foto Fisik ID Card / KTP</label>
                    <input type="file" name="photo_identity" class="form-control" accept="image/*" required>
                </div>
                <div class="modal-footer bg-light col-12 mb-n3 mx-n3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_guest" class="btn btn-info text-white btn-sm rounded-pill fw-bold">Simpan Kartu Tamu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDIT GUEST CARD MANDIRI -->
<div class="modal fade" id="modalEditGuest" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Perbarui Profil Guest Card</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" class="p-3 row g-3">
                <input type="hidden" name="id_guest" id="edit_id_guest">
                <div class="col-12">
                    <label class="form-label small fw-bold">Nama Lengkap Sesuai KTP</label>
                    <input type="text" name="guest_name" id="edit_guest_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">No. KTP / Paspor</label>
                    <input type="text" name="identity_number" id="edit_identity" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">No. Telepon</label>
                    <input type="text" name="phone_number" id="edit_phone" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted"><i class="bi bi-camera"></i> Ganti Foto Wajah (Biarkan kosong jika tidak diubah)</label>
                    <input type="file" name="photo_profile" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted"><i class="bi bi-card-image"></i> Ganti Foto KTP/ID (Biarkan kosong jika tidak diubah)</label>
                    <input type="file" name="photo_identity" class="form-control" accept="image/*">
                </div>
                <div class="modal-footer bg-light col-12 mb-n3 mx-n3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_guest" class="btn btn-dark btn-sm rounded-pill fw-bold">Simpan Pembaruan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PREVIEW FOTO JENDELA BESAR -->
<div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-1 bg-dark text-center rounded">
                <img src="" id="bigPhotoPreview" class="img-fluid rounded" style="max-height:80vh;">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit-guest');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.json);
            document.getElementById('edit_id_guest').value = data.id_guest;
            document.getElementById('edit_guest_name').value = data.guest_name;
            document.getElementById('edit_identity').value = data.identity_number;
            document.getElementById('edit_phone').value = data.phone_number;
        });
    });
});
function showBigPhoto(src) {
    document.getElementById('bigPhotoPreview').src = src;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>