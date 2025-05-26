<?php
// Fungsi untuk mengecek apakah user sudah login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('../auth/login.php');
    }
}

// Fungsi untuk mengecek role admin
function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] !== 'admin') {
        redirect('../index.php');
    }
}

// Fungsi untuk mengecek role user
function checkUser() {
    checkLogin();
    if ($_SESSION['role'] !== 'user') {
        redirect('../index.php');
    }
}

// Fungsi untuk mengecek apakah user belum login (untuk halaman login)
function checkNotLogin() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] == 'admin') {
            redirect('../admin/');
        } else {
            redirect('../user/');
        }
    }
}
?>