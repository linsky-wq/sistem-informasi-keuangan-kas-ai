<?php
/**
 * Configuration & Environment Loader
 * Sistem Keuangan Kas AI
 */

// Load .env if present
$envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv(sprintf('%s=%s', $key, $value));
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Environment helper function
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        return match (strtolower($val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $val,
        };
    }
}

// Application Constants
define('APP_NAME', env('APP_NAME', 'Sistem Keuangan Kas AI'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', true));
define('APP_ROOT', dirname(__DIR__));
define('ROOT_PATH', dirname(__DIR__, 2));

// Dynamic Base URL Auto-detection
if (!function_exists('detect_base_url')) {
    function detect_base_url(): string {
        $envUrl = env('APP_URL');
        if (!empty($envUrl) && $envUrl !== 'http://localhost/Project%20RPL/Project%20Kas') {
            return rtrim($envUrl, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($scriptName);
        $dir = str_replace('\\', '/', $dir);
        $dir = ($dir === '/' || $dir === '.') ? '' : $dir;

        return rtrim("$scheme://$host$dir", '/');
    }
}

define('BASE_URL', detect_base_url());

// Database Credentials
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'db_kas_ai'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// AI API Configuration
define('GEMINI_API_KEY', (string)env('GEMINI_API_KEY', ''));

// WhatsApp Gateway Configuration (External Supporting Service)
define('WA_ENABLED', (bool)env('WA_ENABLED', true));
define('WA_PROVIDER', (string)env('WA_PROVIDER', 'mock'));
define('WA_API_URL', (string)env('WA_API_URL', 'https://api.fonnte.com/send'));
define('WA_API_TOKEN', (string)env('WA_API_TOKEN', ''));
define('WA_SENDER_NUMBER', (string)env('WA_SENDER_NUMBER', '081234567890'));
define('WA_AUTO_NOTIFY_PAYMENT', (bool)env('WA_AUTO_NOTIFY_PAYMENT', true));
define('WA_AUTO_NOTIFY_VERIFICATION', (bool)env('WA_AUTO_NOTIFY_VERIFICATION', true));

// Helper URL functions
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $path = ltrim($path, '/');
        return BASE_URL . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('current_route')) {
    function current_route(): string {
        if (isset($_GET['url']) && trim($_GET['url']) !== '') {
            return trim(filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL), '/');
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (false !== ($pos = strpos($uri, '?'))) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);
        if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        $uri = trim($uri, '/');
        return $uri !== '' ? $uri : 'dashboard';
    }
}

if (!function_exists('is_active_route')) {
    function is_active_route(string $route): string {
        $current = current_route();
        $route = trim($route, '/');
        if ($route === $current) {
            return 'active';
        }
        if ($route !== '' && $route !== 'dashboard' && $route !== 'member/dashboard' && str_starts_with($current, $route . '/')) {
            return 'active';
        }
        return '';
    }
}

