<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$db = \App\Core\Database::getInstance()->getConnection();
$users = $db->query("SELECT id, username, password FROM users WHERE id IN (2, 3, 4)")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    $match = password_verify('member123', $u['password']) ? 'YES (member123)' : 'NO';
    echo "User {$u['id']} ({$u['username']}): password_verify('member123') => {$match}\n";
}
