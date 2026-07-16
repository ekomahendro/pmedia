<?php
include 'koneksi.php'; // Hubungkan ke database

/**
 * Fungsi untuk mengambil data menu berdasarkan kategori
 * @param mysqli $conn Objek koneksi database
 * @param string $kategori Kategori yang ingin diambil ('Food', 'Beverage', 'Dessert')
 * @return array Hasil query
 */
function getMenuByCategory($conn, $kategori) {
    $sql = "SELECT id, nama_makanan, deskripsi, harga, gambar FROM menu_makanan WHERE kategori = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kategori);
    $stmt->execute();
    return $stmt->get_result();
}

// Ambil data untuk setiap kategori
$food_menu = getMenuByCategory($conn, 'Food');
$beverage_menu = getMenuByCategory($conn, 'Beverage');
$dessert_menu = getMenuByCategory($conn, 'Dessert');

// Daftar kategori untuk judul
$categories = [
    'Food' => ['title' => 'Makanan Utama', 'data' => $food_menu],
    'Beverage' => ['title' => 'Minuman Segar', 'data' => $beverage_menu],
    'Dessert' => ['title' => 'Makanan Penutup', 'data' => $dessert_menu]
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Restoran Kami - Berdasarkan Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .menu-card {
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease-in-out;
            border-radius: 10px;
        }
        .menu-card:hover {
            transform: translateY(-5px);
        }
        .menu-img {
            height: 200px; 
            object-fit: cover; 
            width: 100%;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .section-separator {
            border-bottom: 2px solid #dc3545; /* Garis pemisah merah */
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

    <header class="bg-danger text-white text-center py-5 mb-5">
        <div class="container">
            <h1 class="display-4">Buku Menu Digital Restoran Lezat</h1>
            <p class="lead">Kami menyajikan yang terbaik dari setiap kategori.</p>
        </div>
    </header>

    <div class="container">

        <?php 
        // Loop melalui setiap kategori dan tampilkan
        foreach ($categories as $kategori_key => $kategori_data) {
            $title = $kategori_data['title'];
            $result_data = $kategori_data['data'];
        ?>
            <h2 class="text-center mb-5 mt-5 section-separator text-danger"><?php echo $title; ?></h2>
            
            <div class="row">
                
                <?php
                if ($result_data->num_rows > 0) {
                    // Loop untuk menampilkan setiap data menu dalam kategori ini
                    while($row = $result_data->fetch_assoc()) {
                ?>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card menu-card h-100">
                        <img src="images/<?php echo htmlspecialchars($row['gambar']); ?>" class="card-img-top menu-img" alt="<?php echo htmlspecialchars($row['nama_makanan']); ?>">
                        <div class="card-body">
                            <h5 class="card-title text-dark fw-bold"><?php echo htmlspecialchars($row['nama_makanan']); ?></h5>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                            <h4 class="text-success fw-bold m-0">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>

                <?php
                    }
                } else {
                    echo "<div class='col-12'><p class='text-center text-secondary'>Tidak ada menu di kategori ini.</p></div>";
                }
                ?>
                
            </div> <?php 
        } // End foreach
        ?>

    </div> <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>&copy; 2025 Restoran Lezat. Menu Digital Dibuat dengan PHP, MySQL, dan Bootstrap.</p>
        <p>&copy; Hubungi kami : pmediaku 0819-9319-1161.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>