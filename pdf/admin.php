<?php
session_start();
require_once 'config.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user']) || strtolower($_SESSION['user']) !== 'admin') { 
    header("Location: index.php"); 
    exit(); 
}

// --- LOGIKA DELETE PDF ---
if (isset($_GET['delete_file'])) {
    $file_to_delete = $_GET['delete_file']; // Format: "Kategori/NamaFile.pdf"
    $full_path = "pdfs/" . $file_to_delete;

    if (file_exists($full_path)) {
        // 1. Hapus file fisik
        unlink($full_path);
        
        // 2. Hapus semua bookmark terkait agar database bersih
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE file_name = ?");
        $stmt->bind_param("s", $file_to_delete);
        $stmt->execute();

        $success_msg = "File berhasil dihapus selamanya.";
    } else {
        $error_msg = "File tidak ditemukan.";
    }
}

// [LOGIKA PHP TETAP SAMA SEPERTI SEBELUMNYA - DILEWATI AGAR RINGKAS]
// (Tetap sertakan semua logika upload, edit_cat, action_admin, dll di bagian paling atas ini)

// --- LOGIKA UPLOAD ---
if (isset($_POST['upload_pdf'])) {
    $target_cat = $_POST['target_category'];
    $file_name = $_FILES['pdf_file']['name'];
    $file_tmp = $_FILES['pdf_file']['tmp_name'];
    $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
    if (strtolower($file_type) != "pdf") {
        $error_msg = "Gagal! Hanya file PDF yang diperbolehkan.";
    } else {
        $target_dir = "pdfs/" . $target_cat . "/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . basename($file_name);
        if (move_uploaded_file($file_tmp, $target_file)) {
            $success_msg = "Berhasil! File '$file_name' diunggah.";
        } else { $error_msg = "Gagal mengunggah file."; }
    }
}
// --- LOGIKA ACTION USER ---
if (isset($_GET['action'])) {
    $id = $_GET['id'];
    if ($_GET['action'] == 'activate') $conn->query("UPDATE pdfuser SET status='active' WHERE id=$id");
    if ($_GET['action'] == 'deactivate') $conn->query("UPDATE pdfuser SET status='inactive' WHERE id=$id");
    if ($_GET['action'] == 'reset') {
        $pass = password_hash("123456", PASSWORD_DEFAULT);
        $conn->query("UPDATE pdfuser SET password='$pass' WHERE id=$id");
    }
    header("Location: admin.php");
}
// --- LOGIKA KATEGORI (ADD/EDIT/DELETE) ---
if (isset($_POST['add_cat'])) {
    $name = $_POST['cat_name'];
    $conn->query("INSERT INTO categories (category_name) VALUES ('$name')");
}
if (isset($_POST['update_access'])) {
    $uid = $_POST['user_id'];
    $access = isset($_POST['cats']) ? implode(",", $_POST['cats']) : "";
    $conn->query("UPDATE pdfuser SET category_access='$access' WHERE id=$uid");
}
// [Logika edit_cat dan delete_cat Anda tetap di sini]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --bg-deep: #0f172a; --card-bg: #1e293b; --accent: #38bdf8; }
        body { background: var(--bg-deep); color: #f8fafc; font-family: 'Inter', sans-serif; }
        
        .sticky-nav { position: sticky; top: 0; z-index: 100; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(10px); padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        .nav-pills .nav-link { color: #94a3b8; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
        .nav-pills .nav-link.active { background: var(--accent); color: var(--bg-deep); }

        .profile-card { 
            background: var(--card-bg); 
            border-radius: 20px; 
            border: 1px solid rgba(255,255,255,0.05); 
            transition: 0.3s;
            overflow: hidden;
        }
        .profile-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        
        .status-badge { font-size: 10px; padding: 4px 8px; border-radius: 20px; text-transform: uppercase; font-weight: 800; }
        .status-active { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .status-inactive { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .avatar-circle { width: 65px; height: 65px; object-fit: cover; border-radius: 50%; border: 2px solid var(--accent); }
        
        .cat-badge { background: rgba(56, 189, 248, 0.1); color: var(--accent); border: 1px solid rgba(56, 189, 248, 0.3); font-size: 11px; padding: 3px 8px; border-radius: 6px; }

        .search-box { background: var(--card-bg); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 12px; }
        .search-box:focus { background: var(--card-bg); border-color: var(--accent); color: white; box-shadow: none; }
        /* Meningkatkan kejelasan teks di dalam Modal */
        .modal-content {
            border: 1px solid rgba(56, 189, 248, 0.5) !important; /* Border biru terang */
        }
        
        .form-check-label {
            color: #ffffff !important; /* Putih pekat agar kontras dengan latar gelap */
            font-weight: 600;
            font-size: 14px !important;
            cursor: pointer;
            width: 100%;
        }
        
        .form-check {
            background: rgba(255, 255, 255, 0.05); /* Latar belakang kotak yang lebih halus */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 10px 10px 10px 35px !important; /* Memberi ruang untuk checkbox */
            border-radius: 8px;
            transition: 0.2s;
        }
        
        .form-check:hover {
            background: rgba(56, 189, 248, 0.15);
            border-color: var(--accent) !important;
        }
        
        /* Memperbesar checkbox agar mudah ditekan jari */
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.1em;
            cursor: pointer;
        }        
    </style>
</head>
<body>

<div class="sticky-nav mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="index.php" class="btn btn-dark btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Library
            </a>
            <h5 class="fw-bold m-0 text-info">Admin Panel</h5>
            <div style="width: 80px;"></div>
        </div>
        
        <ul class="nav nav-pills mt-3 justify-content-center" id="adminTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="user-tab" data-bs-toggle="pill" data-bs-target="#tab-user"><i class="bi bi-people me-2"></i>Users</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="cat-tab" data-bs-toggle="pill" data-bs-target="#tab-cat"><i class="bi bi-tags me-2"></i>Kategori</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="files-tab" data-bs-toggle="pill" data-bs-target="#tab-files">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Kelola File
                </button>
            </li>            
            <li class="nav-item">
                <button class="nav-link" id="upload-tab" data-bs-toggle="pill" data-bs-target="#tab-upload"><i class="bi bi-cloud-arrow-up me-2"></i>Upload</button>
            </li>
        </ul>
    </div>
</div>

<div class="container pb-5">
    <div class="tab-content" id="adminTabsContent">
        
        <!-- TAB 1: USERS -->
        <div class="tab-pane fade show active" id="tab-user">
            <div class="mb-4">
                <input type="text" id="userSearch" class="form-control search-box" placeholder="Cari nama atau nomor WA user...">
            </div>

            <div class="row g-3" id="userList">
                <?php
                $cats_res = $conn->query("SELECT * FROM categories");
                $all_cats = [];
                while($c = $cats_res->fetch_assoc()) $all_cats[] = $c['category_name'];

                $res = $conn->query("SELECT * FROM pdfuser WHERE username != 'admin' ORDER BY id DESC");
                while($row = $res->fetch_assoc()):
                    $user_access = explode(",", $row['category_access']);
                    $status_class = ($row['status'] == 'active') ? 'status-active' : 'status-inactive';
                ?>
                <div class="col-md-6 user-item" data-name="<?php echo strtolower($row['username'] . $row['no_wa']); ?>">
                    <div class="profile-card p-3">
                        <div class="d-flex align-items-start">
                            <img src="uploads/<?php echo $row['profile_pic']; ?>" class="avatar-circle me-3 shadow" 
                                 data-bs-toggle="modal" data-bs-target="#imgModal<?php echo $row['id']; ?>" style="cursor:zoom-in;">
                            
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="mb-0 fw-bold"><?php echo $row['username']; ?></h6>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                                </div>
                                <small class="text-muted d-block mb-2"><i class="bi bi-whatsapp"></i> <?php echo $row['no_wa']; ?></small>
                                
                                <div class="mb-3 d-flex flex-wrap gap-1">
                                    <?php 
                                    if(!empty($row['category_access'])) {
                                        foreach($user_access as $ac) echo "<span class='cat-badge'>$ac</span>";
                                    } else { echo "<small class='text-danger' style='font-size:10px;'>Tidak ada akses kategori</small>"; }
                                    ?>
                                </div>

                                <!-- ACTION BUTTONS: Dibuat lebih terlihat -->
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-info fw-bold" data-bs-toggle="modal" data-bs-target="#accessModal<?php echo $row['id']; ?>">
                                        <i class="bi bi-shield-lock"></i> Atur Akses
                                    </button>

                                    <?php if($row['status'] == 'active'): ?>
                                        <a href="admin.php?action=deactivate&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-person-x"></i> Nonaktifkan
                                        </a>
                                    <?php else: ?>
                                        <a href="admin.php?action=activate&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success fw-bold">
                                            <i class="bi bi-person-check"></i> Aktifkan User
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="admin.php?action=reset&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reset password ke 123456?')">
                                        <i class="bi bi-key"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Akses -->
                <div class="modal fade" id="accessModal<?php echo $row['id']; ?>">
                    <div class="modal-dialog modal-dialog-centered">
                        <form method="POST" class="modal-content bg-dark border-secondary text-white">
                            <div class="modal-header border-secondary"><h6>Hak Akses: <?php echo $row['username']; ?></h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <div class="row g-2">
                                    <?php foreach($all_cats as $cat): ?>
                                    <div class="col-6">
                                        <div class="form-check card bg-secondary bg-opacity-10 p-2 border-0">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="cats[]" value="<?php echo $cat; ?>" <?php echo in_array($cat, $user_access) ? 'checked' : ''; ?>>
                                            <label class="form-check-label small"><?php echo $cat; ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary">
                                <button type="submit" name="update_access" class="btn btn-info btn-sm w-100 fw-bold">SIMPAN PERUBAHAN AKSES</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- TAB 2: KATEGORI -->
        <div class="tab-pane fade" id="tab-cat">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0 text-info">Daftar Kategori</h6>
                <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCatModal">+ Kategori Baru</button>
            </div>
            <div class="row g-2">
                <?php
                $cats_res = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
                while($c = $cats_res->fetch_assoc()):
                ?>
                <div class="col-md-4">
                    <div class="profile-card p-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-folder2 me-2 text-info"></i><?php echo $c['category_name']; ?></span>
                        <div class="btn-group">
                            <a href="admin.php?delete_cat=<?php echo $c['id']; ?>&name=<?php echo $c['category_name']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus Kategori?')"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- TAB 3: KELOLA FILE -->
        <div class="tab-pane fade" id="tab-files">
            <h6 class="fw-bold text-info mb-3">Daftar Semua File PDF</h6>
            <div class="row g-3">
                <?php
                $dirs = array_filter(glob('pdfs/*'), 'is_dir');
                foreach ($dirs as $dir):
                    $cat_name = basename($dir);
                    $files = glob($dir . "/*.pdf");
                ?>
                    <div class="col-12 mt-4 mb-2">
                        <div class="p-2 bg-dark rounded border border-secondary small text-uppercase fw-bold text-muted">
                            Kategori: <?php echo $cat_name; ?>
                        </div>
                    </div>
                    <?php foreach ($files as $f): 
                        $fn = basename($f); 
                        $db_path = $cat_name . "/" . $fn;
                    ?>
                        <div class="col-md-6">
                            <div class="profile-card p-3 d-flex justify-content-between align-items-center">
                                <div class="text-truncate me-2">
                                    <span class="d-block text-truncate fw-bold" style="font-size:14px;"><?php echo $fn; ?></span>
                                </div>
                                <div class="btn-group">
                                    <a href="reader.php?file=<?php echo urlencode($db_path); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="admin.php?delete_file=<?php echo urlencode($db_path); ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Hapus file ini secara permanen?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 4: UPLOAD -->
        <div class="tab-pane fade" id="tab-upload">
            <div class="profile-card p-4">
                <h5 class="fw-bold text-info mb-4"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Center</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="small text-muted mb-2">Pilih Kategori Tujuan</label>
                        <select name="target_category" class="form-select bg-dark text-white border-secondary rounded-3" required>
                            <?php
                            $cats_res = $conn->query("SELECT * FROM categories");
                            while($c = $cats_res->fetch_assoc()) echo "<option value='{$c['category_name']}'>{$c['category_name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="small text-muted mb-2">File PDF</label>
                        <input type="file" name="pdf_file" class="form-control bg-dark text-white border-secondary rounded-3" accept=".pdf" required>
                    </div>
                    <button type="submit" name="upload_pdf" class="btn btn-info w-100 fw-bold py-3 shadow">MULAI UPLOAD PDF</button>
                </form>
            </div>
        </div>

    </div> <!-- TUTUP tab-content -->
</div> <!-- TUTUP container -->

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addCatModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary"><h6>Kategori Baru</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="text" name="cat_name" class="form-control bg-dark text-white border-secondary" placeholder="Nama Kategori..." required>
            </div>
            <div class="modal-footer border-secondary">
                <button type="submit" name="add_cat" class="btn btn-success btn-sm w-100 fw-bold">SIMPAN KATEGORI</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('userSearch').addEventListener('keyup', function(){
    let val = this.value.toLowerCase();
    document.querySelectorAll('.user-item').forEach(el => {
        el.style.display = el.getAttribute('data-name').includes(val) ? "block" : "none";
    });
});
</script>
<div class="tab-pane fade" id="tab-files">
    <h6 class="fw-bold text-info mb-3">Daftar Semua File PDF</h6>
    <div class="row g-3">
        <?php
        $dirs = array_filter(glob('pdfs/*'), 'is_dir');
        foreach ($dirs as $dir):
            $cat_name = basename($dir);
            $files = glob($dir . "/*.pdf");
        ?>
            <div class="col-12 mb-2">
                <div class="p-2 bg-dark rounded border border-secondary small text-uppercase fw-bold text-muted">
                    Kategori: <?php echo $cat_name; ?>
                </div>
            </div>
            
            <?php foreach ($files as $f): 
                $fn = basename($f); 
                $db_path = $cat_name . "/" . $fn;
            ?>
                <div class="col-md-6">
                    <div class="profile-card p-3 d-flex justify-content-between align-items-center">
                        <div class="text-truncate me-2">
                            <span class="d-block text-truncate fw-bold" style="font-size:14px;"><?php echo $fn; ?></span>
                        </div>
                        <div class="btn-group">
                            <a href="reader.php?file=<?php echo urlencode($db_path); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="admin.php?delete_file=<?php echo urlencode($db_path); ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Hapus file ini secara permanen?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
<div class="modal fade" id="addCatModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary"><h6>Kategori Baru</h6></div>
            <div class="modal-body">
                <input type="text" name="cat_name" class="form-control bg-dark text-white border-secondary" placeholder="Nama Kategori..." required>
            </div>
            <div class="modal-footer border-secondary">
                <button type="submit" name="add_cat" class="btn btn-success btn-sm w-100">SIMPAN KATEGORI</button>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search Logic
document.getElementById('userSearch').addEventListener('keyup', function(){
    let val = this.value.toLowerCase();
    document.querySelectorAll('.user-item').forEach(el => {
        el.style.display = el.getAttribute('data-name').includes(val) ? "block" : "none";
    });
});
</script>
</body>
</html>