<?php 
include '../auth/session.php'; 

// Logika Filtering Berdasarkan Hak Akses
$level = $_SESSION['level'];
$user_wil = $_SESSION['wilayah'];
$user_blok = $_SESSION['blok'];

$sql = "SELECT * FROM tr_warga_kk WHERE 1=1";
$params = [];

if ($level == 'Kawil') {
    $sql .= " AND wilayah = ?";
    $params[] = $user_wil;
} elseif ($level == 'Kablok') {
    $sql .= " AND wilayah = ? AND blok = ?";
    $params[] = $user_wil;
    $params[] = $user_blok;
}

// Filter Pencarian
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " AND (nama_kk LIKE ? OR nik LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$warga = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Warga - Bukit Sanggulan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; // Sebaiknya navbar dipisah agar rapi ?>
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Master Data Kepala Keluarga</h5>
                <a href="warga_input.php" class="btn btn-primary btn-sm">+ Tambah KK</a>
            </div>
            <div class="card-body">
                <form class="row g-3 mb-4">
                    <div class="col-auto">
                        <input type="text" name="search" class="form-control" placeholder="Cari Nama/NIK..." value="<?= $_GET['search'] ?? '' ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Nama KK</th>
                                <th>NIK</th>
                                <th>Wilayah</th>
                                <th>Blok</th>
                                <th>Telepon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($warga as $row): ?>
                            <tr>
                                <td><?= $row['nama_kk'] ?></td>
                                <td><?= $row['nik'] ?></td>
                                <td><?= $row['wilayah'] ?></td>
                                <td><?= $row['blok'] ?></td>
                                <td><?= $row['telepon'] ?></td>
                                <td>
                                    <a href="warga_detail.php?id=<?= $row['id_kk'] ?>" class="btn btn-info btn-sm text-white">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>