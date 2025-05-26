<?php
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user sudah login dan bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Ambil data ruangan yang tersedia (status tersedia saja)
$stmt = $pdo->query("SELECT * FROM ruangan WHERE status = 'tersedia' ORDER BY nama_ruangan");
$ruangan = $stmt->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ruangan_id = $_POST['ruangan_id'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $durasi_pinjam = $_POST['durasi_pinjam'];
    $waktu_selesai = $_POST['waktu_selesai'];
    $keperluan = $_POST['keperluan'];
    
    // Validasi
    if (empty($ruangan_id) || empty($tanggal_pinjam) || empty($waktu_mulai) || empty($durasi_pinjam) || empty($waktu_selesai)) {
        $error = "Semua field wajib diisi!";
    } else {
        // Cek konflik jadwal
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM peminjaman_ruangan 
            WHERE ruangan_id = ? AND tanggal_pinjam = ? 
            AND status IN ('menunggu', 'disetujui')
            AND (
                (waktu_mulai <= ? AND waktu_selesai >= ?) OR
                (waktu_mulai <= ? AND waktu_selesai >= ?) OR
                (waktu_mulai >= ? AND waktu_selesai <= ?)
            )
        ");
        $stmt->execute([
            $ruangan_id, $tanggal_pinjam,
            $waktu_mulai, $waktu_mulai,
            $waktu_selesai, $waktu_selesai,
            $waktu_mulai, $waktu_selesai
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Ruangan sudah dipinjam pada waktu tersebut!";
        } else {
            // Cek konflik jadwal dengan peminjaman yang sudah disetujui atau menunggu
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM peminjaman_ruangan 
                WHERE ruangan_id = ? AND tanggal_pinjam = ? 
                AND status IN ('menunggu', 'disetujui')
                AND (
                    (waktu_mulai <= ? AND waktu_selesai >= ?) OR
                    (waktu_mulai <= ? AND waktu_selesai >= ?) OR
                    (waktu_mulai >= ? AND waktu_selesai <= ?)
                )
            ");
            $stmt->execute([
                $ruangan_id, $tanggal_pinjam,
                $waktu_mulai, $waktu_mulai,
                $waktu_selesai, $waktu_selesai,
                $waktu_mulai, $waktu_selesai
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Ruangan sudah dipinjam pada waktu tersebut!";
            } else {
                // Mulai transaksi database
                $pdo->beginTransaction();
                
                try {
                    // Insert peminjaman dengan status menunggu
                    $stmt = $pdo->prepare("
                        INSERT INTO peminjaman_ruangan (user_id, ruangan_id, tanggal_pinjam, waktu_mulai, durasi_pinjam, waktu_selesai, keperluan, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'menunggu')
                    ");
                    
                    if ($stmt->execute([$user_id, $ruangan_id, $tanggal_pinjam, $waktu_mulai, $durasi_pinjam, $waktu_selesai, $keperluan])) {
                        // Update status ruangan menjadi "diajukan"
                        $stmt_update = $pdo->prepare("UPDATE ruangan SET status = 'diajukan' WHERE ruangan_id = ?");
                        if ($stmt_update->execute([$ruangan_id])) {
                            // Commit transaksi
                            $pdo->commit();
                            header("Location: index.php?success=1");
                            exit;
                        } else {
                            throw new Exception("Gagal mengubah status ruangan");
                        }
                    } else {
                        throw new Exception("Gagal menyimpan data peminjaman");
                    }
                } catch (Exception $e) {
                    // Rollback transaksi jika terjadi error
                    $pdo->rollback();
                    $error = "Gagal mengajukan peminjaman: " . $e->getMessage();
                }
            }
        }
    }
}

$title = "Ajukan Peminjaman Ruangan";
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

    .alert-modern {
        border: none;
        border-radius: 12px;
        padding: var(--spacing-md) var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
        display: flex;
        align-items: center;
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

        .schedule-grid {
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
    }
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header fade-in-up">
        <h1 class="page-title">
            <i class="fas fa-plus-circle"></i>
            Ajukan Peminjaman Ruangan
        </h1>
        <p class="page-subtitle">Isi formulir di bawah ini untuk mengajukan peminjaman ruangan</p>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Form Card -->
            <div class="content-card fade-in-up animate-delay-1">
                <div class="card-header-modern">
                    <h4>
                        <i class="fas fa-edit"></i>
                        Form Peminjaman Ruangan
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert-modern alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Error!</strong><br>
                                <?= $error ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="peminjamanForm">
                        <div class="mb-4">
                            <label for="ruangan_id" class="form-label">
                                <i class="fas fa-door-open"></i>
                                Pilih Ruangan <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="ruangan_id" name="ruangan_id" required>
                                <option value="">-- Pilih Ruangan --</option>
                                <?php if (empty($ruangan)): ?>
                                    <option value="" disabled>Tidak ada ruangan yang tersedia</option>
                                <?php else: ?>
                                    <?php foreach ($ruangan as $r): ?>
                                        <option value="<?= $r['ruangan_id'] ?>" <?= (isset($_POST['ruangan_id']) && $_POST['ruangan_id'] == $r['ruangan_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['nama_ruangan']) ?> - <?= htmlspecialchars($r['lokasi']) ?> (Kapasitas: <?= $r['kapasitas'] ?>) - Status: Tersedia
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> 
                                Hanya ruangan dengan status "Tersedia" yang dapat dipinjam
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="tanggal_pinjam" class="form-label">
                                <i class="fas fa-calendar"></i>
                                Tanggal Peminjaman <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam" 
                                   min="<?= date('Y-m-d') ?>" value="<?= $_POST['tanggal_pinjam'] ?? '' ?>" required>
                            <div class="form-text">
                                <i class="fas fa-calendar-check"></i>
                                Pilih tanggal minimal hari ini
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="waktu_mulai" class="form-label">
                                        <i class="fas fa-clock"></i>
                                        Waktu Mulai <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="waktu_mulai" name="waktu_mulai" required>
                                        <option value="">-- Pilih Jam Mulai --</option>
                                        <option value="JP 1 (07:00-07:45)" data-jp="1" data-start="07:00" data-end="07:45" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 1 (07:00-07:45)') ? 'selected' : '' ?>>JP 1 (07:00-07:45)</option>
                                        <option value="JP 2 (07:45-08:30)" data-jp="2" data-start="07:45" data-end="08:30" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 2 (07:45-08:30)') ? 'selected' : '' ?>>JP 2 (07:45-08:30)</option>
                                        <option value="JP 3 (08:30-09:15)" data-jp="3" data-start="08:30" data-end="09:15" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 3 (08:30-09:15)') ? 'selected' : '' ?>>JP 3 (08:30-09:15)</option>
                                        <option value="JP 4 (09:30-10:15)" data-jp="4" data-start="09:30" data-end="10:15" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 4 (09:30-10:15)') ? 'selected' : '' ?>>JP 4 (09:30-10:15)</option>
                                        <option value="JP 5 (10:15-11:00)" data-jp="5" data-start="10:15" data-end="11:00" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 5 (10:15-11:00)') ? 'selected' : '' ?>>JP 5 (10:15-11:00)</option>
                                        <option value="JP 6 (11:00-11:45)" data-jp="6" data-start="11:00" data-end="11:45" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 6 (11:00-11:45)') ? 'selected' : '' ?>>JP 6 (11:00-11:45)</option>
                                        <option value="JP 7 (12:30-13:15)" data-jp="7" data-start="12:30" data-end="13:15" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 7 (12:30-13:15)') ? 'selected' : '' ?>>JP 7 (12:30-13:15)</option>
                                        <option value="JP 8 (13:15-14:00)" data-jp="8" data-start="13:15" data-end="14:00" <?= (isset($_POST['waktu_mulai']) && $_POST['waktu_mulai'] == 'JP 8 (13:15-14:00)') ? 'selected' : '' ?>>JP 8 (13:15-14:00)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="durasi_pinjam" class="form-label">
                                        <i class="fas fa-hourglass-half"></i>
                                        Durasi <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="durasi_pinjam" name="durasi_pinjam" required>
                                        <option value="">-- Pilih Durasi --</option>
                                        <option value="1 JP" data-jp="1" <?= (isset($_POST['durasi_pinjam']) && $_POST['durasi_pinjam'] == '1 JP') ? 'selected' : '' ?>>1 JP (45 menit)</option>
                                        <option value="2 JP" data-jp="2" <?= (isset($_POST['durasi_pinjam']) && $_POST['durasi_pinjam'] == '2 JP') ? 'selected' : '' ?>>2 JP (90 menit)</option>
                                        <option value="3 JP" data-jp="3" <?= (isset($_POST['durasi_pinjam']) && $_POST['durasi_pinjam'] == '3 JP') ? 'selected' : '' ?>>3 JP (135 menit)</option>
                                        <option value="4 JP" data-jp="4" <?= (isset($_POST['durasi_pinjam']) && $_POST['durasi_pinjam'] == '4 JP') ? 'selected' : '' ?>>4 JP (180 menit)</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i>
                                        Durasi akan menentukan waktu selesai otomatis
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="waktu_selesai" class="form-label">
                                <i class="fas fa-clock"></i>
                                Waktu Selesai <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="waktu_selesai" name="waktu_selesai" required readonly>
                                <option value="">-- Akan terisi otomatis --</option>
                                <option value="JP 1 (07:45)" data-jp="1" data-time="07:45">JP 1 (07:45)</option>
                                <option value="JP 2 (08:30)" data-jp="2" data-time="08:30">JP 2 (08:30)</option>
                                <option value="JP 3 (09:15)" data-jp="3" data-time="09:15">JP 3 (09:15)</option>
                                <option value="JP 4 (10:15)" data-jp="4" data-time="10:15">JP 4 (10:15)</option>
                                <option value="JP 5 (11:00)" data-jp="5" data-time="11:00">JP 5 (11:00)</option>
                                <option value="JP 6 (11:45)" data-jp="6" data-time="11:45">JP 6 (11:45)</option>
                                <option value="JP 7 (13:15)" data-jp="7" data-time="13:15">JP 7 (13:15)</option>
                                <option value="JP 8 (14:00)" data-jp="8" data-time="14:00">JP 8 (14:00)</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> 
                                Waktu selesai akan diisi otomatis berdasarkan waktu mulai dan durasi yang dipilih
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="keperluan" class="form-label">
                                <i class="fas fa-clipboard-list"></i>
                                Keperluan
                            </label>
                            <textarea class="form-control" id="keperluan" name="keperluan" rows="4" 
                                      placeholder="Jelaskan keperluan peminjaman ruangan (contoh: Rapat dosen, Presentasi tugas akhir, Workshop, dll)..."><?= $_POST['keperluan'] ?? '' ?></textarea>
                        </div>

                        <!-- Info jadwal yang terpilih -->
                        <div class="alert-modern alert-info" id="jadwalInfo" style="display: none;">
                            <i class="fas fa-calendar-check fa-2x"></i>
                            <div>
                                <h6 class="mb-2">Ringkasan Jadwal:</h6>
                                <div id="jadwalDetail"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3">
                            <a href="index.php" class="btn-modern btn-secondary-modern">
                                <i class="fas fa-arrow-left"></i> 
                                Kembali
                            </a>
                            <button type="submit" class="btn-modern btn-primary-modern">
                                <i class="fas fa-paper-plane"></i> 
                                Ajukan Peminjaman
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info jadwal harian -->
            <div class="info-card fade-in-up animate-delay-2">
                <h6 class="mb-3">
                    <i class="fas fa-calendar-day"></i> 
                    Jadwal Jam Pelajaran
                </h6>
                <div class="schedule-grid">
                    <div class="schedule-section">
                        <h6>Jadwal Pagi:</h6>
                        <small>
                            JP 1: 07:00 - 07:45<br>
                            JP 2: 07:45 - 08:30<br>
                            JP 3: 08:30 - 09:15<br>
                            <em>Istirahat: 09:15 - 09:30</em><br>
                            JP 4: 09:30 - 10:15
                        </small>
                    </div>
                    <div class="schedule-section">
                        <h6>Jadwal Siang:</h6>
                        <small>
                            JP 5: 10:15 - 11:00<br>
                            JP 6: 11:00 - 11:45<br>
                            <em>Istirahat: 11:45 - 12:30</em><br>
                            JP 7: 12:30 - 13:15<br>
                            JP 8: 13:15 - 14:00
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const waktuMulai = document.getElementById('waktu_mulai');
    const durasi = document.getElementById('durasi_pinjam');
    const waktuSelesai = document.getElementById('waktu_selesai');
    const jadwalInfo = document.getElementById('jadwalInfo');
    const jadwalDetail = document.getElementById('jadwalDetail');
    const ruangan = document.getElementById('ruangan_id');
    const tanggal = document.getElementById('tanggal_pinjam');

    // Fungsi untuk menghitung waktu selesai
    function hitungWaktuSelesai() {
        const mulaiOption = waktuMulai.options[waktuMulai.selectedIndex];
        const durasiOption = durasi.options[durasi.selectedIndex];
        
        if (mulaiOption.value && durasiOption.value) {
            const mulaiJP = parseInt(mulaiOption.dataset.jp);
            const durasiJP = parseInt(durasiOption.dataset.jp);
            const selesaiJP = mulaiJP + durasiJP - 1;
            
            // Validasi apakah waktu selesai tidak melebihi JP 8
            if (selesaiJP > 8) {
                alert('Durasi terlalu panjang! Maksimal sampai JP 8 (14:00)');
                durasi.value = '';
                waktuSelesai.value = '';
                return;
            }
            
            // Cari option waktu selesai yang sesuai
            for (let option of waktuSelesai.options) {
                if (option.dataset.jp == selesaiJP) {
                    waktuSelesai.value = option.value;
                    break;
                }
            }
            
            // Tampilkan info jadwal
            tampilkanInfoJadwal();
        } else {
            waktuSelesai.value = '';
            jadwalInfo.style.display = 'none';
        }
    }
    
    // Fungsi untuk menampilkan info jadwal
    function tampilkanInfoJadwal() {
        const ruanganText = ruangan.options[ruangan.selectedIndex].text;
        const tanggalValue = tanggal.value;
        const mulaiText = waktuMulai.options[waktuMulai.selectedIndex].text;
        const durasiText = durasi.options[durasi.selectedIndex].text;
        const selesaiText = waktuSelesai.options[waktuSelesai.selectedIndex].text;
        
        if (ruangan.value && tanggalValue && mulaiText && durasiText && selesaiText) {
            const tanggalFormatted = new Date(tanggalValue).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            
            jadwalDetail.innerHTML = `
                <strong>Ruangan:</strong> ${ruanganText}<br>
                <strong>Tanggal:</strong> ${tanggalFormatted}<br>
                <strong>Waktu:</strong> ${mulaiText} - ${selesaiText}<br>
                <strong>Durasi:</strong> ${durasiText}
            `;
            jadwalInfo.style.display = 'block';
        }
    }
    
    // Event listeners
    waktuMulai.addEventListener('change', hitungWaktuSelesai);
    durasi.addEventListener('change', hitungWaktuSelesai);
    ruangan.addEventListener('change', tampilkanInfoJadwal);
    tanggal.addEventListener('change', tampilkanInfoJadwal);
    
    // Validasi tanggal tidak boleh masa lalu
    tanggal.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('Tanggal tidak boleh masa lalu!');
            this.value = '';
        }
    });
    
    // Validasi form sebelum submit
    document.getElementById('peminjamanForm').addEventListener('submit', function(e) {
        if (!waktuSelesai.value) {
            e.preventDefault();
            alert('Silakan pilih waktu mulai dan durasi terlebih dahulu!');
            return false;
        }
        
        // Konfirmasi sebelum submit
        const konfirmasi = confirm('Apakah data peminjaman sudah benar?\n\n' + jadwalDetail.innerText.replace(/<br>/g, '\n'));
        if (!konfirmasi) {
            e.preventDefault();
            return false;
        }
    });
    
    // Inisialisasi jika ada data yang sudah dipilih (untuk kasus error)
    if (waktuMulai.value && durasi.value) {
        hitungWaktuSelesai();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>