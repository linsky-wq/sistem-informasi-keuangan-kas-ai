-- ==========================================================
-- Migration: Tambah Role Bendahara & Pengawas
-- Sistem Keuangan Kas dengan AI
-- Jalankan di phpMyAdmin atau CLI MySQL
-- ==========================================================

USE `db_kas_ai`;

-- Step 1: Tambah nilai ENUM baru (aman, data existing tidak terganggu)
ALTER TABLE `users`
    MODIFY `role` ENUM('super_user', 'member', 'bendahara', 'pengawas')
    NOT NULL DEFAULT 'member';

-- Step 2: Verifikasi hasil perubahan
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'db_kas_ai'
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'role';

-- Step 3: Insert akun Bendahara (jika belum ada)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `status`)
VALUES (
    'bendahara',
    'bendahara@kas.local',
    '$2y$12$bBF.hRcG0eqVW1bVX9XdSuZ8uIr6IVcwM5Sp1yFmMFbQmF9nC9qge', -- 'bendahara123'
    'bendahara',
    'active'
);

-- Step 4: Insert akun Pengawas (jika belum ada)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `status`)
VALUES (
    'pengawas',
    'pengawas@kas.local',
    '$2y$12$bBF.hRcG0eqVW1bVX9XdSuZ8uIr6IVcwM5Sp1yFmMFbQmF9nC9qge', -- 'pengawas123'
    'pengawas',
    'active'
);

-- Catatan: Hash di atas adalah placeholder.
-- Gunakan seed script PHP untuk hash yang benar.
-- Password bendahara: bendahara123
-- Password pengawas: pengawas123
