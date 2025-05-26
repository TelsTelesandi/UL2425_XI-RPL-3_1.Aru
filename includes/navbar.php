<?php
// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$userName = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : '';
$jenisUser = isset($_SESSION['jenis_pengguna']) ? $_SESSION['jenis_pengguna'] : '';

// Dapatkan document root dan current directory
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$currentFile = $_SERVER['SCRIPT_FILENAME'];
$currentDir = dirname($currentFile);

// Hitung path relatif dari current directory ke document root
$relativePath = str_replace($documentRoot, '', $currentDir);
$relativePath = str_replace('\\', '/', $relativePath); // untuk Windows

// Hitung berapa level naik ke root
$levels = substr_count(trim($relativePath, '/'), '/');
$baseUrl = str_repeat('../', $levels);

// Jika di root directory, baseUrl kosong
if ($levels == 0) {
    $baseUrl = './';
}
?>

<!-- Modern CSS Styles -->
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
        --card-hover-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Navbar Modern Styles */
    .navbar-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none;
        backdrop-filter: blur(10px);
        padding: 1rem 0;
        box-shadow: none;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        color: white !important;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .navbar-brand:hover {
        transform: scale(1.05);
        color: white !important;
    }

    .navbar-nav .nav-link {
        color: rgba(255,255,255,0.9) !important;
        font-weight: 500;
        margin: 0 0.25rem;
        border-radius: 25px;
        padding: 0.5rem 1rem !important;
        transition: all 0.3s ease;
    }

    .navbar-nav .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: white !important;
        transform: translateY(-1px);
    }

    .dropdown-menu {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        padding: 0.5rem;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        margin-top: 0.5rem;
    }

    .dropdown-item {
        border-radius: 10px;
        margin: 0.25rem 0;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        color: #495057;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateX(5px);
    }

    .dropdown-item.text-danger:hover {
        background: var(--danger-gradient);
        color: white;
    }

    .dashboard-header{
        position: relative;
        z-index: -1;
    }

    .user-info-bar {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 0.75rem 0;
    }

    .user-badge {
        background: var(--primary-gradient);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .admin-badge {
        background: var(--danger-gradient);
    }

    .btn-outline-light-modern {
        border: 2px solid rgba(255,255,255,0.8);
        color: white;
        background: transparent;
        border-radius: 25px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-outline-light-modern:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255,255,255,0.3);
    }

    .navbar-toggler {
        border: none;
        padding: 0.25rem 0.5rem;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .user-display {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        color: rgba(255,255,255,0.9);
    }

    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #f8f9fa;
    }

    .user-type-small {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .info-text {
        color: #6c757d;
        font-size: 0.875rem;
    }

    .info-text strong {
        color: #495057;
    }

    @media (max-width: 991.98px) {
        .navbar-nav {
            padding-top: 1rem;
        }
        
        .navbar-nav .nav-link {
            margin: 0.25rem 0;
        }
        
        .user-display {
            align-items: flex-start;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
    }
    .navbar-expand-lg .navbar-nav .dropdown-menu{
        z-index: 1000000000;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-modern">
    <div class="container">
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($isLoggedIn): ?>
                    <?php if ($userRole == 'admin'): ?>
                        <!-- Menu untuk Admin -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="ruanganDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-door-open me-1"></i>Ruangan
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/ruangan/index.php">
                                    <i class="fas fa-list me-2"></i>List Ruangan
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/ruangan/create.php">
                                    <i class="fas fa-plus me-2"></i>Tambah Ruangan
                                </a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="peminjamanDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar-check me-1"></i>Peminjaman
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/peminjaman/index.php">
                                    <i class="fas fa-list me-2"></i>List Peminjaman
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/peminjaman/approve.php">
                                    <i class="fas fa-check-circle me-2"></i>Approval
                                </a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>admin/report/index.php">
                                <i class="fas fa-chart-bar me-1"></i>Report
                            </a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>admin/peminjaman/return_approval.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Return Approval
                            </a>
                         </li>
                        
                    <?php elseif ($userRole == 'user'): ?>
                        <!-- Menu untuk User -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userPeminjamanDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar-alt me-1"></i>Peminjaman
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>user/peminjaman/index.php">
                                    <i class="fas fa-list me-2"></i>Riwayat Peminjaman
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>user/peminjaman/create.php">
                                    <i class="fas fa-plus me-2"></i>Ajukan Peminjaman
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- Dashboard untuk semua user yang login -->
                    <li class="nav-item">
                        <?php if ($userRole == 'admin'): ?>
                            <a class="nav-link" href="<?php echo $baseUrl; ?>admin/index.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="<?php echo $baseUrl; ?>user/index.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- User info dan logout untuk user yang sudah login -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>
                            <div class="user-display">
                                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                                <small class="user-type-small">(<?php echo htmlspecialchars($jenisUser); ?>)</small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($userRole == 'user'): ?>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>user/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Login button untuk user yang belum login -->
                    <li class="nav-item">
                        <a class="btn-outline-light-modern" href="<?php echo $baseUrl; ?>auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($isLoggedIn): ?>
<!-- Alert untuk menampilkan informasi user -->
<div class="user-info-bar">
    <div class="container">
        <small class="info-text">
            <i class="fas fa-info-circle me-1"></i>
            Masuk sebagai: <strong><?php echo htmlspecialchars($userName); ?></strong> 
            (<?php echo htmlspecialchars($jenisUser); ?>) - 
            Role: <span class="user-badge <?php echo $userRole == 'admin' ? 'admin-badge' : ''; ?>">
                <?php echo strtoupper($userRole); ?>
            </span>
        </small>
    </div>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.js"></script>