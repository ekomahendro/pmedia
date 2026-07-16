<?php
// Sederhananya, gunakan session check di sini untuk keamanan
include 'koneksi.php';

if($_POST) {
    $groom = $_POST['groom'];
    $bride = $_POST['bride'];
    $color = $_POST['color'];
    // Update data ke SQL
    mysqli_query($conn, "UPDATE settings SET groom_name='$groom', bride_name='$bride', theme_color='$color' WHERE id=1");
    echo "<script>alert('Data Terupdate!');</script>";
}
?>
<form method="POST" class="p-10 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-4">Admin Panel</h2>
    <label>Nama Mempelai Pria:</label>
    <input type="text" name="groom" class="w-full border p-2 mb-4">
    <label>Nama Mempelai Wanita:</label>
    <input type="text" name="bride" class="w-full border p-2 mb-4">
    <label>Warna Tema (Hex):</label>
    <input type="color" name="color" class="w-full h-10 mb-4">
    <button type="submit" class="bg-green-500 text-white p-3 w-full">Simpan Perubahan</button>
</form>