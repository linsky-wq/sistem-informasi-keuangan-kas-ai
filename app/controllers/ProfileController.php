<?php
/**
 * Profile Controller Dispatcher
 * Routes /profile to the appropriate role-based profile management
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

class ProfileController extends Controller {
    public function index(): void {
        Auth::requireLogin();

        if (Auth::isMember()) {
            $this->redirect('member/profile');
        } else {
            $this->redirect('admin/profile');
        }
    }
}
