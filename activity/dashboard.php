<?php
session_start();
// require_once '_header.php'; // Memuat CSS dan Navigasi

// Cek jika user belum login, redirect ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'config.php';
$user_status = $_SESSION['status'];
$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['id'];

// Logika untuk menampilkan nama pembina (khusus Anggota)
$pembina_name = "N/A";
// if ($user_status === 'anggota') {
    $sql_pembina = "
        SELECT u.full_name 
        FROM bimbingan b 
        JOIN users u ON b.pembina_id = u.id 
        WHERE b.anggota_id = ? AND b.is_active = TRUE
    ";
    $stmt_pembina = $conn->prepare($sql_pembina);
    $stmt_pembina->bind_param("i", $user_id);
    $stmt_pembina->execute();
    $result_pembina = $stmt_pembina->get_result();
    if ($row = $result_pembina->fetch_assoc()) {
        $pembina_name = $row['full_name'];
    }
    $stmt_pembina->close();
// }

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Catatan Amalan </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
        }
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -10rem;
            transition: margin .25s ease-out;
            background-color: #343a40; /* Dark background for sidebar */
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
            color: white;
            border-bottom: 1px solid #495057;
        }
        #sidebar-wrapper .list-group-item {
            color: #adb5bd;
            background-color: #343a40;
            border: none;
            padding: 1rem 1.5rem;
        }
        #sidebar-wrapper .list-group-item:hover {
            background-color: #495057;
            color: white;
        }
        #page-content-wrapper {
            min-width: 100vw;
        }
        .sidebar-toggled #sidebar-wrapper {
            margin-left: 0;
        }
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }
            .sidebar-toggled #sidebar-wrapper {
                margin-left: -15rem;
            }
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div id="sidebar-wrapper">
        <div class="sidebar-heading"> Amalan App</div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a>
            
            <?php if ($user_status === 'anggota' || $user_status === 'pembina'): ?>
                <a href="amalan_history.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-line fa-fw"></i> Riwayat</a>
                <a href="catat_amalan.php" class="list-group-item list-group-item-action"><i class="fas fa-pen-to-square fa-fw"></i> Catat Amalan</a>
                <a href="profile_edit.php" class="list-group-item list-group-item-action"><i class="fas fa-user-edit fa-fw"></i> Update Profil</a>
                <a href="pesan_list.php" class="list-group-item list-group-item-action"><i class="fas fa-envelope fa-fw"></i> Pesan</a>
            <?php endif; ?>

            <?php if ($user_status === 'pembina' || $user_status === 'super_admin'): ?>
                <a href="member_management.php" class="list-group-item list-group-item-action"><i class="fas fa-users fa-fw"></i> Manajemen Anggota</a>
                <a href="group_management.php" class="list-group-item list-group-item-action"><i class="fas fa-users-cog me-3"></i>Kelola Grup</a>            
                <a href="report_anggota.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-bar fa-fw"></i> Laporan</a>
                <a href="pesan_send.php" class="list-group-item list-group-item-action"><i class="fas fa-paper-plane fa-fw"></i> Kirim Pesan</a>
            <?php endif; ?>
                <a href="group_members.php" class="list-group-item list-group-item-action"><i class="fas fa-list-check fa-fw"></i> Rekan 1 Grup</a>
            <?php if ($user_status === 'super_admin'): ?>
                <div class="sidebar-heading mt-2">ADMIN AREA</div>
                <a href="user_management.php" class="list-group-item list-group-item-action"><i class="fas fa-user-gear fa-fw"></i> Manajemen User Global</a>
                <a href="audit_log.php" class="list-group-item list-group-item-action"><i class="fas fa-history fa-fw"></i> Audit Log</a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action mt-auto text-danger"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a>
        </div>
    </div>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div class="ms-auto me-3">
                    <span class="navbar-text">
                        Halo, <strong><?php echo htmlspecialchars($full_name); ?></strong> (<?php echo strtoupper($user_status); ?>)
                    </span>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
            <h1 class="mt-4">Dashboard Utama</h1>
            <p class="lead mb-4">
  <a href="http://pmediaku.my.id">Web Developer</a>
</p>

            <div class="row mb-5">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-uppercase small">Status Anda</div>
                                    <h4 class="mb-0"><?php echo strtoupper($user_status); ?></h4>
                                </div>
                                <i class="fas fa-user-tag fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-uppercase small">Pembina Aktif</div>
                                    <h4 class="mb-0"><?php echo htmlspecialchars($pembina_name); ?></h4>
                                </div>
                                <i class="fas fa-handshake fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-uppercase small">Pesan Baru</div>
                                    <h4 class="mb-0">0</h4> 
                                </div>
                                <i class="fas fa-bell fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <?php if ($user_status === 'anggota'): ?>
                        <div class="card">
                            <div class="card-header bg-secondary text-white">Amalan Harian Hari Ini</div>
                            <div class="card-body">
                                <p>Silakan segera catat amalan Anda. <a href="catat_amalan.php">Klik di sini</a> untuk mengisi.</p>
                                </div>
                        </div>
                    <?php elseif ($user_status === 'pembina'): ?>
                        <div class="card">
                            <div class="card-header bg-success text-white">Ringkasan Bimbingan</div>
                            <div class="card-body">
                                <p>Anda saat ini membimbing **[Jumlah Anggota Aktif]** anggota.</p>
                                <p>Cek **Laporan Capaian** untuk memantau progres mereka.</p>
                                </div>
                        </div>
                    <?php elseif ($user_status === 'super_admin'): ?>
                        <div class="card">
                            <div class="card-header bg-danger text-white">Akses Global</div>
                            <div class="card-body">
                                <p>Anda memiliki kontrol penuh atas semua pengguna dan sistem. Gunakan **Manajemen User Global** dengan bijak.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                        <div class="card">
                            <div class="card-header bg-secondary text-white">Developer</div>
                            <div class="card-body">
                                <p>Silakan hubungi kami untuk pengembangan. <a href="http://pmediaku.my.id">Contact</a> .</p>
                                </div>
                        </div>
                </div>
            </div>
            
        </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    var sidebarToggle = document.getElementById('sidebarToggle');
    var wrapper = document.getElementById('wrapper');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            wrapper.classList.toggle('sidebar-toggled');
        });
    }
</script>

</body>
</html>