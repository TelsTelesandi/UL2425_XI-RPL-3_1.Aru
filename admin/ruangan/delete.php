<?php
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$ruangan_id = (int)$_GET['id'];

try {
    // Cek apakah ruangan exists dan masih aktif
    $stmt = $pdo->prepare("SELECT nama_ruangan, is_enabled FROM ruangan WHERE ruangan_id = ?");
    $stmt->execute([$ruangan_id]);
    $ruangan = $stmt->fetch();
    
    if (!$ruangan) {
        $_SESSION['error'] = "Ruangan tidak ditemukan!";
        header('Location: index.php');
        exit();
    }
    
    if ($ruangan['is_enabled'] == 0) {
        $_SESSION['error'] = "Ruangan '{$ruangan['nama_ruangan']}' sudah tidak aktif!";
        header('Location: index.php');
        exit();
    }
    
    // Cek apakah ruangan sedang digunakan dalam peminjaman aktif
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count,
               GROUP_CONCAT(CONCAT(tanggal_pinjam, ' (', waktu_mulai, '-', waktu_selesai, ')') SEPARATOR ', ') as booking_details
        FROM peminjaman_ruangan 
        WHERE ruangan_id = ? 
        AND status IN ('menunggu', 'disetujui')
        AND is_enabled = 1
    ");
    $stmt->execute([$ruangan_id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $booking_details = $result['booking_details'];
        $_SESSION['error'] = "Ruangan '{$ruangan['nama_ruangan']}' tidak dapat dihapus karena sedang digunakan dalam peminjaman aktif: {$booking_details}";
    } else {
        // Soft delete: set is_enabled = 0 instead of physically deleting
        $stmt = $pdo->prepare("UPDATE ruangan SET is_enabled = 0, status = 'tidak_tersedia' WHERE ruangan_id = ?");
        $stmt->execute([$ruangan_id]);
        
        if ($stmt->rowCount() > 0) {
            // Also soft delete related bookings that are pending or waiting
            $stmt = $pdo->prepare("
                UPDATE peminjaman_ruangan 
                SET is_enabled = 0, status = 'dibatalkan' 
                WHERE ruangan_id = ? 
                AND status IN ('menunggu') 
            ");
            $stmt->execute([$ruangan_id]);
            
            $_SESSION['success'] = "Ruangan '{$ruangan['nama_ruangan']}' berhasil dihapus! Peminjaman yang masih menunggu juga dibatalkan.";
        } else {
            $_SESSION['error'] = "Gagal menghapus ruangan!";
        }
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header('Location: index.php');
exit();
?>