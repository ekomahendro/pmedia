<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }

// Menggunakan require_once config.php sesuai kode lama Anda
require_once 'config.php'; 

$file = $_GET['file']; // "Kategori/NamaFile.pdf"
$username = $_SESSION['user'];

// --- LOGIKA DATABASE TETAP DIJAGA ---
// 1. Cek progress dan Update jumlah klik (Hitung sebagai "sering dibaca")
$stmt = $conn->prepare("SELECT last_page FROM bookmarks WHERE file_name = ? AND username = ?");
$stmt->bind_param("ss", $file, $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    // Jika sudah ada, tambah jumlah klik
    $conn->query("UPDATE bookmarks SET click_count = click_count + 1 WHERE file_name = '$file' AND username = '$username'");
    $startPage = $row['last_page'];
} else {
    // Jika belum ada, buat record baru (klik pertama)
    $conn->query("INSERT INTO bookmarks (file_name, username, last_page, click_count) VALUES ('$file', '$username', 1, 1)");
    $startPage = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?php echo htmlspecialchars(basename($file)); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #1a1a1a; margin: 0; overflow: hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Area Canvas PDF */
        #pdf-render-container {
            height: 100vh;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            background: #2d2d2d;
            padding-bottom: 100px; /* Ruang agar tidak tertutup toolbar */
        }
        canvas { 
            max-width: 100%; 
            height: auto !important; 
            box-shadow: 0 0 30px rgba(0,0,0,0.5); 
            background: white;
        }

        /* UI Toolbar Mobile (Floating & Blur) */
        .reader-toolbar {
            position: fixed;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 450px;
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 1000;
        }

        /* Tombol Navigasi Besar */
        .nav-btn {
            background: #38bdf8;
            color: #0f172a;
            border: none;
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            transition: all 0.2s;
        }
        .nav-btn:active { transform: scale(0.9); }
        .nav-btn:disabled { background: #334155; color: #64748b; }

        .page-info { color: white; text-align: center; }
        .page-info b { display: block; font-size: 20px; color: #38bdf8; line-height: 1; }
        .page-info small { font-size: 11px; opacity: 0.7; }

        /* Spinner Loading */
        #loader {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            color: #38bdf8; z-index: 2000; display: none;
        }
        /* Judul File di Atas */
        .pdf-header-title {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to bottom, rgba(15, 23, 42, 0.9), transparent);
            color: #38bdf8;
            padding: 15px 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            z-index: 1000;
            pointer-events: none; /* Agar tidak menghalangi klik pada canvas */
        }
        
    </style>
</head>
<body>
<div class="pdf-header-title">
    <i class="bi bi-file-earmark-pdf-fill me-1"></i> 
    <?php echo htmlspecialchars(basename($file)); ?>
</div>
<div id="loader" class="spinner-border" role="status"></div>

<div id="pdf-render-container">
    <canvas id="pdf-render"></canvas>
</div>

<div class="reader-toolbar">
    <button class="nav-btn" id="prev-page">
        <i class="bi bi-chevron-left"></i>
    </button>
    
    <div class="page-info">
        <span id="page-num-display"><b><?php echo $startPage; ?></b></span>
        <small>DARI <span id="page-count">0</span></small>
    </div>

    <button class="nav-btn" id="next-page">
        <i class="bi bi-chevron-right"></i>
    </button>
    
    <div class="d-flex flex-column gap-1">
        <button onclick="saveProgressManual()" class="btn btn-sm btn-success" style="border-radius: 8px; font-size: 10px;">
            <i class="bi bi-bookmark-fill"></i> SAVE
        </button>
        <a href="index.php" class="btn btn-sm btn-danger" style="border-radius: 8px; font-size: 10px;">
            <i class="bi bi-box-arrow-left"></i> EXIT
        </a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script>
    const url = 'pdfs/<?php echo $file; ?>';
    let pdfDoc = null,
        pageNum = <?php echo $startPage; ?>,
        pageIsRendering = false,
        pageNumIsPending = null;

    const scale = 1.5, // Kualitas render
        canvas = document.querySelector('#pdf-render'),
        ctx = canvas.getContext('2d');

    // Fungsi Render Halaman
    const renderPage = num => {
        pageIsRendering = true;
        document.getElementById('loader').style.display = 'block';

        pdfDoc.getPage(num).then(page => {
            const viewport = page.getViewport({ scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderCtx = {
                canvasContext: ctx,
                viewport
            };

            page.render(renderCtx).promise.then(() => {
                pageIsRendering = false;
                document.getElementById('loader').style.display = 'none';
                
                if (pageNumIsPending !== null) {
                    renderPage(pageNumIsPending);
                    pageNumIsPending = null;
                }
            });

            // Update UI Halaman
            document.querySelector('#page-num-display b').textContent = num;
            pageNum = num;

            // Auto-Save setiap ganti halaman (Fitur Baru yang Memudahkan)
            saveProgressSilent(num);
        });
    };

    const queueRenderPage = num => {
        if (pageIsRendering) {
            pageNumIsPending = num;
        } else {
            renderPage(num);
        }
    };

    // Tombol Navigasi
    document.querySelector('#prev-page').addEventListener('click', () => {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
    });

    document.querySelector('#next-page').addEventListener('click', () => {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
    });

    // Fungsi Save Progress Tanpa Notifikasi (Auto-save)
    function saveProgressSilent(page) {
        const formData = new FormData();
        formData.append('file', '<?php echo $file; ?>');
        formData.append('page', page);
        fetch('save_progress.php', { method: 'POST', body: formData });
    }

    // Fungsi Save Progress Manual (Sesuai fungsi lama Anda)
    function saveProgressManual() {
        const formData = new FormData();
        formData.append('file', '<?php echo $file; ?>');
        formData.append('page', pageNum);

        fetch('save_progress.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            alert('Progress berhasil disimpan pada halaman ' + pageNum);
        });
    }

    // Inisialisasi PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    pdfjsLib.getDocument(url).promise.then(pdfDoc_ => {
        pdfDoc = pdfDoc_;
        document.querySelector('#page-count').textContent = pdfDoc.numPages;
        renderPage(pageNum);
    }).catch(err => {
        console.error("Error load PDF: ", err);
        alert("File PDF tidak ditemukan atau rusak.");
    });
</script>

</body>
</html>