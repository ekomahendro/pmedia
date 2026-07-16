<?php
session_start();

// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'config.php';

$petugas = $bawaan = $keterangan = "";
$petugas_err = $bawaan_err = "";
$search_query = "";

// Process search
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
}

// Process CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new item
    if (isset($_POST['add'])) {
        // Validate petugas
        if (empty(trim($_POST["petugas"]))) {
            $petugas_err = "Mohon masukkan nama petugas.";
        } else {
            $petugas = trim($_POST["petugas"]);
        }

        // Validate bawaan
        if (empty(trim($_POST["bawaan"]))) {
            $bawaan_err = "Mohon masukkan bawaan.";
        } else {
            $bawaan = trim($_POST["bawaan"]);
        }

        $keterangan = trim($_POST["keterangan"]);

        // Check input errors before inserting in database
        if (empty($petugas_err) && empty($bawaan_err)) {
            $sql = "INSERT INTO items (petugas, bawaan, keterangan) VALUES (?, ?, ?)";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sss", $param_petugas, $param_bawaan, $param_keterangan);

                $param_petugas = $petugas;
                $param_bawaan = $bawaan;
                $param_keterangan = $keterangan;

                if (mysqli_stmt_execute($stmt)) {
                    header("location: index.php");
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Terjadi kesalahan. Mohon coba lagi nanti.</div>";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Edit item
    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $petugas = trim($_POST["petugas_edit"]);
        $bawaan = trim($_POST["bawaan_edit"]);
        $keterangan = trim($_POST["keterangan_edit"]);

        if (empty($petugas) || empty($bawaan)) {
             echo "<div class='alert alert-danger'>Petugas dan Bawaan tidak boleh kosong.</div>";
        } else {
            $sql = "UPDATE items SET petugas = ?, bawaan = ?, keterangan = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssi", $param_petugas, $param_bawaan, $param_keterangan, $param_id);
                $param_petugas = $petugas;
                $param_bawaan = $bawaan;
                $param_keterangan = $keterangan;
                $param_id = $id;

                if (mysqli_stmt_execute($stmt)) {
                    header("location: index.php");
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Terjadi kesalahan saat mengupdate.</div>";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Delete item
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM items WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;

            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php");
                exit();
            } else {
                echo "<div class='alert alert-danger'>Terjadi kesalahan saat menghapus.</div>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang Bawaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .wrapper{
            width: 80%;
            margin: 0 auto;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="my-4 text-center">Manajemen Barang Bawaan Camping 23 agustus 2025</h1>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <form class="d-flex" action="index.php" method="get">
                            <input class="form-control me-2" type="search" placeholder="Cari Petugas atau Bawaan..." aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-outline-success" type="submit">Cari</button>
                            <?php if (!empty($search_query)): ?>
                                <a href="index.php" class="btn btn-outline-secondary ms-2">Reset</a>
                            <?php endif; ?>
                        </form>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>

                    <hr>

                    <h2>Tambah Data Baru</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="petugas" class="form-label">Petugas</label>
                                <input type="text" name="petugas" id="petugas" class="form-control <?php echo (!empty($petugas_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $petugas; ?>">
                                <span class="invalid-feedback"><?php echo $petugas_err; ?></span>
                            </div>
                            <div class="col-md-4">
                                <label for="bawaan" class="form-label">Bawaan</label>
                                <input type="text" name="bawaan" id="bawaan" class="form-control <?php echo (!empty($bawaan_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $bawaan; ?>">
                                <span class="invalid-feedback"><?php echo $bawaan_err; ?></span>
                            </div>
                            <div class="col-md-4">
                                <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                                <input type="text" name="keterangan" id="keterangan" class="form-control" value="<?php echo $keterangan; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="submit" class="btn btn-primary" name="add" value="Tambah Data">
                        </div>
                    </form>

                    <hr>

                    <h2>Daftar Barang Bawaan</h2>
                    <?php
                    $sql = "SELECT * FROM items";
                    if (!empty($search_query)) {
                        $sql .= " WHERE petugas LIKE ? OR bawaan LIKE ?";
                    }
                    $sql .= " ORDER BY petugas ASC, bawaan ASC";

                    if ($stmt = mysqli_prepare($link, $sql)) {
                        if (!empty($search_query)) {
                            $search_param = "%" . $search_query . "%";
                            mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
                        }

                        if (mysqli_stmt_execute($stmt)) {
                            $result = mysqli_stmt_get_result($stmt);

                            if (mysqli_num_rows($result) > 0) {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-bordered table-striped">';
                                    echo "<thead>";
                                        echo "<tr>";
                                            echo "<th>#</th>";
                                            echo "<th>Petugas</th>";
                                            echo "<th>Bawaan</th>";
                                            echo "<th>Keterangan</th>";
                                            echo "<th>Aksi</th>";
                                        echo "</tr>";
                                    echo "</thead>";
                                    echo "<tbody>";
                                    while ($row = mysqli_fetch_array($result)) {
                                        echo "<tr>";
                                            echo "<td>" . $row['id'] . "</td>";
                                            echo "<td>" . $row['petugas'] . "</td>";
                                            echo "<td>" . $row['bawaan'] . "</td>";
                                            echo "<td>" . (empty($row['keterangan']) ? '-' : $row['keterangan']) . "</td>";
                                            echo "<td>";
                                                echo '<button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id'] . '">Edit</button>';
                                                echo '<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal' . $row['id'] . '">Delete</button>';
                                            echo "</td>";
                                        echo "</tr>";

                                        // Edit Modal
                                        echo '<div class="modal fade" id="editModal' . $row['id'] . '" tabindex="-1" aria-labelledby="editModalLabel' . $row['id'] . '" aria-hidden="true">';
                                            echo '<div class="modal-dialog">';
                                                echo '<div class="modal-content">';
                                                    echo '<div class="modal-header">';
                                                        echo '<h5 class="modal-title" id="editModalLabel' . $row['id'] . '">Edit Data</h5>';
                                                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                                                    echo '</div>';
                                                    echo '<form action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="post">';
                                                        echo '<div class="modal-body">';
                                                            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                                                            echo '<div class="mb-3">';
                                                                echo '<label for="petugas_edit" class="form-label">Petugas</label>';
                                                                echo '<input type="text" name="petugas_edit" id="petugas_edit" class="form-control" value="' . $row['petugas'] . '" required>';
                                                            echo '</div>';
                                                            echo '<div class="mb-3">';
                                                                echo '<label for="bawaan_edit" class="form-label">Bawaan</label>';
                                                                echo '<input type="text" name="bawaan_edit" id="bawaan_edit" class="form-control" value="' . $row['bawaan'] . '" required>';
                                                            echo '</div>';
                                                            echo '<div class="mb-3">';
                                                                echo '<label for="keterangan_edit" class="form-label">Keterangan (Opsional)</label>';
                                                                echo '<input type="text" name="keterangan_edit" id="keterangan_edit" class="form-control" value="' . $row['keterangan'] . '">';
                                                            echo '</div>';
                                                        echo '</div>';
                                                        echo '<div class="modal-footer">';
                                                            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>';
                                                            echo '<button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>';
                                                        echo '</div>';
                                                    echo '</form>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</div>';

                                        // Delete Modal
                                        echo '<div class="modal fade" id="deleteModal' . $row['id'] . '" tabindex="-1" aria-labelledby="deleteModalLabel' . $row['id'] . '" aria-hidden="true">';
                                            echo '<div class="modal-dialog">';
                                                echo '<div class="modal-content">';
                                                    echo '<div class="modal-header">';
                                                        echo '<h5 class="modal-title" id="deleteModalLabel' . $row['id'] . '">Hapus Data</h5>';
                                                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                                                    echo '</div>';
                                                    echo '<div class="modal-body">';
                                                        echo '<p>Apakah Anda yakin ingin menghapus data ini?</p>';
                                                        echo '<p><strong>ID:</strong> ' . $row['id'] . '<br>';
                                                        echo '<strong>Petugas:</strong> ' . $row['petugas'] . '<br>';
                                                        echo '<strong>Bawaan:</strong> ' . $row['bawaan'] . '</p>';
                                                    echo '</div>';
                                                    echo '<div class="modal-footer">';
                                                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>';
                                                        echo '<form action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="post" class="d-inline">';
                                                            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                                                            echo '<button type="submit" name="delete" class="btn btn-danger">Hapus</button>';
                                                        echo '</form>';
                                                    echo '</div>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</div>';
                                    }
                                    echo "</tbody>";
                                echo "</table>";
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info">Tidak ada data ditemukan.</div>';
                            }
                        } else {
                            echo "<div class='alert alert-danger'>Terjadi kesalahan saat mengambil data.</div>";
                        }
                        mysqli_stmt_close($stmt);
                    }

                    // Close connection
                    // mysqli_close($link); // Close connection here if not needed for resume
                    ?>

                    <hr>

                    <h2>Resume Bawaan per Petugas</h2>
                    <div class="row">
                    <?php
                    // Re-establish connection if closed previously, or ensure it's open
                    if (!isset($link) || mysqli_connect_errno()) {
                        require_once 'config.php';
                    }

                    $sql_resume = "SELECT petugas, GROUP_CONCAT(bawaan SEPARATOR ', ') AS all_bawaan, COUNT(*) AS total_items FROM items GROUP BY petugas ORDER BY petugas ASC";
                    if ($result_resume = mysqli_query($link, $sql_resume)) {
                        if (mysqli_num_rows($result_resume) > 0) {
                            while ($row_resume = mysqli_fetch_assoc($result_resume)) {
                                echo '<div class="col-md-6 mb-3">';
                                    echo '<div class="card">';
                                        echo '<div class="card-body">';
                                            echo '<h5 class="card-title">' . htmlspecialchars($row_resume['petugas']) . '</h5>';
                                            echo '<p class="card-text"><strong>Total Item Bawaan:</strong> ' . $row_resume['total_items'] . '</p>';
                                            echo '<p class="card-text"><strong>Daftar Bawaan:</strong> ' . htmlspecialchars($row_resume['all_bawaan']) . '</p>';
                                        echo '</div>';
                                    echo '</div>';
                                echo '</div>';
                            }
                            mysqli_free_result($result_resume);
                        } else {
                            echo '<div class="col-md-12"><div class="alert alert-info">Tidak ada data resume.</div></div>';
                        }
                    } else {
                        echo '<div class="col-md-12"><div class="alert alert-danger">Terjadi kesalahan saat mengambil resume data.</div></div>';
                    }

                    mysqli_close($link);
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>