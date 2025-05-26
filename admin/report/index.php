<?php
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$error = '';

// Filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : '';
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$ruangan_filter = isset($_GET['ruangan']) ? $_GET['ruangan'] : '';

// Query untuk statistik
try {
    // Total peminjaman
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman_ruangan");
    $total_peminjaman = $stmt->fetch()['total'];
    
    // Peminjaman berdasarkan status
    $stmt = $pdo->query("SELECT status, COUNT(*) as jumlah FROM peminjaman_ruangan GROUP BY status");
    $stats_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Ruangan terpopuler
    $stmt = $pdo->query("SELECT r.nama_ruangan, COUNT(*) as jumlah 
                        FROM peminjaman_ruangan p 
                        JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
                        GROUP BY p.ruangan_id 
                        ORDER BY jumlah DESC 
                        LIMIT 5");
    $ruangan_populer = $stmt->fetchAll();
    
    // Ambil daftar ruangan untuk filter
    $stmt = $pdo->query("SELECT ruangan_id, nama_ruangan FROM ruangan ORDER BY nama_ruangan");
    $ruangan_list = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Query untuk report detail
$sql = "SELECT p.*, u.nama_lengkap, u.jenis_pengguna, r.nama_ruangan, r.lokasi 
        FROM peminjaman_ruangan p 
        JOIN users u ON p.user_id = u.user_id 
        JOIN ruangan r ON p.ruangan_id = r.ruangan_id";

$params = [];
$where_conditions = [];

if (!empty($tanggal_dari)) {
    $where_conditions[] = "p.tanggal_pinjam >= ?";
    $params[] = $tanggal_dari;
}

if (!empty($tanggal_sampai)) {
    $where_conditions[] = "p.tanggal_pinjam <= ?";
    $params[] = $tanggal_sampai;
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($ruangan_filter)) {
    $where_conditions[] = "p.ruangan_id = ?";
    $params[] = $ruangan_filter;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY p.tanggal_pinjam DESC, p.waktu_mulai ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report_data = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php include '../../includes/header.php'; ?>

<head>
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
        margin: 0;
        padding: 0;
    }

    .main-content {
        padding: 1rem;
        margin: 0;
    }

    @media (min-width: 768px) {
        .main-content {
            padding: 1.5rem;
        }
    }

    @media (min-width: 1200px) {
        .main-content {
            padding: 2rem;
        }
    }

    .dashboard-header {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        border: none;
    }

    @media (min-width: 768px) {
        .dashboard-header {
            padding: 2rem;
            margin-bottom: 2rem;
        }
    }

    .dashboard-title {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 1.8rem;
        margin: 0;
        line-height: 1.2;
    }

    @media (min-width: 768px) {
        .dashboard-title {
            font-size: 2.2rem;
        }
    }

    @media (min-width: 1200px) {
        .dashboard-title {
            font-size: 2.5rem;
        }
    }

    .stats-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
        color: white;
        height: auto;
        min-height: 120px;
    }

    @media (min-width: 768px) {
        .stats-card {
            border-radius: 20px;
            margin-bottom: 1.5rem;
            height: 140px;
            min-height: auto;
        }
    }

    .stats-card .card-body {
        padding: 1rem;
    }

    @media (min-width: 768px) {
        .stats-card .card-body {
            padding: 1.5rem;
        }
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-hover-shadow);
    }

    .stats-card-primary {
        background: var(--primary-gradient);
    }

    .stats-card-warning {
        background: var(--warning-gradient);
    }

    .stats-card-success {
        background: var(--success-gradient);
    }

    .stats-card-info {
        background: var(--info-gradient);
    }

    .content-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }

    @media (min-width: 768px) {
        .content-card {
            border-radius: 20px;
            margin-bottom: 1.5rem;
        }
    }

    @media (min-width: 1200px) {
        .content-card {
            margin-bottom: 2rem;
        }
    }

    .content-card .card-body {
        padding: 1rem;
    }

    @media (min-width: 768px) {
        .content-card .card-body {
            padding: 1.5rem;
        }
    }

    .content-card:hover {
        box-shadow: var(--card-hover-shadow);
    }

    .card-header-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }

    @media (min-width: 768px) {
        .card-header-modern {
            padding: 1.5rem 2rem;
        }
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
        border-radius: 25px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    @media (min-width: 768px) {
        .btn-modern {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            margin-bottom: 0;
        }
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

    .filter-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    @media (min-width: 768px) {
        .filter-section {
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e0e6ed;
        padding: 0.5rem 0.75rem;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    @media (min-width: 768px) {
        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-top: 1rem;
    }

    @media (min-width: 768px) {
        .table-responsive {
            border-radius: 15px;
        }
    }

    .table thead th {
        background: var(--primary-gradient);
        color: white;
        border: none;
        font-weight: 600;
        padding: 0.75rem;
        font-size: 0.85rem;
    }

    @media (min-width: 768px) {
        .table thead th {
            padding: 1rem;
            font-size: 0.9rem;
        }
    }

    .table tbody td {
        padding: 0.75rem;
        border-color: #f8f9fa;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    @media (min-width: 768px) {
        .table tbody td {
            padding: 1rem;
            font-size: 0.9rem;
        }
    }

    .table {
        margin: 0;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }

    @media (min-width: 992px) {
        .table tbody tr:hover {
            transform: scale(1.01);
        }
    }

    .popular-room-item {
        background: white;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    @media (min-width: 768px) {
        .popular-room-item {
            border-radius: 10px;
            padding: 1rem;
        }
    }

    .popular-room-item:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }

    .popular-room-badge {
        background: var(--primary-gradient);
        color: white;
        border-radius: 50px;
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
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
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header fade-in-up">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="dashboard-title">📊 Report & Analytics</h1>
                <small class="text-muted">Dashboard Administrator</small>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-md-3 fade-in-up animate-delay-1">
                <div class="stats-card stats-card-primary">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Total Peminjaman</h5>
                            <h2 class="mb-0"><?php echo $total_peminjaman; ?></h2>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.3;">📋</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 fade-in-up animate-delay-2">
                <div class="stats-card stats-card-warning">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Menunggu</h5>
                            <h2 class="mb-0"><?php echo isset($stats_status['menunggu']) ? $stats_status['menunggu'] : 0; ?></h2>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.3;">⏳</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 fade-in-up animate-delay-3">
                <div class="stats-card stats-card-success">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Disetujui</h5>
                            <h2 class="mb-0"><?php echo isset($stats_status['disetujui']) ? $stats_status['disetujui'] : 0; ?></h2>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.3;">✅</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 fade-in-up animate-delay-4">
                <div class="stats-card stats-card-info">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Selesai</h5>
                            <h2 class="mb-0"><?php echo isset($stats_status['selesai']) ? $stats_status['selesai'] : 0; ?></h2>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.3;">🎯</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Ruangan Terpopuler -->
            <div class="col-md-4 mb-4 fade-in-up">
                <div class="content-card">
                    <div class="card-header-modern">
                        <h5 class="mb-0">🏆 Ruangan Terpopuler</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($ruangan_populer)): ?>
                            <?php foreach ($ruangan_populer as $index => $ruangan): ?>
                                <div class="popular-room-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="font-size: 1.5rem;">
                                            <?php 
                                            $medals = ['🥇', '🥈', '🥉', '🏅', '🏅'];
                                            echo $medals[$index] ?? '🏅';
                                            ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($ruangan['nama_ruangan']); ?></div>
                                        </div>
                                    </div>
                                    <span class="popular-room-badge"><?php echo $ruangan['jumlah']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <div style="font-size: 3rem; opacity: 0.3;">📊</div>
                                <p class="mt-2">Belum ada data</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Report Detail -->
            <div class="col-md-8 fade-in-up">
                <div class="content-card">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📈 Report Peminjaman Detail</h5>
                        <?php if (!empty($report_data)): ?>
                            <button onclick="window.print()" class="btn btn-light btn-sm" style="border-radius: 50px;">
                                🖨️ Print Report
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert-modern"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <!-- Filter Form -->
                        <div class="filter-section">
                            <form method="GET">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">📅 Tanggal Dari</label>
                                        <input type="date" name="tanggal_dari" class="form-control" value="<?php echo $tanggal_dari; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">📅 Tanggal Sampai</label>
                                        <input type="date" name="tanggal_sampai" class="form-control" value="<?php echo $tanggal_sampai; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">🔍 Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">Semua Status</option>
                                            <option value="menunggu" <?php echo $status_filter == 'menunggu' ? 'selected' : ''; ?>>⏳ Menunggu</option>
                                            <option value="disetujui" <?php echo $status_filter == 'disetujui' ? 'selected' : ''; ?>>✅ Disetujui</option>
                                            <option value="ditolak" <?php echo $status_filter == 'ditolak' ? 'selected' : ''; ?>>❌ Ditolak</option>
                                            <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>🎯 Selesai</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">🏢 Ruangan</label>
                                        <select name="ruangan" class="form-select">
                                            <option value="">Semua Ruangan</option>
                                            <?php foreach ($ruangan_list as $ruangan): ?>
                                                <option value="<?php echo $ruangan['ruangan_id']; ?>" 
                                                        <?php echo $ruangan_filter == $ruangan['ruangan_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ruangan['nama_ruangan']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-modern">
                                            🔍 Filter Data
                                        </button>
                                        <a href="index.php" class="btn btn-secondary-modern">
                                            🔄 Reset Filter
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Report Table -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>📅 Tanggal</th>
                                        <th>👤 Peminjam</th>
                                        <th>🏢 Ruangan</th>
                                        <th>⏰ Waktu</th>
                                        <th>📊 Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($report_data)): ?>
                                        <?php foreach ($report_data as $index => $data): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($data['tanggal_pinjam'])); ?></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($data['nama_lengkap']); ?></div>
                                                    <small class="text-muted"><?php echo $data['jenis_pengguna']; ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($data['nama_ruangan']); ?></td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($data['waktu_mulai']); ?></div>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($data['durasi_pinjam']); ?>)</small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_icon = '';
                                                    switch($data['status']) {
                                                        case 'menunggu':
                                                            $status_class = 'badge-warning';
                                                            $status_icon = '⏳';
                                                            break;
                                                        case 'disetujui':
                                                            $status_class = 'badge-success';
                                                            $status_icon = '✅';
                                                            break;
                                                        case 'ditolak':
                                                            $status_class = 'badge-danger';
                                                            $status_icon = '❌';
                                                            break;
                                                        case 'selesai':
                                                            $status_class = 'badge-info';
                                                            $status_icon = '🎯';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge-modern <?php echo $status_class; ?>">
                                                        <?php echo $status_icon; ?> <?php echo ucfirst($data['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div style="font-size: 3rem; opacity: 0.3;">📊</div>
                                                <p class="mt-2 text-muted">Tidak ada data untuk ditampilkan</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (!empty($report_data)): ?>
                            <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>📊 Total data: <?php echo count($report_data); ?> peminjaman</strong>
                                        <?php if (!empty($tanggal_dari) || !empty($tanggal_sampai)): ?>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    📅 Periode: 
                                                    <?php echo !empty($tanggal_dari) ? date('d/m/Y', strtotime($tanggal_dari)) : '...'; ?>
                                                    s/d 
                                                    <?php echo !empty($tanggal_sampai) ? date('d/m/Y', strtotime($tanggal_sampai)) : '...'; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>