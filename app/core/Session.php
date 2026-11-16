<?php
/**
 * Session Manager & Flash Message Handler
 */

namespace App\Core;
class Session {
    public const IDLE_TIMEOUT = 7200; // 2 hours

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.use_strict_mode', '1');
                ini_set('session.cookie_samesite', 'Lax');

                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
                if ($isHttps) {
                    ini_set('session.cookie_secure', '1');
                }

                session_start();
            } else {
                @session_start();
            }
        }

        // Idle Timeout Check for Authenticated Sessions
        if (isset($_SESSION['user']) && isset($_SESSION['_last_activity'])) {
            if (time() - (int)$_SESSION['_last_activity'] > self::IDLE_TIMEOUT) {
                self::destroy();
                self::start();
                $_SESSION['_flash']['warning'][] = 'Sesi Anda telah kedaluwarsa karena tidak ada aktivitas. Silakan login kembali.';
                return;
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function setFlash(string $type, string $message): void {
        self::start();
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function flash(string $type, mixed $message): void {
        if (in_array($type, ['success', 'danger', 'warning', 'info', 'primary', 'secondary'])) {
            self::setFlash($type, (string)$message);
        } else {
            self::set('_flash_' . $type, $message);
        }
    }

    public static function getFlash(string $key, mixed $default = null): mixed {
        self::start();
        $flashKey = '_flash_' . $key;
        if (isset($_SESSION[$flashKey])) {
            $val = $_SESSION[$flashKey];
            unset($_SESSION[$flashKey]);
            return $val;
        }
        return $default;
    }

    public static function getFlashes(): array {
        self::start();
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }
}
