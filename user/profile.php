<?php
// user/profile.php
session_start();
require_once '../config/database.php';
require_once '../auth/check_auth.php';

// Cek apakah user sudah login dan bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($nama_lengkap) || empty($username)) {
        $error = "Nama lengkap dan username wajib diisi!";
    } else {
        // Cek apakah username sudah digunakan user lain
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $user_id]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Update data dasar
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, username = ? WHERE user_id = ?");
            $stmt->execute([$nama_lengkap, $username, $user_id]);
            
            // Update password jika diisi
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = "Password saat ini wajib diisi untuk mengubah password!";
                } elseif (md5($current_password) !== $user['password']) {
                    $error = "Password saat ini tidak benar!";
                } elseif ($new_password !== $confirm_password) {
                    $error = "Konfirmasi password tidak cocok!";
                } elseif (strlen($new_password) < 6) {
                    $error = "Password baru minimal 6 karakter!";
                } else {
                    $new_password_hash = md5($new_password);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$new_password_hash, $user_id]);
                    $message = "Profil dan password berhasil diperbarui!";
                }
            } else {
                $message = "Profil berhasil diperbarui!";
            }
            
            // Refresh data user
            if (empty($error)) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            }
        }
    }
}

// Ambil statistik peminjaman user
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
    FROM peminjaman_ruangan 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$title = "Profil Saya";
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

    .card-header-modern h5, .card-header-modern h6 {
        margin: 0;
        font-size: 1.125rem;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
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

    .btn-secondary-modern {
        background: var(--dark-gradient);
        color: white;
    }

    .btn-secondary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(44, 62, 80, 0.4);
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

    .form-control-modern {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        outline: none;
    }

    .form-control-modern[readonly] {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
    }

    .profile-avatar {
        background: var(--primary-gradient);
        color: white;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin: 0 auto var(--spacing-md);
        box-shadow: var(--card-shadow);
    }

    .progress-modern {
        height: 8px;
        border-radius: 10px;
        background: #f8f9fa;
        overflow: hidden;
        margin-top: var(--spacing-xs);
    }

    .progress-bar-modern {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .progress-bar-warning {
        background: var(--warning-gradient);
    }

    .progress-bar-success {
        background: var(--success-gradient);
    }

    .progress-bar-danger {
        background: var(--danger-gradient);
    }

    .progress-bar-secondary {
        background: var(--dark-gradient);
    }

    .alert-modern {
        border: none;
        border-radius: var(--border-radius);
        padding: var(--spacing-md);
        margin-bottom: var(--spacing-md);
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .alert-success-modern {
        background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%);
        color: #0f5132;
        border-left: 4px solid #11998e;
    }

    .alert-danger-modern {
        background: linear-gradient(135deg, rgba(250, 112, 154, 0.1) 0%, rgba(254, 225, 64, 0.1) 100%);
        color: #842029;
        border-left: 4px solid #fa709a;
    }

    .alert-info-modern {
        background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
        color: #055160;
        border-left: 4px solid #4facfe;
    }
    /* Matikan fitur toggle password bawaan Bootstrap */
        input[type="password"]::-ms-reveal {
            display: none;
        }

        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none !important;
            visibility: hidden;
            pointer-events: none;
            position: absolute;
            right: 0;
        }

        /* Untuk browser Edge/IE */
        input[type="password"]::-ms-clear {
            display: none;
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
    }
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="dashboard-header fade-in-up">
        <h1 class="dashboard-title">Profil Saya</h1>
        <p class="dashboard-subtitle">Kelola informasi dan pengaturan akun Anda</p>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="content-card fade-in-up animate-delay-1">
                <div class="card-body text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h5 class="mb-2"><?= htmlspecialchars($user['nama_lengkap']) ?></h5>
                    <p class="text-muted mb-3">
                        <?= htmlspecialchars($user['jenis_pengguna']) ?><br>
                        <small><?= htmlspecialchars($user['id_card']) ?></small>
                    </p>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stats-card stats-card-primary">
                                <div class="stats-card-body">
                                    <h4 class="stats-number"><?= $stats['total'] ?></h4>
                                    <p class="stats-label">Total Peminjaman</p>
                                </div>
                                <i class="fas fa-clipboard-list stats-icon"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card stats-card-success">
                                <div class="stats-card-body">
                                    <h4 class="stats-number"><?= $stats['selesai'] ?></h4>
                                    <p class="stats-label">Selesai</p>
                                </div>
                                <i class="fas fa-check-circle stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="content-card fade-in-up animate-delay-2">
                <div class="card-header-modern">
                    <h6><i class="fas fa-chart-bar"></i> Statistik Peminjaman</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Menunggu</span>
                            <span class="badge-modern badge-warning"><?= $stats['menunggu'] ?></span>
                        </div>
                        <div class="progress-modern">
                            <div class="progress-bar-modern progress-bar-warning" style="width: <?= $stats['total'] > 0 ? ($stats['menunggu']/$stats['total']*100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Disetujui</span>
                            <span class="badge-modern badge-success"><?= $stats['disetujui'] ?></span>
                        </div>
                        <div class="progress-modern">
                            <div class="progress-bar-modern progress-bar-success" style="width: <?= $stats['total'] > 0 ? ($stats['disetujui']/$stats['total']*100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Ditolak</span>
                            <span class="badge-modern badge-danger"><?= $stats['ditolak'] ?></span>
                        </div>
                        <div class="progress-modern">
                            <div class="progress-bar-modern progress-bar-danger" style="width: <?= $stats['total'] > 0 ? ($stats['ditolak']/$stats['total']*100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Selesai</span>
                            <span class="badge-modern badge-secondary"><?= $stats['selesai'] ?></span>
                        </div>
                        <div class="progress-modern">
                            <div class="progress-bar-modern progress-bar-secondary" style="width: <?= $stats['total'] > 0 ? ($stats['selesai']/$stats['total']*100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Edit Profile Form -->
            <div class="content-card fade-in-up animate-delay-3">
                <div class="card-header-modern">
                    <h5><i class="fas fa-edit"></i> Edit Profil</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert-modern alert-success-modern">
                            <i class="fas fa-check-circle"></i>
                            <span><?= $message ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert-modern alert-danger-modern">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?= $error ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_card" class="form-label fw-semibold">ID Card</label>
                                    <input type="text" class="form-control form-control-modern" id="id_card" value="<?= htmlspecialchars($user['id_card']) ?>" readonly>
                                    <div class="form-text">ID Card tidak dapat diubah</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jenis_pengguna" class="form-label fw-semibold">Jenis Pengguna</label>
                                    <input type="text" class="form-control form-control-modern" id="jenis_pengguna" value="<?= htmlspecialchars($user['jenis_pengguna']) ?>" readonly>
                                    <div class="form-text">Jenis pengguna tidak dapat diubah</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-modern" id="nama_lengkap" name="nama_lengkap" 
                                   value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-modern" id="username" name="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted mb-3">Ubah Password (Opsional)</h6>
                        <div class="alert-modern alert-info-modern mb-4">
                            <i class="fas fa-info-circle"></i>
                            <span>Kosongkan field password jika tidak ingin mengubah password.</span>
                        </div>

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Password Saat Ini</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-modern" id="current_password" name="current_password" style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold">Password Baru</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-modern" id="new_password" name="new_password" style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                            <i class="fas fa-eye" id="new_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimal 6 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-modern" id="confirm_password" name="confirm_password" style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <a href="index.php" class="btn btn-secondary-modern btn-modern">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary-modern btn-modern">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validation untuk konfirmasi password
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword && confirmPassword !== '') {
        this.classList.add('is-invalid');
        if (!document.getElementById('password_mismatch_error')) {
            const errorDiv = document.createElement('div');
            errorDiv.id = 'password_mismatch_error';
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = 'Password tidak cocok';
            this.parentNode.appendChild(errorDiv);
        }
    } else {
        this.classList.remove('is-invalid');
        const errorDiv = document.getElementById('password_mismatch_error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
});

// Validation untuk password baru
document.getElementById('new_password').addEventListener('input', function() {
    if (this.value.length > 0 && this.value.length < 6) {
        this.classList.add('is-invalid');
        if (!document.getElementById('password_length_error')) {
            const errorDiv = document.createElement('div');
            errorDiv.id = 'password_length_error';
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = 'Password minimal 6 karakter';
            this.parentNode.appendChild(errorDiv);
        }
    } else {
        this.classList.remove('is-invalid');
        const errorDiv = document.getElementById('password_length_error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    // Trigger konfirmasi password validation
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value !== '') {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});

// Add smooth animations on page load
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.fade-in-up');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php include '../includes/footer.php'; ?>