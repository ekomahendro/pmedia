<?php 
include '../auth/session.php'; 

// Proteksi Halaman: Hanya Super Admin yang boleh akses
if ($_SESSION['level'] !== 'Super Admin') {
    echo "<script>alert('Akses Ditolak! Hanya Super Admin yang dapat mengelola user.'); window.location='dashboard.php';</script>";
    exit;
}

// 1. Logika Tambah User
if (isset($_POST['tambah_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $level    = $_POST['level'];
    $wilayah  = ($_POST['level'] == 'Super Admin') ? NULL : $_POST['wilayah'];
    $blok     = ($_POST['level'] == 'Kablok') ? $_POST['blok'] : NULL;

    try {
        $stmt = $pdo->prepare("INSERT INTO tr_users (username, password, level, wilayah, blok) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $level, $wilayah, $blok]);
        $success = "User berhasil ditambahkan!";
    } catch (PDOException $e) {
        $error = "Gagal menambah user: " . $e->getMessage();
    }
}

// 2. Logika Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cegah hapus diri sendiri
    if ($id == $_SESSION['user_id']) {
        $error = "Anda tidak bisa menghapus akun sendiri!";
    } else {
        $pdo->prepare("DELETE FROM tr_users WHERE id_user = ?")->execute([$id]);
        header("Location: user_master.php?msg=deleted");
        exit;
    }
}

// 3. Ambil Semua Data User
$users = $pdo->query("SELECT * FROM tr_users ORDER BY level ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master User - Bukit Sanggulan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; // Sebaiknya navbar dipisah agar rapi ?>

    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">Tambah Akun Baru</div>
                    <div class="card-body">
                        <?php if(isset($success)) echo "<div class='alert alert-success small'>$success</div>"; ?>
                        <?php if(isset($error)) echo "<div class='alert alert-danger small'>$error</div>"; ?>
                        
                        <form method="POST">
                            <div class="mb-2">
                                <label class="small fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Level Akses</label>
                                <select name="level" id="levelSelect" class="form-select" onchange="toggleWilayah()">
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Kawil">Kawil (Ketua Wilayah)</option>
                                    <option value="Kablok">Kablok (Ketua Blok)</option>
                                </select>
                            </div>
                            <div id="wilayahGroup" class="mb-2 d-none">
                                <label class="small fw-bold">Tugas Wilayah</label>
                                <select name="wilayah" class="form-select">
                                    <?php for($i=1; $i<=11; $i++) {
                                        $val = "Wilayah ".str_pad($i, 2, "0", STR_PAD_LEFT);
                                        echo "<option value='$val'>$val</option>";
                                    } ?>
                                </select>
                            </div>
                            <div id="blokGroup" class="mb-3 d-none">
                                <label class="small fw-bold">Tugas Blok</label>
                                <input type="text" name="blok" class="form-control" placeholder="Contoh: A1">
                            </div>
                            <button type="submit" name="tambah_user" class="btn btn-primary w-100">Simpan User</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">Daftar Pengguna Sistem</div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Wilayah/Blok</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td><span class="fw-bold"><?= $u['username'] ?></span></td>
                                    <td><span class="badge bg-secondary"><?= $u['level'] ?></span></td>
                                    <td><?= $u['wilayah'] ?? '-' ?> <?= $u['blok'] ? "/ ".$u['blok'] : '' ?></td>
                                    <td class="text-center">
                                        <?php if($u['id_user'] != $_SESSION['user_id']): ?>
                                            <a href="?hapus=<?= $u['id_user'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus user ini?')">Hapus</a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Sesi Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleWilayah() {
        const level = document.getElementById('levelSelect').value;
        const wGroup = document.getElementById('wilayahGroup');
        const bGroup = document.getElementById('blokGroup');

        if (level === 'Super Admin') {
            wGroup.classList.add('d-none');
            bGroup.classList.add('d-none');
        } else if (level === 'Kawil') {
            wGroup.classList.remove('d-none');
            bGroup.classList.add('d-none');
        } else if (level === 'Kablok') {
            wGroup.classList.remove('d-none');
            bGroup.classList.remove('d-none');
        }
    }
    </script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>