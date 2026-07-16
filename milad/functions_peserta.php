<?php
// ==========================================
// LOGIKA BISNIS CRUD - GEBYAR MILAD XV
// ==========================================

// CRUD Kategori Lomba
function handleKategori($pdo) {
    if (isset($_POST['action_kategori'])) {
        $id = $_POST['id'] ?? '';
        $nama_kategori = $_POST['nama_kategori'];
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO mld_kategori (nama_kategori) VALUES (?)");
            $stmt->execute([$nama_kategori]);
        } else {
            $stmt = $pdo->prepare("UPDATE mld_kategori SET nama_kategori = ? WHERE id = ?");
            $stmt->execute([$nama_kategori, $id]);
        }
        header("Location: peserta.php?tab=kategori&status=success");
        exit;
    }
    if (isset($_GET['hapus_kategori'])) {
        $stmt = $pdo->prepare("DELETE FROM mld_kategori WHERE id = ?");
        $stmt->execute([$_GET['hapus_kategori']]);
        header("Location: peserta.php?tab=kategori&status=deleted");
        exit;
    }
}

// CRUD Majelis Taklim
function handleMajelis($pdo) {
    if (isset($_POST['action_majelis'])) {
        $id = $_POST['id'] ?? '';
        $nama_mt = $_POST['nama_mt'];
        $pic = $_POST['pic'];
        $no_hp = $_POST['no_hp'];
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO mld_majelis (nama_mt, pic, no_hp) VALUES (?, ?, ?)");
            $stmt->execute([$nama_mt, $pic, $no_hp]);
        } else {
            $stmt = $pdo->prepare("UPDATE mld_majelis SET nama_mt = ?, pic = ?, no_hp = ? WHERE id = ?");
            $stmt->execute([$nama_mt, $pic, $no_hp, $id]);
        }
        header("Location: peserta.php?tab=majelis&status=success");
        exit;
    }
    if (isset($_GET['hapus_majelis'])) {
        $stmt = $pdo->prepare("DELETE FROM mld_majelis WHERE id = ?");
        $stmt->execute([$_GET['hapus_majelis']]);
        header("Location: peserta.php?tab=majelis&status=deleted");
        exit;
    }
}

// CRUD Peserta Lomba
function handlePeserta($pdo) {
    if (isset($_POST['action_peserta'])) {
        $id = $_POST['id'] ?? '';
        $nama_peserta = $_POST['nama_peserta'];
        $no_hp = $_POST['no_hp'];
        $majelis_id = $_POST['majelis_id'];
        $kategori_ids = $_POST['kategori_ids'] ?? []; // Array dari banyak lomba

        // Logika upload foto tetap sama
        $foto_name = $_POST['old_foto'] ?? '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
            $foto_name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto_name);
        }

        if (empty($id)) {
            // INSERT PESERTA BARU
            $stmt = $pdo->prepare("INSERT INTO mld_peserta (nama_peserta, no_hp, majelis_id, foto) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_peserta, $no_hp, $majelis_id, $foto_name]);
            $peserta_id = $pdo->lastInsertId();
        } else {
            // UPDATE PESERTA
            $stmt = $pdo->prepare("UPDATE mld_peserta SET nama_peserta = ?, no_hp = ?, majelis_id = ?, foto = ? WHERE id = ?");
            $stmt->execute([$nama_peserta, $no_hp, $majelis_id, $foto_name, $id]);
            $peserta_id = $id;

            // Hapus relasi lomba lama terlebih dahulu sebelum menulis yang baru
            $stmt_del = $pdo->prepare("DELETE FROM mld_peserta_lomba WHERE peserta_id = ?");
            $stmt_del->execute([$peserta_id]);
        }

        // Simpan semua cabang lomba yang diikuti oleh peserta ini ke tabel jembatan
        if (!empty($kategori_ids)) {
            $stmt_lomba = $pdo->prepare("INSERT INTO mld_peserta_lomba (peserta_id, kategori_id) VALUES (?, ?)");
            foreach ($kategori_ids as $kat_id) {
                $stmt_lomba->execute([$peserta_id, $kat_id]);
            }
        }

        header("Location: peserta.php?tab=peserta&status=saved");
        exit;
    }

    if (isset($_GET['hapus_peserta'])) {
        $id = $_GET['hapus_peserta'];
        // Hapus data pendaftaran lombanya dulu
        $stmt_lomba = $pdo->prepare("DELETE FROM mld_peserta_lomba WHERE peserta_id = ?");
        $stmt_lomba->execute([$id]);
        
        // Hapus data peserta utama
        $stmt = $pdo->prepare("DELETE FROM mld_peserta WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: peserta.php?tab=peserta&status=deleted");
        exit;
    }
}

