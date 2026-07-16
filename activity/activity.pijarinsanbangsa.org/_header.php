<?php
// Pastikan sesi dan config sudah diinisialisasi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Hak Akses Cek
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_status = $_SESSION['status'];
$full_name = $_SESSION['full_name'];
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
        :root {
            --sidebar-width: 14rem; /* DIUBAH: Sidebar lebih ramping */
        }
        body {
            background-color: #f4f7f6;
            overflow-x: hidden; /* PENTING: Mencegah scroll horizontal */
        }
        #wrapper {
            display: flex;
            width: 100%; 
            transition: all 0.25s ease-out;
        }
        #sidebar-wrapper {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: #212529;
            /* Default: Sembunyi di mobile */
            margin-left: calc(-1 * var(--sidebar-width)); 
            transition: margin .25s ease-out;
            position: fixed; /* Membuat sidebar melayang di mobile */
            z-index: 1030; 
        }
        
        /* Gaya Sidebar lainnya tetap sama */
        #sidebar-wrapper .sidebar-heading {
            padding: 1.5rem 1.25rem;
            font-size: 1.2rem;
            color: white;
            border-bottom: 1px solid #343a40;
        }
        #sidebar-wrapper .list-group-item {
            color: #adb5bd;
            background-color: #212529;
            border: none;
            padding: 0.8rem 1.5rem;
            transition: background-color 0.15s ease-in-out;
        }
        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {
            background-color: #0d6efd;
            color: white;
        }
        
        /* Sidebar Muncul (Mobile) */
        .sidebar-toggled #sidebar-wrapper {
            margin-left: 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }

        #page-content-wrapper {
            width: 100%; /* Konten utama selalu 100% di mobile */
            transition: margin .25s ease-out;
        }

        /* Tampilan Desktop (min-width: 768px) */
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0; /* Sidebar selalu terlihat di desktop */
                position: relative; /* Kembali ke posisi normal */
            }
            
            /* Konten utama menyesuaikan lebar sidebar yang terlihat */
            #page-content-wrapper {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }

            /* Saat di-toggle di desktop, sembunyikan sidebar dan lebarkan konten */
            .sidebar-toggled #sidebar-wrapper {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            .sidebar-toggled #page-content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
        
        /* Opsi tambahan untuk menutupi konten saat sidebar muncul di mobile */
        /* Anda bisa menambahkan overlay di _header.php dan meng-toggle-nya di JS */

    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="fas fa-sun text-warning me-2"></i> Amalan Apps
        </div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a>
            
            <?php if ($user_status === 'anggota' || $user_status === 'pembina'): ?>
                <div class="list-group-item text-secondary small pt-3 pb-1">AMALAN & PROFIL</div>
                <a href="amalan_history.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-line fa-fw me-2"></i> Riwayat & Grafik</a>
                <a href="catat_amalan.php" class="list-group-item list-group-item-action"><i class="fas fa-edit me-3"></i>Catat Amalan Harian</a>
                <a href="profile_edit.php" class="list-group-item list-group-item-action"><i class="fas fa-user-edit fa-fw me-2"></i> Update Profil & CV</a>
                <a href="pesan_list.php" class="list-group-item list-group-item-action"><i class="fas fa-envelope fa-fw me-2"></i> Pesan Masuk</a>
            <?php endif; ?>

            <?php if ($user_status === 'pembina' || $user_status === 'super_admin'): ?>
                <!--<div class="list-group-item text-secondary small pt-3 pb-1">PEMBINA AREA</div>-->
                <a href="member_management.php" class="list-group-item list-group-item-action"><i class="fas fa-users fa-fw me-2"></i> Manajemen Anggota</a>
                <a href="report_anggota.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-bar fa-fw me-2"></i> Laporan Capaian</a>
                <a href="pesan_send.php" class="list-group-item list-group-item-action"><i class="fas fa-paper-plane fa-fw me-2"></i> Kirim Pesan</a>
                <a href="group_management.php" class="list-group-item list-group-item-action"><i class="fas fa-users-cog me-3"></i>Kelola Grup </a>     
            <?php endif; ?>
                <a href="group_members.php" class="list-group-item list-group-item-action"><i class="fas fa-list-check fa-fw"></i> Rekan 1 Grup</a>
            <?php if ($user_status === 'super_admin'): ?>
                <div class="list-group-item text-secondary small pt-3 pb-1">ADMIN AREA</div>
                <a href="user_management.php" class="list-group-item list-group-item-action"><i class="fas fa-user-gear fa-fw me-2"></i> Manajemen User Global</a>
                <a href="audit_log.php" class="list-group-item list-group-item-action"><i class="fas fa-history fa-fw me-2"></i> Audit Log</a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action text-danger mt-3"><i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout</a>
        </div>
    </div>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
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
            