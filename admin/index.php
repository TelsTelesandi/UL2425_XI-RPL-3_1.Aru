<?php
require_once '../config/database.php';
require_once '../auth/check_auth.php';

checkAdmin();

// Mengambil statistik
try {
    // Total ruangan
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ruangan");
    $total_ruangan = $stmt->fetch()['total'];
    
    // Total peminjaman
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman_ruangan");
    $total_peminjaman = $stmt->fetch()['total'];
    
    // Peminjaman menunggu approval
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE status = 'menunggu'");
    $pending_approval = $stmt->fetch()['total'];
     // Peminjaman return approval
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE status = 'return_pending'");
    $return_pending = $stmt->fetch()['total'];
    
    // Total user
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch()['total'];
    
    // Peminjaman hari ini
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE tanggal_pinjam = CURDATE()");
    $peminjaman_hari_ini = $stmt->fetch()['total'];
    
    // Peminjaman terbaru (5 data)
    $stmt = $pdo->query("
        SELECT pr.*, u.nama_lengkap, r.nama_ruangan 
        FROM peminjaman_ruangan pr
        JOIN users u ON pr.user_id = u.user_id
        JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
        ORDER BY pr.created_at DESC
        LIMIT 5
    ");
    $peminjaman_terbaru = $stmt->fetchAll();
    
    // Ruangan paling sering dipinjam
    $stmt = $pdo->query("
        SELECT r.nama_ruangan, COUNT(pr.ruangan_id) as total_pinjam
        FROM ruangan r
        LEFT JOIN peminjaman_ruangan pr ON r.ruangan_id = pr.ruangan_id
        GROUP BY r.ruangan_id, r.nama_ruangan
        ORDER BY total_pinjam DESC
        LIMIT 5
    ");
    $ruangan_populer = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

$page_title = 'Dashboard Admin';
include '../includes/header.php';
include '../includes/navbar.php';
?>

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

    .stats-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        position: relative;
    }

    .stats-card:hover {
        transform: translateY(-10px);
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
        padding: 2rem;
        position: relative;
        z-index: 2;
    }

    .stats-number {
        font-size: 3rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stats-label {
        font-size: 0.9rem;
        opacity: 0.9;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stats-icon {
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.2;
        font-size: 4rem;
    }

    .content-card {
        border: none;
        border-radius: 20px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
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

    .table-modern {
        margin: 0;
    }

    .table-modern thead th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
    }

    .table-modern tbody td {
        padding: 1rem;
        border-top: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .badge-modern {
        padding: 0.5rem 1rem;
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

    .btn-warning-modern {
        background: var(--warning-gradient);
        color: white;
    }

    .btn-warning-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
        color: white;
    }

    .btn-info-modern {
        background: var(--info-gradient);
        color: white;
    }

    .btn-info-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        color: white;
    }

    .popular-room-item {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .popular-room-item:hover {
        background: #e9ecef;
        border-left-color: #667eea;
        transform: translateX(5px);
    }

    .refresh-btn {
        background: white;
        border: 2px solid #667eea;
        color: #667eea;
        border-radius: 50px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .refresh-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    .quick-action-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .notification-badge {
        background: #ff6b6b;
        color: white;
        border-radius: 50%;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        margin-left: 0.5rem;
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
    .animate-delay-4 { animation-delay: 0.4s; }
</style>

<div class="container-fluid">
   
        <!-- Dashboard Header -->
        <div class="dashboard-header fade-in-up">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="dashboard-title">Dashboard Admin</h1>
                    <p class="text-muted mb-0">Selamat datang di panel admin sistem peminjaman ruangan</p>
                </div>
                <button type="button" class="refresh-btn" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card stats-card-primary fade-in-up animate-delay-1">
                    <div class="stats-card-body">
                        <div class="stats-label">Total Ruangan</div>
                        <div class="stats-number"><?= $total_ruangan ?></div>
                        <i class="bi bi-badge-sd stats-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card stats-card-success fade-in-up animate-delay-2">
                    <div class="stats-card-body">
                        <div class="stats-label">Total Peminjaman</div>
                        <div class="stats-number"><?= $total_peminjaman ?></div>
                        <i class="bi bi-calendar-check stats-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card stats-card-warning fade-in-up animate-delay-3">
                    <div class="stats-card-body">
                        <div class="stats-label">Menunggu Approval</div>
                        <div class="stats-number"><?= $pending_approval ?></div>
                        <i class="bi bi-clock stats-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card stats-card-info fade-in-up animate-delay-4">
                    <div class="stats-card-body">
                        <div class="stats-label">Total Users</div>
                        <div class="stats-number"><?= $total_users ?></div>
                        <i class="bi bi-people stats-icon"></i>
                    </div>
                </div>
            </div>

             <div class="col-xl-3 col-md-6">
                <div class="stats-card stats-card-info fade-in-up animate-delay-4">
                    <div class="stats-card-body">
                        <div class="stats-label">Return Approval</div>
                        <div class="stats-number"><?= $return_pending ?></div>
                        <i class="bi bi-clock stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Peminjaman Terbaru -->
            <div class="col-lg-8 mb-4">
                <div class="content-card fade-in-up">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <h6 class="m-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Peminjaman Terbaru
                        </h6>
                        <a href="<?= $base_url ?>/peminjaman/" class="btn btn-sm btn-light btn-modern">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($peminjaman_terbaru)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mt-3">Belum ada data peminjaman</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-person me-2"></i>Peminjam</th>
                                            <th><i class="bi bi-building me-2"></i>Ruangan</th>
                                            <th><i class="bi bi-calendar me-2"></i>Tanggal</th>
                                            <th><i class="bi bi-flag me-2"></i>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($peminjaman_terbaru as $pinjam): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                            <i class="bi bi-person text-white"></i>
                                                        </div>
                                                        <?= htmlspecialchars($pinjam['nama_lengkap']) ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($pinjam['nama_ruangan']) ?></td>
                                                <td><?= formatTanggalIndo($pinjam['tanggal_pinjam']) ?></td>
                                                <td>
                                                    <?php
                                                    $status = $pinjam['status'];
                                                    $badge_class = '';
                                                    switch($status) {
                                                        case 'disetujui':
                                                            $badge_class = 'badge-success';
                                                            break;
                                                        case 'ditolak':
                                                            $badge_class = 'badge-danger';
                                                            break;
                                                        case 'menunggu':
                                                            $badge_class = 'badge-warning';
                                                            break;
                                                        default:
                                                            $badge_class = 'badge-info';
                                                    }
                                                    ?>
                                                    <span class="badge badge-modern <?= $badge_class ?>">
                                                        <?= ucfirst($status) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Ruangan Populer -->
                <div class="content-card mb-4 fade-in-up">
                    <div class="card-header-modern">
                        <h6 class="m-0">
                            <i class="bi bi-star me-2"></i>
                            Ruangan Populer
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ruangan_populer)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bar-chart display-6 text-muted"></i>
                                <p class="text-muted mt-2 mb-0">Belum ada data</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($ruangan_populer as $index => $ruangan): ?>
                                <div class="popular-room-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="rank-badge bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-size: 0.8rem; font-weight: 600;">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="fw-medium"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></span>
                                        </div>
                                        <span class="badge badge-modern badge-info">
                                            <?= $ruangan['total_pinjam'] ?> kali
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card quick-action-card fade-in-up">
                    <div class="card-body p-4">
                        <h6 class="text-white mb-4">
                            <i class="bi bi-lightning me-2"></i>
                            Quick Actions
                        </h6>
                        <div class="d-grid gap-3">
                            <a href="<?= $base_url ?>/ruangan/create.php" class="btn btn-light btn-modern">
                                <i class="bi bi-plus-circle"></i>
                                Tambah Ruangan
                            </a>
                            <a href="<?= $base_url ?>/peminjaman/" class="btn btn-light btn-modern">
                                <i class="bi bi-clock"></i>
                                Approval Peminjaman
                                <?php if ($pending_approval > 0): ?>
                                    <span class="notification-badge"><?= $pending_approval ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?= $base_url ?>/report/" class="btn btn-light btn-modern">
                                <i class="bi bi-graph-up"></i>
                                Lihat Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>