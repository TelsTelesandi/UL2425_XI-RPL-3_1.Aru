
-- -----------------------------------------------------
-- Database: PJR_Aru
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS PJR_ARU;
USE PJR_Aru;

-- -----------------------------------------------------
-- Table: user
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY,
    id_card VARCHAR(50),
    username VARCHAR(50),
    password VARCHAR(100),
    role VARCHAR(20),
    jenis_pengguna VARCHAR(50),
    nama_lengkap VARCHAR(100),
    created_at DATETIME
);

INSERT INTO users (user_id, id_card, username, password, role, jenis_pengguna, nama_lengkap, created_at) VALUES
(1, 'ADM001', 'admin', '0190203a7bbd73250516f069df18b500', 'admin', 'Admin', 'Administrator Sistem', '2025-05-23 08:33:00'),
(2, '12345678', 'siswa1', '3afa0d81296a4f17d47dec823261b1ec', 'user', 'Siswa', 'Ahmad Rizki', '2025-05-23 08:33:00'),
(3, '87654321', 'siswa2', 'f3aca4a61956bcedf28d382f40dfeac9', 'user', 'Siswa', 'Siti Nurhaliza', '2025-05-23 08:33:00'),
(4, 'GR001', 'guru1', '9310f83135f238b04f729fec041cac8', 'user', 'Guru', 'Budi Santoso, S.Pd', '2025-05-23 08:33:00'),
(5, 'GR002', 'guru2', '9310f83135f238b04f729fec041cac8', 'user', 'Guru', 'Dr. Andi Wijaya, M.Pd', '2025-05-23 08:33:00'),
(6, '313113213', 'Aruu', 'a190d0d1f77037ec1f73106ef4b329f8', 'user', 'siswa', 'Aru Yuri Rikira', '2025-05-26 08:28:23'),
(7, 'ID009', 'Ikuyoo', '7308dde3d8e77577856d248790730cf5', 'user', 'siswa', 'Kita Ikuy0', '2025-05-26 11:28:51');

-- -----------------------------------------------------
-- Table: ruangan
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS ruangan (
    ruangan_id INT PRIMARY KEY,
    nama_ruangan VARCHAR(100),
    lokasi VARCHAR(100),
    kapasitas INT,
    status VARCHAR(50),
    is_enabled BOOLEAN,
    created_at DATETIME
);

INSERT INTO ruangan (ruangan_id, nama_ruangan, lokasi, kapasitas, status, is_enabled, created_at) VALUES
(1, 'Lab Komputer 1', 'Lantai 2 Gedung A', 30, 'tidak_tersedia', 0, '2025-05-23 08:33:00'),
(3, 'Ruang Multimedia', 'Lantai 1 Gedung B', 40, 'tersedia', 1, '2025-05-23 08:33:00'),
(4, 'Aula Serbaguna', 'Lantai 1 Gedung C', 100, 'dipakai', 1, '2025-05-23 08:33:00'),
(5, 'Lab Bahasa', 'Lantai 3 Gedung A', 25, 'tersedia', 1, '2025-05-23 08:33:00'),
(8, 'Toilet', 'Gedung 1', 1, 'tersedia', 1, '2025-05-25 20:18:39'),
(9, 'Perpustakaan', 'Gedung 1', 25, 'tidak_tersedia', 0, '2025-05-25 20:20:37'),
(10, 'miaw', 'madada', 1, 'tidak_tersedia', 0, '2025-05-25 20:40:35'),
(11, '11 RPL 3', 'Gedung A', 30, 'tersedia', 1, '2025-05-26 11:25:58');

-- -----------------------------------------------------
-- Table: peminjaman
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS peminjaman_ruangan (
    peminjaman_id INT PRIMARY KEY,
    user_id INT,
    ruangan_id INT,
    tanggal_pinjam DATE,
    waktu_mulai VARCHAR(50),
    durasi_pinjam VARCHAR(50),
    waktu_selesai VARCHAR(50),
    status VARCHAR(50),
    keperluan TEXT,
    keterangan TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    is_enabled BOOLEAN,
    kondisi_ruangan VARCHAR(50),
    catatan_pengembalian TEXT,
    return_requested_at DATETIME,
    return_approved_at DATETIME,
    return_approved_by INT
);

