<?php
// Secure session configuration
session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Secure password hashing
$admin_username = 'admin';
$admin_password_hash = password_hash('password123', PASSWORD_BCRYPT);

// Handle login
if (isset($_POST['login'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    if ($username === $admin_username && password_verify($password, $admin_password_hash)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['last_activity'] = time();
    } else {
        $error = "Invalid credentials";
    }
}

// Session timeout (30 minutes)
if (isset($_SESSION['loggedin']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: admin.php");
    exit;
}

// === BAGIAN UNTUK SIMPAN LINK GAMBAR ===
if (isset($_POST['update_image_link'])) {
    $id = $_POST['id'];
    $link = $_POST['image_link'];
    $stmt = $conn->prepare("UPDATE box_images SET image_link=? WHERE id=?");
    $stmt->bind_param("si", $link, $id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Link gambar berhasil diperbarui'); window.location='admin.php';</script>";
}
// Handle updates & CRUD
if (isset($_SESSION['loggedin'])) {
    // Update Description
    if (isset($_POST['update_description'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("UPDATE content SET content = ? WHERE section = 'description'");
        $stmt->bind_param("s", $description);
        $stmt->execute();
        $stmt->close();
    }

    // Update Contact Info
    if (isset($_POST['update_contact'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $facebook = filter_input(INPUT_POST, 'facebook', FILTER_SANITIZE_URL);
        $instagram = filter_input(INPUT_POST, 'instagram', FILTER_SANITIZE_URL);
        $linkedin = filter_input(INPUT_POST, 'linkedin', FILTER_SANITIZE_URL);
        $whatsapp = filter_input(INPUT_POST, 'whatsapp', FILTER_SANITIZE_URL);
        $stmt = $conn->prepare("UPDATE contact_info SET email=?, phone=?, facebook=?, instagram=?, linkedin=?, whatsapp=?");
        $stmt->bind_param("ssssss", $email, $phone, $facebook, $instagram, $linkedin, $whatsapp);
        $stmt->execute();
        $stmt->close();
    }

    // Add Slider
    if (isset($_POST['add_slider'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024;
        if (in_array($_FILES['slider_image']['type'], $allowed_types) && $_FILES['slider_image']['size'] <= $max_size) {
            $target_dir = "uploads/";
            $ext = pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('slider_') . '.' . $ext;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['slider_image']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO slider_images (image_path) VALUES (?)");
                $stmt->bind_param("s", $target_file);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Edit Slider
    if (isset($_POST['edit_slider'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $id = intval($_POST['slider_id']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if ($_FILES['slider_image_edit']['size'] > 0 && in_array($_FILES['slider_image_edit']['type'], $allowed_types)) {
            $target_dir = "uploads/";
            $ext = pathinfo($_FILES['slider_image_edit']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('slider_') . '.' . $ext;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['slider_image_edit']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("UPDATE slider_images SET image_path=? WHERE id=?");
                $stmt->bind_param("si", $target_file, $id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Delete Slider
    if (isset($_POST['delete_slider'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $id = intval($_POST['slider_id']);
        $stmt = $conn->prepare("SELECT image_path FROM slider_images WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $path = $stmt->get_result()->fetch_assoc()['image_path'];
        $stmt->close();
        if (file_exists($path)) unlink($path);
        $stmt = $conn->prepare("DELETE FROM slider_images WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // Add Box
    if (isset($_POST['add_box'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $title = filter_input(INPUT_POST, 'box_title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'box_description', FILTER_SANITIZE_STRING);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024;
        if (in_array($_FILES['box_image']['type'], $allowed_types) && $_FILES['box_image']['size'] <= $max_size) {
            $target_dir = "uploads/";
            $ext = pathinfo($_FILES['box_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('box_') . '.' . $ext;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['box_image']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO box_images (image_path, title, description) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $target_file, $title, $description);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Edit Box
    if (isset($_POST['edit_box'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $id = intval($_POST['box_id']);
        $title = filter_input(INPUT_POST, 'box_title_edit', FILTER_SANITIZE_STRING);
        $desc = filter_input(INPUT_POST, 'box_description_edit', FILTER_SANITIZE_STRING);
        $query = "UPDATE box_images SET title=?, description=?";
        $params = [$title, $desc];
        $types = "ss";
        if ($_FILES['box_image_edit']['size'] > 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['box_image_edit']['type'], $allowed_types)) {
                $target_dir = "uploads/";
                $ext = pathinfo($_FILES['box_image_edit']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('box_') . '.' . $ext;
                $target_file = $target_dir . $filename;
                move_uploaded_file($_FILES['box_image_edit']['tmp_name'], $target_file);
                $query .= ", image_path=?";
                $params[] = $target_file;
                $types .= "s";
            }
        }
        $query .= " WHERE id=?";
        $params[] = $id;
        $types .= "i";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }

    // Delete Box
    if (isset($_POST['delete_box'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF token validation failed");
        $id = intval($_POST['box_id']);
        $stmt = $conn->prepare("SELECT image_path FROM box_images WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $path = $stmt->get_result()->fetch_assoc()['image_path'];
        $stmt->close();
        if (file_exists($path)) unlink($path);
        $stmt = $conn->prepare("DELETE FROM box_images WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // Fetch all data
    $description = $conn->query("SELECT content FROM content WHERE section='description'")->fetch_assoc()['content'];
    $contact = $conn->query("SELECT * FROM contact_info")->fetch_assoc();
    $sliders = $conn->query("SELECT * FROM slider_images")->fetch_all(MYSQLI_ASSOC);
    $boxes = $conn->query("SELECT * FROM box_images")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
<?php if (!isset($_SESSION['loggedin'])): ?>
    <div class="card p-4 mx-auto" style="max-width:400px;">
        <h2 class="text-center">Admin Login</h2>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input class="form-control mb-2" name="username" placeholder="Username" required>
            <input class="form-control mb-2" name="password" type="password" placeholder="Password" required>
            <button class="btn btn-primary w-100" name="login">Login</button>
        </form>
    </div>
<?php else: ?>
    <h1 class="text-center mb-4">Admin Panel</h1>

    <!-- Update Description -->
    <div class="card p-4 mb-4">
        <h3>Update Description</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <textarea name="description" class="form-control mb-3" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
            <button class="btn btn-primary" name="update_description">Update</button>
        </form>
    </div>

    <!-- Update Contact -->
    <div class="card p-4 mb-4">
        <h3>Update Contact Info</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="row">
                <div class="col-md-6 mb-2"><input class="form-control" name="email" value="<?php echo htmlspecialchars($contact['email']); ?>" placeholder="Email"></div>
                <div class="col-md-6 mb-2"><input class="form-control" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>" placeholder="Phone"></div>
                <div class="col-md-6 mb-2"><input class="form-control" name="facebook" value="<?php echo htmlspecialchars($contact['facebook']); ?>" placeholder="Facebook URL"></div>
                <div class="col-md-6 mb-2"><input class="form-control" name="instagram" value="<?php echo htmlspecialchars($contact['instagram']); ?>" placeholder="Instagram URL"></div>
                <div class="col-md-6 mb-2"><input class="form-control" name="linkedin" value="<?php echo htmlspecialchars($contact['linkedin']); ?>" placeholder="LinkedIn URL"></div>
                <div class="col-md-6 mb-2"><input class="form-control" name="whatsapp" value="<?php echo htmlspecialchars($contact['whatsapp']); ?>" placeholder="WhatsApp URL"></div>
            </div>
            <button class="btn btn-primary" name="update_contact">Update</button>
        </form>
    </div>

    <!-- Manage Slider -->
    <div class="card p-4 mb-4">
        <h3>Manage Slider Images</h3>
        <form method="post" enctype="multipart/form-data" class="mb-3">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input class="form-control mb-2" type="file" name="slider_image" required>
            <button class="btn btn-success" name="add_slider">Add Image</button>
        </form>
        <div class="row">
            <?php foreach($sliders as $s): ?>
                <div class="col-md-3 text-center mb-3">
                    <img src="<?php echo htmlspecialchars($s['image_path']); ?>" class="img-fluid mb-2">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="slider_id" value="<?php echo $s['id']; ?>">
                        <input type="file" name="slider_image_edit" class="form-control mb-2">
                        <button class="btn btn-warning btn-sm" name="edit_slider">Edit</button>
                        <button class="btn btn-danger btn-sm" name="delete_slider" onclick="return confirm('Delete this image?')">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Manage Boxes -->
    <div class="card p-4 mb-4">
        <h3>Manage Box Images</h3>
        <form method="post" enctype="multipart/form-data" class="mb-3">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input class="form-control mb-2" name="box_title" placeholder="Title" required>
            <textarea class="form-control mb-2" name="box_description" rows="3" placeholder="Description" required></textarea>
            <input class="form-control mb-2" type="file" name="box_image" required>
            <button class="btn btn-success" name="add_box">Add Box</button>
        </form>

        <div class="row">
            <?php foreach($boxes as $b): ?>
                <div class="col-md-4 mb-4">
                    <img src="<?php echo htmlspecialchars($b['image_path']); ?>" class="img-fluid mb-2">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="box_id" value="<?php echo $b['id']; ?>">
                        <input class="form-control mb-2" name="box_title_edit" value="<?php echo htmlspecialchars($b['title']); ?>">
                        <textarea class="form-control mb-2" name="box_description_edit" rows="2"><?php echo htmlspecialchars($b['description']); ?></textarea>
                        <input class="form-control mb-2" type="file" name="box_image_edit">
                        <button class="btn btn-warning btn-sm" name="edit_box">Edit</button>
                        <button class="btn btn-danger btn-sm" name="delete_box" onclick="return confirm('Delete this box?')">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
