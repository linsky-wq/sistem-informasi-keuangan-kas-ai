-- ==========================================================
-- Migration: Tambah Fitur Integrasi WhatsApp (WA)
-- Sistem Keuangan Kas dengan AI
-- External Supporting Actor / External Service
-- ==========================================================

USE `db_kas_ai`;

-- 1. Tabel whatsapp_settings (Penyimpanan konfigurasi aman)
CREATE TABLE IF NOT EXISTS `whatsapp_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_wa_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel whatsapp_logs (Log riwayat pengiriman notifikasi)
CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recipient_phone` VARCHAR(25) NOT NULL,
    `recipient_name` VARCHAR(100) DEFAULT NULL,
    `member_id` INT UNSIGNED DEFAULT NULL,
    `notification_type` ENUM(
        'pengingat_pembayaran',
        'konfirmasi_pembayaran',
        'status_verifikasi',
        'informasi_tagihan',
        'transaksi',
        'laporan_ringkasan',
        'test'
    ) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    `response_payload` TEXT DEFAULT NULL,
    `sent_by` INT UNSIGNED DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_wa_logs_status` (`status`),
    INDEX `idx_wa_logs_type` (`notification_type`),
    INDEX `idx_wa_logs_member` (`member_id`),
    INDEX `idx_wa_logs_phone` (`recipient_phone`),
    INDEX `idx_wa_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Default Initial Settings
INSERT IGNORE INTO `whatsapp_settings` (`setting_key`, `setting_value`) VALUES
('wa_enabled', '1'),
('wa_provider', 'mock'),
('wa_api_url', 'https://api.fonnte.com/send'),
('wa_api_token', ''),
('wa_sender_number', '081234567890'),
('wa_auto_notify_payment', '1'),
('wa_auto_notify_verification', '1');
