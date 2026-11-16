<?php
/**
 * Automated Test Suite: Task 03 — Authentication & Role RBAC
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
echo "   TASK 03: AUTOMATED AUTHENTICATION & ROLE (RBAC) TESTS\n";
echo "==========================================================\n\n";

$userModel = new \App\Models\User();
$memberModel = new \App\Models\Member();
$auditModel = new \App\Models\AuditLog();

// Setup: Ensure an inactive test user exists
$inactiveUser = $userModel->findByUsernameOrEmail('test_inactive');
if (!$inactiveUser) {
    $userModel->register([
        'username' => 'test_inactive',
        'email'    => 'inactive@kas.local',
        'password' => 'inactive123',
        'role'     => 'member',
        'status'   => 'inactive',
    ]);
}

// -------------------------------------------------------------
// TEST 1: Login Super User
// -------------------------------------------------------------
echo "[TEST 1] Testing Super User Login (admin / admin123)...\n";
\App\Core\Session::destroy();
$userAdmin = $userModel->authenticate('admin', 'admin123');
if ($userAdmin && $userAdmin['role'] === \App\Core\Auth::ROLE_SUPER_USER && $userAdmin['status'] === 'active') {
    \App\Core\Auth::login($userAdmin);
    assert(\App\Core\Auth::check() === true, "Auth::check() must be true");
    assert(\App\Core\Auth::isSuperUser() === true, "Auth::isSuperUser() must be true");
    assert(\App\Core\Auth::isMember() === false, "Auth::isMember() must be false");
    echo "  ✓ Super User successfully authenticated. Role: {$userAdmin['role']}. Session active.\n";
} else {
    echo "  ✗ Super User authentication FAILED.\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 2: Login Member
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Member Login (ahmad_fauzi / member123)...\n";
\App\Core\Session::destroy();
$userMember = $userModel->authenticate('ahmad_fauzi', 'member123');
if ($userMember && ($userMember['role'] === \App\Core\Auth::ROLE_MEMBER || $userMember['role'] === 'anggota') && $userMember['status'] === 'active') {
    \App\Core\Auth::login($userMember);
    assert(\App\Core\Auth::check() === true, "Auth::check() must be true");
    assert(\App\Core\Auth::isSuperUser() === false, "Auth::isSuperUser() must be false");
    assert(\App\Core\Auth::isMember() === true, "Auth::isMember() must be true");
    $loadedMember = \App\Core\Auth::member();
    assert($loadedMember !== null, "Auth::member() must be automatically loaded");
    assert($loadedMember['nama'] === 'Ahmad Fauzi', "Member name must match");
    echo "  ✓ Member successfully authenticated. Logged in as: {$loadedMember['nama']} ({$loadedMember['nomor_anggota']}).\n";
} else {
    echo "  ✗ Member authentication FAILED.\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 3: Password Salah
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Wrong Password (admin / wrongpassword)...\n";
$wrongPassAttempt = $userModel->authenticate('admin', 'wrongpassword');
if ($wrongPassAttempt === null) {
    echo "  ✓ Wrong password correctly REJECTED (returned null).\n";
} else {
    echo "  ✗ Wrong password was unexpectedly accepted.\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 4: User Inactive
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Inactive User Login (test_inactive / inactive123)...\n";
$inactiveAttempt = $userModel->authenticate('test_inactive', 'inactive123');
if ($inactiveAttempt === null) {
    echo "  ✓ Inactive user correctly REJECTED from authentication (returned null).\n";
} else {
    echo "  ✗ Inactive user was unexpectedly authenticated.\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 5: Member mencoba akses modul Super User (RBAC check)
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Authorization: Member accessing Super User module...\n";
// Set session as member
\App\Core\Session::destroy();
\App\Core\Auth::login($userMember);

// Check if member is recognized as Super User
$canAccessAdmin = \App\Core\Auth::isSuperUser();
if (!$canAccessAdmin) {
    echo "  ✓ Member authorization check strictly DENIES Super User access.\n";
} else {
    echo "  ✗ Member was incorrectly granted Super User permissions.\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 6: Logout
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Logout Process...\n";
\App\Core\Auth::logout();
assert(\App\Core\Auth::check() === false, "Auth::check() must be false after logout");
assert(\App\Core\Auth::user() === null, "Auth::user() must be null after logout");
assert(\App\Core\Auth::member() === null, "Auth::member() must be null after logout");
echo "  ✓ Logout successful. Session destroyed and cleared.\n";

echo "\n==========================================================\n";
echo "✓ ALL 6 AUTHENTICATION & ROLE (RBAC) TESTS PASSED!\n";
echo "==========================================================\n";
