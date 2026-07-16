<?php
session_start();
include 'config.php';

$is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Fungsi untuk mengompresi gambar
function compressImage($source, $destination, $quality = 75) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $destination, $quality / 10);
    }
    imagedestroy($image);
}

// Proses CRUD
$error = '';
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $nama = $_POST['nama'];
            $tempat_lahir = $_POST['tempat_lahir'];
            $tgl_lahir = $_POST['tgl_lahir'];
            $ayah = ($_POST['ayah_select'] === 'other') ? $_POST['ayah_manual'] : $_POST['ayah_select'];
            $ayah = $ayah ?: NULL;
            $ibu = ($_POST['ibu_select'] === 'other') ? $_POST['ibu_manual'] : $_POST['ibu_select'];
            $ibu = $ibu ?: NULL;
            $domisili = $_POST['domisili'];
            $foto = ($_POST['action'] === 'edit') ? $_POST['existing_foto'] : NULL;

            // Validasi dan kompresi foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['foto']['size'] > 2 * 1024 * 1024) { // Maks 2MB
                    $error = "Ukuran foto terlalu besar. Maksimum 2MB.";
                } else {
                    $allowed_types = ['image/jpeg', 'image/png'];
                    if (in_array($_FILES['foto']['type'], $allowed_types)) {
                        if ($foto && file_exists($foto)) {
                            unlink($foto); // Hapus foto lama
                        }
                        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                        $foto = 'uploads/' . uniqid() . '.' . $ext;
                        compressImage($_FILES['foto']['tmp_name'], $foto, 75);
                    } else {
                        $error = "Jenis file tidak diizinkan. Gunakan jpg atau png.";
                    }
                }
            }

            // Tambahkan ayah manual sebagai anggota baru
            if ($ayah && $_POST['ayah_select'] === 'other') {
                $ayah_result = $conn->query("SELECT id FROM anggota WHERE nama='$ayah'");
                if ($ayah_result->num_rows == 0) {
                    $ayah_foto = NULL;
                    if (isset($_FILES['ayah_foto']) && $_FILES['ayah_foto']['error'] === UPLOAD_ERR_OK) {
                        if ($_FILES['ayah_foto']['size'] > 2 * 1024 * 1024) {
                            $error = "Ukuran foto ayah terlalu besar. Maksimum 2MB.";
                        } else {
                            $allowed_types = ['image/jpeg', 'image/png'];
                            if (in_array($_FILES['ayah_foto']['type'], $allowed_types)) {
                                $ext = pathinfo($_FILES['ayah_foto']['name'], PATHINFO_EXTENSION);
                                $ayah_foto = 'uploads/' . uniqid() . '.' . $ext;
                                compressImage($_FILES['ayah_foto']['tmp_name'], $ayah_foto, 75);
                            } else {
                                $error = "Jenis file foto ayah tidak diizinkan. Gunakan jpg atau png.";
                            }
                        }
                    }
                    if (!$error) {
                        $stmt = $conn->prepare("INSERT INTO anggota (nama, tempat_lahir, tgl_lahir, domisili, foto) VALUES (?, ?, ?, ?, ?)");
                        $tempat_lahir_dummy = 'Tidak Diketahui';
                        $tgl_lahir_dummy = '1900-01-01';
                        $domisili_dummy = 'Tidak Diketahui';
                        $stmt->bind_param("sssss", $ayah, $tempat_lahir_dummy, $tgl_lahir_dummy, $domisili_dummy, $ayah_foto);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            // Tambahkan ibu manual sebagai anggota baru
            if ($ibu && $_POST['ibu_select'] === 'other') {
                $ibu_result = $conn->query("SELECT id FROM anggota WHERE nama='$ibu'");
                if ($ibu_result->num_rows == 0) {
                    $ibu_foto = NULL;
                    if (isset($_FILES['ibu_foto']) && $_FILES['ibu_foto']['error'] === UPLOAD_ERR_OK) {
                        if ($_FILES['ibu_foto']['size'] > 2 * 1024 * 1024) {
                            $error = "Ukuran foto ibu terlalu besar. Maksimum 2MB.";
                        } else {
                            $allowed_types = ['image/jpeg', 'image/png'];
                            if (in_array($_FILES['ibu_foto']['type'], $allowed_types)) {
                                $ext = pathinfo($_FILES['ibu_foto']['name'], PATHINFO_EXTENSION);
                                $ibu_foto = 'uploads/' . uniqid() . '.' . $ext;
                                compressImage($_FILES['ibu_foto']['tmp_name'], $ibu_foto, 75);
                            } else {
                                $error = "Jenis file foto ibu tidak diizinkan. Gunakan jpg atau png.";
                            }
                        }
                    }
                    if (!$error) {
                        $stmt = $conn->prepare("INSERT INTO anggota (nama, tempat_lahir, tgl_lahir, domisili, foto) VALUES (?, ?, ?, ?, ?)");
                        $tempat_lahir_dummy = 'Tidak Diketahui';
                        $tgl_lahir_dummy = '1900-01-01';
                        $domisili_dummy = 'Tidak Diketahui';
                        $stmt->bind_param("sssss", $ibu, $tempat_lahir_dummy, $tgl_lahir_dummy, $domisili_dummy, $ibu_foto);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            // Simpan atau perbarui anggota
            if (!$error) {
                if ($_POST['action'] === 'add') {
                    $stmt = $conn->prepare("INSERT INTO anggota (nama, tempat_lahir, tgl_lahir, ayah, ibu, domisili, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $nama, $tempat_lahir, $tgl_lahir, $ayah, $ibu, $domisili, $foto);
                    $stmt->execute();
                    $stmt->close();
                } elseif ($_POST['action'] === 'edit') {
                    $id = $_POST['id'];
                    $stmt = $conn->prepare("UPDATE anggota SET nama=?, tempat_lahir=?, tgl_lahir=?, ayah=?, ibu=?, domisili=?, foto=? WHERE id=?");
                    $stmt->bind_param("sssssssi", $nama, $tempat_lahir, $tgl_lahir, $ayah, $ibu, $domisili, $foto, $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $foto_result = $conn->query("SELECT foto FROM anggota WHERE id=$id");
            if ($foto_result->num_rows > 0) {
                $foto = $foto_result->fetch_assoc()['foto'];
                if ($foto && file_exists($foto)) {
                    unlink($foto);
                }
            }
            $stmt = $conn->prepare("DELETE FROM anggota WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Ambil data anggota untuk dropdown dan tabel
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = $search ? " WHERE nama LIKE '%" . $conn->real_escape_string($search) . "%'" : '';
$anggota = $conn->query("SELECT * FROM anggota $search_query ORDER BY nama")->fetch_all(MYSQLI_ASSOC);
$nodes = [];
$edges = [];
$marriage_nodes = [];
$marriage_id = 10000;

// Buat node untuk setiap anggota
foreach ($anggota as $row) {
    $nodes[] = [
        'id' => $row['id'],
        'label' => $row['nama'] . "\n" . $row['tempat_lahir'] . ", " . date('d-m-Y', strtotime($row['tgl_lahir'])) . "\n" . $row['domisili'],
        'title' => "Nama: {$row['nama']}<br>Tempat Lahir: {$row['tempat_lahir']}<br>Tgl Lahir: " . date('d-m-Y', strtotime($row['tgl_lahir'])) . "<br>Domisili: {$row['domisili']}",
        'shape' => $row['foto'] ? 'image' : 'box',
        'image' => $row['foto'] ? $row['foto'] : 'https://via.placeholder.com/50',
        'font' => ['multi' => 'html', 'size' => 12],
        'margin' => 10
    ];
}

// Buat node pernikahan dan edge
foreach ($anggota as $row) {
    if ($row['ayah'] && $row['ibu']) {
        $ayah_result = $conn->query("SELECT id FROM anggota WHERE nama='{$row['ayah']}'");
        $ibu_result = $conn->query("SELECT id FROM anggota WHERE nama='{$row['ibu']}'");
        if ($ayah_result->num_rows > 0 && $ibu_result->num_rows > 0) {
            $ayah_id = $ayah_result->fetch_assoc()['id'];
            $ibu_id = $ibu_result->fetch_assoc()['id'];
            $marriage_key = $ayah_id . '-' . $ibu_id;
            if (!isset($marriage_nodes[$marriage_key])) {
                $marriage_nodes[$marriage_key] = $marriage_id;
                $nodes[] = [
                    'id' => $marriage_id,
                    'label' => '',
                    'shape' => 'circle',
                    'size' => 10,
                    'color' => '#ffcc00'
                ];
                $edges[] = ['from' => $ayah_id, 'to' => $marriage_id, 'label' => 'Suami', 'color' => '#007bff'];
                $edges[] = ['from' => $ibu_id, 'to' => $marriage_id, 'label' => 'Istri', 'color' => '#007bff'];
                $marriage_id++;
            }
            $edges[] = ['from' => $marriage_nodes[$marriage_key], 'to' => $row['id'], 'arrows' => 'to'];
        }
    } elseif ($row['ayah']) {
        $ayah_result = $conn->query("SELECT id FROM anggota WHERE nama='{$row['ayah']}'");
        if ($ayah_result->num_rows > 0) {
            $ayah_id = $ayah_result->fetch_assoc()['id'];
            $edges[] = ['from' => $ayah_id, 'to' => $row['id'], 'label' => 'Ayah', 'arrows' => 'to'];
        }
    } elseif ($row['ibu']) {
        $ibu_result = $conn->query("SELECT id FROM anggota WHERE nama='{$row['ibu']}'");
        if ($ibu_result->num_rows > 0) {
            $ibu_id = $ibu_result->fetch_assoc()['id'];
            $edges[] = ['from' => $ibu_id, 'to' => $row['id'], 'label' => 'Ibu', 'arrows' => 'to'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silsilah Keluarga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis-network.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="text-center mb-4">Silsilah Keluarga</h1>
        <?php if ($is_admin): ?>
            <div class="d-flex justify-content-end mb-3">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>
        <!-- Visualisasi Silsilah -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Pohon Keluarga</h5>
                <div id="network" style="height: 500px;"></div>
            </div>
        </div>
        <!-- Tabel Data dan Form -->
        <?php if ($is_admin): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tambah Anggota</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="nama" placeholder="Nama" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="tempat_lahir" placeholder="Tempat Lahir" required>
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" name="tgl_lahir" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ayah</label>
                                <select class="form-control" name="ayah_select" id="ayah_select" onchange="toggleAyahInput()">
                                    <option value="">Pilih Ayah</option>
                                    <?php foreach ($anggota as $a): ?>
                                        <option value="<?= $a['nama'] ?>"><?= $a['nama'] ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Lainnya</option>
                                </select>
                                <div id="ayah_manual_fields" style="display: none;" class="mt-2">
                                    <input type="text" class="form-control mb-2" name="ayah_manual" id="ayah_manual" placeholder="Masukkan nama ayah">
                                    <input type="file" class="form-control" name="ayah_foto" accept="image/jpeg,image/png">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ibu</label>
                                <select class="form-control" name="ibu_select" id="ibu_select" onchange="toggleIbuInput()">
                                    <option value="">Pilih Ibu</option>
                                    <?php foreach ($anggota as $a): ?>
                                        <option value="<?= $a['nama'] ?>"><?= $a['nama'] ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Lainnya</option>
                                </select>
                                <div id="ibu_manual_fields" style="display: none;" class="mt-2">
                                    <input type="text" class="form-control mb-2" name="ibu_manual" id="ibu_manual" placeholder="Masukkan nama ibu">
                                    <input type="file" class="form-control" name="ibu_foto" accept="image/jpeg,image/png">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="domisili" placeholder="Domisili" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Foto</label>
                                <input type="file" class="form-control" name="foto" accept="image/jpeg,image/png">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Tambah</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Daftar Anggota</h5>
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan nama" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tempat Lahir</th>
                            <th>Tgl Lahir</th>
                            <th>Ayah</th>
                            <th>Ibu</th>
                            <th>Domisili</th>
                            <th>Foto</th>
                            <?php if ($is_admin): ?>
                                <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anggota as $row): ?>
                            <tr>
                                <td><?= $row['nama'] ?></td>
                                <td><?= $row['tempat_lahir'] ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tgl_lahir'])) ?></td>
                                <td><?= $row['ayah'] ?: '-' ?></td>
                                <td><?= $row['ibu'] ?: '-' ?></td>
                                <td><?= $row['domisili'] ?></td>
                                <td>
                                    <?php if ($row['foto']): ?>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#fotoModal<?= $row['id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <?php if ($is_admin): ?>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <!-- Modal Foto -->
                            <?php if ($row['foto']): ?>
                                <div class="modal fade" id="fotoModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Foto: <?= $row['nama'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="<?= $row['foto'] ?>" alt="Foto <?= $row['nama'] ?>" class="img-fluid" style="max-height: 400px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- Modal Edit -->
                            <?php if ($is_admin): ?>
                                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Anggota</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="existing_foto" value="<?= $row['foto'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama</label>
                                                        <input type="text" class="form-control" name="nama" value="<?= $row['nama'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Tempat Lahir</label>
                                                        <input type="text" class="form-control" name="tempat_lahir" value="<?= $row['tempat_lahir'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Tgl Lahir</label>
                                                        <input type="date" class="form-control" name="tgl_lahir" value="<?= $row['tgl_lahir'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Ayah</label>
                                                        <select class="form-control" name="ayah_select" id="ayah_select_<?= $row['id'] ?>" onchange="toggleAyahInput(<?= $row['id'] ?>)">
                                                            <option value="">Pilih Ayah</option>
                                                            <?php foreach ($anggota as $a): ?>
                                                                <option value="<?= $a['nama'] ?>" <?= $a['nama'] === $row['ayah'] ? 'selected' : '' ?>><?= $a['nama'] ?></option>
                                                            <?php endforeach; ?>
                                                            <option value="other" <?= !in_array($row['ayah'], array_column($anggota, 'nama')) && $row['ayah'] ? 'selected' : '' ?>>Lainnya</option>
                                                        </select>
                                                        <div id="ayah_manual_fields_<?= $row['id'] ?>" style="display: <?= !in_array($row['ayah'], array_column($anggota, 'nama')) && $row['ayah'] ? 'block' : 'none' ?>;" class="mt-2">
                                                            <input type="text" class="form-control mb-2" name="ayah_manual" id="ayah_manual_<?= $row['id'] ?>" value="<?= !in_array($row['ayah'], array_column($anggota, 'nama')) ? $row['ayah'] : '' ?>" placeholder="Masukkan nama ayah">
                                                            <input type="file" class="form-control" name="ayah_foto" accept="image/jpeg,image/png">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Ibu</label>
                                                        <select class="form-control" name="ibu_select" id="ibu_select_<?= $row['id'] ?>" onchange="toggleIbuInput(<?= $row['id'] ?>)">
                                                            <option value="">Pilih Ibu</option>
                                                            <?php foreach ($anggota as $a): ?>
                                                                <option value="<?= $a['nama'] ?>" <?= $a['nama'] === $row['ibu'] ? 'selected' : '' ?>><?= $a['nama'] ?></option>
                                                            <?php endforeach; ?>
                                                            <option value="other" <?= !in_array($row['ibu'], array_column($anggota, 'nama')) && $row['ibu'] ? 'selected' : '' ?>>Lainnya</option>
                                                        </select>
                                                        <div id="ibu_manual_fields_<?= $row['id'] ?>" style="display: <?= !in_array($row['ibu'], array_column($anggota, 'nama')) && $row['ibu'] ? 'block' : 'none' ?>;" class="mt-2">
                                                            <input type="text" class="form-control mb-2" name="ibu_manual" id="ibu_manual_<?= $row['id'] ?>" value="<?= !in_array($row['ibu'], array_column($anggota, 'nama')) ? $row['ibu'] : '' ?>" placeholder="Masukkan nama ibu">
                                                            <input type="file" class="form-control" name="ibu_foto" accept="image/jpeg,image/png">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Domisili</label>
                                                        <input type="text" class="form-control" name="domisili" value="<?= $row['domisili'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Foto</label>
                                                        <input type="file" class="form-control" name="foto" accept="image/jpeg,image/png">
                                                        <?php if ($row['foto']): ?>
                                                            <img src="<?= $row['foto'] ?>" alt="Foto <?= $row['nama'] ?>" class="img-thumbnail mt-2" style="max-width: 100px;">
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        // Data untuk vis.js
        var nodes = new vis.DataSet(<?= json_encode($nodes) ?>);
        var edges = new vis.DataSet(<?= json_encode($edges) ?>);
        var container = document.getElementById('network');
        var data = { nodes: nodes, edges: edges };
        var options = {
            layout: {
                improvedLayout: true
            },
            physics: {
                enabled: true,
                forceAtlas2Based: {
                    gravitationalConstant: -50,
                    centralGravity: 0.01,
                    springLength: 100,
                    springConstant: 0.08
                },
                solver: 'forceAtlas2Based'
            },
            nodes: {
                shape: 'box',
                font: { multi: 'html', size: 12 },
                widthConstraint: { maximum: 200 },
                margin: 10
            },
            edges: {
                arrows: 'to',
                font: { align: 'middle' }
            }
        };
        var network = new vis.Network(container, data, options);

        // JavaScript untuk toggle input manual
        function toggleAyahInput(id = '') {
            var select = document.getElementById('ayah_select' + (id ? '_' + id : ''));
            var manual = document.getElementById('ayah_manual_fields' + (id ? '_' + id : ''));
            manual.style.display = select.value === 'other' ? 'block' : 'none';
        }

        function toggleIbuInput(id = '') {
            var select = document.getElementById('ibu_select' + (id ? '_' + id : ''));
            var manual = document.getElementById('ibu_manual_fields' + (id ? '_' + id : ''));
            manual.style.display = select.value === 'other' ? 'block' : 'none';
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>