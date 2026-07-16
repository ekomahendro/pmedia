<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$username = $_SESSION['user'];
$is_admin = (strtolower($username) === 'admin');

// Ambil hak akses user dari database
$allowed_cats = [];
if (!$is_admin) {
    $stmt = $conn->prepare("SELECT category_access FROM pdfuser WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && $res['category_access']) {
        $allowed_cats = explode(",", $res['category_access']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<link rel="manifest" href="manifest.json">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Ebook Dashboard">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
        :root { --bg-dark: #0f172a; --card-dark: #1e293b; --accent: #38bdf8; }
        body { background-color: var(--bg-dark); color: #f8fafc; font-family: 'Inter', sans-serif; }
        
        .header-section { background: var(--card-dark); padding: 20px; border-radius: 0 0 25px 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .category-title { color: var(--accent); font-weight: 800; border-left: 4px solid var(--accent); padding-left: 12px; }
        
        .mobile-card {
            background: var(--card-dark);
            border-radius: 18px;
            display: flex;
            align-items: center;
            padding: 12px;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 12px;
        }
        .mobile-card:active { transform: scale(0.97); background: #2d3a4f; }
        
        .thumb-wrapper {
            width: 70px; height: 90px;
            background: #0f172a;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .thumbnail-canvas { width: 100%; height: 100%; object-fit: cover; }
        
        .info-wrapper { flex-grow: 1; padding: 0 15px; overflow: hidden; }
        .pdf-title { font-weight: 700; font-size: 15px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pdf-author { font-size: 12px; color: var(--accent); font-style: italic; margin-bottom: 4px; }
        .pdf-meta { font-size: 11px; color: #94a3b8; }
        
        .badge-count { background: rgba(56, 189, 248, 0.1); color: var(--accent); border: 1px solid var(--accent); }
        .btn-profile { background: rgba(255,255,255,0.1); color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div class="header-section mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Halo, <?php echo htmlspecialchars($username); ?>!</h5>
            <small class="text-muted">Mau baca buku apa hari ini?</small>
        </div>
        <div class="d-flex gap-2">
            <?php if($is_admin): ?>
                <a href="admin.php" class="btn-profile"><i class="bi bi-person-gear"></i></a>
            <?php endif; ?>
            <a href="profile.php" class="btn-profile"><i class="bi bi-person-circle"></i></a>
            <a href="logout.php" class="btn-profile text-danger" title="Keluar" onclick="return confirm('Yakin ingin keluar?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="container">
    <div class="mb-4">
        <input type="text" id="searchPdf" class="form-control bg-dark border-secondary text-white" placeholder="Cari judul atau penulis..." style="border-radius: 12px;">
    </div>

    <?php
    $dir = "pdfs/";
    $category_folders = array_filter(glob($dir . '*'), 'is_dir');

    if (empty($category_folders)) {
        echo '<div class="text-center mt-5 text-muted"><i class="bi bi-folder-x display-1"></i><p>Belum ada kategori PDF.</p></div>';
    }

    foreach ($category_folders as $cat_path) {
        $cat_name = basename($cat_path);
        
        // Pengecekan Akses (Bypass jika admin)
        if (!$is_admin && !in_array($cat_name, $allowed_cats)) {
            continue; 
        }

        // Ambil file PDF
        $files = glob($cat_path . "/*.pdf");
        $pdf_list_data = [];

        foreach ($files as $file_path) {
            $filename = basename($file_path);
            $db_path = $cat_name . '/' . $filename;

            // Ambil statistik dari database
            $stmt_stat = $conn->prepare("SELECT last_page, click_count FROM bookmarks WHERE file_name = ? AND username = ?");
            $stmt_stat->bind_param("ss", $db_path, $username);
            $stmt_stat->execute();
            $stat = $stmt_stat->get_result()->fetch_assoc();
            
            $pdf_list_data[] = [
                'full_path' => $file_path,
                'db_path' => $db_path,
                'filename' => $filename,
                'clicks' => $stat['click_count'] ?? 0,
                'last_page' => $stat['last_page'] ?? 0
            ];
        }

        // Sorting: Yang sering dibaca oleh USER INI akan berada di atas
        usort($pdf_list_data, function($a, $b) {
            return $b['clicks'] <=> $a['clicks'];
        });

        if (!empty($pdf_list_data)):
    ?>
        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
            <h5 class="category-title m-0"><?php echo htmlspecialchars($cat_name); ?></h5>
            <span class="badge badge-count rounded-pill"><?php echo count($pdf_list_data); ?> PDF</span>
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-2">
            <?php foreach ($pdf_list_data as $pdf): 
                $clean_name = str_replace('.pdf', '', $pdf['filename']);
                $parts = explode('-', $clean_name, 2);
                $title = trim($parts[0]);
                $author = isset($parts[1]) ? trim($parts[1]) : '';
                $canvasId = 'thumb-' . md5($pdf['full_path']);
            ?>
                <div class="col pdf-item" data-search="<?php echo strtolower($clean_name); ?>">
                    <a href="reader.php?file=<?php echo urlencode($pdf['db_path']); ?>" class="mobile-card">
                        <div class="thumb-wrapper">
                            <canvas id="<?php echo $canvasId; ?>" class="thumbnail-canvas"></canvas>
                        </div>
                        <div class="info-wrapper">
                            <div class="pdf-title"><?php echo htmlspecialchars($title); ?></div>
                            <?php if($author): ?>
                                <div class="pdf-author text-truncate">
                                    <i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($author); ?>
                                </div>
                            <?php endif; ?>
                            <div class="pdf-meta">
                                <span class="me-2"><i class="bi bi-bookmark-fill text-warning"></i> Hal: <?php echo $pdf['last_page']; ?></span>
                                <?php if($pdf['clicks'] > 0): ?>
                                    <span><i class="bi bi-eye"></i> <?php echo $pdf['clicks']; ?>x</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-muted"><i class="bi bi-chevron-right"></i></div>
                    </a>
                </div>
                <script>
                    (function() {
                        const url = '<?php echo $pdf['full_path']; ?>';
                        const canvas = document.getElementById('<?php echo $canvasId; ?>');
                        const ctx = canvas.getContext('2d');
                        
                        pdfjsLib.getDocument(url).promise.then(pdf => {
                            pdf.getPage(1).then(page => {
                                const viewport = page.getViewport({ scale: 0.3 });
                                canvas.height = viewport.height;
                                canvas.width = viewport.width;
                                page.render({ canvasContext: ctx, viewport: viewport });
                            });
                        }).catch(e => console.log("Thumb error"));
                    })();
                </script>
            <?php endforeach; ?>
        </div>
    <?php 
        endif; 
    } 
    ?>
</div>

<script>
    // Fitur Pencarian Real-time
    document.getElementById('searchPdf').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.pdf-item').forEach(item => {
            const text = item.getAttribute('data-search');
            item.style.display = text.includes(query) ? 'block' : 'none';
        });
    });
</script>

<br><br><br>
</body>
</html>