<?php
session_start();
require_once '../config/database.php';
require_once '../auth/check_auth.php';

// Cek apakah user sudah login dan bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

try {
    // Statistik peminjaman user
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_peminjaman = $stmt->fetch()['total'];
    
    // Peminjaman berdasarkan status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as jumlah FROM peminjaman_ruangan WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $stats_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Peminjaman terbaru
    $stmt = $pdo->prepare("SELECT p.*, r.nama_ruangan, r.lokasi 
                          FROM peminjaman_ruangan p 
                          JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
                          WHERE p.user_id = ? 
                          ORDER BY p.created_at DESC 
                          LIMIT 5");
    $stmt->execute([$user_id]);
    $peminjaman_terbaru = $stmt->fetchAll();
    
    // Peminjaman hari ini
    $stmt = $pdo->prepare("SELECT p.*, r.nama_ruangan, r.lokasi 
                          FROM peminjaman_ruangan p 
                          JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
                          WHERE p.user_id = ? AND p.tanggal_pinjam = CURDATE() 
                          ORDER BY p.waktu_mulai ASC");
    $stmt->execute([$user_id]);
    $peminjaman_hari_ini = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php include '../includes/header.php'; ?>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        --card-shadow: 0 8px 25px rgba(0,0,0,0.08);
        --card-hover-shadow: 0 15px 35px rgba(0,0,0,0.12);
        --border-radius: 16px;
        --spacing-xs: 0.5rem;
        --spacing-sm: 1rem;
        --spacing-md: 1.5rem;
        --spacing-lg: 2rem;
        --spacing-xl: 2.5rem;
        --spacing-xxl: 3rem;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }

    .main-content {
        padding: var(--spacing-lg) var(--spacing-md);
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-header {
        background: white;
        border-radius: var(--border-radius);
        padding: var(--spacing-xl) var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
        box-shadow: var(--card-shadow);
        border: none;
    }

    .dashboard-title {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 2.25rem;
        margin: 0 0 var(--spacing-xs) 0;
        line-height: 1.2;
    }

    .dashboard-subtitle {
        color: #6c757d;
        font-size: 1rem;
        margin: 0;
        font-weight: 400;
    }

    .stats-row {
        margin-bottom: var(--spacing-xl);
    }

    .stats-card {
        border: none;
        border-radius: var(--border-radius);
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: var(--spacing-md);
        box-shadow: var(--card-shadow);
        position: relative;
        height: 140px;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-hover-shadow);
    }

    .stats-card-primary {
        background: var(--primary-gradient);
        color: white;
    }

    .stats-card-success {
        background: var(--success-gradient);
        color: white;
    }

    .stats-card-warning {
        background: var(--warning-gradient);
        color: white;
    }

    .stats-card-info {
        background: var(--info-gradient);
        color: white;
    }

    .stats-card-body {
        padding: var(--spacing-lg);
        position: relative;
        z-index: 2;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 var(--spacing-xs) 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        line-height: 1;
    }

    .stats-label {
        font-size: 0.875rem;
        opacity: 0.9;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .stats-icon {
        position: absolute;
        right: var(--spacing-lg);
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.15;
        font-size: 3.5rem;
    }

    .content-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
        margin-bottom: var(--spacing-lg);
    }

    .content-card:hover {
        box-shadow: var(--card-hover-shadow);
    }

    .card-header-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: var(--spacing-lg);
        font-weight: 600;
    }

    .card-header-modern h5 {
        margin: 0;
        font-size: 1.125rem;
    }

    .card-body {
        padding: var(--spacing-lg);
    }

    .btn-modern {
        border-radius: 50px;
        padding: var(--spacing-xs) var(--spacing-md);
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        font-size: 0.875rem;
    }

    .btn-primary-modern {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-success-modern {
        background: var(--success-gradient);
        color: white;
    }

    .btn-success-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
        color: white;
    }

    .btn-warning-modern {
        background: var(--warning-gradient);
        color: white;
    }

    .btn-warning-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
        color: white;
    }

    .btn-info-modern {
        background: var(--info-gradient);
        color: white;
    }

    .btn-info-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
        color: white;
    }

    .badge-modern {
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .badge-success {
        background: var(--success-gradient);
        color: white;
    }

    .badge-warning {
        background: var(--warning-gradient);
        color: white;
    }

    .badge-danger {
        background: var(--danger-gradient);
        color: white;
    }

    .badge-info {
        background: var(--info-gradient);
        color: white;
    }

    .list-group-item-modern {
        border: none;
        border-radius: 12px;
        margin-bottom: var(--spacing-sm);
        transition: all 0.3s ease;
        background: #f8f9fa;
        border-left: 4px solid transparent;
        padding: var(--spacing-md);
    }

    .list-group-item-modern:hover {
        background: #e9ecef;
        border-left-color: #667eea;
        transform: translateX(3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .list-group-item-modern h6 {
        margin: 0 0 var(--spacing-xs) 0;
        font-weight: 600;
        font-size: 1rem;
    }

    .list-group-item-modern p {
        margin: 0;
    }

    .list-group-item-modern small {
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .empty-state {
        text-align: center;
        padding: var(--spacing-xl) var(--spacing-lg);
    }

    .empty-state i {
        color: #6c757d;
        margin-bottom: var(--spacing-md);
    }

    .empty-state p {
        color: #6c757d;
        margin-bottom: var(--spacing-md);
        font-size: 1rem;
    }

    .quick-action-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
        text-decoration: none;
        display: block;
        padding: var(--spacing-xl) var(--spacing-lg);
        text-align: center;
        box-shadow: var(--card-shadow);
        height: 160px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .quick-action-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--card-hover-shadow);
        color: white;
        text-decoration: none;
    }

    .quick-action-card i {
        margin-bottom: var(--spacing-md);
    }

    .quick-action-card h6 {
        margin: 0 0 var(--spacing-xs) 0;
        font-weight: 600;
        font-size: 1.125rem;
    }

    .quick-action-card small {
        opacity: 0.9;
        font-size: 0.875rem;
        margin: 0;
    }

    .notification-badge {
        background: #ff6b6b;
        color: white;
        border-radius: 50%;
        padding: var(--spacing-xs);
        font-size: 0.75rem;
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .content-row {
        margin-bottom: var(--spacing-xl);
    }

    .quick-actions-row {
        margin-bottom: var(--spacing-lg);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in-up {
        animation: fadeInUp 0.6s ease-out;
    }

    .animate-delay-1 { animation-delay: 0.1s; }
    .animate-delay-2 { animation-delay: 0.2s; }
    .animate-delay-3 { animation-delay: 0.3s; }
    .animate-delay-4 { animation-delay: 0.4s; }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .main-content {
            padding: var(--spacing-md);
        }
        
        .dashboard-header {
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .dashboard-title {
            font-size: 1.875rem;
        }
        
        .stats-card {
            margin-bottom: var(--spacing-sm);
            height: 120px;
        }
        
        .stats-card-body {
            padding: var(--spacing-md);
        }
        
        .stats-number {
            font-size: 2rem;
        }
        
        .quick-action-card {
            height: 140px;
            padding: var(--spacing-lg) var(--spacing-md);
        }
        
        .card-body {
            padding: var(--spacing-md);
        }
        
        .list-group-item-modern {
            padding: var(--spacing-sm);
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: var(--spacing-sm);
        }
        
        .dashboard-header {
            padding: var(--spacing-md);
        }
        
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .stats-card {
            height: 100px;
        }
        
        .stats-number {
            font-size: 1.75rem;
        }
        
        .stats-label {
            font-size: 0.75rem;
        }
        
        .quick-action-card {
            height: 120px;
        }
        
        .quick-action-card h6 {
            font-size: 1rem;
        }
    }
</style>

<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger fade-in-up"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Welcome Message -->
        <div class="dashboard-header fade-in-up">
            <h1 class="dashboard-title">Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h1>
            <p class="dashboard-subtitle">Dashboard Peminjaman Ruangan - <?php echo $_SESSION['jenis_pengguna']; ?></p>
        </div>
        
        <!-- Statistik Cards -->
        <div class="row stats-row">
            <div class="col-md-3 col-sm-6 fade-in-up animate-delay-1">
                <div class="stats-card stats-card-info">
                    <div class="stats-card-body text-center">
                        <h2 class="stats-number"><?php echo $total_peminjaman; ?></h2>
                        <p class="stats-label">Total Peminjaman</p>
                        <i class="fas fa-chart-line stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in-up animate-delay-2">
                <div class="stats-card stats-card-warning">
                    <div class="stats-card-body text-center">
                        <h2 class="stats-number"><?php echo isset($stats_status['menunggu']) ? $stats_status['menunggu'] : 0; ?></h2>
                        <p class="stats-label">Menunggu</p>
                        <i class="fas fa-clock stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in-up animate-delay-3">
                <div class="stats-card stats-card-success">
                    <div class="stats-card-body text-center">
                        <h2 class="stats-number"><?php echo isset($stats_status['disetujui']) ? $stats_status['disetujui'] : 0; ?></h2>
                        <p class="stats-label">Disetujui</p>
                        <i class="fas fa-check-circle stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in-up animate-delay-4">
                <div class="stats-card stats-card-primary">
                    <div class="stats-card-body text-center">
                        <h2 class="stats-number"><?php echo isset($stats_status['selesai']) ? $stats_status['selesai'] : 0; ?></h2>
                        <p class="stats-label">Selesai</p>
                        <i class="fas fa-trophy stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row content-row">
            <!-- Peminjaman Hari Ini -->
            <div class="col-lg-6 col-md-12 mb-4 fade-in-up">
                <div class="content-card">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-calendar-day"></i> Peminjaman Hari Ini</h5>
                        <span class="notification-badge"><?php echo count($peminjaman_hari_ini); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($peminjaman_hari_ini)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($peminjaman_hari_ini as $peminjaman): ?>
                                    <div class="list-group-item-modern">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6><?php echo htmlspecialchars($peminjaman['nama_ruangan']); ?></h6>
                                                <p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($peminjaman['lokasi']); ?><br>
                                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($peminjaman['waktu_mulai']); ?> 
                                                        (<?php echo htmlspecialchars($peminjaman['durasi_pinjam']); ?>)
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="ms-3">
                                                <?php
                                                $status_class = '';
                                                switch($peminjaman['status']) {
                                                    case 'menunggu':
                                                        $status_class = 'badge-warning';
                                                        break;
                                                    case 'disetujui':
                                                        $status_class = 'badge-success';
                                                        break;
                                                    case 'ditolak':
                                                        $status_class = 'badge-danger';
                                                        break;
                                                    case 'selesai':
                                                        $status_class = 'badge-info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge-modern <?php echo $status_class; ?>"><?php echo ucfirst($peminjaman['status']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times fa-3x"></i>
                                <p>Tidak ada peminjaman hari ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Peminjaman Terbaru -->
            <div class="col-lg-6 col-md-12 mb-4 fade-in-up">
                <div class="content-card">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-history"></i> Peminjaman Terbaru</h5>
                        <a href="peminjaman/index.php" class="btn btn-sm btn-light btn-modern">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($peminjaman_terbaru)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($peminjaman_terbaru as $peminjaman): ?>
                                    <div class="list-group-item-modern">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6><?php echo htmlspecialchars($peminjaman['nama_ruangan']); ?></h6>
                                                <p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($peminjaman['waktu_mulai']); ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="ms-3">
                                                <?php
                                                $status_class = '';
                                                switch($peminjaman['status']) {
                                                    case 'menunggu':
                                                        $status_class = 'badge-warning';
                                                        break;
                                                    case 'disetujui':
                                                        $status_class = 'badge-success';
                                                        break;
                                                    case 'ditolak':
                                                        $status_class = 'badge-danger';
                                                        break;
                                                    case 'selesai':
                                                        $status_class = 'badge-info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge-modern <?php echo $status_class; ?>"><?php echo ucfirst($peminjaman['status']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list fa-3x"></i>
                                <p>Belum ada peminjaman</p>
                                <a href="peminjaman/create.php" class="btn-primary-modern btn-modern">
                                    <i class="fas "></i> Buat Peminjaman Baru
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row quick-actions-row fade-in-up">
            <div class="col-12">
                <div class="content-card">
                    <div class="card-header-modern">
                        <h5><i class="fas fa-bolt"></i> Menu Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                <a href="peminjaman/create.php" class="quick-action-card" style="background: var(--success-gradient);">
                                    <i class="fas fa-plus-circle fa-2x"></i>
                                    <h6>Peminjaman Baru</h6>
                                    <small>Buat reservasi ruangan</small>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                <a href="peminjaman/index.php" class="quick-action-card" style="background: var(--info-gradient);">
                                    <i class="fas fa-list fa-2x"></i>
                                    <h6>Riwayat Peminjaman</h6>
                                    <small>Lihat semua peminjaman</small>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                <a href="profile.php" class="quick-action-card" style="background: var(--warning-gradient);">
                                    <i class="fas fa-user-edit fa-2x"></i>
                                    <h6>Edit Profile</h6>
                                    <small>Ubah data profil</small>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                <a href="../auth/logout.php" class="quick-action-card" style="background: var(--danger-gradient);" 
                                   onclick="return confirm('Yakin ingin logout?')">
                                    <i class="fas fa-sign-out-alt fa-2x"></i>
                                    <h6>Logout</h6>
                                    <small>Keluar dari sistem</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>