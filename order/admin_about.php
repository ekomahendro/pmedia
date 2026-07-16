<?php
session_start();
include 'config/db_connect.php'; 
include 'config/app_config.php'; // Sertakan konfigurasi aplikasi

// Cek Keamanan
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$page_key_about = 'about_us'; 
$page_key_contact = 'contact_info';
$message = '';
$about_data = [];
$contact_data = [];

// --- FUNGSI AMBIL DATA ---
function fetch_page_data($pdo, $key) {
    try {
        $stmt = $pdo->prepare("SELECT title, content FROM t_pages WHERE page_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null; 
    }
}

// Ambil konten saat ini
$about_data = fetch_page_data($pdo, $page_key_about);
$contact_data = fetch_page_data($pdo, $page_key_contact);


// --- LOGIKA UPDATE KONTEN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_content'])) {
    $target_key = $_POST['target_key'];
    $new_title = $_POST['new_title'];
    $new_content = $_POST['new_content'];

    try {
        $sql = "UPDATE t_pages SET title = ?, content = ? WHERE page_key = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_title, $new_content, $target_key]);

        if ($target_key === $page_key_about) {
            $message = '<div class="alert alert-success">Konten **Tentang Kami** berhasil diupdate.</div>';
            $about_data['title'] = $new_title;
            $about_data['content'] = $new_content;
        } elseif ($target_key === $page_key_contact) {
            $message = '<div class="alert alert-success">Informasi **Kontak** berhasil diupdate.</div>';
            $contact_data['title'] = $new_title;
            $contact_data['content'] = $new_content;
        }

    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Gagal mengupdate konten: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Halaman Statis - Admin <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light"> <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Panel - <?php echo APP_NAME; ?></a>
        <div class="collapse navbar-collapse" id="navbarNavAdmin">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_menu.php">Manajemen Menu</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_about.php">Edit Halaman Statis</a></li>
            </ul>
        </div>
        <a class="btn btn-outline-light btn-sm" href="admin_logout.php">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">✏️ Edit Konten Statis</h2>
    <?php echo $message; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Edit Halaman Tentang Kami</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($about_data)): ?>
                    <form method="POST" action="admin_about.php">
                        <input type="hidden" name="target_key" value="<?php echo $page_key_about; ?>">
                        
                        <div class="mb-3">
                            <label for="about_title" class="form-label">Judul Halaman</label>
                            <input type="text" class="form-control" id="about_title" name="new_title" value="<?php echo htmlspecialchars($about_data['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="about_content" class="form-label">Isi Konten (About Us)</label>
                            <textarea class="form-control" id="about_content" name="new_content" rows="8" required><?php echo htmlspecialchars($about_data['content']); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_content" class="btn btn-primary w-100">Simpan Perubahan About Us</button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-danger">Data About Us tidak ditemukan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5>Edit Informasi Kontak & Footer</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($contact_data)): ?>
                    <form method="POST" action="admin_about.php">
                        <input type="hidden" name="target_key" value="<?php echo $page_key_contact; ?>">
                        
                        <div class="mb-3">
                            <label for="contact_title" class="form-label">Judul Kontak (Abaikan)</label>
                            <input type="text" class="form-control" id="contact_title" name="new_title" value="<?php echo htmlspecialchars($contact_data['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="contact_content" class="form-label">Isi Kontak (Alamat | Telp | Email)</label>
                            <textarea class="form-control" id="contact_content" name="new_content" rows="8" required><?php echo htmlspecialchars($contact_data['content']); ?></textarea>
                            <small class="text-muted">Gunakan pemisah "|" (pipe) untuk memisahkan Alamat, Telepon, dan Email.</small>
                        </div>
                        
                        <button type="submit" name="update_content" class="btn btn-secondary w-100">Simpan Perubahan Kontak</button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-danger">Data Kontak tidak ditemukan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>