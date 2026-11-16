<?php
/**
 * Home & System Diagnostics Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class HomeController extends Controller {
    public function index(): void {
        // Redirect authenticated users immediately to their operational dashboard
        if (Auth::check()) {
            if (Auth::isMember()) {
                $this->redirect('member/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return;
        }

        // Test database status
        $db = Database::getInstance();
        $dbStatus = [
            'connected' => $db->isConnected(),
            'error'     => $db->getError(),
            'host'      => DB_HOST,
            'database'  => DB_NAME,
        ];

        // System information
        $sysInfo = [
            'php_version' => PHP_VERSION,
            'app_name'    => APP_NAME,
            'app_env'     => APP_ENV,
            'base_url'    => BASE_URL,
            'db_status'   => $dbStatus,
            'is_logged_in'=> Auth::check(),
            'current_user'=> Auth::user(),
        ];

        $this->view('home.index', [
            'title'   => 'Dashboard Status & Setup — ' . APP_NAME,
            'sysInfo' => $sysInfo,
        ]);
    }

    public function ping(): void {
        $this->json([
            'status'    => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'app'       => APP_NAME,
        ]);
    }
}
