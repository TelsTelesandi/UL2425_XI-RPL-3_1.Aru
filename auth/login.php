<?php
require_once '../config/database.php';
require_once 'check_auth.php';

checkNotLogin();

$error = '';
$success = '';

// Handle Register
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $username = trim($_POST['reg_username']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['reg_confirm_password'];
    $nama_lengkap = trim($_POST['reg_nama_lengkap']);
    $id_card = trim($_POST['reg_id_card']);
    
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nama_lengkap) || empty($id_card)) {
        $error = 'Semua field harus diisi!';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan!';
            } else {
                // Check if ID Card already exists
                $stmt = $pdo->prepare("SELECT id_card FROM users WHERE id_card = ?");
                $stmt->execute([$id_card]);
                
                if ($stmt->fetch()) {
                    $error = 'Nomor ID/NIM sudah terdaftar!';
                } else {
                    // Insert new user
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, id_card, role, jenis_pengguna) VALUES (?, ?, ?, ?, 'user', 'siswa')");
                    $stmt->execute([$username, md5($password), $nama_lengkap, $id_card]);
                    
                    $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem saat mendaftar!';
        }
    }
}

// Handle Login
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && md5($password) === $user['password']) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['jenis_pengguna'] = $user['jenis_pengguna'];
                $_SESSION['id_card'] = $user['id_card'];
                
                if ($user['role'] == 'admin') {
                    redirect('../admin/');
                } else {
                    redirect('../user/');
                }
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem!';
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
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
        
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-header {
            background: var(--primary-gradient);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-floating {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 15px 50px 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.2rem;
            z-index: 5;
        }
        
        .password-toggle {
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .btn-login, .btn-register {
            background: var(--primary-gradient);
            border: none;
            border-radius: 15px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover, .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register {
            background: var(--success-gradient);
        }
        
        .alert-modern {
            border: none;
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-danger-modern {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .alert-success-modern {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }
        
        .demo-info {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 15px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .demo-account {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .demo-account:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .demo-account strong {
            color: #667eea;
        }
        
        .demo-account span {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 20%, 40%, 60%, 80% {
                transform: translateX(-5px);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(5px);
            }
        }
        
        .loading {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }
        
        .modal-header {
            background: var(--success-gradient);
            color: white;
            border: none;
            padding: 25px 30px;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>

<div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
</div>

<div class="login-container">
    <div class="login-card">
        <div class="card-header">
            <h4><i class="bi bi-shield-lock"></i> Masuk Sistem</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-modern alert-danger-modern" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-modern alert-success-modern" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                           required>
                    <label for="username">Username</label>
                    <i class="bi bi-person-fill input-icon"></i>
                </div>
                
                <div class="form-floating position-relative">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                    <i class="bi bi-lock-fill input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye-fill" id="toggleIcon"></i>
                    </button>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                    </button>
                </div>
            </form>
            
            <div class="register-link">
                <p class="mb-2">Belum punya akun?</p>
                <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                    <i class="bi bi-person-plus-fill me-1"></i>Daftar Sekarang
                </a>
            </div>
            
            <div class="demo-info">
                <div class="text-center mb-3">
                    <i class="bi bi-info-circle-fill text-primary"></i>
                    <strong style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"> Akun Demo</strong>
                </div>
                
                <div class="demo-account" onclick="fillLogin('admin', 'admin123')">
                    <strong>Administrator</strong>
                    <span>admin / admin123</span>
                </div>
                
                <div class="demo-account" onclick="fillLogin('siswa1', 'siswa123')">
                    <strong>Pengguna</strong>
                    <span>siswa1 / siswa123</span>
                </div>
                
                <small class="text-muted d-block text-center mt-2">
                    <i class="bi bi-hand-index-fill"></i> Klik untuk mengisi otomatis
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Daftar Akun Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-floating">
                        <input type="text" class="form-control" id="reg_nama_lengkap" name="reg_nama_lengkap" 
                               placeholder="Nama Lengkap" required>
                        <label for="reg_nama_lengkap">Nama Lengkap</label>
                        <i class="bi bi-person-badge-fill input-icon"></i>
                    </div>
                    
                    <div class="form-floating">
                        <input type="text" class="form-control" id="reg_id_card" name="reg_id_card" 
                               placeholder="Nomor ID/NIM" required>
                        <label for="reg_id_card">Nomor ID/NIM</label>
                        <i class="bi bi-card-text input-icon"></i>
                    </div>
                    
                    <div class="form-floating">
                        <input type="text" class="form-control" id="reg_username" name="reg_username" 
                               placeholder="Username" required minlength="4">
                        <label for="reg_username">Username</label>
                        <i class="bi bi-person-fill input-icon"></i>
                    </div>
                    
                    <div class="form-floating position-relative">
                        <input type="password" class="form-control" id="reg_password" name="reg_password" 
                               placeholder="Password" required minlength="6">
                        <label for="reg_password">Password</label>
                        <i class="bi bi-lock-fill input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePasswordReg()">
                            <i class="bi bi-eye-fill" id="toggleIconReg"></i>
                        </button>
                    </div>
                    
                    <div class="form-floating position-relative">
                        <input type="password" class="form-control" id="reg_confirm_password" name="reg_confirm_password" 
                               placeholder="Konfirmasi Password" required minlength="6">
                        <label for="reg_confirm_password">Konfirmasi Password</label>
                        <i class="bi bi-shield-check-fill input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePasswordConfirm()">
                            <i class="bi bi-eye-fill" id="toggleIconConfirm"></i>
                        </button>
                    </div>
                    
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="bi bi-person-plus-fill me-2"></i>Daftar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash-fill';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye-fill';
    }
    
    // Add bounce effect
    toggleIcon.style.transform = 'scale(1.2)';
    setTimeout(() => {
        toggleIcon.style.transform = 'scale(1)';
    }, 150);
}

function togglePasswordReg() {
    const passwordInput = document.getElementById('reg_password');
    const toggleIcon = document.getElementById('toggleIconReg');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash-fill';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye-fill';
    }
    
    toggleIcon.style.transform = 'scale(1.2)';
    setTimeout(() => {
        toggleIcon.style.transform = 'scale(1)';
    }, 150);
}

function togglePasswordConfirm() {
    const passwordInput = document.getElementById('reg_confirm_password');
    const toggleIcon = document.getElementById('toggleIconConfirm');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash-fill';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye-fill';
    }
    
    toggleIcon.style.transform = 'scale(1.2)';
    setTimeout(() => {
        toggleIcon.style.transform = 'scale(1)';
    }, 150);
}

function fillLogin(username, password) {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    // Clear fields first
    usernameField.value = '';
    passwordField.value = '';
    
    // Animate typing effect
    animateTyping(usernameField, username, () => {
        animateTyping(passwordField, password);
    });
    
    // Add visual feedback
    const clickedAccount = event.currentTarget;
    clickedAccount.style.transform = 'scale(0.95)';
    setTimeout(() => {
        clickedAccount.style.transform = 'scale(1)';
    }, 150);
}

// Add form validation animation for login
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('.btn-login');
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        e.preventDefault();
        
        // Shake animation for empty fields
        if (!username) {
            document.getElementById('username').classList.add('shake');
            setTimeout(() => document.getElementById('username').classList.remove('shake'), 500);
        }
        if (!password) {
            document.getElementById('password').classList.add('shake');
            setTimeout(() => document.getElementById('password').classList.remove('shake'), 500);
        }
        return;
    }
    
    // Loading state with spinner
    submitBtn.innerHTML = '<span class="loading"></span> Memproses...';
    submitBtn.disabled = true;
});

// Add form validation animation for register
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('.btn-register');
    const password = document.getElementById('reg_password').value;
    const confirmPassword = document.getElementById('reg_confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        
        // Shake animation for password mismatch
        document.getElementById('reg_confirm_password').classList.add('shake');
        setTimeout(() => document.getElementById('reg_confirm_password').classList.remove('shake'), 500);
        
        // Show error in field
        document.getElementById('reg_confirm_password').style.borderColor = '#dc3545';
        setTimeout(() => {
            document.getElementById('reg_confirm_password').style.borderColor = '#e9ecef';
        }, 2000);
        return;
    }
    
    // Loading state with spinner
    submitBtn.innerHTML = '<span class="loading"></span> Mendaftar...';
    submitBtn.disabled = true;
});

