<?php
// admin/return_approval.php - Halaman approval pengembalian untuk admin
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

// Proses approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $peminjaman_id = $_POST['peminjaman_id'];
    $action = $_POST['action']; // 'approve' atau 'reject'
    
    if ($action == 'approve') {
        try {
            // Mulai transaction
            $pdo->beginTransaction();
            
            // Ambil data ruangan_id dari peminjaman
            $stmt = $pdo->prepare("SELECT ruangan_id FROM peminjaman_ruangan WHERE peminjaman_id = ? AND status = 'return_pending'");
            $stmt->execute([$peminjaman_id]);
            $peminjaman_data = $stmt->fetch();
            
            if (!$peminjaman_data) {
                throw new Exception("Data peminjaman tidak ditemukan atau status tidak valid");
            }
            
            // Update status peminjaman menjadi selesai
            $stmt = $pdo->prepare("
                UPDATE peminjaman_ruangan 
                SET status = 'selesai',
                    return_approved_at = CURRENT_TIMESTAMP,
                    return_approved_by = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE peminjaman_id = ? AND status = 'return_pending'
            ");
            
            if (!$stmt->execute([$_SESSION['user_id'], $peminjaman_id])) {
                throw new Exception("Gagal mengupdate status peminjaman");
            }
            
            // Update status ruangan menjadi tersedia
            $stmt = $pdo->prepare("
                UPDATE ruangan 
                SET status = 'tersedia'
                WHERE ruangan_id = ?
            ");
            
            if (!$stmt->execute([$peminjaman_data['ruangan_id']])) {
                throw new Exception("Gagal mengupdate status ruangan");
            }
            
            // Commit transaction
            $pdo->commit();
            $message = "Pengembalian berhasil disetujui dan ruangan telah tersedia kembali!";
            
        } catch (Exception $e) {
            // Rollback transaction jika ada error
            $pdo->rollBack();
            $error = "Gagal menyetujui pengembalian: " . $e->getMessage();
        }
        
    } else if ($action == 'reject') {
        // Reject pengembalian - kembalikan ke status disetujui
        $stmt = $pdo->prepare("
            UPDATE peminjaman_ruangan 
            SET status = 'disetujui',
                kondisi_ruangan = NULL,
                catatan_pengembalian = NULL,
                return_requested_at = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE peminjaman_id = ? AND status = 'return_pending'
        ");
        
        if ($stmt->execute([$peminjaman_id])) {
            $message = "Pengembalian ditolak, peminjaman dikembalikan ke status aktif!";
        } else {
            $error = "Gagal menolak pengembalian!";
        }
    }
}

// Ambil data pending returns
$stmt = $pdo->prepare("
    SELECT p.*, r.nama_ruangan, r.lokasi, u.nama_lengkap 
    FROM peminjaman_ruangan p 
    JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
    JOIN users u ON p.user_id = u.user_id
    WHERE p.status = 'return_pending'
    ORDER BY p.return_requested_at ASC
");
$stmt->execute();
$pending_returns = $stmt->fetchAll();

$title = "Approval Pengembalian Ruangan";
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
        padding: 1rem;
    }

    .dashboard-header {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        border: none;
    }

    .dashboard-title {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 2rem;
        margin: 0;
    }

    .content-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        height: 100%;
    }

    .content-card:hover {
        box-shadow: var(--card-hover-shadow);
        transform: translateY(-5px);
    }

    .card-header-modern {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
        position: relative;
    }

    .card-header-modern .badge {
        position: absolute;
        top: 8px;
        right: 12px;
        background: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .badge-modern {
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.7rem;
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
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
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
        border-radius: 12px;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
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
        padding: 2.5rem 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        margin: 1rem 0;
        box-shadow: var(--card-shadow);
    }

    .empty-state-icon {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 3rem;
        margin-bottom: 0.75rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.4rem 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.8rem;
    }

    .info-value {
        color: #6c757d;
        font-size: 0.8rem;
    }

    .condition-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.6rem;
        border-radius: 25px;
        font-weight: 500;
        font-size: 0.7rem;
    }

    .modal-content {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
    }

    .modal-header {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 15px 15px 0 0;
        padding: 1rem 1.5rem;
    }

    .table-modern {
        margin: 0;
    }

    .table-modern td {
        padding: 0.5rem;
        border: none;
        vertical-align: middle;
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

<div class="main-content">
    <div class="dashboard-header fade-in-up">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="dashboard-title">
                <i class="fas fa-clipboard-check me-3"></i>
                Approval Pengembalian Ruangan
            </h1>
            <div class="badge-modern badge-warning">
                <i class="fas fa-clock"></i>
                <?= count($pending_returns) ?> Pending
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert-modern alert-success-modern fade-in-up">
            <i class="fas fa-check-circle me-2"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern fade-in-up">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pending_returns)): ?>
        <div class="empty-state fade-in-up">
            <div class="empty-state-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h4 class="mb-3">Tidak Ada Pengembalian yang Menunggu Approval</h4>
            <p class="text-muted">Semua pengembalian sudah diproses atau belum ada request pengembalian baru.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($pending_returns as $index => $return): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="content-card fade-in-up animate-delay-<?= ($index % 4) + 1 ?>">
                        <div class="card-header-modern">
                            <h6 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Pending Return
                            </h6>
                            <span class="badge">ID: <?= str_pad($return['peminjaman_id'], 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="p-2";>
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-user me-1"></i> Peminjam
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($return['nama_lengkap']) ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-door-open me-1"></i> Ruangan
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($return['nama_ruangan']) ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-map-marker-alt me-1"></i> Lokasi
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($return['lokasi']) ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-calendar me-1"></i> Tanggal
                                    </span>
                                    <span class="info-value"><?= date('d/m/Y', strtotime($return['tanggal_pinjam'])) ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-clock me-1"></i> Waktu
                                    </span>
                                    <span class="info-value"><?= $return['waktu_mulai'] ?> - <?= $return['waktu_selesai'] ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-paper-plane me-1"></i> Request
                                    </span>
                                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($return['return_requested_at'])) ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">
                                        <i class="fas fa-check-circle me-1"></i> Kondisi
                                    </span>
                                    <span class="condition-badge bg-<?= 
                                        $return['kondisi_ruangan'] == 'Baik' ? 'success' : 
                                        ($return['kondisi_ruangan'] == 'Cukup Baik' ? 'info' : 
                                        ($return['kondisi_ruangan'] == 'Kurang Baik' ? 'warning' : 'danger'))
                                    ?>">
                                        <?= htmlspecialchars($return['kondisi_ruangan']) ?>
                                    </span>
                                </div>
                                
                                <?php if ($return['catatan_pengembalian']): ?>
                                <div class="mt-2">
                                    <div class="info-label mb-1">
                                        <i class="fas fa-sticky-note me-1"></i> Catatan
                                    </div>
                                    <div class="bg-light p-2 rounded">
                                        <small class="text-muted">
                                            <?= nl2br(htmlspecialchars(substr($return['catatan_pengembalian'], 0, 100))) ?>
                                            <?= strlen($return['catatan_pengembalian']) > 100 ? '...' : '' ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light p-2">
                            <div class="row g-1 mb-2">
                                <div class="col-6">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="peminjaman_id" value="<?= $return['peminjaman_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-modern btn-success-modern w-100" 
                                                onclick="return confirm('Setujui pengembalian ruangan ini?')">
                                            <i class="fas fa-check"></i> Setujui
                                        </button>
                                    </form>
                                </div>
                                <div class="col-6">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="peminjaman_id" value="<?= $return['peminjaman_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-modern btn-danger-modern w-100" 
                                                onclick="return confirm('Tolak pengembalian ini? Peminjaman akan kembali aktif.')">
                                            <i class="fas fa-times"></i> Tolak
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <button class="btn btn-modern btn-info-modern w-100" type="button" 
                                    data-bs-toggle="modal" data-bs-target="#detailModal<?= $return['peminjaman_id'] ?>">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Detail -->
                <div class="modal fade" id="detailModal<?= $return['peminjaman_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Detail Pengembalian
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-clipboard me-2"></i>
                                            Informasi Peminjaman
                                        </h6>
                                        <table class="table table-modern table-sm">
                                            <tr><td class="info-label">ID Peminjaman:</td><td><?= str_pad($return['peminjaman_id'], 4, '0', STR_PAD_LEFT) ?></td></tr>
                                            <tr><td class="info-label">Peminjam:</td><td><?= htmlspecialchars($return['nama_lengkap']) ?></td></tr>
                                            <tr><td class="info-label">Ruangan:</td><td><?= htmlspecialchars($return['nama_ruangan']) ?></td></tr>
                                            <tr><td class="info-label">Lokasi:</td><td><?= htmlspecialchars($return['lokasi']) ?></td></tr>
                                            <tr><td class="info-label">Tanggal:</td><td><?= date('d/m/Y', strtotime($return['tanggal_pinjam'])) ?></td></tr>
                                            <tr><td class="info-label">Waktu:</td><td><?= $return['waktu_mulai'] ?> - <?= $return['waktu_selesai'] ?></td></tr>
                                            <tr><td class="info-label">Keperluan:</td><td><?= htmlspecialchars($return['keperluan']) ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-warning mb-3">
                                            <i class="fas fa-undo me-2"></i>
                                            Informasi Pengembalian
                                        </h6>
                                        <table class="table table-modern table-sm">
                                            <tr><td class="info-label">Request Dikirim:</td><td><?= date('d/m/Y H:i:s', strtotime($return['return_requested_at'])) ?></td></tr>
                                            <tr><td class="info-label">Kondisi Ruangan:</td><td>
                                                <span class="badge bg-<?= 
                                                    $return['kondisi_ruangan'] == 'Baik' ? 'success' : 
                                                    ($return['kondisi_ruangan'] == 'Cukup Baik' ? 'info' : 
                                                    ($return['kondisi_ruangan'] == 'Kurang Baik' ? 'warning' : 'danger'))
                                                ?>">
                                                    <?= htmlspecialchars($return['kondisi_ruangan']) ?>
                                                </span>
                                            </td></tr>
                                        </table>
                                        
                                        <?php if ($return['catatan_pengembalian']): ?>
                                        <div class="mt-3">
                                            <h6 class="text-info mb-2">
                                                <i class="fas fa-sticky-note me-2"></i>
                                                Catatan Pengembalian:
                                            </h6>
                                            <div class="border p-3 rounded bg-light">
                                                <?= nl2br(htmlspecialchars($return['catatan_pengembalian'])) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-modern btn-secondary-modern" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Tutup
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="peminjaman_id" value="<?= $return['peminjaman_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-modern btn-danger-modern" 
                                            onclick="return confirm('Tolak pengembalian ini? Peminjaman akan kembali aktif.')">
                                        <i class="fas fa-times"></i> Tolak
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="peminjaman_id" value="<?= $return['peminjaman_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-modern btn-success-modern" 
                                            onclick="return confirm('Setujui pengembalian ruangan ini?')">
                                        <i class="fas fa-check"></i> Setujui
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>