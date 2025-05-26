<?php
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$peminjaman_id = (int)$_GET['id'];

try {
    // Ambil detail peminjaman
    $stmt = $pdo->prepare("SELECT p.*, u.nama_lengkap, u.jenis_pengguna, u.id_card, r.nama_ruangan, r.lokasi, r.kapasitas 
                          FROM peminjaman_ruangan p 
                          JOIN users u ON p.user_id = u.user_id 
                          JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
                          WHERE p.peminjaman_id = ?");
    $stmt->execute([$peminjaman_id]);
    $peminjaman = $stmt->fetch();
    
    if (!$peminjaman) {
        header('Location: index.php');
        exit();
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php include '../../includes/header.php'; ?>

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

    .main-content {
        padding: 2rem;
    }

    .dashboard-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        border: none;
    }

    .dashboard-title {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 2.5rem;
        margin: 0;
    }

    .content-card {
        border: none;
        border-radius: 20px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
        margin-bottom: 2rem;
    }

    .content-card:hover {
        box-shadow: var(--card-hover-shadow);
    }

    .card-header-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1.5rem 2rem;
        font-weight: 600;
    }

    .info-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .info-table {
        margin: 0;
    }

    .info-table td {
        padding: 0.75rem 0;
        border: none;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: #495057;
        width: 40%;
    }

    .info-table td:last-child {
        color: #6c757d;
    }

    .section-title {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-modern {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
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

    .btn-modern {
        border-radius: 50px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        margin-right: 0.5rem;
    }

    .btn-primary-modern {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-success-modern {
        background: var(--success-gradient);
        color: white;
    }

    .btn-success-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        color: white;
    }

    .btn-danger-modern {
        background: var(--danger-gradient);
        color: white;
    }

    .btn-danger-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(250, 112, 154, 0.4);
        color: white;
    }

    .btn-secondary-modern {
        background: var(--dark-gradient);
        color: white;
    }

    .btn-secondary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
        color: white;
    }

    .alert-modern {
        border: none;
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        color: white;
        background: var(--danger-gradient);
    }

    .keterangan-card {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid #fa709a;
    }

    .action-buttons {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
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
</style>

<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <!-- Header -->
                    <div class="dashboard-header fade-in-up">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="dashboard-title">
                                <i class="fas fa-file-alt me-3"></i>Detail Peminjaman
                            </h1>
                           
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <?php if (isset($error)): ?>
                        <div class="alert-modern fade-in-up animate-delay-1">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Main Content Card -->
                    <div class="content-card fade-in-up animate-delay-2">
                        <div class="card-body p-4">
                            <div class="row">
                                <!-- Informasi Peminjam -->
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h5 class="section-title">
                                            <i class="fas fa-user"></i> Informasi Peminjam
                                        </h5>
                                        <table class="info-table table">
                                            <tr>
                                                <td><strong>Nama Lengkap</strong></td>
                                                <td>: <?php echo htmlspecialchars($peminjaman['nama_lengkap']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Jenis Pengguna</strong></td>
                                                <td>: <span class="badge badge-info"><?php echo $peminjaman['jenis_pengguna']; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID Card</strong></td>
                                                <td>: <?php echo htmlspecialchars($peminjaman['id_card']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Informasi Ruangan -->
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h5 class="section-title">
                                            <i class="fas fa-building"></i> Informasi Ruangan
                                        </h5>
                                        <table class="info-table table">
                                            <tr>
                                                <td><strong>Nama Ruangan</strong></td>
                                                <td>: <?php echo htmlspecialchars($peminjaman['nama_ruangan']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Lokasi</strong></td>
                                                <td>: <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($peminjaman['lokasi']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Kapasitas</strong></td>
                                                <td>: <i class="fas fa-users me-1"></i><?php echo $peminjaman['kapasitas']; ?> orang</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detail Peminjaman -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h5 class="section-title">
                                            <i class="fas fa-calendar-alt"></i> Waktu Peminjaman
                                        </h5>
                                        <table class="info-table table">
                                            <tr>
                                                <td><strong>Tanggal Pinjam</strong></td>
                                                <td>: <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Waktu Mulai</strong></td>
                                                <td>: <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($peminjaman['waktu_mulai']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Durasi Pinjam</strong></td>
                                                <td>: <i class="fas fa-hourglass-half me-1"></i><?php echo htmlspecialchars($peminjaman['durasi_pinjam']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Waktu Selesai</strong></td>
                                                <td>: <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($peminjaman['waktu_selesai']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h5 class="section-title">
                                            <i class="fas fa-info-circle"></i> Status & Timeline
                                        </h5>
                                        <table class="info-table table">
                                            <tr>
                                                <td><strong>Status</strong></td>
                                                <td>: 
                                                    <?php
                                                    $status_class = '';
                                                    $status_icon = '';
                                                    switch($peminjaman['status']) {
                                                        case 'menunggu':
                                                            $status_class = 'badge-warning';
                                                            $status_icon = 'fas fa-clock';
                                                            break;
                                                        case 'disetujui':
                                                            $status_class = 'badge-success';
                                                            $status_icon = 'fas fa-check';
                                                            break;
                                                        case 'ditolak':
                                                            $status_class = 'badge-danger';
                                                            $status_icon = 'fas fa-times';
                                                            break;
                                                        case 'selesai':
                                                            $status_class = 'badge-info';
                                                            $status_icon = 'fas fa-check-double';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge-modern <?php echo $status_class; ?>">
                                                        <i class="<?php echo $status_icon; ?>"></i>
                                                        <?php echo ucfirst($peminjaman['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tanggal Dibuat</strong></td>
                                                <td>: <i class="fas fa-calendar-plus me-1"></i><?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Terakhir Update</strong></td>
                                                <td>: <i class="fas fa-sync-alt me-1"></i><?php echo date('d/m/Y H:i', strtotime($peminjaman['updated_at'])); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            <?php if (!empty($peminjaman['keterangan'])): ?>
                                <div class="keterangan-card">
                                    <h5 class="section-title">
                                        <i class="fas fa-comment-alt"></i> Keterangan
                                    </h5>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($peminjaman['keterangan'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <?php if ($peminjaman['status'] == 'menunggu'): ?>
                                <div class="action-buttons">
                                    <h5 class="section-title mb-3">
                                        <i class="fas fa-cogs"></i> Aksi
                                    </h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="approve.php?id=<?php echo $peminjaman['peminjaman_id']; ?>&action=approve" 
                                           class="btn btn-success-modern" 
                                           onclick="return confirm('Setujui peminjaman ini?')">
                                           <i class="fas fa-check"></i> Setujui Peminjaman
                                        </a>
                                        <a href="approve.php?id=<?php echo $peminjaman['peminjaman_id']; ?>&action=reject" 
                                           class="btn btn-danger-modern" 
                                           onclick="return confirm('Tolak peminjaman ini?')">
                                           <i class="fas fa-times"></i> Tolak Peminjaman
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>