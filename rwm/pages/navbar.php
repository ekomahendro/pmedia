<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">RW BUKIT SANGGULAN</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="warga_list.php">Data Warga</a>
                </li>
                <?php if ($_SESSION['level'] == 'Super Admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="user_master.php">Master User</a>
                </li>
                <?php endif; ?>
                    <?php if($_SESSION['level'] != 'Kablok'): ?>
                        <li class="nav-item"><a class="nav-link" href="pengurus.php">Pengurus RW</a></li>
                    <?php endif; ?>
            </ul>
            <div class="d-flex border-top border-secondary pt-2 pt-lg-0">
                <span class="navbar-text me-3 d-none d-lg-inline">
                     <strong><?= $_SESSION['username'] ?></strong>
                </span>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm w-100">Logout</a>
            </div>
        </div>
    </div>
</nav>