
<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }
require_once 'config.php';
$username = $_SESSION['user'];

// Ambil hak akses user
$user_data = $conn->query("SELECT category_access FROM pdfuser WHERE username = '$username'")->fetch_assoc();
// Jika admin, biarkan array kosong karena kita akan pakai bypass logic
$allowed_cats = $user_data ? explode(",", $user_data['category_access']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Abi Syamil Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --primary-accent: #38bdf8;
        }

        body { 
            background-color: var(--bg-color); 
            color: #f1f5f9; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding-bottom: 80px; /* Ruang untuk navbar bawah jika ingin ditambah nanti */
        }

        /* Navbar Style */
        .mobile-header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 15px 0;
        }

        .search-container {
            padding: 0 15px;
            margin-bottom: 20px;
        }

        .search-input {
            background: var(--card-bg) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: white !important;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 16px; /* Mencegah auto-zoom di iPhone */
        }

        /* Mobile List Item */
        .pdf-item {
            margin-bottom: 12px;
            padding: 0 15px;
        }

        .mobile-card {
            background: var(--card-bg);
            border-radius: 16px;
            display: flex;
            align-items: center;
            padding: 12px;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
            border: 1px solid rgba(255,255,255,0.03);
        }

        .mobile-card:active {
            background: #334155;
            transform: scale(0.98);
        }

        .thumb-wrapper {
            width: 70px;
            height: 90px;
            background: #0f172a;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .thumbnail-canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .info-wrapper {
            margin-left: 15px;
            overflow: hidden;
            flex-grow: 1;
        }

        .pdf-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pdf-meta {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .badge-read {
            background: rgba(56, 189, 248, 0.1);
            color: var(--primary-accent);
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<script>
    const pdfjsLib = window['pdfjs-dist/build/pdf'];
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    function generateThumbnail(url, canvasId) {
        pdfjsLib.getDocument(url).promise.then(pdf => {
            pdf.getPage(1).then(page => {
                const canvas = document.getElementById(canvasId);
                const context = canvas.getContext('2d');
                const viewport = page.getViewport({ scale: 0.3 }); // Skala kecil untuk list view
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({ canvasContext: context, viewport: viewport });
            });
        });
    }
</script>

<div class="mobile-header sticky-top mb-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="m-0 fw-bold">My Library</h5>
            <small class="text-primary" style="font-size: 11px;">Hi, <?php echo htmlspecialchars($_SESSION['user']); ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="profile.php" class="btn btn-sm btn-outline-info" style="font-size: 11px; border-radius: 8px;">Profile</a>
            
            <?php if($_SESSION['user'] === 'admin'): ?>
                <a href="admin.php" class="btn btn-sm btn-outline-warning" style="font-size: 11px; border-radius: 8px;">Admin</a>
            <?php endif; ?>
            
            <a href="logout.php" class="btn btn-sm btn-outline-danger" style="font-size: 11px; border-radius: 8px;">Logout</a>
        </div>
    </div>
</div>

<div class="search-container">
    <input type="text" id="searchInput" class="form-control search-input" placeholder="Cari buku atau dokumen...">
</div>

<div id="pdfList" class="container mt-3">
    <?php
    $dir = "pdfs/";
    $category_folders = array_filter(glob($dir . '*'), 'is_dir');

    foreach ($category_folders as $cat_path) {
        $cat_name = basename($cat_path);
        if ($username !== 'admin' && !in_array($cat_name, $allowed_cats)) continue;

        // --- Ambil Data PDF di folder ini ---
        $files = glob($cat_path . "/*.pdf");
        $pdf_list_data = [];

        foreach ($files as $file_path) {
            $filename = basename($file_path);
            $db_path = $cat_name . '/' . $filename;

            // Ambil data statistik dari DB untuk user ini
            $stat = $conn->query("SELECT last_page, click_count FROM bookmarks 
                                 WHERE file_name = '$db_path' AND username = '$username'")->fetch_assoc();
            
            $pdf_list_data[] = [
                'full_path' => $file_path,
                'db_path' => $db_path,
                'filename' => $filename,
                'clicks' => $stat['click_count'] ?? 0,
                'last_page' => $stat['last_page'] ?? 0
            ];
        }

        // --- SORTING: Berdasarkan 'clicks' terbanyak ---
        usort($pdf_list_data, function($a, $b) {
            return $b['clicks'] <=> $a['clicks'];
        });

        echo "
        <div class='d-flex justify-content-between align-items-center mt-4 mb-3 border-bottom pb-2'>
            <h5 class='text-info fw-bold m-0'>📂 $cat_name</h5>
            <span class='badge bg-secondary'>" . count($files) . " PDF</span>
        </div>";

        foreach ($pdf_list_data as $pdf) {
            $canvasId = 'thumb-' . md5($pdf['full_path']);
            ?>
            <div class="pdf-item mb-2" data-title="<?php echo htmlspecialchars($pdf['filename']); ?>">
                <a href="reader.php?file=<?php echo urlencode($pdf['db_path']); ?>" class="mobile-card">
                    <div class="thumb-wrapper">
                        <canvas id="<?php echo $canvasId; ?>" class="thumbnail-canvas"></canvas>
                    </div>
                    <div class="info-wrapper">
                        <?php 
                            // Bersihkan ekstensi .pdf
                            $clean_name = str_replace('.pdf', '', $pdf['filename']);
                            
                            // Pecah string berdasarkan tanda "-"
                            $parts = explode('-', $clean_name, 2);
                            $title = trim($parts[0]);
                            $author = isset($parts[1]) ? trim($parts[1]) : '';
                        ?>
                        
                        <div class="pdf-title text-truncate" style="max-width: 200px; font-weight: 700; color: #f1f5f9; margin-bottom: 2px;">
                            <?php echo htmlspecialchars($title); ?>
                        </div>
                    
                        <?php if($author): ?>
                            <div class="pdf-author text-truncate" style="max-width: 180px; font-size: 11px; color: #38bdf8; font-style: italic; margin-bottom: 4px;">
                                <i class="bi bi-person-fill" style="font-size: 10px;"></i> <?php echo htmlspecialchars($author); ?>
                            </div>
                        <?php endif; ?>
                    
                        <div class="pdf-meta" style="font-size: 10px;">
                            <span class="text-white-50"><i class="bi bi-bookmark-check"></i> Hal: <?php echo $pdf['last_page']; ?></span>
                            <?php if($pdf['clicks'] > 0): ?>
                                <span class="ms-2 text-white-50">| <?php echo $pdf['clicks']; ?>x klik</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="chevron text-muted"><i class="bi bi-chevron-right"></i></div>
                </a>
            </div>
            <script>generateThumbnail("<?php echo $pdf['full_path']; ?>", "<?php echo $canvasId; ?>");</script>
            <?php
        }
    }
    ?>
</div>

<script>
    document.getElementById('searchInput').addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        let items = document.querySelectorAll('.pdf-item');
        items.forEach(item => {
            let title = item.getAttribute('data-title').toLowerCase();
            item.style.display = title.includes(filter) ? "block" : "none";
        });
    });
</script>

</body>
</html>