function handleDisplayKontrol($pdo) {
    if (isset($_POST['action_display'])) {
        $kategori_id = $_POST['kategori_id'];
        $_SESSION['display_mode'] = 'lomba';
        // Reset status panggung semua Majelis Taklim & Peserta terdahulu
        $pdo->query("UPDATE mld_majelis SET is_tampil = 0, skor = 0");
        $pdo->query("UPDATE mld_peserta SET is_tampil = 0");

        if (isset($_POST['majelis_id_a'])) {
            // JIKA MODE CERDAS CERMAT: Yang di-update adalah tabel mld_majelis!
            $skor_a = intval($_POST['skor_a']);
            $skor_b = intval($_POST['skor_b']);
            $skor_c = intval($_POST['skor_c']);

            if($_POST['majelis_id_a']) {
                $stmt1 = $pdo->prepare("UPDATE mld_majelis SET is_tampil = 1, skor = ? WHERE id = ?");
                $stmt1->execute([$skor_a, $_POST['majelis_id_a']]);
            }
            if($_POST['majelis_id_b']) {
                $stmt2 = $pdo->prepare("UPDATE mld_majelis SET is_tampil = 2, skor = ? WHERE id = ?");
                $stmt2->execute([$skor_b, $_POST['majelis_id_b']]);
            }
            if($_POST['majelis_id_c']) {
                $stmt3 = $pdo->prepare("UPDATE mld_majelis SET is_tampil = 3, skor = ? WHERE id = ?");
                $stmt3->execute([$skor_c, $_POST['majelis_id_c']]);
            }
        } else {
            // JIKA MODE LOMBA BIASA INDIVIDU: Ambil ID peserta tunggal
            $peserta_id = $_POST['peserta_id'] ?: null;
            if ($peserta_id) {
                $stmt = $pdo->prepare("UPDATE mld_peserta SET is_tampil = 1 WHERE id = ?");
                $stmt->execute([$peserta_id]);
            }
        }
        
        header("Location: peserta.php?tab=kontrol_display&status=display_updated&kat_id=" . $kategori_id);
        exit;
    }
}
function handlePanitia($pdo) {
    if (isset($_POST['action_panitia'])) {
        $id = $_POST['id'] ?? '';
        $nama_panitia = $_POST['nama_panitia'];
        $jabatan = $_POST['jabatan'];
        $no_hp = $_POST['no_hp'];

        // Logika upload foto panitia
        $foto_name = $_POST['old_foto'] ?? '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
            $foto_name = time() . '_panitia_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto_name);
        }

        if (empty($id)) {
            // INSERT BARU
            $stmt = $pdo->prepare("INSERT INTO mld_panitia (nama_panitia, jabatan, no_hp, foto) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_panitia, $jabatan, $no_hp, $foto_name]);
        } else {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE mld_panitia SET nama_panitia = ?, jabatan = ?, no_hp = ?, foto = ? WHERE id = ?");
            $stmt->execute([$nama_panitia, $jabatan, $no_hp, $foto_name, $id]);
        }

        header("Location: peserta.php?tab=panitia&status=saved");
        exit;
    }

    if (isset($_GET['hapus_panitia'])) {
        $id = $_GET['hapus_panitia'];
        $stmt = $pdo->prepare("DELETE FROM mld_panitia WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: peserta.php?tab=panitia&status=deleted");
        exit;
    }
}