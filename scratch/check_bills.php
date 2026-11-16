<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$db = \App\Core\Database::getInstance()->getConnection();

echo "=== PAYMENTS FOR BUDI SANTOSO (MEMBER 2) ===\n";
$bills2 = $db->query("SELECT * FROM payments WHERE member_id = 2")->fetchAll(PDO::FETCH_ASSOC);
print_r($bills2);

echo "=== PAYMENTS FOR CITRA LESTARI (MEMBER 3) ===\n";
$bills3 = $db->query("SELECT * FROM payments WHERE member_id = 3")->fetchAll(PDO::FETCH_ASSOC);
print_r($bills3);
