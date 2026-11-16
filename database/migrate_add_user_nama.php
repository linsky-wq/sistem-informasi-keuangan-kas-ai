<?php
/**
 * Migration: Add nama column to users table if not exists
 */

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/core/Database.php';

$db = \App\Core\Database::getInstance()->getConnection();

$check = $db->query("SHOW COLUMNS FROM users LIKE 'nama'")->fetch(PDO::FETCH_ASSOC);
if (!$check) {
    echo "Adding `nama` column to `users` table...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `nama` VARCHAR(100) NULL DEFAULT NULL AFTER `username`");
    echo "✓ Column `nama` added successfully.\n";
} else {
    echo "✓ Column `nama` already exists in `users` table.\n";
}

// Check current admin row
$admin = $db->query("SELECT id, username, nama, email, role, status FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
echo "Admin current data:\n";
print_r($admin);
