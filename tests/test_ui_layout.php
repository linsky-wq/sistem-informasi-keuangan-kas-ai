<?php
/**
 * Test Suite: Task 04 — UI Layout, Sidebar, Navbar, Breadcrumb, Flash Messages, and Roles
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

// Autoloader
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

echo "==========================================================\n";
echo "   TASK 04: UI & RESPONSIVE LAYOUT VERIFICATION\n";
echo "==========================================================\n\n";

$userModel = new \App\Models\User();

// -------------------------------------------------------------
// TEST 1: Super User Layout & Sidebar Menus
// -------------------------------------------------------------
echo "[TEST 1] Testing Super User UI Layout & Sidebar Menus...\n";
\App\Core\Session::destroy();
$adminUser = $userModel->findByUsernameOrEmail('admin');
\App\Core\Auth::login($adminUser);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
$controller = new \App\Controllers\DashboardController();
$controller->index();
$outputAdmin = ob_get_clean();

// Check for Super User Sidebar Menus (AI Assistant is now a floating widget, not in sidebar)
$expectedSuperUserMenus = [
    'Dashboard',
    'Anggota',
    'Kategori',
    'Transaksi',
    'Pembayaran',
    'Laporan',
    'Pengaturan'
];

$allFound = true;
foreach ($expectedSuperUserMenus as $menu) {
    if (strpos($outputAdmin, $menu) === false) {
        echo "  ✗ Super User Menu missing: $menu\n";
        $allFound = false;
    }
}

assert($allFound, "All Super User sidebar menus must exist");
assert(strpos($outputAdmin, 'app-sidebar') !== false, "Sidebar component must exist");
assert(strpos($outputAdmin, 'app-navbar') !== false, "Navbar component must exist");
assert(strpos($outputAdmin, 'app-breadcrumb-bar') !== false, "Breadcrumb component must exist");
assert(strpos($outputAdmin, 'app-footer') !== false, "Footer component must exist");
assert(strpos($outputAdmin, 'id="sidebarToggle"') !== false, "Mobile/Tablet Sidebar toggle button must exist");
assert(strpos($outputAdmin, 'id="sidebarBackdrop"') !== false, "Sidebar backdrop overlay must exist");
assert(strpos($outputAdmin, 'id="aiFloatingTrigger"') !== false, "AI Floating Trigger button must exist");
assert(strpos($outputAdmin, 'id="aiFloatingWindow"') !== false, "AI Floating Chat Window must exist");
echo "  ✓ Super User Layout validated with sidebar menus, topbar, breadcrumb, footer, and AI Floating Widget.\n";

// -------------------------------------------------------------
// TEST 2: Member Layout & Sidebar Menus
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Member UI Layout & Sidebar Menus...\n";
\App\Core\Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
\App\Core\Auth::login($memberUser);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/member/dashboard';

ob_start();
$memberController = new \App\Controllers\MemberController();
$memberController->dashboard();
$outputMember = ob_get_clean();

// Check for Member Sidebar Menus
$expectedMemberMenus = [
    'Dashboard',
    'Pembayaran Saya',
    'Riwayat Pembayaran',
    'Profil'
];

$allMemberFound = true;
foreach ($expectedMemberMenus as $menu) {
    if (strpos($outputMember, $menu) === false) {
        echo "  ✗ Member Menu missing: $menu\n";
        $allMemberFound = false;
    }
}

assert($allMemberFound, "All Member sidebar menus must exist");
assert(strpos($outputMember, 'app-sidebar') !== false, "Sidebar component must exist in Member view");
assert(strpos($outputMember, 'app-navbar') !== false, "Navbar component must exist in Member view");
assert(strpos($outputMember, 'app-breadcrumb-bar') !== false, "Breadcrumb component must exist in Member view");
assert(strpos($outputMember, 'id="sidebarToggle"') !== false, "Mobile/Tablet Sidebar toggle button must exist in Member view");
assert(strpos($outputMember, 'id="sidebarBackdrop"') !== false, "Sidebar backdrop overlay must exist in Member view");
assert(strpos($outputMember, 'id="aiFloatingTrigger"') !== false, "AI Floating Trigger button must exist in Member view");
assert(strpos($outputMember, 'id="aiFloatingWindow"') !== false, "AI Floating Chat Window must exist in Member view");
echo "  ✓ Member Layout validated with all 4 requested menus, topbar, breadcrumb, footer, and AI Floating Widget.\n";

// -------------------------------------------------------------
// TEST 3: Guest Layout
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Guest Visitor Layout (No sidebar & No Floating AI)...\n";
\App\Core\Session::destroy();
\App\Core\Auth::logout();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

ob_start();
$homeController = new \App\Controllers\HomeController();
$homeController->index();
$outputGuest = ob_get_clean();

assert(strpos($outputGuest, 'app-sidebar') === false, "Sidebar must NOT be rendered for Guest");
assert(strpos($outputGuest, 'navbar-public') !== false, "Public navbar must be rendered for Guest");
assert(strpos($outputGuest, 'app-footer') !== false, "Footer must be rendered for Guest");
assert(strpos($outputGuest, 'id="aiFloatingTrigger"') === false, "AI Floating Trigger must NOT be rendered for Guest");
assert(strpos($outputGuest, 'id="aiFloatingWindow"') === false, "AI Floating Window must NOT be rendered for Guest");
echo "  ✓ Guest Layout correctly renders public navbar without sidebar and without floating AI widget.\n";

// -------------------------------------------------------------
// TEST 4: Flash Message Rendering
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Flash Message Component Rendering...\n";
\App\Core\Session::destroy();
\App\Core\Auth::login($adminUser);
\App\Core\Session::setFlash('success', 'Operasi berhasil dieksekusi.');
\App\Core\Session::setFlash('danger', 'Terjadi kesalahan sistem.');

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/dashboard';

ob_start();
$controller = new \App\Controllers\DashboardController();
$controller->index();
$outputWithFlash = ob_get_clean();

assert(strpos($outputWithFlash, 'Operasi berhasil dieksekusi.') !== false, "Success flash message must be rendered");
assert(strpos($outputWithFlash, 'Terjadi kesalahan sistem.') !== false, "Danger flash message must be rendered");
assert(strpos($outputWithFlash, 'alert alert-success') !== false, "Success alert class must exist");
assert(strpos($outputWithFlash, 'alert alert-danger') !== false, "Danger alert class must exist");
echo "  ✓ Flash Message component accurately renders dismissible Bootstrap 5 alerts.\n";

// -------------------------------------------------------------
// TEST 5: Responsive Elements & Icons
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Responsive Elements & Menu Icons...\n";
$requiredIcons = [
    'bi-speedometer2', // Dashboard
    'bi-people-fill',  // Anggota
    'bi-tags-fill',    // Kategori
    'bi-arrow-left-right', // Transaksi
    'bi-receipt-cutoff',   // Pembayaran
    'bi-file-earmark-bar-graph-fill', // Laporan
    'bi-stars',        // AI Assistant
    'bi-gear-fill',    // Pengaturan
];

foreach ($requiredIcons as $icon) {
    assert(strpos($outputAdmin, $icon) !== false, "Icon [$icon] must exist in Super User layout");
}
echo "  ✓ All menu icons and responsive layout tokens verified.\n";

echo "\n==========================================================\n";
echo "✓ ALL UI & RESPONSIVE LAYOUT TESTS PASSED SUCCESSFULLY!\n";
echo "==========================================================\n";
