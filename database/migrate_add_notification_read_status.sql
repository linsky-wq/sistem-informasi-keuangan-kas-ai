-- ==========================================================
-- Migration: Tambah Status Baca dan Detail Log Notifikasi WhatsApp
-- Untuk Portal Notifikasi Anggota & Riwayat Pesan WhatsApp
-- ==========================================================

USE `db_kas_ai`;

ALTER TABLE `whatsapp_logs`
  ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN `read_at` DATETIME DEFAULT NULL AFTER `is_read`,
  ADD COLUMN `provider_message_id` VARCHAR(255) DEFAULT NULL AFTER `read_at`,
  ADD COLUMN `delivered_at` DATETIME DEFAULT NULL AFTER `provider_message_id`,
  ADD COLUMN `error_message` TEXT DEFAULT NULL AFTER `delivered_at`;

CREATE INDEX `idx_wa_logs_member_read` ON `whatsapp_logs` (`member_id`, `is_read`);
CREATE INDEX `idx_wa_logs_provider_msg` ON `whatsapp_logs` (`provider_message_id`);
