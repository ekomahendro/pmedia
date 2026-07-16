<?php
// Mengambil nama file yang sedang aktif untuk class 'active' di Bootstrap
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staff';
$user_name = isset($_SESSION['admin']) ? $_SESSION['admin'] : 'Guest';
?>

<!-- 1. TOP BAR KHUSUS MOBILE (Hanya muncul di layar < 768px) -->
<div class="col-12 d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center sticky-top shadow">
    <h5 class="m-0 fw-bold text-primary">Maluku Paradise</h5>
    <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
        <i class="bi bi-list fs-4"></i>
    </button>
</div>

<!-- 2. SIDEBAR DESKTOP (Hanya muncul di layar >= 768px) -->
<div class="col-md-3 col-lg-2 p-3 sidebar d-none d-md-block">
    <!-- Info Akun -->
    <div class="d-flex align-items-center p-2 mb-3 bg-dark bg-opacity-25 rounded border border-secondary border-opacity-25">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
            <i class="bi bi-person-fill fs-5"></i>
        </div>
        <div class="ms-3 overflow-hidden">
            <div class="fw-bold text-white text-truncate" title="<?= htmlspecialchars($user_name) ?>"><?= htmlspecialchars($user_name) ?></div>
            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="bi bi-shield-check text-success me-1"></i><?= $user_role ?></small>
        </div>
    </div>
    <hr class="border-secondary mt-2">
    <!-- Menu Navigasi Desktop -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="admin_bookings.php" class="nav-link <?= $current_page == 'admin_bookings.php' ? 'active' : '' ?>">
                <i class="bi bi-wallet2 me-2"></i> Pesanan Masuk
            </a>
        </li>
        <?php if ($user_role == 'Super Admin'): ?>
            <li class="nav-item">
                <a href="admin.php" class="nav-link <?= $current_page == 'admin.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill me-2"></i> Paket Wisata
                </a>
            </li>
            <li class="nav-item">
                <a href="manageuser.php" class="nav-link <?= $current_page == 'manageuser.php' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill me-2"></i> Manajemen User
                </a>
            </li>
        <?php endif; ?>
        <?php if (in_array($user_role, ['Super Admin', 'Demo'])): ?>
            <li class="nav-item">
                <a href="admin.php" class="nav-link <?= $current_page == 'admin.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill me-2"></i> Paket Wisata
                </a>
            </li>
        <?php endif; ?>
        <li class="nav-item mt-5">
            <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
        </li>
    </ul>
</div>

<!-- 3. OFFCANVAS SIDEBAR MOBILE (Hanya aktif saat tombol hamburger di klik di mobile) -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileSidebar" style="width: 280px;">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title fw-bold text-primary" id="mobileSidebarLabel">Maluku Paradise</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-replace="offcanvas" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body sidebar p-3">
        <!-- Info Akun Mobile -->
        <div class="d-flex align-items-center p-2 mb-4 bg-secondary bg-opacity-10 rounded border border-secondary border-opacity-25">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-person-fill fs-5"></i>
            </div>
            <div class="ms-3">
                <div class="fw-bold text-white"><?= htmlspecialchars($user_name) ?></div>
                <small class="text-muted"><i class="bi bi-shield-check text-success me-1"></i><?= $user_role ?></small>
            </div>
        </div>
        
        <!-- Menu Navigasi Mobile -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="admin_bookings.php" class="nav-link <?= $current_page == 'admin_bookings.php' ? 'active' : '' ?>">
                    <i class="bi bi-wallet2 me-2"></i> Pesanan Masuk
                </a>
            </li>
            <?php if ($user_role == 'Super Admin'): ?>
                <li class="nav-item">
                    <a href="admin.php" class="nav-link <?= $current_page == 'admin.php' ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2-fill me-2"></i> Paket Wisata
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manageuser.php" class="nav-link <?= $current_page == 'manageuser.php' ? 'active' : '' ?>">
                        <i class="bi bi-people-fill me-2"></i> Manajemen User
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item mt-5">
                <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
            </li>
        </ul>
    </div>
</div>