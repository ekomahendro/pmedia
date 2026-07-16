<?php 
include '../auth/session.php'; 

if (isset($_POST['simpan_warga'])) {
    try {
        $pdo->beginTransaction();
        $target_dir = "../assets/uploads/";

        // --- 1. PROSES FOTO KEPALA KELUARGA ---
        $foto_kk = NULL;
        if (!empty($_FILES['foto_kk']['name'])) {
            $ext_kk = pathinfo($_FILES['foto_kk']['name'], PATHINFO_EXTENSION);
            $foto_kk = "kk_" . time() . "_" . rand(100,999) . "." . $ext_kk;
            move_uploaded_file($_FILES['foto_kk']['tmp_name'], $target_dir . $foto_kk);
        }

        // --- 2. SIMPAN DATA KEPALA KELUARGA ---
        $sql_kk = "INSERT INTO tr_warga_kk (nama_kk, nik, telepon, jk, alamat, wilayah, blok, status_rumah, pekerjaan, tmp_lahir, tgl_lahir, pendidikan, gol_darah, asal_daerah, foto) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_kk = $pdo->prepare($sql_kk);
        $stmt_kk->execute([
            $_POST['nama_kk'], $_POST['nik_kk'], $_POST['telepon_kk'], $_POST['jk_kk'], 
            $_POST['alamat'], $_POST['wilayah'], $_POST['blok'], $_POST['status_rumah'], 
            $_POST['pekerjaan_kk'], $_POST['tmp_lahir_kk'], $_POST['tgl_lahir_kk'], 
            $_POST['pendidikan_kk'], $_POST['gol_darah_kk'], $_POST['asal_daerah'], $foto_kk
        ]);

        $id_kk = $pdo->lastInsertId();

        // --- 3. SIMPAN DATA ANGGOTA KELUARGA & FOTO ---
        if (!empty($_POST['nama_anggota'])) {
            $sql_anggota = "INSERT INTO tr_warga_anggota (id_kk, nama, nik, telepon, jk, pekerjaan, tmp_lahir, tgl_lahir, pendidikan, gol_darah, foto) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_agt = $pdo->prepare($sql_anggota);

            foreach ($_POST['nama_anggota'] as $key => $nama) {
                if (!empty($nama)) {
                    $foto_agt = NULL;
                    // Cek apakah ada file foto untuk anggota urutan ke-$key
                    if (!empty($_FILES['foto_anggota']['name'][$key])) {
                        $ext_agt = pathinfo($_FILES['foto_anggota']['name'][$key], PATHINFO_EXTENSION);
                        $foto_agt = "agt_" . time() . "_" . $key . "_" . rand(100,999) . "." . $ext_agt;
                        move_uploaded_file($_FILES['foto_anggota']['tmp_name'][$key], $target_dir . $foto_agt);
                    }

                    $stmt_agt->execute([
                        $id_kk, $nama, $_POST['nik_anggota'][$key], $_POST['telepon_anggota'][$key], 
                        $_POST['jk_anggota'][$key], $_POST['pekerjaan_anggota'][$key], 
                        $_POST['tmp_lahir_anggota'][$key], $_POST['tgl_lahir_anggota'][$key], 
                        $_POST['pendidikan_anggota'][$key], $_POST['gol_darah_anggota'][$key], $foto_agt
                    ]);
                }
            }
        }

        $pdo->commit();
        header("Location: warga_list.php?status=success");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal simpan: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Warga - Bukit Sanggulan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; // Sebaiknya navbar dipisah agar rapi ?>
    <div class="container my-5">
        <form method="POST" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Form Pendaftaran Keluarga</h3>
                <a href="warga_list.php" class="btn btn-secondary">Batal</a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white"><h5>Data Kepala Keluarga</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="fw-bold text-primary">Foto KK (Opsional)</label>
                        <input type="file" name="foto_kk" class="form-control">
                    </div>
                    <div class="col-md-3"><label>Nama Lengkap</label><input type="text" name="nama_kk" class="form-control" required></div>
                    <div class="col-md-3"><label>NIK</label><input type="text" name="nik_kk" class="form-control" required></div>
                    <div class="col-md-3"><label>Telepon</label><input type="text" name="telepon_kk" class="form-control"></div>
                    
                    <div class="col-md-3"><label>Wilayah</label>
                        <select name="wilayah" class="form-select">
                            <?php for($i=1; $i<=11; $i++) echo "<option value='Wilayah ".str_pad($i, 2, "0", STR_PAD_LEFT)."'>Wilayah ".str_pad($i, 2, "0", STR_PAD_LEFT)."</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><label>Blok</label><input type="text" name="blok" class="form-control" placeholder="A1"></div>
                    <div class="col-md-3"><label>Status Rumah</label>
                        <select name="status_rumah" class="form-select">
                            <option>Milik Sendiri</option><option>Sewa</option><option>Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label>Pekerjaan</label><input type="text" name="pekerjaan_kk" class="form-control"></div>
                    <div class="col-md-12"><label>Alamat Lengkap</label><textarea name="alamat" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-3"><label>Jenis Kelamin</label><select name="jk_kk" class="form-select"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                    <div class="col-md-3"><label>Tempat Lahir</label><input type="text" name="tmp_lahir_kk" class="form-control"></div>
                    <div class="col-md-3"><label>Tanggal Lahir</label><input type="date" name="tgl_lahir_kk" class="form-control"></div>
                    <div class="col-md-3"><label>Pendidikan</label><input type="text" name="pendidikan_kk" class="form-control"></div>
                    <input type="hidden" name="gol_darah_kk" value="-">
                    <input type="hidden" name="asal_daerah" value="-">
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5>Data Anggota Keluarga</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="addAnggota()">+ Tambah Anggota</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tableAnggota">
                            <thead class="bg-light">
                                <tr class="small">
                                    <th>Nama Anggota</th>
                                    <th>NIK</th>
                                    <th>JK</th>
                                    <th>Lahir (Tmp, Tgl)</th>
                                    <th>Pekerjaan</th>
                                    <th>Foto</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <button type="submit" name="simpan_warga" class="btn btn-primary btn-lg w-100 shadow">Simpan Seluruh Data & Foto</button>
        </form>
    </div>

    <script>
        function addAnggota() {
            const table = document.getElementById('tableAnggota').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="nama_anggota[]" class="form-control form-control-sm" required></td>
                <td><input type="text" name="nik_anggota[]" class="form-control form-control-sm"></td>
                <td><select name="jk_anggota[]" class="form-select form-select-sm"><option value="L">L</option><option value="P">P</option></select></td>
                <td>
                    <div class="d-flex gap-1">
                        <input type="text" name="tmp_lahir_anggota[]" class="form-control form-control-sm" placeholder="Kota">
                        <input type="date" name="tgl_lahir_anggota[]" class="form-control form-control-sm">
                    </div>
                </td>
                <td><input type="text" name="pekerjaan_anggota[]" class="form-control form-control-sm"></td>
                <td><input type="file" name="foto_anggota[]" class="form-control form-control-sm"></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">X</button></td>
                <input type="hidden" name="telepon_anggota[]" value="">
                <input type="hidden" name="pendidikan_anggota[]" value="">
                <input type="hidden" name="gol_darah_anggota[]" value="">
            `;
        }
        window.onload = addAnggota;
    </script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>