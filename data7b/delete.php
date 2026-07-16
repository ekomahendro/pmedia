<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header("location: index.php");
    exit;
}

include_once 'config.php';

if (isset($_GET["no"]) && !empty(trim($_GET["no"]))) {
    $no = trim($_GET["no"]);

    // Ambil nama file foto sebelum dihapus dari database
    $sql_select_foto = "SELECT foto FROM data7b WHERE no = ?";
    if ($stmt_select = mysqli_prepare($link, $sql_select_foto)) {
        mysqli_stmt_bind_param($stmt_select, "i", $param_no);
        $param_no = $no;
        if (mysqli_stmt_execute($stmt_select)) {
            $result_select = mysqli_stmt_get_result($stmt_select);
            if (mysqli_num_rows($result_select) == 1) {
                $row_select = mysqli_fetch_assoc($result_select);
                $foto_to_delete = $row_select['foto'];

                // Hapus data dari database
                $sql_delete = "DELETE FROM data7b WHERE no = ?";
                if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
                    mysqli_stmt_bind_param($stmt_delete, "i", $param_no);
                    if (mysqli_stmt_execute($stmt_delete)) {
                        // Hapus file foto dari server jika ada
                        if (!empty($foto_to_delete) && file_exists('uploads/' . $foto_to_delete)) {
                            unlink('uploads/' . $foto_to_delete);
                        }
                        header("location: dashboard.php");
                        exit();
                    } else {
                        echo "Terjadi kesalahan saat menghapus data. Silakan coba lagi nanti.";
                    }
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                echo "Data tidak ditemukan.";
            }
        } else {
            echo "Terjadi kesalahan saat mengambil data foto. Silakan coba lagi nanti.";
        }
    }
    mysqli_stmt_close($stmt_select);
} else {
    header("location: dashboard.php");
    exit();
}

mysqli_close($link);
?>