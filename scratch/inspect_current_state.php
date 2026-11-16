<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$db = \App\Core\Database::getInstance()->getConnection();

echo "=== USERS ===\n";
$users = $db->query("SELECT id, username, email, role, status FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['id']} | Username: {$u['username']} | Email: {$u['email']} | Role: {$u['role']} | Status: {$u['status']}\n";
}

echo "\n=== MEMBERS ===\n";
$members = $db->query("SELECT id, user_id, nomor_anggota, nis, nama, kelas, status FROM members ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($members as $m) {
    echo "ID: {$m['id']} | UserID: " . ($m['user_id'] ?? 'NULL') . " | No: {$m['nomor_anggota']} | Nama: {$m['nama']} | Kelas: {$m['kelas']}\n";
}

echo "\n=== PAYMENTS TABLE STRUCTURE ===\n";
$cols = $db->query("DESCRIBE payments")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "Field: {$c['Field']} | Type: {$c['Type']}\n";
}

echo "\n=== BILLS PER MEMBER ===\n";
$bills = $db->query("SELECT member_id, COUNT(*) as cnt, SUM(nominal) as total_nominal FROM payments GROUP BY member_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($bills as $b) {
    echo "MemberID: {$b['member_id']} | Bills: {$b['cnt']} | Total Nominal: {$b['total_nominal']}\n";
}
