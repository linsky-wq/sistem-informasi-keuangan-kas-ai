<?php
/**
 * Entry Point - Sistem Keuangan Kas dengan AI
 * MVC Front Controller
 */

declare(strict_types=1);

// Error reporting configuration based on environment
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Require configuration
require_once __DIR__ . '/app/config/config.php';

// Register PSR-4 Autoloader for App namespace
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    
    // Map namespace directories to lowercase directory names
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    
    $file = $baseDir . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize session
\App\Core\Session::start();

// Launch Application
new \App\Core\App();
