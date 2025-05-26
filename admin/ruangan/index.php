<?php
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

checkAdmin();

// Mengambil data ruangan
try {
    $stmt = $pdo->query("
        SELECT r.*, 
               COUNT(pr.ruangan_id) as total_peminjaman,
               COUNT(CASE WHEN pr.status IN ('menunggu', 'disetujui') THEN 1 END) as peminjaman_aktif
        FROM ruangan r
        LEFT JOIN peminjaman_ruangan pr ON r.ruangan_id = pr.ruangan_id
        WHERE r.is_enabled = 1
        GROUP BY r.ruangan_id
        ORDER BY r.nama_ruangan
    ");
    $ruangan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

$page_title = 'Kelola Ruangan';
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

    .btn-outline-primary-modern {
        background: transparent;
        border: 2px solid #667eea;
        color: #667eea;
        padding: 0.5rem 1rem;
    }

    .btn-outline-primary-modern:hover {
        background: var(--primary-gradient);
        border-color: transparent;
        color: white;
        transform: translateY(-2px);
    }

    .btn-outline-danger-modern {
        background: transparent;
        border: 2px solid #fa709a;
        color: #fa709a;
        padding: 0.5rem 1rem;
    }

    .btn-outline-danger-modern:hover {
        background: var(--danger-gradient);
        border-color: transparent;
        color: white;
        transform: translateY(-2px);
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
            <h1 class="dashboard-title">Kelola Ruangan</h1>
            <a href="create.php" class="btn btn-modern btn-primary-modern">
                <i class="bi bi-plus-circle"></i> Tambah Ruangan
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-modern alert-success-modern fade-in-up animate-delay-1" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close btn-close-white float-end" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-modern alert-danger-modern fade-in-up animate-delay-1" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close btn-close-white float-end" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-modern alert-danger-modern fade-in-up animate-delay-1" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= $error ?>
            <button type="button" class="btn-close btn-close-white float-end" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Content Card -->
    <div class="content-card fade-in-up animate-delay-2">
        <div class="card-header-modern">
            <h6 class="m-0">
                <i class="bi bi-building me-2"></i>
                Daftar Ruangan
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ruangan_list)): ?>
                <div class="empty-state">
                    <i class="bi bi-building empty-state-icon"></i>
                    <h4 style="color: #495057; margin-bottom: 1rem;">Belum ada data ruangan</h4>
                    <p class="text-muted mb-3">Mulai dengan menambahkan ruangan pertama untuk sistem peminjaman</p>
                    <a href="create.php" class="btn btn-modern btn-primary-modern">
                        <i class="bi bi-plus-circle"></i> Tambah Ruangan Pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Ruangan</th>
                                <th>Lokasi</th>
                                <th>Kapasitas</th>
                                <th>Total Peminjaman</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ruangan_list as $index => $ruangan): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong style="color: #495057;"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></strong>
                                    </td>
                                    <td>
                                        <i class="bi bi-geo-alt text-muted me-1"></i>
                                        <?= htmlspecialchars($ruangan['lokasi']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-modern badge-info">
                                            <i class="bi bi-people me-1"></i>
                                            <?= $ruangan['kapasitas'] ?> orang
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-modern" style="background: var(--dark-gradient); color: white;">
                                            <?= $ruangan['total_peminjaman'] ?> kali
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ruangan['peminjaman_aktif'] > 0): ?>
                                            <span class="badge badge-modern badge-warning">
                                                <i class="bi bi-clock me-1"></i>
                                                Sedang Dipinjam
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-modern badge-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Tersedia
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="edit.php?id=<?= $ruangan['ruangan_id'] ?>" 
                                               class="btn btn-modern btn-outline-primary-modern btn-sm" 
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $ruangan['ruangan_id'] ?>" 
                                               class="btn btn-modern btn-outline-danger-modern btn-sm" 
                                               title="Hapus"
                                               onclick="return confirmDelete('Apakah Anda yakin ingin menghapus ruangan <?= htmlspecialchars($ruangan['nama_ruangan']) ?>?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
function confirmDelete(message) {
    return confirm(message);
}

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