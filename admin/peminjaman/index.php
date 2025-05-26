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
$success = '';

// Ambil pesan dari session
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Query peminjaman dengan join
$sql = "SELECT p.*, u.nama_lengkap, u.jenis_pengguna, r.nama_ruangan, r.lokasi 
        FROM peminjaman_ruangan p 
        JOIN users u ON p.user_id = u.user_id 
        JOIN ruangan r ON p.ruangan_id = r.ruangan_id";

$params = [];
$where_conditions = [];

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($tanggal_filter)) {
    $where_conditions[] = "p.tanggal_pinjam = ?";
    $params[] = $tanggal_filter;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $peminjaman_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

$page_title = 'Daftar Peminjaman Ruangan';
include '../../includes/header.php';
include '../../includes/navbar.php';
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

    .filter-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
    }

    .form-control-modern {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        background: white;
    }

    .form-select-modern {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .form-select-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        background: white;
    }

    .table-modern {
        margin: 0;
    }

    .table-modern thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        border-bottom: 2px solid #dee2e6;
    }

    .table-modern tbody td {
        padding: 1rem;
        border-top: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        transform: scale(1.005);
        transition: all 0.2s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .badge-modern {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.75rem;
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
        padding: 0.5rem 1rem;
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
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

    .btn-info-modern {
        background: var(--info-gradient);
        color: white;
    }

    .btn-info-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
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
    }

    .alert-success-modern {
        background: var(--success-gradient);
    }

    .alert-danger-modern {
        background: var(--danger-gradient);
    }

    .user-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .user-name {
        font-weight: 600;
        color: #495057;
    }

    .user-type {
        font-size: 0.75rem;
        color: #6c757d;
        background: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 15px;
        display: inline-block;
        width: fit-content;
    }

    .room-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .room-name {
        font-weight: 600;
        color: #495057;
    }

    .room-location {
        font-size: 0.75rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        margin: 2rem 0;
    }

    .empty-state-icon {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 4rem;
        margin-bottom: 1rem;
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

<div class="container-fluid main-content">
    <!-- Header Section -->
    <div class="dashboard-header fade-in-up">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="dashboard-title">Daftar Peminjaman Ruangan</h1>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check" style="font-size: 2rem; color: #667eea;"></i>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="alert alert-modern alert-danger-modern fade-in-up animate-delay-1" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close btn-close-white float-end" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-modern alert-success-modern fade-in-up animate-delay-1" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close btn-close-white float-end" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-card fade-in-up animate-delay-2">
        <h6 style="color: #495057; margin-bottom: 1rem; font-weight: 600;">
            <i class="bi bi-funnel me-2"></i>Filter Peminjaman
        </h6>
        <form method="GET">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="color: #6c757d; font-weight: 500;">Status</label>
                    <select name="status" class="form-select form-select-modern">
                        <option value="">Semua Status</option>
                        <option value="menunggu" <?php echo $status_filter == 'menunggu' ? 'selected' : ''; ?>>Menunggu Persetujuan</option>
                        <option value="disetujui" <?php echo $status_filter == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="ditolak" <?php echo $status_filter == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                         <option value="return_pending" <?php echo $status_filter == 'return_pending' ? 'selected' : ''; ?>>Return Pending</option>
                        <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: #6c757d; font-weight: 500;">Tanggal Peminjaman</label>
                    <input type="date" name="tanggal" class="form-control form-control-modern" value="<?php echo $tanggal_filter; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-modern btn-primary-modern">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-modern btn-secondary-modern">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Main Content Card -->
    <div class="content-card fade-in-up animate-delay-3">
        <div class="card-header-modern">
            <h6 class="m-0">
                <i class="bi bi-list-check me-2"></i>
                Data Peminjaman Ruangan
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($peminjaman_list)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x empty-state-icon"></i>
                    <h4 style="color: #495057; margin-bottom: 1rem;">Tidak ada data peminjaman</h4>
                    <p class="text-muted mb-3">Belum ada peminjaman ruangan yang tercatat dalam sistem</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Peminjam</th>
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
                            <?php foreach ($peminjaman_list as $index => $peminjaman): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($peminjaman['nama_lengkap']); ?></span>
                                            <span class="user-type"><?php echo $peminjaman['jenis_pengguna']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="room-info">
                                            <span class="room-name"><?php echo htmlspecialchars($peminjaman['nama_ruangan']); ?></span>
                                            <span class="room-location">
                                                <i class="bi bi-geo-alt"></i>
                                                <?php echo htmlspecialchars($peminjaman['lokasi']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-date text-muted me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-clock text-muted me-1"></i>
                                        <?php echo htmlspecialchars($peminjaman['waktu_mulai']); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-hourglass-split text-muted me-1"></i>
                                        <?php echo htmlspecialchars($peminjaman['durasi_pinjam']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_icon = '';
                                        switch($peminjaman['status']) {
                                            case 'menunggu':
                                                $status_class = 'badge-warning';
                                                $status_icon = 'bi-clock';
                                                break;
                                            case 'disetujui':
                                                $status_class = 'badge-success';
                                                $status_icon = 'bi-check-circle';
                                                break;
                                            case 'ditolak':
                                                $status_class = 'badge-danger';
                                                $status_icon = 'bi-x-circle';
                                                break;
                                            case 'selesai':
                                                $status_class = 'badge-info';
                                                $status_icon = 'bi-check-all';
                                                break;
                                            case 'return_pending':
                                                $status_class = 'badge-warning';
                                                $status_icon = 'bi-clock';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-modern <?php echo $status_class; ?>">
                                            <i class="bi <?php echo $status_icon; ?>"></i>
                                            <?php echo ucfirst($peminjaman['status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.875rem; color: #6c757d;">
                                        <?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="detail.php?id=<?php echo $peminjaman['peminjaman_id']; ?>" 
                                               class="btn btn-modern btn-info-modern btn-sm">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <?php if ($peminjaman['status'] == 'menunggu'): ?>
                                                <a href="approve.php?id=<?php echo $peminjaman['peminjaman_id']; ?>&action=approve" 
                                                   class="btn btn-modern btn-success-modern btn-sm" 
                                                   onclick="return confirm('Setujui peminjaman ini?')">
                                                    <i class="bi bi-check"></i> Setujui
                                                </a>
                                                <a href="approve.php?id=<?php echo $peminjaman['peminjaman_id']; ?>&action=reject" 
                                                   class="btn btn-modern btn-danger-modern btn-sm" 
                                                   onclick="return confirm('Tolak peminjaman ini?')">
                                                    <i class="bi bi-x"></i> Tolak
                                                </a>
                                            <?php endif; ?>
                                        </div>
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

<script>
// Add fade-in animation on page load
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.fade-in-up');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            el.style.transition = 'all 0.6s ease-out';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>