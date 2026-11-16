<?php
/**
 * Security Helper: CSRF, XSS, and Input Sanitization
 */

namespace App\Core;

class Security {
    public static function generateCsrfToken(): string {
        Session::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool {
        Session::start();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function sanitize(mixed $data): mixed {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
            return $data;
        }
        if (is_string($data)) {
            // Strip null bytes and control chars except newlines/tabs
            $data = str_replace(chr(0), '', $data);
            return trim(strip_tags($data));
        }
        return $data;
    }

    /**
     * Clean and parse monetary / numeric input strings safely
     */
    public static function cleanFloat(mixed $raw, float $default = 0.0): float {
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (is_numeric($raw)) {
            return (float)$raw;
        }
        if (is_string($raw)) {
            $cleaned = str_replace(['.', ',', 'Rp', ' ', "\t", "\n", "\r"], ['', '.', '', '', '', '', ''], $raw);
            if (is_numeric($cleaned)) {
                return (float)$cleaned;
            }
        }
        return $default;
    }
}