INSERT INTO peminjaman_ruangan (
  peminjaman_id, user_id, ruangan_id, tanggal_pinjam, waktu_mulai, durasi_pinjam, waktu_selesai,
  status, keperluan, keterangan, created_at, updated_at, is_enabled,
  kondisi_ruangan, catatan_pengembalian, return_requested_at, return_approved_at, return_approved_by
) VALUES
(20, 6, 5, '2025-05-27', 'JP 1 (07:00-07:45)', '1 JP', 'JP 1 (07:00-07:45)', 'selesai', 'Untuk Presentasi', NULL,
 '2025-05-26 11:17:44', '2025-05-26 11:24:40', 1, 'Cukup Baik', 'baik', '2025-05-26 11:24:19', '2025-05-26 11:24:40', 1),
(19, 6, 4, '2025-05-26', 'JP 1 (07:00-07:45)', '1 JP', 'JP 1 (07:00-07:45)', 'disetujui', 'dadada', NULL,
 '2025-05-26 10:49:30', '2025-05-26 10:49:50', 1, NULL, NULL, NULL, NULL, NULL),
(18, 6, 4, '2025-05-28', 'JP 1 (07:00-07:45)', '2 JP', 'JP 2 (08:30)', 'selesai', 'sdsdfgfnfdsfa', NULL,
 '2025-05-26 10:39:31', '2025-05-26 10:47:19', 1, 'Baik', 'fdsfdsdasd', '2025-05-26 10:39:59', '2025-05-26 10:47:19', 1),
(17, 6, 4, '2025-05-28', 'JP 1 (07:00-07:45)', '1 JP', 'JP 1 (07:45)', 'selesai', 'dadad', NULL,
 '2025-05-26 10:37:19', '2025-05-26 10:38:07', 1, 'Baik', 'saffndfhgsfdaf', '2025-05-26 10:37:56', '2025-05-26 10:38:07', 1),
(16, 6, 4, '2025-05-28', 'JP 1 (07:00-07:45)', '2 JP', 'JP 2 (08:30)', 'selesai', 'sASASA', NULL,
 '2025-05-26 10:31:33', '2025-05-26 10:32:52', 1, 'Baik', 'CSADFFEQ', '2025-05-26 10:32:03', '2025-05-26 10:32:52', 1),
(15, 6, 5, '2025-05-27', 'JP 1 (07:00-07:45)', '2 JP', 'JP 2 (08:30)', 'selesai', 'daxad', NULL,
 '2025-05-26 10:26:21', '2025-05-26 10:28:34', 1, 'Baik', 'dafsaed', '2025-05-26 10:28:16', '2025-05-26 10:28:34', 1),
(14, 6, 3, '2025-05-28', 'JP 2 (07:45-08:30)', '2 JP', 'JP 3 (09:15)', 'selesai', 'dadada', NULL,
 '2025-05-26 09:23:21', '2025-05-26 10:17:37', 1, 'Cukup Baik', 'dadaada', '2025-05-26 09:24:01', '2025-05-26 10:17:37', 1),
(13, 6, 5, '2025-05-28', 'JP 1 (07:00-07:45)', '1 JP', 'JP 1 (07:45)', 'selesai', 'czczczc', NULL,
 '2025-05-26 08:38:57', '2025-05-26 09:16:25', 1, 'Cukup Baik', 'dadda', '2025-05-26 09:14:42', '2025-05-26 09:16:25', 1),
(12, 3, 8, '2025-05-26', 'JP 1 (07:00-07:45)', '2 JP', 'JP 2 (08:30)', 'selesai', 'dadada', NULL,
 '2025-05-26 07:39:39', '2025-05-26 10:27:46', 1, 'Cukup Baik', 'dsafddaf', '2025-05-26 10:27:12', '2025-05-26 10:27:46', 1),
(11, 3, 8, '2025-05-30', 'JP 1 (07:00-07:45)', '1 JP', 'JP 1 (07:45)', 'ditolak', 'daadada', NULL,
 '2025-05-26 07:38:23', '2025-05-26 07:38:57', 1, NULL, NULL, NULL, NULL, NULL);
