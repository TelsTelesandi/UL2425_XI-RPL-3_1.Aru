<?php
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

checkAdmin();

$error = '';
$success = '';

if ($_POST) {
    $nama_ruangan = trim($_POST['nama_ruangan']);
    $lokasi = trim($_POST['lokasi']);
    $kapasitas = (int)$_POST['kapasitas'];
    
    // Validasi
    if (empty($nama_ruangan)) {
        $error = 'Nama ruangan harus diisi!';
    } elseif (empty($lokasi)) {
        $error = 'Lokasi ruangan harus diisi!';
    } elseif ($kapasitas <= 0) {
        $error = 'Kapasitas harus lebih dari 0!';
    } else {
        try {
            // Cek duplikasi nama ruangan (hanya yang masih aktif)
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ruangan WHERE nama_ruangan = ? AND is_enabled = 1");
            $stmt->execute([$nama_ruangan]);
            $existing = $stmt->fetch()['total'];
            
            if ($existing > 0) {
                $error = 'Nama ruangan sudah ada!';
            } else {
                // Insert data dengan status 'tersedia' dan is_enabled = 1
                $stmt = $pdo->prepare("INSERT INTO ruangan (nama_ruangan, lokasi, kapasitas, status, is_enabled) VALUES (?, ?, ?, 'tersedia', 1)");
                $stmt->execute([$nama_ruangan, $lokasi, $kapasitas]);
                
                $success = 'Ruangan berhasil ditambahkan!';
                
                // Clear form
                $_POST = array();
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Tambah Ruangan';
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
        background: var(--primary-gradient);
        color: white;
        border: none;
        padding: 1.5rem 2rem;
        font-weight: 600;
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

    .btn-secondary-modern {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }

    .btn-secondary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        color: white;
    }

    .btn-outline-modern {
        background: white;
        border: 2px solid #667eea;
        color: #667eea;
        border-radius: 50px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-outline-modern:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    .form-control-modern {
        border-radius: 15px;
        border: 2px solid #e9ecef;
        padding: 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }

    .form-label-modern {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .alert-modern {
        border: none;
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .alert-success-modern {
        background: var(--success-gradient);
        color: white;
    }

    .alert-danger-modern {
        background: var(--danger-gradient);
        color: white;
    }

    .info-card {
        background: var(--info-gradient);
        color: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
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

    .input-group-text-modern {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 0 15px 15px 0;
        font-weight: 500;
    }
</style>

<div class="container-fluid main-content">
    <div class="row">
        <!-- Main content -->
        <div class="col-12">
            <!-- Header -->
            <div class="dashboard-header fade-in-up">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="dashboard-title">
                        <i class="bi bi-plus-circle-fill me-3"></i>Tambah Ruangan
                    </h1>
                    <a href="index.php" class="btn-outline-modern">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert-modern alert-success-modern fade-in-up animate-delay-1" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $success ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern fade-in-up animate-delay-1" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Form Card -->
                    <div class="content-card fade-in-up animate-delay-2">
                        <div class="card-header-modern">
                            <h6 class="m-0">
                                <i class="bi bi-house-add me-2"></i>Form Tambah Ruangan
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="nama_ruangan" class="form-label form-label-modern">
                                        Nama Ruangan <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-modern" id="nama_ruangan" name="nama_ruangan" 
                                           value="<?= isset($_POST['nama_ruangan']) ? htmlspecialchars($_POST['nama_ruangan']) : '' ?>" 
                                           required placeholder="Masukkan nama ruangan">
                                    <div class="form-text mt-2">
                                        <i class="bi bi-info-circle me-1"></i>Contoh: Lab Komputer 1, Ruang Multimedia, dll.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="lokasi" class="form-label form-label-modern">
                                        Lokasi <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-modern" id="lokasi" name="lokasi" 
                                           value="<?= isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : '' ?>" 
                                           required placeholder="Masukkan lokasi ruangan">
                                    <div class="form-text mt-2">
                                        <i class="bi bi-geo-alt me-1"></i>Contoh: Lantai 2 Gedung A, Lantai 1 Gedung B, dll.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="kapasitas" class="form-label form-label-modern">
                                        Kapasitas <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control form-control-modern" id="kapasitas" name="kapasitas" 
                                               value="<?= isset($_POST['kapasitas']) ? $_POST['kapasitas'] : '' ?>" 
                                               min="1" required placeholder="0" style="border-radius: 15px 0 0 15px;">
                                        <span class="input-group-text input-group-text-modern">
                                            <i class="bi bi-people me-1"></i>orang
                                        </span>
                                    </div>
                                    <div class="form-text mt-2">
                                        <i class="bi bi-calculator me-1"></i>Jumlah maksimal orang yang dapat menggunakan ruangan.
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="index.php" class="btn btn-secondary-modern btn-modern me-md-2">
                                        <i class="bi bi-x-circle"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary-modern btn-modern">
                                        <i class="bi bi-save"></i> Simpan Ruangan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="info-card fade-in-up animate-delay-3">
                        <h6 class="mb-3">
                            <i class="bi bi-lightbulb me-2"></i>Tips Pengisian Form
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="mb-0" style="list-style: none; padding: 0;">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle me-2"></i>Gunakan nama ruangan yang jelas dan mudah diidentifikasi
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle me-2"></i>Lokasi sebaiknya mencantumkan lantai dan gedung
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0" style="list-style: none; padding: 0;">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle me-2"></i>Kapasitas disesuaikan dengan kondisi fisik ruangan
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle me-2"></i>Ruangan otomatis tersedia setelah ditambahkan
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>