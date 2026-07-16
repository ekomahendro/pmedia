<?php
require_once 'config.php';
check_login();

// Hanya Superadmin atau Admin yang bisa mengelola user
if ($_SESSION['role'] !== 'superadmin' && $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$msg = '';
$msg_type = 'success';

// ==========================================
// 1. PROSES TAMBAH USER BARU (CREATE)
// ==========================================
if (isset($_POST['add_user'])) {
    $id_license    = intval($_POST['id_license']);
    $id_department = intval($_POST['id_department']); // Inputan Departemen Baru
    $username      = trim($_POST['username']);
    $fullname      = trim($_POST['fullname']);
    $role          = $_POST['role'];
    $password      = $_POST['password'];

    // Validasi username kembar
    $stmt_check = mysqli_prepare($conn, "SELECT id_user FROM htl_users WHERE username = ?");
    mysqli_stmt_bind_param($stmt_check, "s", $username);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $msg = "Gagal! Username '@$username' sudah digunakan oleh staf lain.";
        $msg_type = "danger";
    } else {
        // Hashing password dengan PASSWORD_DEFAULT (Aman & Rekomendasi PHP 8)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = 'active';

        // Ditambahkan parameter id_department pada query insert
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO htl_users (id_license, id_department, username, password, fullname, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins, "iisssss", $id_license, $id_department, $username, $hashed_password, $fullname, $role, $status);
        
        if (mysqli_stmt_execute($stmt_ins)) {
            $msg = "User baru berhasil didaftarkan!";
            $msg_type = "success";
        } else {
            $msg = "Terjadi kesalahan sistem saat menyimpan user.";
            $msg_type = "danger";
        }
    }
}

