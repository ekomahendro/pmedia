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

// --- LOGIKA PENGAMBILAN DATA PROFIL LAMA ---
$sql_fetch = "SELECT username, full_name, profile_pic, cv_text FROM users WHERE id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$profile_data = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();

// --- LOGIKA PEMBARUAN PROFIL (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // Logika 1: PEMBARUAN DATA PROFIL DASAR (Nama, Username, CV)
    if ($action == 'update_info') {
        $full_name = sanitize_input($conn, $_POST['full_name']);
        $username = sanitize_input($conn, $_POST['username']);
        $cv_text = sanitize_input($conn, $_POST['cv_text']);
        
        // Cek duplikasi username (kecuali username sendiri)
        $sql_check_username = "SELECT id FROM users WHERE username = ? AND id != ?";
        $stmt_check = $conn->prepare($sql_check_username);
        $stmt_check->bind_param("si", $username, $user_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Username **{$username}** sudah digunakan oleh user lain.</div>";
        } else {
            $sql_update = "UPDATE users SET full_name=?, username=?, cv_text=? WHERE id=?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssi", $full_name, $username, $cv_text, $user_id);
            if ($stmt_update->execute()) {
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Informasi profil berhasil diperbarui!</div>";
                $_SESSION['full_name'] = $full_name; // Update sesi
                // Refresh data profil setelah update
                $profile_data['full_name'] = $full_name;
                $profile_data['username'] = $username;
                $profile_data['cv_text'] = $cv_text;
            } else {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal memperbarui profil.</div>";
            }
            $stmt_update->close();
        }
        $stmt_check->close();

    // Logika 2: UBAH PASSWORD
    } elseif ($action == 'change_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Cek password lama
        $sql_pwd = "SELECT password FROM users WHERE id = ?";
        $stmt_pwd = $conn->prepare($sql_pwd);
        $stmt_pwd->bind_param("i", $user_id);
        $stmt_pwd->execute();
        $stored_hash = $stmt_pwd->get_result()->fetch_column();
        $stmt_pwd->close();

        if (!password_verify($old_password, $stored_hash)) {
            $message = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Password lama salah!</div>";
        } elseif ($new_password !== $confirm_password) {
            $message = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Konfirmasi password tidak cocok!</div>";
        } elseif (strlen($new_password) < 6) {
             $message = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Password baru minimal 6 karakter.</div>";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update_pwd = "UPDATE users SET password = ? WHERE id = ?";
            $stmt_update_pwd = $conn->prepare($sql_update_pwd);
            $stmt_update_pwd->bind_param("si", $new_hashed_password, $user_id);
            if ($stmt_update_pwd->execute()) {
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Password berhasil diubah! Silakan login ulang pada sesi berikutnya.</div>";
            } else {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mengubah password.</div>";
            }
            $stmt_update_pwd->close();
        }

    // Logika 3: UPLOAD FOTO PROFIL
    } elseif ($action == 'upload_photo' && isset($_FILES['profile_pic'])) {
        $target_dir = "uploads/profile/";
        $file_name = basename($_FILES["profile_pic"]["name"]);
        $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = $user_id . '_' . time() . '.' . $imageFileType;
        $target_file = $target_dir . $new_file_name;
        $uploadOk = 1;

        // Cek file
        if ($_FILES["profile_pic"]["size"] > 500000) { // 500KB
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Ukuran file terlalu besar (Max 500KB).</div>";
            $uploadOk = 0;
        }
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Hanya file JPG, JPEG, & PNG yang diizinkan.</div>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                
                // Update nama file di database
                $sql_update_photo = "UPDATE users SET profile_pic = ? WHERE id = ?";
                $stmt_update_photo = $conn->prepare($sql_update_photo);
                $stmt_update_photo->bind_param("si", $new_file_name, $user_id);
                $stmt_update_photo->execute();
                
                // Hapus foto lama jika ada dan bukan default
                $old_pic = $profile_data['profile_pic'] ?? '';
                if (!empty($old_pic) && $old_pic !== 'default.jpg' && file_exists($target_dir . $old_pic)) {
                    unlink($target_dir . $old_pic);
                }

                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Foto profil berhasil diunggah!</div>";
                // Refresh data profil
                $profile_data['profile_pic'] = $new_file_name;

            } else {
                $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Gagal mengunggah file. Pastikan folder `uploads/profile/` ada dan memiliki izin tulis.</div>";
            }
        }
    }
}
?>

    <h1 class="mb-4 text-warning"><i class="fas fa-user-edit"></i> Update Profil & CV</h1>
    <?= $message ?>
    
    <div class="row">
        <div class="col-lg-4">
            
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-camera"></i> Foto Profil</div>
                <div class="card-body text-center">
                    <img src="uploads/profile/<?= htmlspecialchars($profile_data['profile_pic'] ?? 'default.jpg') ?>" 
                         class="img-fluid rounded-circle mb-3 border border-3 border-warning" 
                         style="width: 150px; height: 150px; object-fit: cover;" 
                         alt="Foto Profil">
                    
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_photo">
                        <div class="mb-3">
                            <input class="form-control form-control-sm" id="profile_pic" name="profile_pic" type="file" required>
                            <small class="text-muted">Max 500KB, JPG/PNG.</small>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm w-100"><i class="fas fa-upload me-1"></i> Upload Foto Baru</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-lg mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-key"></i> Ubah Password</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required>
                            <small class="text-muted">Min. 6 karakter.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-lock me-1"></i> Simpan Password</button>
                    </form>
                </div>
            </div>

        </div>

        <div class="col-lg-8">
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-id-card"></i> Data Informasi Dasar & CV</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="update_info">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($profile_data['full_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($profile_data['username'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="cv_text" class="form-label">Curriculum Vitae (CV) / Biodata Singkat</label>
                            <textarea name="cv_text" id="cv_text" class="form-control" rows="8"><?= htmlspecialchars($profile_data['cv_text'] ?? '') ?></textarea>
                            <small class="text-muted">Tuliskan riwayat singkat atau data diri yang perlu diketahui oleh Pembina Anda.</small>
                        </div>

                        <button type="submit" class="btn btn-warning w-100"><i class="fas fa-save me-1"></i> Simpan Perubahan Informasi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php 
$conn->close();
require_once '_footer.php';
?>