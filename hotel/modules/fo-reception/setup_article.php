<?php
require_once '../../config.php';
check_login();

$id_license = $_SESSION['id_license'];
$msg = ''; $msg_type = 'success';

// Handle Simpan / Update Artikel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_article'])) {
    $article_code = strtoupper(trim($_POST['article_code']));
    $article_name = trim($_POST['article_name']);
    $account_code = trim($_POST['account_code']);
    $is_edit      = intval($_POST['is_edit']);
    $id_article   = intval($_POST['id_article']);

    if ($is_edit === 1) {
        $stmt = mysqli_prepare($conn, "UPDATE htl_articles SET article_code = ?, article_name = ?, account_code = ? WHERE id_article = ? AND id_license = ?");
        mysqli_stmt_bind_param($stmt, "ssSII", $article_code, $article_name, $account_code, $id_article, $id_license);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO htl_articles (id_license, article_code, article_name, account_code) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $id_license, $article_code, $article_name, $account_code);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Artikel pendapatan berhasil disimpan!";
    }
}

// Handle Hapus Artikel
if (isset($_GET['delete_id'])) {
    $id_del = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM htl_articles WHERE id_article = $id_del AND id_license = $id_license");
    header("Location: setup_article.php");
    exit();
}

// Ambil list data artikel
$res_articles = mysqli_query($conn, "SELECT * FROM htl_articles WHERE id_license = $id_license ORDER BY article_code ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Master Artikel Revenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="bi bi-tags-fill text-primary me-2"></i>Master Artikel Pendapatan</h4>
            <p class="text-muted small mb-0">Kelola komponen pos pendapatan hotel untuk breakdown laporan revenue operasional.</p>
        </div>
        <a href="setup_arrangement.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left"></i> Kembali ke Breakdown</a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORM MASTER ARTIKEL -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold mb-3 small text-uppercase text-secondary" id="form-title">Tambah Artikel Baru</h5>
                <form action="" method="POST" id="formArticle">
                    <input type="hidden" name="is_edit" id="is_edit" value="0">
                    <input type="hidden" name="id_article" id="id_article" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Artikel</label>
                        <input type="text" name="article_code" id="article_code" class="form-control form-control-sm text-uppercase" placeholder="Contoh: ROOM, BKFST" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Artikel</label>
                        <input type="text" name="article_name" id="article_name" class="form-control form-control-sm" placeholder="Contoh: Room Charge" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Akun G/L (Opsional)</label>
                        <input type="text" name="account_code" id="account_code" class="form-control form-control-sm" placeholder="Contoh: 411001">
                    </div>
                    <div class="pt-2">
                        <button type="submit" name="save_article" id="btn-submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold">Simpan Artikel</button>
                        <button type="button" id="btn-cancel" class="btn btn-link btn-sm w-100 text-muted d-none" onclick="resetForm()">Batal Edit</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL DATA ARTIKEL -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold mb-3 small text-uppercase text-secondary">Daftar Artikel Tersedia</h5>
                <table class="table table-sm table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Komponen Artikel</th>
                            <th>G/L Code</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_articles) == 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada artikel pendapatan dibuat.</td></tr>
                        <?php endif; ?>
                        <?php while($art = mysqli_fetch_assoc($res_articles)): ?>
                            <tr>
                                <td><span class="badge bg-dark"><?= $art['article_code']; ?></span></td>
                                <td><strong><?= htmlspecialchars($art['article_name']); ?></strong></td>
                                <td><?= !empty($art['account_code']) ? $art['account_code'] : '<span class="text-muted">-</span>'; ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm text-dark p-1" onclick="editArticle(<?= $art['id_article']; ?>, '<?= $art['article_code']; ?>', '<?= htmlspecialchars($art['article_name'], ENT_QUOTES); ?>', '<?= $art['account_code']; ?>')"><i class="bi bi-pencil-square"></i></button>
                                    <a href="setup_article.php?delete_id=<?= $art['id_article']; ?>" class="btn btn-sm text-danger p-1" onclick="return confirm('Hapus artikel pendapatan ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function editArticle(id, code, name, account) {
    document.getElementById('form-title').innerText = "Edit Artikel Pendapatan";
    document.getElementById('is_edit').value = "1";
    document.getElementById('id_article').value = id;
    document.getElementById('article_code').value = code;
    document.getElementById('article_code').readOnly = true;
    document.getElementById('article_name').value = name;
    document.getElementById('account_code').value = account;
    document.getElementById('btn-submit').className = "btn btn-success btn-sm w-100 rounded-pill fw-bold";
    document.getElementById('btn-cancel').classList.remove('d-none');
}

function resetForm() {
    document.getElementById('form-title').innerText = "Tambah Artikel Baru";
    document.getElementById('is_edit').value = "0";
    document.getElementById('id_article').value = "0";
    document.getElementById('article_code').value = "";
    document.getElementById('article_code').readOnly = false;
    document.getElementById('article_name').value = "";
    document.getElementById('account_code').value = "";
    document.getElementById('btn-submit').className = "btn btn-primary btn-sm w-100 rounded-pill fw-bold";
    document.getElementById('btn-cancel').classList.add('d-none');
}
</script>
</body>
</html>