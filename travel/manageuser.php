<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Proteksi Akses: Hanya Super Admin yang boleh mengelola user
if($_SESSION['role'] != 'Super Admin') {
    echo "<script>alert('Akses Ditolak! Halaman ini khusus Super Admin.'); window.location='admin_bookings.php';</script>";
    exit;
}

// Logika Tambah User Baru
if(isset($_POST['tambah_user'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); // Disarankan menggunakan password_hash() jika sistem login mendukung
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Cek apakah username sudah ada
    $cek = mysqli_query($conn, "SELECT * FROM tra_admin WHERE username='$username'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Username sudah terdaftar! Gunakan nama lain.'); window.location='manageuser.php';</script>";
    } else {
        $sql = "INSERT INTO tra_admin (username, password, role) VALUES ('$username', '$password', '$role')";
        if(mysqli_query($conn, $sql)){
            echo "<script>alert('User baru berhasil ditambahkan!'); window.location='manageuser.php';</script>";
        }
    }
}

// Logika Edit User
if(isset($_POST['edit_user'])){
    $id_admin = mysqli_real_escape_string($conn, $_POST['id_admin']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Jika password diisi, maka password ikut diupdate
    if(!empty($_POST['password'])){
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $sql = "UPDATE tra_admin SET username='$username', password='$password', role='$role' WHERE id_admin='$id_admin'";
    } else {
        $sql = "UPDATE tra_admin SET username='$username', role='$role' WHERE id_admin='$id_admin'";
    }
    
    if(mysqli_query($conn, $sql)){
        echo "<script>alert('Data user berhasil diperbarui!'); window.location='manageuser.php';</script>";
    }
}

// Logika Hapus User
if(isset($_GET['hapus'])){
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Mencegah Super Admin menghapus dirinya sendiri saat login
    if($id_hapus == $_SESSION['id_admin'] || $id_hapus == 1){ 
        echo "<script>alert('Gagal! Anda tidak bisa menghapus akun utama yang sedang aktif.'); window.location='manageuser.php';</script>";
    } else {
        mysqli_query($conn, "DELETE FROM tra_admin WHERE id_admin='$id_hapus'");
        header("Location: manageuser.php");
        exit;
    }
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Maluku Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Roboto, sans-serif; }
        .sidebar { background: #1e293b; min-height: 100vh; color: #fff; position: sticky; top: 0; }
        .sidebar .nav-link { color: #94a3b8; border-radius: 8px; margin-bottom: 5px; padding: 10px 15px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #334155; color: #fff; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Memanggil Navigasi Sidebar (Otomatis memisahkan Mobile Top Bar & Desktop Sidebar) -->
        <?php include 'sidebar.php'; ?>

        <!-- Kolom Konten Utama -->
        <div class="col-12 col-md-9 col-lg-10 p-3 p-md-4 p-lg-5">
            
            <!-- Top Dashboard Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-0">Manajemen Akses User</h4>
                    <small class="text-muted">Kelola akun administrator kredensial untuk staff dan super admin.</small>
                </div>
            </div>

            <div class="row">
                <!-- Form Tambah User -->
                <div class="col-lg-4 mb-4">
                    <div class="card card-custom p-4 bg-white shadow-sm">
                        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Tambah User Baru</h5>
                        <form method="POST" class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">USERNAME</label>
                                <input type="text" name="username" class="form-control bg-light" placeholder="Masukkan username..." required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                                <input type="password" name="password" class="form-control bg-light" placeholder="Masukkan password..." required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">ROLE HAK AKSES</label>
                                <select name="role" class="form-select bg-light">
                                    <option value="Staff">Staff (Hanya Lihat Pesanan)</option>
                                    <option value="Super Admin">Super Admin (Kontrol Penuh)</option>
                                </select>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" name="tambah_user" class="btn btn-primary w-100 fw-semibold"><i class="bi bi-save me-1"></i> Daftarkan Akun</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel List User -->
                <div class="col-lg-8 mb-4">
                    <div class="card card-custom border-0 bg-white shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h5 class="fw-bold text-dark m-0">Kredensial Terdaftar</h5>
                            <form method="GET" action="" class="d-flex" style="max-width: 250px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="q" class="form-control bg-light border-end-0" placeholder="Cari nama user..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-light border border-start-0 text-muted" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </form>
                        </div>

                        <!-- Tabel Responsive khusus Mobile -->
                        <div class="table-responsive shadow-sm rounded-3">
                            <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.9rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3" style="width: 70px;">ID</th>
                                        <th>Username</th>
                                        <th>Role Akses</th>
                                        <th class="pe-3 text-center" style="width: 120px;">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $res = mysqli_query($conn, "SELECT * FROM tra_admin WHERE username LIKE '%$search%' ORDER BY id_admin DESC");
                                    if(mysqli_num_rows($res) > 0) {
                                        while($row = mysqli_fetch_array($res)){ 
                                            $role_user = $row['role'];
                                            $badge_role = ($role_user == 'Super Admin') ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-info-subtle text-info border-info-subtle';
                                            ?>
                                            <tr>
                                                <td class="ps-3 text-muted fw-semibold">#<?= $row['id_admin'] ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><i class="bi bi-person-circle text-secondary me-2"></i><?= htmlspecialchars($row['username']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge border <?= $badge_class ?? $badge_role ?>" style="font-size: 0.75rem;"><?= $role_user ?></span>
                                                </td>
                                                <td class="pe-3 text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- Tombol memicu modal edit dengan melemparkan data lewat atribut data-bs -->
                                                        <button type="button" class="btn btn-outline-warning text-dark border-warning-subtle" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalEditUser"
                                                                data-id="<?= $row['id_admin'] ?>"
                                                                data-username="<?= htmlspecialchars($row['username']) ?>"
                                                                data-role="<?= $role_user ?>">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <a href="?hapus=<?= $row['id_admin'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus pengguna ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } 
                                    } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted"><i class="bi bi-people fs-3 d-block mb-1"></i> Data pengguna tidak ditemukan.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- End Kolom Konten Utama -->
    </div> <!-- End Row -->
</div> <!-- End Container fluid -->

<!-- ================= MODAL EDIT USER (POPUP) ================= -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold" id="modalEditUserLabel"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Data User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <!-- ID Tersembunyi (Hidden) -->
                    <input type="hidden" name="id_admin" id="edit_id_admin">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">USERNAME</label>
                        <input type="text" name="username" id="edit_username" class="form-control bg-light" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PASSWORD BARU</label>
                        <input type="password" name="password" class="form-control bg-light" placeholder="Kosongkan jika tidak diganti">
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Biarkan kosong jika tetap memakai password lama.</div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted">ROLE AKSES</label>
                        <select name="role" id="edit_role" class="form-select bg-light">
                            <option value="Staff">Staff (Hanya Lihat Pesanan)</option>
                            <option value="Super Admin">Super Admin (Kontrol Penuh)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light p-3" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-sm btn-secondary fw-semibold px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_user" class="btn btn-sm btn-warning fw-semibold px-3 text-dark">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script JS untuk memindahkan data baris tabel ke dalam field formulir Modal Edit secara otomatis
    const modalEditUser = document.getElementById('modalEditUser');
    if (modalEditUser) {
        modalEditUser.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            // Mengambil nilai data-* attributes dari tombol yang di-klik
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const role = button.getAttribute('data-role');
            
            // Memasukkan nilai ke elemen input di dalam modal
            document.getElementById('edit_id_admin').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
        });
    }
</script>
</body>
</html>