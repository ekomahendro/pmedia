
<?php
include('koneksi.php');
// // 1. KONEKSI DATABASE
// $host = "localhost"; $user = "root"; $pass = ""; $db = "nama_database_anda";
// $conn = mysqli_connect($host, $user, $pass, $db);

// 2. LOGIKA CRUD (Proses Simpan, Edit, Hapus)
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama']; $blok = $_POST['blok']; $kk = $_POST['jumlahkk']; $p = $_POST['pengurus'];
    if (isset($_POST['id']) && $_POST['id'] != "") {
        mysqli_query($conn, "UPDATE wargagmj SET nama='$nama', blok='$blok', jumlahkk='$kk', pengurus='$p' WHERE id=".$_POST['id']);
    } else {
        mysqli_query($conn, "INSERT INTO wargagmj (nama, blok, jumlahkk, pengurus) VALUES ('$nama', '$blok', '$kk', '$p')");
    }
    header("Location: index.php");
}

if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM wargagmj WHERE id=" . $_GET['hapus']);
    header("Location: index.php");
}

// Data untuk Edit
$e = ['id' => '', 'nama' => '', 'blok' => '', 'jumlahkk' => '', 'pengurus' => '0'];
if (isset($_GET['edit'])) {
    $res = mysqli_query($conn, "SELECT * FROM wargagmj WHERE id=" . $_GET['edit']);
    $e = mysqli_fetch_assoc($res);
}

// 3. LOGIKA PENCARIAN
$search = isset($_GET['cari']) ? $_GET['cari'] : '';
$query = "SELECT * FROM wargagmj WHERE nama LIKE '%$search%' OR blok LIKE '%$search%' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Warga GMJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .header-bg { background: #1e3c72; color: white; padding: 30px 0; margin-bottom: 20px; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="header-bg text-center">
    <h1>Warga Griya Multi Jadi Tabanan</h1>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= $e['id'] ? 'Edit' : 'Tambah' ?> Warga</h5>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <div class="mb-2">
                            <label class="small">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?= $e['nama'] ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="small">Blok</label>
                            <input type="text" name="blok" class="form-control" value="<?= $e['blok'] ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="small">Jumlah KK</label>
                            <input type="number" name="jumlahkk" class="form-control" value="<?= $e['jumlahkk'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="small">Status</label>
                            <select name="pengurus" class="form-select">
                                <option value="0" <?= $e['pengurus'] == 0 ? 'selected' : '' ?>>Warga</option>
                                <option value="1" <?= $e['pengurus'] == 1 ? 'selected' : '' ?>>Pengurus</option>
                            </select>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan Data</button>
                        <?php if($e['id']): ?> <a href="index.php" class="btn btn-light w-100 mt-2">Batal</a> <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-4">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="cari" class="form-control" placeholder="Cari nama atau blok..." value="<?= $search ?>">
                        <button class="btn btn-dark" type="submit">Cari</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Blok</th>
                                <th class="text-center">KK</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_warga = 0; $total_kk = 0;
                            while($row = mysqli_fetch_assoc($result)): 
                                $total_warga++; 
                                $total_kk += $row['jumlahkk'];
                            ?>
                            <tr>
                                <td><strong><?= $row['nama'] ?></strong></td>
                                <td><span class="badge bg-secondary"><?= $row['blok'] ?></span></td>
                                <td class="text-center"><?= $row['jumlahkk'] ?></td>
                                <td><?= $row['pengurus'] ? '<span class="text-primary">Pengurus</span>' : 'Warga' ?></td>
                                <td>
                                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 p-3 bg-light rounded d-flex justify-content-between">
                    <span>Total Baris Data: <strong><?= $total_warga ?></strong></span>
                    <span>Total Akumulasi KK: <strong><?= $total_kk ?></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>