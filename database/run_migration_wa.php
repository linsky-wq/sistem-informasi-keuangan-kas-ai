<?php
/**
 * Migration Runner for WhatsApp Tables
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

// PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    if (count($parts) > 1) $parts[0] = strtolower($parts[0]);
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "Executing database/migrate_add_whatsapp.sql...\n";
$db = \App\Core\Database::getInstance()->getConnection();
if (!$db) {
    echo "Database connection failed: " . \App\Core\Database::getInstance()->getError() . "\n";
    exit(1);
}

$sql = file_get_contents(__DIR__ . '/migrate_add_whatsapp.sql');
try {
    $db->exec($sql);
    echo "✓ Migration migrate_add_whatsapp.sql executed successfully!\n";
} catch (\PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
