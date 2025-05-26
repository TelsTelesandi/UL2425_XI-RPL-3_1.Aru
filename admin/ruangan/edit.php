<?php
session_start();
require_once '../../config/database.php';
require_once '../../auth/check_auth.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$error = '';
$success = '';

// Ambil ID ruangan dari parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$ruangan_id = (int)$_GET['id'];

// Ambil data ruangan
try {
    $stmt = $pdo->prepare("SELECT * FROM ruangan WHERE ruangan_id = ?");
    $stmt->execute([$ruangan_id]);
    $ruangan = $stmt->fetch();
    
    if (!$ruangan) {
        header('Location: index.php');
        exit();
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Proses form update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ruangan = $_POST['nama_ruangan'];
    $lokasi = $_POST['lokasi'];
    $kapasitas = (int)$_POST['kapasitas'];
    
    // Validasi
    if (empty($nama_ruangan) || empty($lokasi) || $kapasitas <= 0) {
        $error = "Semua field harus diisi dengan benar!";
    } else {
        try {
            // Cek apakah nama ruangan sudah ada (kecuali ruangan yang sedang diedit)
            $stmt = $pdo->prepare("SELECT ruangan_id FROM ruangan WHERE nama_ruangan = ? AND ruangan_id != ?");
            $stmt->execute([$nama_ruangan, $ruangan_id]);
            
            if ($stmt->fetch()) {
                $error = "Nama ruangan sudah ada!";
            } else {
                // Update ruangan
                $stmt = $pdo->prepare("UPDATE ruangan SET nama_ruangan = ?, lokasi = ?, kapasitas = ? WHERE ruangan_id = ?");
                $stmt->execute([$nama_ruangan, $lokasi, $kapasitas, $ruangan_id]);
                
                $success = "Ruangan berhasil diupdate!";
                
                // Update data ruangan untuk ditampilkan
                $ruangan['nama_ruangan'] = $nama_ruangan;
                $ruangan['lokasi'] = $lokasi;
                $ruangan['kapasitas'] = $kapasitas;
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>
<body>
 
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Ruangan</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nama_ruangan" class="form-label">Nama Ruangan</label>
                                <input type="text" class="form-control" id="nama_ruangan" name="nama_ruangan" 
                                       value="<?php echo htmlspecialchars($ruangan['nama_ruangan']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="lokasi" class="form-label">Lokasi</label>
                                <input type="text" class="form-control" id="lokasi" name="lokasi" 
                                       value="<?php echo htmlspecialchars($ruangan['lokasi']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kapasitas" class="form-label">Kapasitas</label>
                                <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                       value="<?php echo $ruangan['kapasitas']; ?>" min="1" required>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">Kembali</a>
                                <button type="submit" class="btn btn-primary">Update Ruangan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>