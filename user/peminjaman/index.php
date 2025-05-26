<?php
// user/peminjaman/index.php - List Peminjaman User
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user sudah login dan bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// Ambil data peminjaman user
$stmt = $pdo->prepare("
    SELECT p.*, r.nama_ruangan, r.lokasi 
    FROM peminjaman_ruangan p 
    JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$peminjaman = $stmt->fetchAll();

// Hitung statistik
$total_peminjaman = count($peminjaman);
$menunggu = count(array_filter($peminjaman, fn($p) => $p['status'] == 'menunggu'));
$disetujui = count(array_filter($peminjaman, fn($p) => $p['status'] == 'disetujui'));
$selesai = count(array_filter($peminjaman, fn($p) => $p['status'] == 'selesai'));
$pending_approval = count(array_filter($peminjaman, fn($p) => $p['status'] == 'return_pending'));

$title = "Riwayat Peminjaman Saya";
include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<!-- Include the modern styles -->
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
        display: flex;
        align-items: center;
        justify-content: space-between;
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

    .badge-secondary {
        background: var(--dark-gradient);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: var(--spacing-xl) var(--spacing-lg);
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
    }

    .empty-state i {
        color: #6c757d;
        margin-bottom: var(--spacing-md);
        font-size: 4rem;
    }

    .empty-state p {
        color: #6c757d;
        margin-bottom: var(--spacing-md);
        font-size: 1rem;
    }

    .table-modern {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    .table-modern th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        padding: var(--spacing-md);
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .table-modern td {
        border: none;
        padding: var(--spacing-md);
        vertical-align: middle;
        border-bottom: 1px solid #f8f9fa;
    }

    .table-modern tbody tr:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }

    .room-info h6 {
        margin: 0 0 0.25rem 0;
        font-weight: 600;
        color: #495057;
    }

    .room-info small {
        color: #6c757d;
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
        
        .card-body {
            padding: var(--spacing-md);
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

        .table-responsive {
            font-size: 0.875rem;
        }
    }
</style>

<div class="main-content">
    <!-- Dashboard Header -->
    <div class="dashboard-header fade-in-up">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="dashboard-title">Riwayat Peminjaman</h1>
                <p class="dashboard-subtitle">Kelola dan pantau status peminjaman ruangan Anda</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row stats-row">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-primary fade-in-up animate-delay-1">
                <div class="stats-card-body">
                    <div class="stats-number"><?= $total_peminjaman ?></div>
                    <div class="stats-label">Total Peminjaman</div>
                </div>
                <i class="stats-icon fas fa-calendar-alt"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-warning fade-in-up animate-delay-2">
                <div class="stats-card-body">
                    <div class="stats-number"><?= $menunggu ?></div>
                    <div class="stats-label">Menunggu Persetujuan</div>
                </div>
                <i class="stats-icon fas fa-clock"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-success fade-in-up animate-delay-3">
                <div class="stats-card-body">
                    <div class="stats-number"><?= $disetujui ?></div>
                    <div class="stats-label">Disetujui</div>
                </div>
                <i class="stats-icon fas fa-check-circle"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-info fade-in-up animate-delay-4">
                <div class="stats-card-body">
                    <div class="stats-number"><?= $selesai ?></div>
                    <div class="stats-label">Selesai</div>
                </div>
                <i class="stats-icon fas fa-clipboard-check"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-info fade-in-up animate-delay-4">
                <div class="stats-card-body">
                    <div class="stats-number"><?= $pending_approval ?></div>
                    <div class="stats-label">Pending Approval</div>
                </div>
                <i class="stats-icon fas fa-clock"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <?php if (empty($peminjaman)): ?>
        <div class="empty-state fade-in-up">
            <i class="fas fa-calendar-times"></i>
            <h4>Belum Ada Peminjaman</h4>
            <p>Anda belum memiliki riwayat peminjaman ruangan.</p>
            <a href="create.php" class="btn btn-primary-modern btn-modern">
                <i class="fas"></i> Ajukan Peminjaman Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="content-card fade-in-up">
            <div class="card-header-modern">
                <h5>
                    <span><i class="fas fa-list"></i> Daftar Peminjaman</span>
                    <span class="badge-modern badge-info"><?= $total_peminjaman ?> Total</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Ruangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peminjaman as $index => $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; color: white; font-weight: 600; font-size: 0.875rem;">
                                            <?= $index + 1 ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="room-info">
                                            <h6><?= htmlspecialchars($item['nama_ruangan']) ?></h6>
                                            <small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['lokasi']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($item['tanggal_pinjam'])) ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars($item['waktu_mulai']) ?></span><br>
                                        <small class="text-muted">s/d <?= htmlspecialchars($item['waktu_selesai']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-modern badge-info"><?= htmlspecialchars($item['durasi_pinjam']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusIcon = '';
                                        switch ($item['status']) {
                                            case 'menunggu':
                                                $statusClass = 'badge-modern badge-warning';
                                                $statusIcon = 'fas fa-clock';
                                                break;
                                            case 'disetujui':
                                                $statusClass = 'badge-modern badge-success';
                                                $statusIcon = 'fas fa-check-circle';
                                                break;
                                            case 'ditolak':
                                                $statusClass = 'badge-modern badge-danger';
                                                $statusIcon = 'fas fa-times-circle';
                                                break;
                                            case 'selesai':
                                                $statusClass = 'badge-modern badge-secondary';
                                                $statusIcon = 'fas fa-clipboard-check';
                                                break;
                                            default:
                                                $statusClass = 'badge-modern badge-info';
                                                $statusIcon = 'fas fa-question-circle';
                                        }
                                        ?>
                                        <span class="<?= $statusClass ?>">
                                            <i class="<?= $statusIcon ?>"></i> <?= ucfirst($item['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="detail.php?id=<?= $item['peminjaman_id'] ?>" class="btn btn-info-modern btn-modern btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($item['status'] == 'disetujui'): ?>
                                                <a href="return.php?id=<?= $item['peminjaman_id'] ?>" class="btn btn-success-modern btn-modern btn-sm">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Add fade-in animation to elements as they come into view
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all fade-in elements
    document.querySelectorAll('.fade-in-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });
});

// Add hover effects to table rows
document.querySelectorAll('.table-modern tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(3px)';
    });
    
    row.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>