<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
include 'koneksi.php';

// Proses Upload Galeri Pre-wedding
if (isset($_POST['upload_gallery'])) {
    if (!empty($_FILES['gallery_files']['name'][0])) {
        foreach ($_FILES['gallery_files']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['gallery_files']['name'][$key];
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_name = "gallery_" . time() . "_" . $key . "." . $ext;
            
            if (move_uploaded_file($tmp_name, "uploads/" . $new_name)) {
                mysqli_query($conn, "INSERT INTO prewedding_gallery (image_path) VALUES ('$new_name')");
            }
        }
    }
}

// Proses Hapus Foto Galeri
if (isset($_GET['delete_img'])) {
    $id_img = $_GET['delete_img'];
    $img_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM prewedding_gallery WHERE id=$id_img"));
    unlink("uploads/" . $img_data['image_path']);
    mysqli_query($conn, "DELETE FROM prewedding_gallery WHERE id=$id_img");
    header("Location: admin_dashboard.php");
}

// Ambil Data Saat Ini
$query = mysqli_query($conn, "SELECT * FROM settings WHERE id=1");
$data = mysqli_fetch_assoc($query);

// Jika data kosong, buat array kosong agar tidak error di form
if (!$data) {
    $data = [
        'groom_name' => '',
        'bride_name' => '',
        'event_date' => date('Y-m-d H:i'),
        'theme_color' => '#d4af37',
        'cerita_singkat' => '',
        'foto_pria' => '',
        'foto_wanita' => ''
    ];
}

// Proses Update Data & Upload Foto
if (isset($_POST['update'])) {
    $groom = mysqli_real_escape_string($conn, $_POST['groom']);
    $bride = mysqli_real_escape_string($conn, $_POST['bride']);
    $tgl = $_POST['tgl'];
    $color = $_POST['color'];
    $cerita = mysqli_real_escape_string($conn, $_POST['cerita']);

    // Logika Upload Foto Pria
    $foto_pria = $data['foto_pria'];
    if ($_FILES['f_pria']['name']) {
        $ext = pathinfo($_FILES['f_pria']['name'], PATHINFO_EXTENSION);
        $foto_pria = "pria_" . time() . "." . $ext;
        move_uploaded_file($_FILES['f_pria']['tmp_name'], "uploads/" . $foto_pria);
    }

    // Logika Upload Foto Wanita
    $foto_wanita = $data['foto_wanita'];
    if ($_FILES['f_wanita']['name']) {
        $ext = pathinfo($_FILES['f_wanita']['name'], PATHINFO_EXTENSION);
        $foto_wanita = "wanita_" . time() . "." . $ext;
        move_uploaded_file($_FILES['f_wanita']['tmp_name'], "uploads/" . $foto_wanita);
    }

    $sql = "UPDATE settings SET 
            groom_name='$groom', 
            bride_name='$bride', 
            event_date='$tgl', 
            theme_color='$color',
            cerita_singkat='$cerita',
            foto_pria='$foto_pria',
            foto_wanita='$foto_wanita' 
            WHERE id=1";
    
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Data Berhasil Diperbarui!'); window.location='admin_dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wedding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-[Inter]">

    <div class="flex min-h-screen">
        <aside class="w-64 bg-stone-900 text-white p-6 hidden md:block">
            <h2 class="text-2xl font-bold mb-10 text-yellow-500">WeddingAdmin</h2>
            <nav class="space-y-4">
                <a href="#" class="block p-3 bg-yellow-600 rounded">Dashboard</a>
                <a href="index.php" target="_blank" class="block p-3 hover:bg-stone-800 rounded">Lihat Undangan</a>
                <a href="logout.php" class="block p-3 text-red-400 hover:bg-red-900/20 rounded">Logout</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <header class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Pengaturan Undangan</h1>
                <span class="text-sm text-gray-500">Selamat datang, Admin</span>
            </header>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Informasi Mempelai</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Pengantin Pria</label>
                            <input type="text" name="groom" value="<?= $data['groom_name'] ?>" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Foto Pengantin Pria</label>
                            <input type="file" name="f_pria" class="text-xs">
                            <p class="text-[10px] text-gray-400 mt-1">File saat ini: <?= $data['foto_pria'] ?></p>
                        </div>
                        <hr>
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Pengantin Wanita</label>
                            <input type="text" name="bride" value="<?= $data['bride_name'] ?>" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Foto Pengantin Wanita</label>
                            <input type="file" name="f_wanita" class="text-xs">
                            <p class="text-[10px] text-gray-400 mt-1">File saat ini: <?= $data['foto_wanita'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Acara & Tampilan</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Tanggal & Waktu Acara</label>
                            <input type="datetime-local" name="tgl" value="<?= date('Y-m-d\TH:i', strtotime($data['event_date'])) ?>" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Warna Tema Undangan</label>
                            <input type="color" name="color" value="<?= $data['theme_color'] ?>" class="w-full h-10 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Cerita Singkat (Love Story)</label>
                            <textarea name="cerita" rows="4" class="w-full p-2 border rounded text-sm"><?= $data['cerita_singkat'] ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" name="update" class="bg-stone-900 text-white px-8 py-3 rounded-lg font-bold hover:bg-black transition shadow-lg w-full md:w-auto">
                        SIMPAN PERUBAHAN
                    </button>
                </div>
            </form>

            <div class="mt-12">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Ucapan Masuk (RSVP)</h3>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="p-4">Nama</th>
                                <th class="p-4">Ucapan</th>
                                <th class="p-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $msg = mysqli_query($conn, "SELECT * FROM comments ORDER BY id DESC LIMIT 10");
                            while($row = mysqli_fetch_assoc($msg)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-4 font-bold text-sm"><?= $row['guest_name'] ?></td>
                                <td class="p-4 text-sm text-gray-600"><?= $row['message'] ?></td>
                                <td class="p-4 text-xs font-bold uppercase text-yellow-600"><?= $row['status'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mt-8">
    <h3 class="text-lg font-bold mb-4 border-b pb-2 text-stone-800">Galeri Pre-Wedding</h3>
    <form method="POST" enctype="multipart/form-data" class="flex gap-4 mb-6">
        <input type="file" name="gallery_files[]" multiple class="text-sm">
        <button type="submit" name="upload_gallery" class="bg-yellow-600 text-white px-4 py-2 rounded text-sm font-bold">Upload Foto</button>
    </form>
    
    <div class="grid grid-cols-3 md:grid-cols-5 gap-4">
        <?php
        $gal = mysqli_query($conn, "SELECT * FROM prewedding_gallery");
        while($g = mysqli_fetch_assoc($gal)): ?>
            <div class="relative group">
                <img src="uploads/<?= $g['image_path'] ?>" class="w-full h-24 object-cover rounded-lg">
                <a href="?delete_img=<?= $g['id'] ?>" onclick="return confirm('Hapus foto?')" class="absolute top-1 right-1 bg-red-600 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">Hapus</a>
            </div>
        <?php endwhile; ?>
    </div>
</div>
        </main>
    </div>

</body>
</html>