<?php
// Secure session configuration
session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);

// Database connection
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch slider images
$stmt = $conn->prepare("SELECT * FROM slider_images ORDER BY id");
$stmt->execute();
$slider_result = $stmt->get_result();
$sliders = [];
while ($row = $slider_result->fetch_assoc()) {
    $sliders[] = $row;
}
$stmt->close();

// Fetch description (about us)
$section = 'description';
$stmt = $conn->prepare("SELECT content,footer_content FROM content WHERE section = ?");
$stmt->bind_param("s", $section);
$stmt->execute();
$desc_result = $stmt->get_result();

// Ambil baris data sekali saja
$desc_row = $desc_result->fetch_assoc();

// Gunakan variabel $desc_row untuk mengisi kedua variabel
$description = htmlspecialchars($desc_row['footer_content'] ?? '', ENT_QUOTES, 'UTF-8');
$description2 = htmlspecialchars($desc_row['content'] ?? '', ENT_QUOTES, 'UTF-8'); // Mengambil dari $desc_row
$stmt->close();

// Fetch box images
$stmt = $conn->prepare("SELECT * FROM box_images ORDER BY id");
$stmt->execute();
$box_result = $stmt->get_result();
$boxes = [];
while ($row = $box_result->fetch_assoc()) {
    $row['title'] = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
    $row['description'] = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
    $row['image_link'] = htmlspecialchars($row['image_link'], ENT_QUOTES, 'UTF-8');
    $boxes[] = $row;
}
$stmt->close();

// Fetch contact info
$stmt = $conn->prepare("SELECT * FROM contact_info");
$stmt->execute();
$contact_result = $stmt->get_result();
$contact = $contact_result->fetch_assoc();
$contact['email'] = htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8');
$contact['phone'] = htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8');
$contact['facebook'] = htmlspecialchars($contact['facebook'], ENT_QUOTES, 'UTF-8');
$contact['instagram'] = htmlspecialchars($contact['instagram'], ENT_QUOTES, 'UTF-8');
$contact['whatsapp'] = htmlspecialchars($contact['whatsapp'], ENT_QUOTES, 'UTF-8');
$contact['linkedin'] = htmlspecialchars($contact['linkedin'] ?? '', ENT_QUOTES, 'UTF-8');
$stmt->close();

// Fetch footer description (site_content)
$stmt = $conn->prepare("SELECT content FROM site_content LIMIT 1");
$stmt->execute();
$footer_result = $stmt->get_result();
//  $footer_content = htmlspecialchars($desc_result->fetch_assoc()['footer_content'] ?? '', ENT_QUOTES, 'UTF-8');
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="manifest" href="manifest.json">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PMedia Dashboard">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net;">
    <title>PMedia Company</title>
    <meta name="keywords" content="developer website, website silsilah keluarga, website alumni, pmediaku">
    <meta name="robots" content="index, follow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">My PMedia Company</a>
        </div>
    </nav>

    <!-- Slider -->
    <div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($sliders as $index => $slider): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($slider['image_path'], ENT_QUOTES, 'UTF-8'); ?>" class="d-block w-100" alt="Slider Image">
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- Description Section -->
    <div class="description-section py-5">
        <div class="container">
            <h2 class="text-center mb-4">About Us</h2>
            <p class="text-center"><?php echo $description; ?></p>
        </div>
    </div>

    <!-- Image Boxes -->
    <div class="container my-5">
        <div class="row">
            <?php foreach ($boxes as $box): ?>
                <div class="col-md-4 text-center mb-4">
                    <a href="<?php echo $box['image_link']; ?>" target="_blank">
                        <img src="<?php echo htmlspecialchars($box['image_path'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid box-img mb-3" alt="Box Image">
                    </a>
                    <h5><?php echo $box['title']; ?></h5>
                    <p><?php echo $box['description']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer Description -->
    <div class="footer-description py-5">
        <div class="container">
            <h4 class="text-center mb-4">More About Us</h4>
            <p class="text-center"><?php echo $description2; ?></p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4 bg-dark text-light">
        <div class="container text-center">
            <p>Contact: <?php echo $contact['email']; ?> | Phone: <?php echo $contact['phone']; ?></p>
            <div class="social-links">
                <a href="<?php echo $contact['facebook']; ?>" class="text-light mx-2" target="_blank">Facebook</a>
                <a href="<?php echo $contact['instagram']; ?>" class="text-light mx-2" target="_blank">Instagram</a>
                <a href="<?php echo $contact['whatsapp']; ?>" class="text-light mx-2" target="_blank">WhatsApp</a>
                <a href="<?php echo $contact['linkedin']; ?>" class="text-light mx-2" target="_blank">LinkedIn</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