// ==========================================
// 2. PROSES EDIT USER (UPDATE STATUS/ROLE/DEPT)
// ==========================================
if (isset($_POST['edit_user'])) {
    $id_user       = intval($_POST['id_user']);
    $id_department = intval($_POST['id_department']); // Update Departemen Baru
    $fullname      = trim($_POST['fullname']);
    $role          = $_POST['role'];
    $status        = $_POST['status'];
    $new_pass      = $_POST['password'];

    if (!empty($new_pass)) {
        // Jika ganti password
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt_up = mysqli_prepare($conn, "UPDATE htl_users SET id_department = ?, fullname = ?, role = ?, status = ?, password = ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_up, "issssi", $id_department, $fullname, $role, $status, $hashed_password, $id_user);
    } else {
        // Jika password dikosongkan (tidak diubah)
        $stmt_up = mysqli_prepare($conn, "UPDATE htl_users SET id_department = ?, fullname = ?, role = ?, status = ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_up, "isssi", $id_department, $fullname, $role, $status, $id_user);
    }

    if (mysqli_stmt_execute($stmt_up)) {
        $msg = "Data user berhasil diperbarui!";
        $msg_type = "success";
    } else {
        $msg = "Gagal memperbarui data user.";
        $msg_type = "danger";
    }
}

// ==========================================
// 3. PROSES HAPUS USER (DELETE)
// ==========================================
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    
    // Mencegah hapus diri sendiri yang sedang login
    if ($id_del === $_SESSION['user_id']) {
        $msg = "Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif!";
        $msg_type = "danger";
    } else {
        // Hapus dari tabel user
        $stmt_del = mysqli_prepare($conn, "DELETE FROM htl_users WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id_del);
        mysqli_stmt_execute($stmt_del);

        // Bersihkan juga hak aksesnya agar database tidak lecek
        $stmt_del_acc = mysqli_prepare($conn, "DELETE FROM htl_user_access WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_del_acc, "i", $id_del);
        mysqli_stmt_execute($stmt_del_acc);

        $msg = "User berhasil dihapus dari sistem.";
        $msg_type = "warning";
    }
}

// Ambil data lisensi, departemen, dan gabungan user untuk tabel list
$licenses_res    = mysqli_query($conn, "SELECT * FROM htl_licenses WHERE status = 'active'");
$departments_res = mysqli_query($conn, "SELECT * FROM htl_departments ORDER BY dept_name ASC");

// Modifikasi query utama untuk menarik nama dan kode departemen dari htl_departments
$users_res       = mysqli_query($conn, "SELECT u.*, l.hotel_name, l.license_code, d.dept_code, d.dept_name 
                                        FROM htl_users u 
                                        JOIN htl_licenses l ON u.id_license = l.id_license 
                                        LEFT JOIN htl_departments d ON u.id_department = d.id_department 
                                        ORDER BY u.id_user DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Core Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card { border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-radius: 15px; }
        .table img { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-building me-2"></i><?= $_SESSION['hotel_name']; ?></a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light rounded-pill me-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="access_control.php" class="btn btn-sm btn-outline-warning rounded-pill"><i class="bi bi-shield-lock"></i> Atur Hak Akses</a>
        </div>
    </div>
</nav>

<div class="container px-4 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h3>
            <p class="text-secondary small mb-0">Kelola kredensial akun staf internal untuk tiap unit properti hotel.</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill shadow-sm fw-bold btn-sm px-4" data-bs-toggle="modal" data-bs-target="#modalAddUser">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah User Baru
        </button>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Nama Lengkap</th>
                            <th>Username</th>
                            <th>Departemen</th>
                            <th>Penempatan Hotel (Lisensi)</th>
                            <th>Role Level</th>
                            <th>Status Akun</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($users_res) > 0): ?>
                            <?php while($user = mysqli_fetch_assoc($users_res)): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light p-2 rounded-circle me-3 text-center text-primary" style="width:40px; height:40px;">
                                                <i class="bi bi-person-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($user['fullname']); ?></span>
                                                <span class="text-muted small" style="font-size:0.75rem;">ID: #USR-<?= $user['id_user']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary opacity-75">@<?= htmlspecialchars($user['username']); ?></span></td>
                                    <td>
                                        <?php if(!empty($user['dept_name'])): ?>
                                            <span class="text-dark fw-semibold small d-block"><?= htmlspecialchars($user['dept_name']); ?></span>
                                            <span class="text-muted font-monospace" style="font-size:0.7rem;">[<?= $user['dept_code']; ?>]</span>
                                        <?php else: ?>
                                            <span class="text-danger small italic">Belum Set Dept</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-dark d-block fw-semibold small"><?= htmlspecialchars($user['hotel_name']); ?></span>
                                        <span class="text-muted" style="font-size:0.75rem;"><i class="bi bi-key small"></i> <?= $user['license_code']; ?></span>
                                    </td>
                                    <td>
                                        <?php if($user['role'] == 'superadmin'): ?>
                                            <span class="badge bg-danger">Super Admin</span>
                                        <?php elseif($user['role'] == 'admin'): ?>
                                            <span class="badge bg-primary">Admin Properti</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Staff Operasional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($user['status'] == 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill btn-edit me-1" 
                                                data-id="<?= $user['id_user']; ?>"
                                                data-fullname="<?= htmlspecialchars($user['fullname']); ?>"
                                                data-department="<?= $user['id_department']; ?>"
                                                data-role="<?= $user['role']; ?>"
                                                data-status="<?= $user['status']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="user_management.php?delete=<?= $user['id_user']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Apakah Anda yakin ingin menghapus staf ini? Semua riwayat hak akses khusus user ini juga akan dibersihkan.')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center p-4 text-muted">Belum ada user yang terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddUser" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Form Registrasi Akun Staf</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Kaitkan ke Lisensi Hotel</label>
                            <select name="id_license" class="form-select" required>
                                <option value="">-- Pilih Properti --</option>
                                <?php mysqli_data_seek($licenses_res, 0); ?>
                                <?php while($l = mysqli_fetch_assoc($licenses_res)): ?>
                                    <option value="<?= $l['id_license']; ?>"><?= $l['hotel_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Departemen Kerja</label>
                            <select name="id_department" class="form-select" required>
                                <option value="">-- Pilih Departemen --</option>
                                <?php mysqli_data_seek($departments_res, 0); ?>
                                <?php while($d = mysqli_fetch_assoc($departments_res)): ?>
                                    <option value="<?= $d['id_department']; ?>">[<?= $d['dept_code']; ?>] <?= $d['dept_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Lengkap Staf</label>
                        <input type="text" name="fullname" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Username Akun</label>
                        <input type="text" name="username" class="form-control" placeholder="Contoh: budi_reception" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Password Awal</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Level Akses (Role)</label>
                            <select name="role" class="form-select" required>
                                <option value="staff">Staff Operasional</option>
                                <option value="admin">Admin Properti</option>
                                <option value="superadmin">Super Admin Vendor</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info small py-2 mb-0 mt-2">
                        <i class="bi bi-shield-exclamation"></i> Setelah user dibuat, jangan lupa untuk mengatur hak modul kerjanya di halaman <strong>Access Control</strong>.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_user" class="btn btn-primary rounded-pill btn-sm px-4 fw-bold">Daftarkan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditUser" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Ubah Data / Kredensial User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="id_user" id="edit_id_user">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Lengkap Staf</label>
                        <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pindah Departemen</label>
                        <select name="id_department" id="edit_id_department" class="form-select" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php mysqli_data_seek($departments_res, 0); ?>
                            <?php while($d = mysqli_fetch_assoc($departments_res)): ?>
                                <option value="<?= $d['id_department']; ?>">[<?= $d['dept_code']; ?>] <?= $d['dept_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Level Akses (Role)</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="staff">Staff Operasional</option>
                                <option value="admin">Admin Properti</option>
                                <option value="superadmin">Super Admin Vendor</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Status Akun</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif (Banned)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted">Ganti Password Baru <span class="text-danger small">(Opsional)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin diubah">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-dark rounded-pill btn-sm px-4 fw-bold">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inject data dinamis ke dalam Modal Edit sewaktu tombol 'Edit' diklik
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id_user').value = this.dataset.id;
            document.getElementById('edit_fullname').value = this.dataset.fullname;
            document.getElementById('edit_id_department').value = this.dataset.department; // Inject otomatis id_department
            document.getElementById('edit_role').value = this.dataset.role;
            document.getElementById('edit_status').value = this.dataset.status;
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>