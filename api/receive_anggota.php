<?php

header('Content-Type: application/json');

$conn = mysqli_connect(
    "localhost",
    "u1775096_uinvit",
    "Admin_local",
    "u1775096_dinvit"
);

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal"
    ]));
}

$api_key = $_POST['api_key'] ?? '';

if ($api_key != 'RAHASIA123') {
    die(json_encode([
        "status" => "error",
        "message" => "API KEY salah"
    ]));
}

$nama   = $_POST['nama'] ?? '';
$alamat = $_POST['alamat'] ?? '';

$nama   = mysqli_real_escape_string($conn, $nama);
$alamat = mysqli_real_escape_string($conn, $alamat);

$query = mysqli_query($conn, "
INSERT INTO apiusers(nama, alamat)
VALUES('$nama', '$alamat')
");

if ($query) {

    echo json_encode([
        "status" => "success",
        "message" => "Data berhasil disimpan"
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => mysqli_error($conn)
    ]);

}
?>