// Auto-focus on username field with delay
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.getElementById('username').focus();
    }, 500);
});

// Enhanced typing animation
let isTyping = false;
function animateTyping(element, text, callback) {
    if (isTyping) return;
    isTyping = true;
    
    element.value = '';
    element.focus();
    let i = 0;
    const interval = setInterval(() => {
        element.value += text[i];
        
        // Trigger input event to show floating label
        element.dispatchEvent(new Event('input'));
        
        i++;
        if (i >= text.length) {
            clearInterval(interval);
            isTyping = false;
            if (callback) {
                setTimeout(callback, 300);
            }
        }
    }, 80);
}

// Add hover effects for form fields
document.querySelectorAll('.form-control').forEach(field => {
    field.addEventListener('mouseenter', function() {
        this.style.borderColor = '#667eea';
    });
    
    field.addEventListener('mouseleave', function() {
        if (!this.matches(':focus')) {
            this.style.borderColor = '#e9ecef';
        }
    });
});

// Add ripple effect to demo accounts
document.querySelectorAll('.demo-account').forEach(account => {
    account.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.style.position = 'absolute';
        ripple.style.borderRadius = '50%';
        ripple.style.background = 'rgba(255, 255, 255, 0.5)';
        ripple.style.transform = 'scale(0)';
        ripple.style.animation = 'ripple 0.6s linear';
        ripple.style.pointerEvents = 'none';
        
        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// Password strength indicator for register
document.getElementById('reg_password').addEventListener('input', function() {
    const password = this.value;
    const strength = getPasswordStrength(password);
    
    // Remove existing strength indicator
    const existingIndicator = this.parentNode.querySelector('.password-strength');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    if (password.length > 0) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength';
        indicator.style.cssText = `
            position: absolute;
            bottom: -25px;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 2px;
            background: ${strength.color};
            width: ${strength.width}%;
            transition: all 0.3s ease;
        `;
        
        this.parentNode.appendChild(indicator);
    }
});

function getPasswordStrength(password) {
    let score = 0;
    if (password.length >= 6) score++;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    const strengths = [
        { width: 20, color: '#dc3545' },  // Very weak
        { width: 40, color: '#fd7e14' },  // Weak
        { width: 60, color: '#ffc107' },  // Fair
        { width: 80, color: '#198754' },  // Good
        { width: 100, color: '#20c997' }  // Strong
    ];
    
    return strengths[Math.min(score, 4)];
}

// Real-time password confirmation check
document.getElementById('reg_confirm_password').addEventListener('input', function() {
    const password = document.getElementById('reg_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            this.style.borderColor = '#198754';
        } else {
            this.style.borderColor = '#dc3545';
        }
    } else {
        this.style.borderColor = '#e9ecef';
    }
});

// Add ripple animation
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Auto-close modal on successful registration
<?php if ($success): ?>
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        if (modal) {
            modal.hide();
        }
    }, 2000);
<?php endif; ?>

// Clear modal form when closed
document.getElementById('registerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('registerForm').reset();
    
    // Remove password strength indicators
    document.querySelectorAll('.password-strength').forEach(el => el.remove());
    
    // Reset field border colors
    document.querySelectorAll('#registerModal .form-control').forEach(field => {
        field.style.borderColor = '#e9ecef';
    });
    
    // Reset button
    const submitBtn = document.querySelector('.btn-register');
    submitBtn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Daftar';
    submitBtn.disabled = false;
});

// Username availability check (optional enhancement)
let usernameTimeout;
document.getElementById('reg_username').addEventListener('input', function() {
    const username = this.value.trim();
    
    clearTimeout(usernameTimeout);
    
    if (username.length >= 4) {
        usernameTimeout = setTimeout(() => {
            // You can add AJAX call here to check username availability
            // For now, just visual feedback
            if (username.length >= 4) {
                this.style.borderColor = '#198754';
            }
        }, 500);
    } else {
        this.style.borderColor = '#e9ecef';
    }
});
</script>

</body>
</html>