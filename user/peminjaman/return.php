<?php
// user/peminjaman/return.php - Pengembalian dengan Approval
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
$message = '';
$error = '';

// Ambil data peminjaman
$stmt = $pdo->prepare("
    SELECT p.*, r.nama_ruangan, r.lokasi 
    FROM peminjaman_ruangan p 
    JOIN ruangan r ON p.ruangan_id = r.ruangan_id 
    WHERE p.peminjaman_id = ? AND p.user_id = ? AND p.status IN ('disetujui', 'return_pending')
");
$stmt->execute([$peminjaman_id, $user_id]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    header("Location: index.php?error=notfound");
    exit;
}

// Cek apakah sudah submit return request
$already_requested = ($peminjaman['status'] == 'return_pending');

// Proses pengembalian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$already_requested) {
    $kondisi_ruangan = $_POST['kondisi_ruangan'];
    $catatan_pengembalian = $_POST['catatan_pengembalian'];
    
    // Validasi
    if (empty($kondisi_ruangan)) {
        $error = "Kondisi ruangan wajib dipilih!";
    } else {
        // Update status menjadi return_pending dan simpan data pengembalian
        $stmt = $pdo->prepare("
            UPDATE peminjaman_ruangan 
            SET status = 'return_pending',
                kondisi_ruangan = ?,
                catatan_pengembalian = ?,
                return_requested_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE peminjaman_id = ? AND user_id = ?
        ");
        
        if ($stmt->execute([$kondisi_ruangan, $catatan_pengembalian, $peminjaman_id, $user_id])) {
            header("Location: index.php?return_submitted=1");
            exit;
        } else {
            $error = "Gagal mengirim request pengembalian!";
        }
    }
}

$title = "Pengembalian Ruangan";
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
        max-width: 1000px;
        margin: 0 auto;
    }

    .page-header {
        background: white;
        border-radius: var(--border-radius);
        padding: var(--spacing-xl) var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
        box-shadow: var(--card-shadow);
        border: none;
        text-align: center;
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

    .page-subtitle {
        color: #6c757d;
        font-size: 1rem;
        margin: 0;
        font-weight: 400;
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
    }

    .card-header-modern h4 {
        margin: 0;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .card-body {
        padding: var(--spacing-xl);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: var(--spacing-xs);
        font-size: 0.9rem;
    }

    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: var(--spacing-sm) var(--spacing-md);
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .form-text {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: var(--spacing-xs);
        display: flex;
        align-items: center;
        gap: 0.25rem;
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

    .btn-warning-modern {
        background: var(--warning-gradient);
        color: white;
    }

    .btn-warning-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(240, 147, 251, 0.4);
        color: white;
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

    .alert-danger {
        background: linear-gradient(135deg, rgba(250, 112, 154, 0.1) 0%, rgba(254, 225, 64, 0.1) 100%);
        color: #721c24;
        border-left: 4px solid #fa709a;
    }

    .alert-info {
        background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
        color: #0c5460;
        border-left: 4px solid #4facfe;
    }

    .alert-warning {
        background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
        color: #856404;
        border-left: 4px solid #f093fb;
    }

    .info-card {
        background: var(--info-gradient);
        color: white;
        border-radius: var(--border-radius);
        padding: var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
        box-shadow: var(--card-shadow);
    }

    .schedule-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--spacing-lg);
    }

    .schedule-section h6 {
        margin: 0 0 var(--spacing-sm) 0;
        font-weight: 700;
        font-size: 1rem;
        opacity: 0.9;
    }

    .schedule-section small {
        display: block;
        line-height: 1.6;
        opacity: 0.8;
        font-size: 0.85rem;
    }

    .guide-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: var(--border-radius);
        padding: var(--spacing-lg);
        margin-top: var(--spacing-lg);
        box-shadow: var(--card-shadow);
    }

    .guide-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--spacing-lg);
        margin-top: var(--spacing-md);
    }

    .guide-section h6 {
        margin: 0 0 var(--spacing-md) 0;
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .guide-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .guide-list li {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-xs);
        margin-bottom: var(--spacing-xs);
        padding: var(--spacing-xs) 0;
        opacity: 0.9;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .guide-list i {
        margin-top: 2px;
        font-size: 0.8rem;
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .main-content {
            padding: var(--spacing-md);
        }
        
        .page-header {
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .page-title {
            font-size: 1.875rem;
        }
        
        .card-body {
            padding: var(--spacing-lg);
        }

        .schedule-grid, .guide-grid {
            grid-template-columns: 1fr;
            gap: var(--spacing-md);
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: var(--spacing-sm);
        }
        
        .page-header {
            padding: var(--spacing-md);
        }
        
        .page-title {
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: var(--spacing-md);
        }

        .btn-modern {
            min-width: 120px;
            padding: var(--spacing-sm);
            font-size: 0.9rem;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: var(--spacing-sm);
        }
    }
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header fade-in-up">
        <h1 class="page-title">
            <?php if ($already_requested): ?>
                <i class="fas fa-clock"></i> Status Pengembalian Ruangan
            <?php else: ?>
                <i class="fas fa-undo-alt"></i> Form Pengembalian Ruangan
            <?php endif; ?>
        </h1>
        <p class="page-subtitle">Proses pengembalian ruangan dengan verifikasi admin</p>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- Main Content Card -->
            <div class="content-card fade-in-up animate-delay-1">
                <div class="card-header-modern">
                    <h4>
                        <?php if ($already_requested): ?>
                            <i class="fas fa-hourglass-half"></i> Status Pengembalian
                        <?php else: ?>
                            <i class="fas fa-clipboard-check"></i> Form Pengembalian
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert-modern alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div><?= $error ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Info Peminjaman -->
                    <div class="info-card">
                        <h6><i class="fas fa-info-circle"></i> Informasi Peminjaman</h6>
                        <div class="schedule-grid">
                            <div class="schedule-section">
                                <h6><i class="fas fa-door-open"></i> Detail Ruangan</h6>
                                <small><strong>Ruangan:</strong> <?= htmlspecialchars($peminjaman['nama_ruangan']) ?></small>
                                <small><strong>Lokasi:</strong> <?= htmlspecialchars($peminjaman['lokasi']) ?></small>
                            </div>
                            <div class="schedule-section">
                                <h6><i class="fas fa-calendar-alt"></i> Jadwal Pemakaian</h6>
                                <small><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></small>
                                <small><strong>Waktu:</strong> <?= htmlspecialchars($peminjaman['waktu_mulai']) ?> - <?= htmlspecialchars($peminjaman['waktu_selesai']) ?></small>
                            </div>
                        </div>
                    </div>

                    <?php if ($already_requested): ?>
                        <!-- Status Return Request -->
                        <div class="alert-modern alert-warning">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h6 style="margin: 0 0 0.5rem 0; color: inherit;">Request Pengembalian Sedang Diproses</h6>
                                <p style="margin: 0 0 0.5rem 0;">Anda telah mengirim request pengembalian pada: <strong><?= date('d/m/Y H:i', strtotime($peminjaman['return_requested_at'])) ?></strong></p>
                                <p style="margin: 0;">Silakan tunggu admin untuk memverifikasi dan menyetujui pengembalian ruangan Anda.</p>
                                
                                <?php if ($peminjaman['kondisi_ruangan']): ?>
                                <hr style="margin: 1rem 0; border-color: rgba(133, 100, 4, 0.2);">
                                <div class="schedule-grid" style="margin-top: 1rem;">
                                    <div>
                                        <strong>Kondisi Ruangan:</strong> <?= htmlspecialchars($peminjaman['kondisi_ruangan']) ?>
                                    </div>
                                </div>
                                <?php if ($peminjaman['catatan_pengembalian']): ?>
                                <div style="margin-top: 0.75rem;">
                                    <strong>Catatan:</strong><br>
                                    <?= nl2br(htmlspecialchars($peminjaman['catatan_pengembalian'])) ?>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="detail.php?id=<?= $peminjaman_id ?>" class="btn-modern btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> Kembali ke Detail
                            </a>
                            <a href="index.php" class="btn-modern btn-primary-modern">
                                <i class="fas fa-list"></i> Daftar Peminjaman
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- Form Pengembalian -->
                        <form method="POST">
                            <div class="mb-3">
                                <label for="kondisi_ruangan" class="form-label">
                                    <i class="fas fa-check-circle"></i> Kondisi Ruangan Saat Dikembalikan 
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="kondisi_ruangan" name="kondisi_ruangan" required>
                                    <option value="">-- Pilih Kondisi Ruangan --</option>
                                    <option value="Baik" <?= (isset($_POST['kondisi_ruangan']) && $_POST['kondisi_ruangan'] == 'Baik') ? 'selected' : '' ?>>
                                        Baik - Ruangan dalam kondisi bersih dan rapi
                                    </option>
                                    <option value="Cukup Baik" <?= (isset($_POST['kondisi_ruangan']) && $_POST['kondisi_ruangan'] == 'Cukup Baik') ? 'selected' : '' ?>>
                                        Cukup Baik - Ada sedikit kotoran tapi masih bisa digunakan
                                    </option>
                                    <option value="Kurang Baik" <?= (isset($_POST['kondisi_ruangan']) && $_POST['kondisi_ruangan'] == 'Kurang Baik') ? 'selected' : '' ?>>
                                        Kurang Baik - Ruangan kotor atau ada kerusakan ringan
                                    </option>
                                    <option value="Rusak" <?= (isset($_POST['kondisi_ruangan']) && $_POST['kondisi_ruangan'] == 'Rusak') ? 'selected' : '' ?>>
                                        Rusak - Ada kerusakan yang perlu perbaikan
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="catatan_pengembalian" class="form-label">
                                    <i class="fas fa-sticky-note"></i> Catatan Pengembalian
                                </label>
                                <textarea class="form-control" id="catatan_pengembalian" name="catatan_pengembalian" rows="4" 
                                          placeholder="Jelaskan kondisi ruangan atau hal-hal yang perlu diperhatikan admin..."><?= $_POST['catatan_pengembalian'] ?? '' ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Jika ada kerusakan atau masalah, silakan jelaskan secara detail agar admin dapat menindaklanjuti.
                                </div>
                            </div>

                            <div class="alert-modern alert-info">
                                <i class="fas fa-lightbulb"></i>
                                <div>
                                    <strong>Informasi:</strong> Setelah Anda mengirim request pengembalian, admin akan melakukan verifikasi kondisi ruangan sebelum menyetujui pengembalian.
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="detail.php?id=<?= $peminjaman_id ?>" class="btn-modern btn-secondary-modern">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn-modern btn-warning-modern" onclick="return confirm('Apakah Anda yakin ingin mengirim request pengembalian ruangan?')">
                                    <i class="fas fa-paper-plane"></i> Kirim Request Pengembalian
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panduan Pengembalian -->
            <div class="guide-card fade-in-up animate-delay-2">
                <h6><i class="fas fa-book-open"></i> Panduan Pengembalian Ruangan</h6>
                <div class="guide-grid">
                    <div class="guide-section">
                        <h6><i class="fas fa-check-circle"></i> Yang Harus Dilakukan</h6>
                        <ul class="guide-list">
                            <li><i class="fas fa-check"></i> Bersihkan ruangan dari sampah</li>
                            <li><i class="fas fa-check"></i> Rapikan meja dan kursi</li>
                            <li><i class="fas fa-check"></i> Matikan AC dan lampu</li>
                            <li><i class="fas fa-check"></i> Tutup jendela dan pintu</li>
                            <li><i class="fas fa-check"></i> Pastikan semua peralatan kembali ke tempatnya</li>
                        </ul>
                    </div>
                    <div class="guide-section">
                        <h6><i class="fas fa-times-circle"></i> Yang Tidak Boleh</h6>
                        <ul class="guide-list">
                            <li><i class="fas fa-times"></i> Meninggalkan sampah</li>
                            <li><i class="fas fa-times"></i> Merusak fasilitas ruangan</li>
                            <li><i class="fas fa-times"></i> Membawa keluar peralatan ruangan</li>
                            <li><i class="fas fa-times"></i> Mencoret-coret papan tulis/dinding</li>
                            <li><i class="fas fa-times"></i> Lupa mengunci ruangan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>