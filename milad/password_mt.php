<?php
require_once 'config.php';

// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman login (Hanya untuk internal panitia)
if (!isset($_SESSION['login_milad'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Proses Simpan / Update Password MT (Teks Biasa agar bisa dilihat langsung)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_password'])) {
    $majelis_id = $_POST['majelis_id'] ?? '';
    $password_baru = trim($_POST['password_daftar'] ?? '');

    if (!empty($majelis_id)) {
        // Menyimpan dalam bentuk plain text sesuai permintaan agar tampil di tabel
        $stmt = $pdo->prepare("UPDATE mld_majelis SET password_daftar = ? WHERE id = ?");
        if ($stmt->execute([$password_baru, $majelis_id])) {
            $message = "Sukses memperbarui password rahasia MT.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui password.";
            $message_type = "danger";
        }
    }
}

// Ambil data semua MT beserta password-nya
$list_majelis = $pdo->query("SELECT id, nama_mt, password_daftar FROM mld_majelis ORDER BY nama_mt ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Password Pendaftaran MT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Arial, sans-serif; }
        .main-box { background: #fff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 30px; margin-top: 30px; }
        code { font-size: 1.05rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="peserta.php?tab=majelis" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Majelis Taklim</a>
                <span class="badge bg-success p-2">Mode Khusus Panitia</span>
            </div>

            <div class="main-box">
                <h3 class="fw-bold text-success border-bottom pb-2 mb-4"><i class="bi bi-key-fill"></i> Kelola Password Akses Kafilah / MT</h3>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Form Set / Ubah Password Cepat -->
                <form method="POST" action="" class="row g-3 bg-light p-3 rounded border mb-4">
                    <input type="hidden" name="action_save_password" value="1">
                    <input type="hidden" name="majelis_id" id="form_majelis_id">
                    
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Kafilah / MT Terpilih</label>
                        <select class="form-select" id="select_majelis_view" onchange="SyncId(this.value)" required>
                            <option value="">-- Pilih MT untuk Membuat Baru --</option>
                            <?php foreach ($list_majelis as $mt): ?>
                                <option value="<?= $mt['id'] ?>"><?= htmlspecialchars($mt['nama_mt']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Element text pelengkap saat mode edit terpicu -->
                        <div id="edit_label" class="form-text text-primary fw-semibold mt-1" style="display:none;"></div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Set Password Baru</label>
                        <input type="text" name="password_daftar" id="form_password_value" class="form-control" placeholder="Ketik password baru..." required autocomplete="off">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100 fw-bold"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </form>

                <!-- Tabel Status & Isi Password MT -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Daftar Password Akun Kafilah</h5>
                    <small class="text-muted">*Gunakan tombol kuning untuk mengubah password baris terkait</small>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="70" class="text-center">No</th>
                                <th>Nama Kafilah / Majelis Taklim</th>
                                <th width="220" class="text-center">Password (Teks Asli)</th>
                                <th width="120" class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($list_majelis as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="fw-bold text-secondary"><?= htmlspecialchars($row['nama_mt']) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['password_daftar'])): ?>
                                            <code class="px-3 py-1.5 bg-light border border-danger text-danger fw-bold rounded shadow-sm"><?= htmlspecialchars($row['password_daftar']) ?></code>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1.5"><i class="bi bi-exclamation-triangle-fill"></i> Belum Ada Sandi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-warning fw-bold text-dark px-3" 
                                                onclick="PicuEdit('<?= $row['id'] ?>', '<?= htmlspecialchars($row['nama_mt'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['password_daftar'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="bi bi-pencil-square"></i> Ubah
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Sinkronisasi ID dari select dropdown ke hidden input
    function SyncId(val) {
        document.getElementById('form_majelis_id').value = val;
        if(val === "") {
            ResetMode();
        }
    }

    // Mengisi form di atas secara otomatis saat tombol "Ubah" diklik
    function PicuEdit(id, namaMt, passwordSekarang) {
        document.getElementById('form_majelis_id').value = id;
        
        // Pilih select option secara otomatis
        const selectMt = document.getElementById('select_majelis_view');
        selectMt.value = id;
        
        // Isi password lama ke kolom input agar tinggal diganti
        document.getElementById('form_password_value').value = passwordSekarang;
        document.getElementById('form_password_value').focus();
        
        // Berikan info text mode edit aktif
        const label = document.getElementById('edit_label');
        label.style.display = 'block';
        label.innerHTML = `<i class="bi bi-info-circle-fill"></i> Mode Edit: ${namaMt}`;
    }

    function ResetMode() {
        document.getElementById('edit_label').style.display = 'none';
        document.getElementById('form_password_value').value = "";
    }
</script>
</body>
</html>