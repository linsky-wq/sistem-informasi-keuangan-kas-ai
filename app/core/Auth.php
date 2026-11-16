<?php
/**
 * Authentication & Role Based Access Control (RBAC) Helper
 */

namespace App\Core;

use App\Models\Member;

class Auth {
    public const ROLE_SUPER_USER = 'super_user';
    public const ROLE_MEMBER = 'member';
    public const ROLE_ANGGOTA = 'member'; // alias
    public const ROLE_BENDAHARA = 'bendahara';
    public const ROLE_PENGAWAS = 'pengawas';

    public static function user(): ?array {
        return Session::get('user');
    }

    public static function member(): ?array {
        return Session::get('member');
    }

    public static function id(): ?int {
        $user = self::user();
        return isset($user['id']) ? (int)$user['id'] : null;
    }

    public static function username(): ?string {
        $user = self::user();
        return $user['username'] ?? null;
    }

    public static function name(): ?string {
        $user = self::user();
        if (!empty($user['nama'])) {
            return $user['nama'];
        }
        if (self::isMember()) {
            $member = self::member();
            if (!empty($member['nama'])) {
                return $member['nama'];
            }
        }
        return $user['username'] ?? null;
    }

    public static function role(): ?string {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function check(): bool {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        // Validate User Agent Fingerprint if available
        $storedHash = Session::get('_user_agent_hash');
        if ($storedHash !== null) {
            $currentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
            if (!hash_equals($storedHash, $currentHash)) {
                self::logout();
                return false;
            }
        }

        return true;
    }

    public static function isSuperUser(): bool {
        return self::check() && (self::role() === self::ROLE_SUPER_USER || self::role() === 'admin');
    }

    public static function isBendahara(): bool {
        return self::check() && self::role() === self::ROLE_BENDAHARA;
    }

    public static function isPengawas(): bool {
        return self::check() && self::role() === self::ROLE_PENGAWAS;
    }

    public static function isMember(): bool {
        return self::check() && (self::role() === self::ROLE_MEMBER || self::role() === 'anggota');
    }

    public static function isAnggota(): bool {
        return self::isMember();
    }

    /**
     * Permission: Can manage WhatsApp settings and gateway configuration (Super User only)
     */
    public static function canManageWhatsApp(): bool {
        return self::isSuperUser();
    }

    /**
     * Permission: Can trigger WhatsApp notifications and view delivery logs (Super User & Bendahara)
     */
    public static function canSendWhatsApp(): bool {
        return self::isSuperUser() || self::isBendahara();
    }


    /**
     * Set authenticated user and member profile in session
     */
    public static function login(array $userData, ?array $memberData = null): void {
        unset($userData['password']);
        Session::set('user', $userData);
        Session::set('_user_agent_hash', hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''));

        if ($memberData !== null) {
            Session::set('member', $memberData);
        } elseif ($userData['role'] === self::ROLE_MEMBER || $userData['role'] === 'anggota') {
            $memberModel = new Member();
            $member = $memberModel->findByUserId((int)$userData['id']);
            if ($member) {
                Session::set('member', $member);
            }
        }
        
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
        }
    }

    /**
     * Refresh member profile stored in session
     */
    public static function refreshMemberProfile(): void {
        $user = self::user();
        if ($user && self::isMember()) {
            $memberModel = new Member();
            $member = $memberModel->findByUserId((int)$user['id']);
            if ($member) {
                Session::set('member', $member);
            }
        }
    }

    /**
     * Refresh user account data stored in session from database
     */
    public static function refreshUserSession(): ?array {
        $userId = self::id();
        if ($userId) {
            $userModel = new \App\Models\User();
            $freshUser = $userModel->find($userId);
            if ($freshUser) {
                unset($freshUser['password']);
                Session::set('user', $freshUser);
                return $freshUser;
            }
        }
        return self::user();
    }

    /**
     * Log out current user
     */
    public static function logout(): void {
        Session::destroy();
    }

    /**
     * Authentication Middleware: Require any logged in user
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            Session::setFlash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
            header('Location: ' . base_url('auth/login'));
            exit;
        }
    }

    /**
     * Authorization Middleware: Require specific role(s)
     */
    public static function requireRole(string ...$allowedRoles): void {
        self::requireLogin();
        $userRole = self::role();
        
        // Normalize role check
        $hasAccess = false;
        foreach ($allowedRoles as $role) {
            if ($userRole === $role) {
                $hasAccess = true;
                break;
            }
            if ($role === self::ROLE_SUPER_USER && ($userRole === 'super_user' || $userRole === 'admin')) {
                $hasAccess = true;
                break;
            }
            if ($role === self::ROLE_MEMBER && ($userRole === 'member' || $userRole === 'anggota')) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            self::render403("Akses Ditolak: Role Anda [{$userRole}] tidak memiliki hak akses ke modul ini.");
        }
    }

    /**
     * Shortcut Middleware: Super User only
     */
    public static function requireSuperUser(): void {
        self::requireRole(self::ROLE_SUPER_USER);
    }

    /**
     * Shortcut Middleware: Member only
     */
    public static function requireMember(): void {
        self::requireRole(self::ROLE_MEMBER);
    }

    /**
     * Render 403 Forbidden page
     */
    public static function render403(string $message = 'Anda tidak memiliki izin untuk mengakses halaman ini.'): void {
        http_response_code(403);
        $errorPath = APP_ROOT . '/views/errors/403.php';
        $headerPath = APP_ROOT . '/views/layouts/header.php';
        $navbarPath = APP_ROOT . '/views/layouts/navbar.php';
        $footerPath = APP_ROOT . '/views/layouts/footer.php';

        $title = "403 - Akses Ditolak";
        $data = ['title' => $title, 'message' => $message];
        extract($data);

        if (file_exists($headerPath)) require_once $headerPath;
        if (file_exists($navbarPath)) require_once $navbarPath;
        if (file_exists($errorPath)) {
            require_once $errorPath;
        } else {
            echo '<div class="container py-5 text-center"><h1>403</h1><p>' . htmlspecialchars($message) . '</p></div>';
        }
        if (file_exists($footerPath)) require_once $footerPath;
        exit;
    }
}
