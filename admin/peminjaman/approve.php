<?php
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: index.php');
    exit();
}

$peminjaman_id = (int)$_GET['id'];
$action = $_GET['action'];

if (!in_array($action, ['approve', 'reject'])) {
    header('Location: index.php');
    exit();
}

try {
    // Ambil data peminjaman
    $stmt = $pdo->prepare("SELECT * FROM peminjaman_ruangan WHERE peminjaman_id = ? AND status = 'menunggu'");
    $stmt->execute([$peminjaman_id]);
    $peminjaman = $stmt->fetch();
    
    if (!$peminjaman) {
        $_SESSION['error'] = "Peminjaman tidak ditemukan atau sudah diproses!";
        header('Location: index.php');
        exit();
    }
    
    // Update status
    $new_status = ($action == 'approve') ? 'disetujui' : 'ditolak';
    
    // Jika approve, cek apakah ruangan tersedia
    if ($action == 'approve') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM peminjaman_ruangan 
                              WHERE ruangan_id = ? AND tanggal_pinjam = ? 
                              AND status = 'disetujui' AND peminjaman_id != ?
                              AND ((waktu_mulai <= ? AND waktu_selesai > ?) 
                                   OR (waktu_mulai < ? AND waktu_selesai >= ?))");
        $stmt->execute([
            $peminjaman['ruangan_id'], 
            $peminjaman['tanggal_pinjam'],
            $peminjaman_id,
            $peminjaman['waktu_mulai'], 
            $peminjaman['waktu_mulai'],
            $peminjaman['waktu_selesai'], 
            $peminjaman['waktu_selesai']
        ]);
        $conflict = $stmt->fetch();
        
        if ($conflict['count'] > 0) {
            $_SESSION['error'] = "Ruangan sudah digunakan pada waktu tersebut!";
            header('Location: index.php');
            exit();
        }
    }
    
    // Begin transaction untuk memastikan konsistensi data
    $pdo->beginTransaction();
    
    // Update status peminjaman
    $stmt = $pdo->prepare("UPDATE peminjaman_ruangan SET status = ?, updated_at = NOW() WHERE peminjaman_id = ?");
    $stmt->execute([$new_status, $peminjaman_id]);
    
    // Update status ruangan berdasarkan action
    if ($action == 'approve') {
        // Set status ruangan menjadi "dipakai" jika peminjaman disetujui
        $stmt = $pdo->prepare("UPDATE ruangan SET status = 'dipakai' WHERE ruangan_id = ?");
        $stmt->execute([$peminjaman['ruangan_id']]);
    } elseif ($action == 'reject') {
        // Set status ruangan menjadi "tersedia" jika peminjaman ditolak
        $stmt = $pdo->prepare("UPDATE ruangan SET status = 'tersedia' WHERE ruangan_id = ?");
        $stmt->execute([$peminjaman['ruangan_id']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    $message = ($action == 'approve') ? "Peminjaman berhasil disetujui dan ruangan diset sebagai dipakai!" : "Peminjaman berhasil ditolak dan ruangan diset sebagai tersedia!";
    $_SESSION['success'] = $message;
    
} catch(PDOException $e) {
    // Rollback transaction jika terjadi error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header('Location: index.php');
exit();
?>