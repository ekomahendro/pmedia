<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }
require_once 'config.php';
$user = $_SESSION['user'];

// Ambil data user saat ini
$data = $conn->query("SELECT * FROM pdfuser WHERE username = '$user'")->fetch_assoc();

if (isset($_POST['update_profile'])) {
    // Logika Ganti Password
    if (!empty($_POST['old_password']) && !empty($_POST['new_password'])) {
        if (password_verify($_POST['old_password'], $data['password'])) {
            $hashed_new = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $conn->query("UPDATE pdfuser SET password = '$hashed_new' WHERE username = '$user'");
            $msg = "Password diperbarui!";
        } else {
            $error = "Password lama salah!";
        }
    }

    // Logika Upload Foto
    if ($_FILES['photo']['name']) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_name = "profile_" . time() . "." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $new_name);
        $conn->query("UPDATE pdfuser SET profile_pic = '$new_name' WHERE username = '$user'");
        header("Location: profile.php?msg=Foto diperbarui");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #0f172a; color: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px 0; }
        .profile-card { background: #1e293b; padding: 25px; border-radius: 24px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #38bdf8; margin-bottom: 15px; }
        .input-group-text { background: #1e293b; border-color: #334155; color: #94a3b8; cursor: pointer; }
        .form-control { background: #0f172a !important; border: 1px solid #334155 !important; color: white !important; }
        .label-text { font-weight: 600; color: #38bdf8; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 5px; display: block; }
    </style>
</head>
<body>
<div class="profile-card mx-3 text-center">
    <form method="POST" enctype="multipart/form-data">
        <img src="uploads/<?php echo $data['profile_pic'] ?: 'default.png'; ?>" class="profile-img">
        <div class="mb-4">
            <label class="label-text">Foto Profil</label>
            <input type="file" name="photo" class="form-control form-control-sm">
        </div>

        <hr class="border-secondary my-4">

        <div class="text-start mb-3">
            <label class="label-text">Password Saat Ini (Lama)</label>
            <div class="input-group">
                <input type="password" id="old_pass" name="old_password" class="form-control" placeholder="Isi untuk ganti pass">
                <span class="input-group-text" onclick="togglePass('old_pass')"><i class="bi bi-eye"></i></span>
            </div>
        </div>

        <div class="text-start mb-4">
            <label class="label-text">Password Baru</label>
            <div class="input-group">
                <input type="password" id="new_pass" name="new_password" class="form-control" placeholder="Minimal 6 karakter">
                <span class="input-group-text" onclick="togglePass('new_pass')"><i class="bi bi-eye"></i></span>
            </div>
        </div>

        <?php if(isset($msg)) echo "<div class='alert alert-success p-2 small'>$msg</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger p-2 small'>$error</div>"; ?>

        <button type="submit" name="update_profile" class="btn btn-primary w-100 py-2 mb-2 fw-bold text-dark">SIMPAN PERUBAHAN</button>
        <a href="index.php" class="btn btn-link text-white-50 text-decoration-none small">Kembali ke Beranda</a>
    </form>
</div>

<script>
function togglePass(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>