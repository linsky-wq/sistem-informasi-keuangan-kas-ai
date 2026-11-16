<?php
/**
 * Authentication Controller
 * Handles Login, Logout, and Session Authentication
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\AuditLog;

class AuthController extends Controller {
    private User $userModel;
    private AuditLog $auditModel;

    public function __construct() {
        $this->userModel = new User();
        $this->auditModel = new AuditLog();
    }

    /**
     * Display Login Form & Handle Authentication
     */
    public function login(): void {
        // Redirect if already logged in
        if (Auth::check()) {
            if (Auth::isMember()) {
                $this->redirect('member/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return;
        }

        // Handle POST Submission
        if ($this->isPost()) {
            // 1. CSRF Verification
            if (!$this->validateCsrf()) {
                Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Token Expired). Silakan coba lagi.');
                $this->redirect('auth/login');
                return;
            }

            // 2. Input extraction & sanitization
            $identifier = trim((string)$this->post('identifier', ''));
            $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

            // 3. Input Validation
            if ($identifier === '' || $password === '') {
                Session::setFlash('danger', 'Username/Email dan Password wajib diisi.');
                $this->redirect('auth/login');
                return;
            }

            // 4. Verify Database connectivity before querying
            if (!\App\Core\Database::getInstance()->isConnected()) {
                Session::setFlash('danger', 'Koneksi ke database sedang terputus. Silakan periksa server database MySQL/MariaDB.');
                $this->redirect('auth/login');
                return;
            }

            // 5. Find user by username or email
            $user = $this->userModel->findByUsernameOrEmail($identifier);

            if (!$user) {
                Session::setFlash('danger', 'Username/Email atau Password tidak cocok.');
                $this->redirect('auth/login');
                return;
            }

            // 6. Check user account active status
            if ($user['status'] !== 'active') {
                Session::setFlash('warning', 'Akun Anda dinonaktifkan. Silakan hubungi Super User/Administrator.');
                $this->redirect('auth/login');
                return;
            }

            // 7. Verify password hash
            if (!password_verify($password, $user['password'])) {
                Session::setFlash('danger', 'Username/Email atau Password tidak cocok.');
                $this->redirect('auth/login');
                return;
            }

            // 8. Successful Authentication - Regenerate Session & Login
            Auth::login($user);

            // 9. Record in Audit Log
            $this->auditModel->record(
                (int)$user['id'],
                'LOGIN',
                'users',
                (int)$user['id'],
                null,
                ['username' => $user['username'], 'role' => $user['role']]
            );

            // 10. Redirect based on User Role
            if ($user['role'] === Auth::ROLE_SUPER_USER || $user['role'] === 'admin') {
                Session::setFlash('success', 'Selamat datang kembali, Super User ' . e($user['username']) . '!');
                $this->redirect('dashboard');
            } elseif ($user['role'] === Auth::ROLE_BENDAHARA) {
                Session::setFlash('success', 'Selamat datang kembali, Bendahara ' . e($user['username']) . '!');
                $this->redirect('dashboard');
            } elseif ($user['role'] === Auth::ROLE_PENGAWAS) {
                Session::setFlash('success', 'Selamat datang kembali, Pengawas ' . e($user['username']) . '!');
                $this->redirect('dashboard');
            } elseif ($user['role'] === Auth::ROLE_MEMBER || $user['role'] === 'anggota') {
                $memberName = Auth::member()['nama'] ?? $user['username'];
                Session::setFlash('success', 'Selamat datang, ' . e($memberName) . '!');
                $this->redirect('member/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return;
        }

        // Render Login View
        $this->view('auth.login', [
            'title' => 'Login — ' . APP_NAME,
        ], false); // render without standard layout to allow custom auth card styling
    }

    /**
     * Handle User Logout
     */
    public function logout(): void {
        $userId = Auth::id();
        $username = Auth::username();

        if ($userId) {
            $this->auditModel->record(
                $userId,
                'LOGOUT',
                'users',
                $userId,
                null,
                ['username' => $username]
            );
        }

        Auth::logout();
        Session::setFlash('info', 'Anda telah berhasil keluar (logout) dari sistem.');
        $this->redirect('auth/login');
    }
}
