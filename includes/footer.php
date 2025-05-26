<!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sistem Peminjaman Ruangan - Sekolah A. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= $base_url ?>/assets/js/custom.js"></script>
    
    <!-- Additional Scripts -->
    <script>
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);

        // Confirm delete actions
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
            return confirm(message);
        }

        // Date validation
        function validateDate() {
            var dateInput = document.getElementById('tanggal_pinjam');
            if (dateInput) {
                var today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
            }
        }

        // Initialize date validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            validateDate();
        });
    </script>
</body>
</html>