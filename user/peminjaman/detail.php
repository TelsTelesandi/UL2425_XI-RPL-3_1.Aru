<?php
// user/peminjaman/detail.php - Detail Peminjaman
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user sudah login dan bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$peminjaman_id = $_GET['id'] ?? 0;

// Ambil data peminjaman
$stmt = $pdo->prepare("
    SELECT p.*, r.nama_ruangan, r.lokasi, r.kapasitas, u.nama_lengkap, u.jenis_pengguna
    FROM peminjaman_ruangan p 
    JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
    JOIN users u ON p.user_id = u.user_id
    WHERE p.peminjaman_id = ? AND p.user_id = ?
");
$stmt->execute([$peminjaman_id, $user_id]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    header("Location: index.php?error=notfound");
    exit;
}

$title = "Detail Peminjaman";
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

    .page-header {
        background: white;
        border-radius: var(--border-radius);
        padding: var(--spacing-xl) var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
        box-shadow: var(--card-shadow);
        border: none;
    }

    .page-title {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 2.25rem;
        margin: 0 0 var(--spacing-xs) 0;
        line-height: 1.2;
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
        background: var(--primary-gradient);
        color: white;
        border: none;
        padding: var(--spacing-lg);
        font-weight: 600;
        display: flex;
        justify-content: between;
        align-items: center;
    }

    .card-header-modern h4 {
        margin: 0;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .status-badge {
        border-radius: 50px;
        padding: var(--spacing-xs) var(--spacing-md);
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .status-warning {
        background: var(--warning-gradient);
        color: white;
    }

    .status-success {
        background: var(--success-gradient);
        color: white;
    }

    .status-danger {
        background: var(--danger-gradient);
        color: white;
    }

    .status-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }

    .card-body {
        padding: var(--spacing-xl);
    }

    .info-section {
        background: linear-gradient(135deg, rgba(79, 172, 254, 0.05) 0%, rgba(0, 242, 254, 0.05) 100%);
        border-radius: 12px;
        padding: var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
        border-left: 4px solid #4facfe;
    }

    .info-section h6 {
        color: #0c5460;
        font-weight: 700;
        margin-bottom: var(--spacing-sm);
        font-size: 1rem;
    }

    .info-table {
        margin: 0;
    }

    .info-table td {
        padding: var(--spacing-xs) 0;
        border: none;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: #495057;
        width: 35%;
    }

    .keperluan-box {
        background: linear-gradient(135deg, rgba(248, 249, 250, 1) 0%, rgba(233, 236, 239, 1) 100%);
        border-radius: 12px;
        padding: var(--spacing-md);
        margin-top: var(--spacing-sm);
        border: 1px solid #e9ecef;
    }

    .alert-modern {
        border: none;
        border-radius: 12px;
        padding: var(--spacing-md) var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }

    .alert-warning {
        background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
        color: #856404;
        border-left: 4px solid #f093fb;
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%);
        color: #155724;
        border-left: 4px solid #11998e;
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(250, 112, 154, 0.1) 0%, rgba(254, 225, 64, 0.1) 100%);
        color: #721c24;
        border-left: 4px solid #fa709a;
    }

    .alert-secondary {
        background: linear-gradient(135deg, rgba(108, 117, 125, 0.1) 0%, rgba(73, 80, 87, 0.1) 100%);
        color: #383d41;
        border-left: 4px solid #6c757d;
    }

    .btn-modern {
        border-radius: 50px;
        padding: var(--spacing-sm) var(--spacing-lg);
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        font-size: 0.95rem;
        min-width: 140px;
        justify-content: center;
    }

    .btn-primary-modern {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-secondary-modern {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }

    .btn-secondary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(108, 117, 125, 0.4);
        color: white;
    }

    .btn-success-modern {
        background: var(--success-gradient);
        color: white;
    }

    .btn-success-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(17, 153, 142, 0.4);
        color: white;
    }

    .btn-outline-primary-modern {
        background: transparent;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .btn-outline-primary-modern:hover {
        background: var(--primary-gradient);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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

    @media print {
        .btn-modern, .navbar, .alert-modern {
            display: none !important;
        }
        .content-card {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background: white !important;
        }
    }

    @media (max-width: 768px) {
        .main-content {
            padding: var(--spacing-md);
        }
        
        .card-body {
            padding: var(--spacing-lg);
        }

        .card-header-modern {
            flex-direction: column;
            gap: var(--spacing-sm);
            align-items: flex-start;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: var(--spacing-sm);
        }
        
        .card-body {
            padding: var(--spacing-md);
        }

        .btn-modern {
            min-width: 120px;
            padding: var(--spacing-sm);
            font-size: 0.9rem;
        }

        .info-table td:first-child {
            width: 40%;
        }
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="content-card fade-in-up">
                    <div class="card-header-modern">
                        <h4>
                            <i class="fas fa-file-alt"></i>
                            Detail Peminjaman #<?= $peminjaman['peminjaman_id'] ?>
                        </h4>
                        <?php
                        $statusClass = '';
                        $statusIcon = '';
                        switch ($peminjaman['status']) {
                            case 'menunggu':
                                $statusClass = 'status-badge status-warning';
                                $statusIcon = 'fas fa-clock';
                                break;
                            case 'disetujui':
                                $statusClass = 'status-badge status-success';
                                $statusIcon = 'fas fa-check-circle';
                                break;
                            case 'ditolak':
                                $statusClass = 'status-badge status-danger';
                                $statusIcon = 'fas fa-times-circle';
                                break;
                            case 'selesai':
                                $statusClass = 'status-badge status-secondary';
                                $statusIcon = 'fas fa-flag-checkered';
                                break;
                            case 'return_pending':
                                $statusClass = 'status-badge status-warning';
                                $statusIcon = 'fas fa-undo';
                                break;
                        }
                        ?>
                        <span class="<?= $statusClass ?>">
                            <i class="<?= $statusIcon ?>"></i>
                            <?= ucfirst($peminjaman['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-section animate-delay-1">
                                    <h6><i class="fas fa-user"></i> Informasi Peminjam</h6>
                                    <table class="table info-table">
                                        <tr>
                                            <td><strong>Nama:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['nama_lengkap']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jenis:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['jenis_pengguna']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Pengajuan:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($peminjaman['created_at'])) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-section animate-delay-2">
                                    <h6><i class="fas fa-door-open"></i> Informasi Ruangan</h6>
                                    <table class="table info-table">
                                        <tr>
                                            <td><strong>Ruangan:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['nama_ruangan']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Lokasi:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['lokasi']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kapasitas:</strong></td>
                                            <td><?= $peminjaman['kapasitas'] ?> orang</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="info-section animate-delay-3">
                            <h6><i class="fas fa-calendar-alt"></i> Detail Peminjaman</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table info-table">
                                        <tr>
                                            <td><strong>Tanggal Pinjam:</strong></td>
                                            <td><?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Waktu Mulai:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['waktu_mulai']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Durasi:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['durasi_pinjam']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Waktu Selesai:</strong></td>
                                            <td><?= htmlspecialchars($peminjaman['waktu_selesai']) ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-clipboard-list"></i> Keperluan:</strong>
                                    <div class="keperluan-box">
                                        <?= !empty($peminjaman['keperluan']) ? nl2br(htmlspecialchars($peminjaman['keperluan'])) : '<em class="text-muted">Tidak ada keterangan keperluan</em>' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($peminjaman['status'] == 'menunggu'): ?>
                            <div class="alert-modern alert-warning">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Status: Menunggu Persetujuan</strong><br>
                                    Peminjaman Anda sedang menunggu persetujuan dari admin. Anda akan mendapat notifikasi setelah admin memproses pengajuan ini.
                                </div>
                            </div>
                        <?php elseif ($peminjaman['status'] == 'disetujui'): ?>
                            <div class="alert-modern alert-success">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Peminjaman Disetujui!</strong><br>
                                    Peminjaman Anda telah disetujui. Silakan gunakan ruangan sesuai jadwal yang telah ditentukan.
                                </div>
                            </div>
                        <?php elseif ($peminjaman['status'] == 'ditolak'): ?>
                            <div class="alert-modern alert-danger">
                                <i class="fas fa-times-circle"></i>
                                <div>
                                    <strong>Peminjaman Ditolak</strong><br>
                                    Maaf, peminjaman Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut atau ajukan peminjaman dengan jadwal lain.
                                </div>
                            </div>
                        <?php elseif ($peminjaman['status'] == 'selesai'): ?>
                            <div class="alert-modern alert-secondary">
                                <i class="fas fa-flag-checkered"></i>
                                <div>
                                    <strong>Peminjaman Selesai</strong><br>
                                    Peminjaman ini telah selesai. Terima kasih telah menggunakan fasilitas ruangan dengan baik.
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between flex-wrap gap-3 mt-4">
                            <a href="index.php" class="btn-modern btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                            </a>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($peminjaman['status'] == 'disetujui'): ?>
                                    <a href="return.php?id=<?= $peminjaman['peminjaman_id'] ?>" class="btn-modern btn-success-modern">
                                        <i class="fas fa-undo"></i> Kembalikan Ruangan
                                    </a>
                                <?php endif; ?>
                                <button onclick="window.print()" class="btn-modern btn-outline-primary-modern">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>