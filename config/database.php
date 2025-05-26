<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'peminjaman_ruangan');

// Koneksi ke database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk mengecek koneksi
function checkConnection($pdo) {
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL
$base_url = "http://localhost/peminjaman_ruangan";

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndo($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $date = date_create($tanggal);
    $tgl = date_format($date, 'd');
    $bln = $bulan[(int)date_format($date, 'm')];
    $thn = date_format($date, 'Y');
    
    return $tgl . ' ' . $bln . ' ' . $thn;
}

// Fungsi untuk mendapatkan status badge
function getStatusBadge($status) {
    switch($status) {
        case 'menunggu':
            return '<span class="badge bg-warning text-dark">Menunggu</span>';
        case 'disetujui':
            return '<span class="badge bg-success">Disetujui</span>';
        case 'ditolak':
            return '<span class="badge bg-danger">Ditolak</span>';
        case 'selesai':
            return '<span class="badge bg-secondary">Selesai</span>';
        default:
            return '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>';
    }
}

// Fungsi untuk mendapatkan jam pelajaran
function getJamPelajaran() {
    return [
        'JP 1' => '07:00 - 07:45',
        'JP 2' => '07:45 - 08:30',
        'JP 3' => '08:30 - 09:15',
        'JP 4' => '09:30 - 10:15',
        'JP 5' => '10:15 - 11:00',
        'JP 6' => '11:00 - 11:45',
        'JP 7' => '12:30 - 13:15',
        'JP 8' => '13:15 - 14:00',
        'JP 9' => '14:00 - 14:45',
        'JP 10' => '15:00 - 15:45'
    ];
}
?>