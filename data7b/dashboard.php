<?php
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}

include_once 'config.php';

$is_admin = $_SESSION['is_admin'] ?? false;
$search_query = '';
$where_clause = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $where_clause = " WHERE nama LIKE '%$search_query%' OR abi LIKE '%$search_query%' OR ummi LIKE '%$search_query%' OR asal LIKE '%$search_query%'";
}

// Ambil data dari database
$sql = "SELECT * FROM data7b" . $where_clause . " ORDER BY no ASC";
$result = mysqli_query($link, $sql);

// Ambil data untuk resume jadwal
$sql_resume = "SELECT nama, jadwaltime, jadwalhari FROM data7b WHERE jadwaltime IS NOT NULL AND jadwalhari IS NOT NULL ORDER BY jadwalhari, jadwaltime";
$result_resume = mysqli_query($link, $sql_resume);

$jadwal_grouped = [];
if ($result_resume && mysqli_num_rows($result_resume) > 0) {
    while ($row_resume = mysqli_fetch_assoc($result_resume)) {
        $jadwal_grouped[$row_resume['jadwalhari']][] = $row_resume;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Data 7B</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Data 7B</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Daftar Data Siswa 7B</h2>

        <div class="mb-3">
            <form class="d-flex" action="dashboard.php" method="get">
                <input class="form-control me-2" type="search" placeholder="Cari Nama, Abi, Ummi, Asal..." aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-outline-success" type="submit">Cari</button>
                <?php if (!empty($search_query)): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($is_admin): ?>
            <a href="add.php" class="btn btn-success mb-3">Tambah Data Baru</a>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Abi</th>
                        <th>Ummi</th>
                        <th>HP Abi</th>
                        <th>HP Ummi</th>
                        <th>Foto Ummi</th> <th>Asal</th>
                        <th>Foto Siswa</th> <th>Jadwal Telepon</th>
                        <th>Jadwal Hari</th>
                        <?php if ($is_admin): ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $row['no'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['abi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['ummi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['hpabbi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['hpummi']) . "</td>";
                            
                            // Cell untuk Foto Ummi
                            echo "<td>";
                            if (!empty($row['fotoummi']) && file_exists('uploads/' . $row['fotoummi'])) {
                                $full_image_path = 'uploads/' . htmlspecialchars($row['fotoummi']);
                                echo "<a href='#' data-bs-toggle='modal' data-bs-target='#imageModal' data-bs-src='" . $full_image_path . "'>";
                                echo "<img src='" . $full_image_path . "' alt='Foto Ummi' style='width: 50px; height: 50px; object-fit: cover; cursor: pointer;'>";
                                echo "</a>";
                            } else {
                                echo "Tidak ada foto";
                            }
                            echo "</td>";
                            
                            echo "<td>" . htmlspecialchars($row['asal']) . "</td>";
                            
                            // Cell untuk Foto Siswa
                            echo "<td>";
                            if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) {
                                $full_image_path = 'uploads/' . htmlspecialchars($row['foto']);
                                echo "<a href='#' data-bs-toggle='modal' data-bs-target='#imageModal' data-bs-src='" . $full_image_path . "'>";
                                echo "<img src='" . $full_image_path . "' alt='Foto Siswa' style='width: 50px; height: 50px; object-fit: cover; cursor: pointer;'>";
                                echo "</a>";
                            } else {
                                echo "Tidak ada foto";
                            }
                            echo "</td>";
                            
                            echo "<td>" . htmlspecialchars($row['jadwaltime']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['jadwalhari']) . "</td>";
                            if ($is_admin) {
                                echo "<td>";
                                echo "<a href='edit.php?no=" . $row['no'] . "' class='btn btn-warning btn-sm me-2'>Edit</a>";
                                echo "<a href='delete.php?no=" . $row['no'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Anda yakin ingin menghapus data ini?\");'>Delete</a>";
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12' class='text-center'>Tidak ada data ditemukan.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

<hr class="my-5">

        <h3 class="mb-4">Resume Jadwal Telepon</h3>
        <?php
        // Definisikan urutan hari
        $day_order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        // Urutkan jadwal_grouped sesuai urutan hari yang diinginkan
        $ordered_jadwal = [];
        foreach ($day_order as $day) {
            if (isset($jadwal_grouped[$day])) {
                $ordered_jadwal[$day] = $jadwal_grouped[$day];
            }
        }

        if (!empty($ordered_jadwal)): ?>
            <div class="row">
                <?php
                foreach ($ordered_jadwal as $hari => $jadwals):
                ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5>Jadwal Hari: <?php echo htmlspecialchars($hari); ?></h5>
                            </div>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($jadwals as $jadwal): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo htmlspecialchars($jadwal['nama']); ?></strong> - <?php echo htmlspecialchars($jadwal['jadwaltime']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php
                endforeach;
                ?>
            </div>
        <?php else: ?>
            <p>Tidak ada jadwal telepon yang tersedia.</p>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Detail Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalImage" class="img-fluid" alt="Foto">
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var imageModal = document.getElementById('imageModal');
        imageModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var imageSrc = button.getAttribute('data-bs-src'); // Ambil nilai data-bs-src
            var modalImage = imageModal.querySelector('#modalImage');
            modalImage.src = imageSrc; // Atur src gambar modal
            
            var altText = button.querySelector('img').alt;
            var modalTitle = imageModal.querySelector('.modal-title');
            modalTitle.textContent = 'Detail ' + altText;
        });
    });
</script>
</body>
</html>

<?php
mysqli_free_result($result);
if (isset($result_resume)) {
    mysqli_free_result($result_resume);
}
mysqli_close($link);
?>