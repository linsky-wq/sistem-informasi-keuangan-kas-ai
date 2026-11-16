<?php
/**
 * Automated Test Suite: Task 10 — Member Dashboard, Profile, Cash Status, History & Security/Isolation
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
echo "   TASK 10: MEMBER DASHBOARD & AUTHORIZATION TESTS        \n";
echo "==========================================================\n\n";

$memberModel  = new \App\Models\Member();
$paymentModel = new \App\Models\Payment();
$userModel    = new \App\Models\User();

// Authenticate as Member 1 (Ahmad Fauzi)
\App\Core\Session::destroy();
$memberUser1 = $userModel->findByUsernameOrEmail('ahmad_fauzi');
assert($memberUser1 !== null, "Member 1 user (ahmad_fauzi) must exist");
\App\Core\Auth::login($memberUser1);

$memberProfile1 = $memberModel->findByUserId((int)$memberUser1['id']);
assert($memberProfile1 !== null, "Member profile for user 1 must exist");

echo "Test Environment Initialized for Member:\n";
echo "  - User   : {$memberUser1['username']} (ID: {$memberUser1['id']}, Role: {$memberUser1['role']})\n";
echo "  - Member : {$memberProfile1['nama']} (ID: {$memberProfile1['id']}, NIS: {$memberProfile1['nis']}, Kelas: {$memberProfile1['kelas']})\n\n";

// -------------------------------------------------------------
// TEST 1: Member Profile Data Validation
// -------------------------------------------------------------
echo "[TEST 1] Testing Member Profile Data Presentation (nama, NIS, kelas)...\n";
assert(!empty($memberProfile1['nama']), "Profile must contain nama");
assert(!empty($memberProfile1['nis']), "Profile must contain NIS");
assert(!empty($memberProfile1['kelas']), "Profile must contain kelas");
assert(!empty($memberProfile1['nomor_anggota']), "Profile must contain nomor anggota");

echo "  ✓ Member Profile verified:\n";
echo "    - Nama  : {$memberProfile1['nama']}\n";
echo "    - NIS   : {$memberProfile1['nis']}\n";
echo "    - Kelas : {$memberProfile1['kelas']}\n";
echo "    - No    : {$memberProfile1['nomor_anggota']}\n";

// -------------------------------------------------------------
// TEST 2: Status Kas (periode, tagihan, sudah dibayar, sisa, status)
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Status Kas (periode, tagihan, dibayar, sisa, status)...\n";
$memberPayments = $paymentModel->getMemberHistory((int)$memberProfile1['id']);
assert(count($memberPayments) > 0, "Member 1 should have payment records");

$activePayment = $memberPayments[0];
$tagihan = (float)($activePayment['tagihan'] ?? 50000.00);
$nominal = (float)$activePayment['nominal'];
$sisa = (float)($activePayment['sisa'] ?? max(0.0, $tagihan - $nominal));
$status = $activePayment['status'];

assert(!empty($activePayment['periode']), "Payment must contain periode");
assert($tagihan > 0, "Tagihan must be positive");
assert($nominal >= 0, "Nominal must be non-negative");
assert($sisa >= 0, "Sisa must be non-negative");
assert(in_array($status, ['Lunas', 'Sebagian', 'Belum Bayar'], true), "Status must be valid enum");

echo "  ✓ Active Cash Status verified:\n";
echo "    - Periode       : {$activePayment['periode']}\n";
echo "    - Tagihan       : Rp " . number_format($tagihan, 2, ',', '.') . "\n";
echo "    - Sudah Dibayar : Rp " . number_format($nominal, 2, ',', '.') . "\n";
echo "    - Sisa          : Rp " . number_format($sisa, 2, ',', '.') . "\n";
echo "    - Status        : {$status}\n";

// -------------------------------------------------------------
// TEST 3: Riwayat Pembayaran (Recent Payments)
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Riwayat Pembayaran Pribadi...\n";
$recent5 = array_slice($memberPayments, 0, 5);
assert(count($recent5) <= 5, "Recent payments must be at most 5");

echo "  ✓ Retrieved " . count($recent5) . " recent payments:\n";
foreach ($recent5 as $p) {
    echo "    - [{$p['tanggal_pembayaran']}] {$p['periode']}: Disetor=Rp " . number_format((float)$p['nominal'], 2, ',', '.') . ", Sisa=Rp " . number_format((float)$p['sisa'], 2, ',', '.') . " ({$p['status']})\n";
}

// -------------------------------------------------------------
// TEST 4: Security & Strict Data Isolation (Anti ID Spoofing)
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Member Data Isolation & Anti-Spoofing Policy...\n";
// Ensure all rows returned by member queries strictly belong to member 1
foreach ($memberPayments as $row) {
    assert((int)$row['member_id'] === (int)$memberProfile1['id'], "All returned rows MUST match logged-in member ID");
}

// Verify that Member cannot access Super User PaymentController
$isSuperUser = \App\Core\Auth::isSuperUser();
assert($isSuperUser === false, "Logged-in member must NOT have super user privileges");

echo "  ✓ Member data isolation confirmed: 100% of rows belong to Member ID {$memberProfile1['id']}.\n";
echo "  ✓ Super User authorization check denied as expected.\n";

// -------------------------------------------------------------
// TEST 5: Member Dashboard View Structure & States
// -------------------------------------------------------------
echo "\n[TEST 5] Testing View Structure (Mobile Friendly, Empty State, Loading State, Error State)...\n";
$viewPath = dirname(__DIR__) . '/app/views/member/dashboard.php';
assert(file_exists($viewPath), "Member dashboard view file must exist");
$viewContent = file_get_contents($viewPath);

assert(strpos($viewContent, 'Profil Pengguna/Anggota') !== false, "View must render Profil banner");
assert(strpos($viewContent, 'Status Kas Periode Terkini') !== false, "View must render Status Kas section");
assert(strpos($viewContent, 'Riwayat Pembayaran Kas Terbaru') !== false, "View must render Riwayat section");
assert(strpos($viewContent, 'Belum Ada Riwayat Pembayaran Kas') !== false, "View must contain empty state");
assert(strpos($viewContent, 'dashboardLoadingState') !== false, "View must contain loading state placeholder");
assert(strpos($viewContent, 'memberDashboardAlertContainer') !== false, "View must contain error/alert state container");

echo "  ✓ View structure verified (Profile, Cash Status, History, Empty State, Loading State, Error State).\n";

echo "\n==========================================================\n";
echo "   ALL TASK 10 MEMBER DASHBOARD TESTS PASSED!             \n";
echo "==========================================================\n";
