-- ==========================================================
-- Database Schema: Sistem Keuangan Kas dengan AI
-- Specification: Simple PRD - Task 02
-- Database Engine: MySQL / MariaDB (InnoDB, UTF8MB4)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `db_kas_ai` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_kas_ai`;

-- Drop tables in reverse foreign key order if needed for clean migration
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `ai_logs`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `members`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. Tabel users
-- ----------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `nama` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_user', 'member') NOT NULL DEFAULT 'member',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Tabel members
-- ----------------------------------------------------------
CREATE TABLE `members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `nomor_anggota` VARCHAR(50) NOT NULL UNIQUE,
    `nis` VARCHAR(50) UNIQUE DEFAULT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `kelas` VARCHAR(50) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_members_nama` (`nama`),
    INDEX `idx_members_kelas` (`kelas`),
    INDEX `idx_members_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Tabel categories
-- ----------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `tipe` ENUM('pemasukan', 'pengeluaran') NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_categories_tipe` (`tipe`),
    INDEX `idx_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Tabel transactions
-- ----------------------------------------------------------
CREATE TABLE `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(50) NOT NULL UNIQUE,
    `tanggal` DATE NOT NULL,
    `tipe` ENUM('pemasukan', 'pengeluaran') NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `nominal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `keterangan` TEXT NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_trx_tipe_tanggal` (`tipe`, `tanggal`),
    INDEX `idx_trx_category` (`category_id`),
    INDEX `idx_trx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. Tabel payments
-- ----------------------------------------------------------
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT UNSIGNED NOT NULL,
    `periode` VARCHAR(50) NOT NULL,
    `tagihan` DECIMAL(15, 2) NOT NULL DEFAULT 50000.00,
    `tanggal_pembayaran` DATE NOT NULL,
    `nominal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('Belum Bayar', 'Sebagian', 'Lunas') NOT NULL DEFAULT 'Belum Bayar',
    `verified_by` INT UNSIGNED DEFAULT NULL,
    `verified_at` DATETIME DEFAULT NULL,
    `catatan` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_payments_member` (`member_id`),
    INDEX `idx_payments_status` (`status`),
    INDEX `idx_payments_periode` (`periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. Tabel ai_logs
-- ----------------------------------------------------------
CREATE TABLE `ai_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `question` TEXT NOT NULL,
    `response` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_ai_logs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. Tabel audit_logs
-- ----------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `old_data` JSON DEFAULT NULL,
    `new_data` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_entity_action` (`entity`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. Tabel payment_settings
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payment_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. Tabel payment_transactions
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `kode_transaksi` VARCHAR(50) NOT NULL UNIQUE,
    `metode` ENUM('transfer_bank', 'tunai', 'qris', 'ewallet', 'lainnya') NOT NULL DEFAULT 'transfer_bank',
    `channel` VARCHAR(50) DEFAULT NULL,
    `nominal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('menunggu_pembayaran', 'menunggu_verifikasi', 'berhasil', 'ditolak', 'gagal', 'kedaluwarsa') NOT NULL DEFAULT 'menunggu_verifikasi',
    `bukti_transfer` VARCHAR(255) DEFAULT NULL,
    `catatan` TEXT DEFAULT NULL,
    `alasan_penolakan` TEXT DEFAULT NULL,
    `gateway_reference` VARCHAR(100) DEFAULT NULL,
    `gateway_payload` LONGTEXT DEFAULT NULL,
    `transaction_id` INT UNSIGNED DEFAULT NULL,
    `verified_by` INT UNSIGNED DEFAULT NULL,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_paytrx_payment` (`payment_id`),
    INDEX `idx_paytrx_member` (`member_id`),
    INDEX `idx_paytrx_status` (`status`),
    INDEX `idx_paytrx_gateway` (`gateway_reference`),
    INDEX `idx_paytrx_trx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

