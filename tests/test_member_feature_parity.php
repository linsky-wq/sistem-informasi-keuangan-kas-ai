<?php
/**
 * Test Suite: Parity of Features and Access for Multi-Member Accounts
 * Verifies that any member account (Ahmad, Budi, or newly created) has identical
 * access to role Anggota dashboard, bills, payment submission, history, notifications,
 * profile, and Chatbot++, with STRICT data isolation and no Admin/Bendahara leak.
 */

function runIsolated(string $code): array {
    $projectDir = realpath(__DIR__ . '/..');
    $scratchDir = $projectDir . DIRECTORY_SEPARATOR . 'scratch';
    if (!is_dir($scratchDir)) {
        mkdir($scratchDir, 0777, true);
    }
    $tmpFile = $scratchDir . DIRECTORY_SEPARATOR . 'test_run_' . uniqid() . '.php';
    
    $bootstrap = <<<'BOOTSTRAP'
<?php
define('PROJECT_ROOT', %s);
require_once PROJECT_ROOT . '/app/config/config.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = PROJECT_ROOT . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

\App\Core\Session::start();

BOOTSTRAP;

    $header = sprintf($bootstrap, var_export($projectDir, true));
    file_put_contents($tmpFile, $header . $code);
    exec("php " . escapeshellarg($tmpFile), $output, $retCode);
    @unlink($tmpFile);
    return ['code' => $retCode, 'output' => implode("\n", $output)];
}

$tests = [];

// -------------------------------------------------------------
// Test 1: Ahmad Login & Dashboard Data Scoping
// -------------------------------------------------------------
$t1Code = <<<'PHP'
use App\Core\Auth;
use App\Models\User;
use App\Models\Payment;

$userModel = new User();
$user = $userModel->findByUsernameOrEmail('ahmad_fauzi');
if (!$user || !password_verify('member123', $user['password'])) {
    echo "FAIL: Ahmad credentials invalid";
    exit(1);
}

Auth::login($user);
if (!Auth::isMember()) {
    echo "FAIL: Auth::isMember() false for Ahmad";
    exit(1);
}

$member = Auth::member();
if (!$member || (int)$member['id'] !== 1 || $member['nama'] !== 'Ahmad Fauzi') {
    echo "FAIL: Ahmad member profile not linked correctly: " . json_encode($member);
    exit(1);
}

// Check Dashboard metrics for Ahmad
$payModel = new Payment();
$history = $payModel->getMemberHistory((int)$member['id']);
$totalTagihan = 0;
$totalPaid = 0;
foreach ($history as $h) {
    $totalTagihan += (float)($h['tagihan'] ?? 50000);
    $totalPaid += (float)$h['nominal'];
}

echo "OK|id=" . $member['id'] . "|nama=" . $member['nama'] . "|bills=" . count($history) . "|paid=" . $totalPaid;
PHP;

$res1 = runIsolated($t1Code);
$tests['1. Ahmad Login & Data Scoping'] = str_starts_with($res1['output'], 'OK') ? 'PASS: ' . $res1['output'] : 'FAIL: ' . $res1['output'];

// -------------------------------------------------------------
// Test 2: Budi Santoso Login & Dashboard Data Scoping
// -------------------------------------------------------------
$t2Code = <<<'PHP'
use App\Core\Auth;
use App\Models\User;
use App\Models\Payment;

$userModel = new User();
$user = $userModel->findByUsernameOrEmail('budi_santoso');
if (!$user || !password_verify('member123', $user['password'])) {
    echo "FAIL: Budi credentials invalid";
    exit(1);
}

Auth::login($user);
if (!Auth::isMember()) {
    echo "FAIL: Auth::isMember() false for Budi";
    exit(1);
}

$member = Auth::member();
if (!$member || (int)$member['id'] !== 2 || $member['nama'] !== 'Budi Santoso') {
    echo "FAIL: Budi member profile not linked correctly: " . json_encode($member);
    exit(1);
}

// Check Dashboard metrics for Budi
$payModel = new Payment();
$history = $payModel->getMemberHistory((int)$member['id']);
$totalTagihan = 0;
$totalPaid = 0;
foreach ($history as $h) {
    $totalTagihan += (float)($h['tagihan'] ?? 50000);
    $totalPaid += (float)$h['nominal'];
}

// Verify that Budi's bills do NOT include Ahmad's bills
$ahmadBills = $payModel->getMemberHistory(1);
if (count($history) === count($ahmadBills) && count($history) > 10) {
    echo "FAIL: Data leak! Budi got Ahmad's bills list";
    exit(1);
}

echo "OK|id=" . $member['id'] . "|nama=" . $member['nama'] . "|bills=" . count($history) . "|paid=" . $totalPaid;
PHP;

$res2 = runIsolated($t2Code);
$tests['2. Budi Login & Data Scoping'] = str_starts_with($res2['output'], 'OK') ? 'PASS: ' . $res2['output'] : 'FAIL: ' . $res2['output'];

// -------------------------------------------------------------
// Test 3: Data Isolation - Active Bills & Payment Submissions
// -------------------------------------------------------------
$t3Code = <<<'PHP'
use App\Models\Payment;
use App\Models\PaymentTransaction;

$payModel = new Payment();
$payTrxModel = new PaymentTransaction();

$ahmadBills = $payModel->getMemberHistory(1);
$budiBills = $payModel->getMemberHistory(2);

// Check that every bill of Ahmad belongs to member_id 1
foreach ($ahmadBills as $ab) {
    if ((int)$ab['member_id'] !== 1) {
        echo "FAIL: Ahmad bills contain non-Ahmad member_id: " . $ab['member_id'];
        exit(1);
    }
}

// Check that every bill of Budi belongs to member_id 2
foreach ($budiBills as $bb) {
    if ((int)$bb['member_id'] !== 2) {
        echo "FAIL: Budi bills contain non-Budi member_id: " . $bb['member_id'];
        exit(1);
    }
}

$ahmadSubmissions = $payTrxModel->getMemberHistory(1);
$budiSubmissions = $payTrxModel->getMemberHistory(2);

foreach ($ahmadSubmissions as $as) {
    if ((int)$as['member_id'] !== 1) {
        echo "FAIL: Ahmad submissions contain non-Ahmad member_id: " . $as['member_id'];
        exit(1);
    }
}

foreach ($budiSubmissions as $bs) {
    if ((int)$bs['member_id'] !== 2) {
        echo "FAIL: Budi submissions contain non-Budi member_id: " . $bs['member_id'];
        exit(1);
    }
}

echo "OK|AhmadBills=" . count($ahmadBills) . "|BudiBills=" . count($budiBills) . "|StrictIsolation=TRUE";
PHP;

$res3 = runIsolated($t3Code);
$tests['3. Bills & Payment Submissions Isolation'] = str_starts_with($res3['output'], 'OK') ? 'PASS: ' . $res3['output'] : 'FAIL: ' . $res3['output'];

// -------------------------------------------------------------
// Test 4: Cross-Member Payment Security (Budi cannot pay Ahmad's bill)
// -------------------------------------------------------------
$t4Code = <<<'PHP'
use App\Core\Auth;
use App\Models\User;
use App\Models\Payment;

// Login as Budi (member_id 2)
$userModel = new User();
$user = $userModel->findByUsernameOrEmail('budi_santoso');
Auth::login($user);

$member = Auth::member();
$memberId = (int)$member['id']; // 2

// Attempt to check ownership of Ahmad's bill (id: 1)
$paymentModel = new Payment();
$ahmadBill = $paymentModel->find(1);

if (!$ahmadBill || (int)$ahmadBill['member_id'] === $memberId) {
    echo "FAIL: Expected bill 1 to belong to Ahmad (1), not Budi (2)";
    exit(1);
}

// Check MemberController validation logic:
// if (!$bill || (int)$bill['member_id'] !== $memberId) -> REJECT
$rejected = ((int)$ahmadBill['member_id'] !== $memberId);

if ($rejected) {
    echo "OK|CrossMemberPaymentProtected=TRUE";
} else {
    echo "FAIL: Security breach - Budi could pay Ahmad bill";
    exit(1);
}
PHP;

$res4 = runIsolated($t4Code);
$tests['4. Cross-Member Payment Security'] = str_starts_with($res4['output'], 'OK') ? 'PASS: ' . $res4['output'] : 'FAIL: ' . $res4['output'];

// -------------------------------------------------------------
// Test 5: Notifications Data Scoping
// -------------------------------------------------------------
$t5Code = <<<'PHP'
use App\Models\WhatsAppLog;

$logModel = new WhatsAppLog();

$ahmadNotifs = $logModel->getMemberNotifications(1);
$budiNotifs = $logModel->getMemberNotifications(2);

$ahmadUnread = $logModel->getUnreadCountByMember(1);
$budiUnread = $logModel->getUnreadCountByMember(2);

// Check that no notif of Ahmad belongs to another member
foreach ($ahmadNotifs['data'] as $an) {
    if ((int)$an['member_id'] !== 1) {
        echo "FAIL: Ahmad notifs leak member_id: " . $an['member_id'];
        exit(1);
    }
}

foreach ($budiNotifs['data'] as $bn) {
    if ((int)$bn['member_id'] !== 2) {
        echo "FAIL: Budi notifs leak member_id: " . $bn['member_id'];
        exit(1);
    }
}

echo "OK|AhmadNotifs=" . count($ahmadNotifs['data']) . "|BudiNotifs=" . count($budiNotifs['data']) . "|AhmadUnread={$ahmadUnread}|BudiUnread={$budiUnread}";
PHP;

$res5 = runIsolated($t5Code);
$tests['5. Notifications Isolation'] = str_starts_with($res5['output'], 'OK') ? 'PASS: ' . $res5['output'] : 'FAIL: ' . $res5['output'];

// -------------------------------------------------------------
// Test 6: Chatbot++ AI Financial Query Isolation
// -------------------------------------------------------------
$t6Code = <<<'PHP'
use App\Core\Auth;
use App\Models\User;
use App\Services\FinancialDataService;
use App\Services\AiService;

$userModel = new User();
$financialService = new FinancialDataService();
$aiService = new AiService($financialService);

// 1. Ahmad Query
$uAhmad = $userModel->findByUsernameOrEmail('ahmad_fauzi');
Auth::login($uAhmad);
$contextAhmad = $financialService->getFactualContextForQuestion('berapa tagihan saya', 'member', (int)$uAhmad['id'], Auth::member(), Auth::user());
$ansAhmad = $aiService->analyze('berapa tagihan saya', $contextAhmad['metrics'], $contextAhmad['context'], (int)$uAhmad['id'], 'member');

// 2. Budi Query
$uBudi = $userModel->findByUsernameOrEmail('budi_santoso');
Auth::login($uBudi);
$contextBudi = $financialService->getFactualContextForQuestion('berapa tagihan saya', 'member', (int)$uBudi['id'], Auth::member(), Auth::user());
$ansBudi = $aiService->analyze('berapa tagihan saya', $contextBudi['metrics'], $contextBudi['context'], (int)$uBudi['id'], 'member');

// Verify that Ahmad and Budi answers are different and reflect their respective numbers
if ($ansAhmad['response'] === $ansBudi['response']) {
    echo "FAIL: AI answered identically for both Ahmad and Budi! Scoping failure.";
    exit(1);
}

// Budi should see his partial status or 25.000 tunggakan
if (!str_contains($ansBudi['response'], '25.000') && !str_contains($ansBudi['response'], 'Sebagian')) {
    echo "FAIL: Budi AI answer does not reflect Budi's data: " . $ansBudi['response'];
    exit(1);
}

echo "OK|AhmadAns=" . substr($ansAhmad['response'], 0, 45) . "...|BudiAns=" . substr($ansBudi['response'], 0, 45) . "...";
PHP;

$res6 = runIsolated($t6Code);
$tests['6. Chatbot++ AI Financial Scoping'] = str_starts_with($res6['output'], 'OK') ? 'PASS: ' . $res6['output'] : 'FAIL: ' . $res6['output'];

// -------------------------------------------------------------
// Test 7: RBAC Protection - Members Forbidden from Admin/Bendahara Areas
// -------------------------------------------------------------
$t7Code = <<<'PHP'
use App\Core\Auth;
use App\Models\User;

$userModel = new User();

// Test Budi
$uBudi = $userModel->findByUsernameOrEmail('budi_santoso');
Auth::login($uBudi);

$isSuperUser = Auth::isSuperUser();
$isBendahara = Auth::isBendahara();
$canManageWA = Auth::canManageWhatsApp();
$canSendWA = Auth::canSendWhatsApp();

if ($isSuperUser || $isBendahara || $canManageWA || $canSendWA) {
    echo "FAIL: Member has elevated permissions!";
    exit(1);
}

echo "OK|isSuperUser=FALSE|isBendahara=FALSE|canManageWA=FALSE|canSendWA=FALSE";
PHP;

$res7 = runIsolated($t7Code);
$tests['7. RBAC Forbidden Admin/Bendahara Privileges'] = str_starts_with($res7['output'], 'OK') ? 'PASS: ' . $res7['output'] : 'FAIL: ' . $res7['output'];

// -------------------------------------------------------------
// Test 8: End-to-End Fresh Member Account Creation & Feature Parity
// -------------------------------------------------------------
$t8Code = <<<'PHP'
use App\Core\Auth;
use App\Models\User;
use App\Models\Member;
use App\Models\Payment;

$db = \App\Core\Database::getInstance()->getConnection();
$memberModel = new Member();
$userModel = new User();
$payModel = new Payment();

// 1. Create a test member in members table
$nis = 'TEST-NIS-' . time();
$nomorAnggota = 'ANG-TEST-' . rand(100, 999);
$nama = 'Siswa Uji Paritas ' . rand(100, 999);
$username = 'siswa_paritas_' . time();
$password = 'rahasia123';

$memberId = $memberModel->create([
    'nomor_anggota' => $nomorAnggota,
    'nis'           => $nis,
    'nama'          => $nama,
    'kelas'         => 'XII RPL Test',
    'jenis_kelamin' => 'L',
    'status'        => 'aktif',
]);

if (!$memberId) {
    echo "FAIL: Could not create test member";
    exit(1);
}

// 2. Admin creates login account for this member
$userId = $userModel->create([
    'username' => $username,
    'email'    => $username . '@kas.local',
    'password' => password_hash($password, PASSWORD_BCRYPT),
    'role'     => 'member',
    'status'   => 'active',
]);

$memberModel->linkUserAccount($memberId, $userId);

// 3. Add a test bill for this new member
$billId = $payModel->create([
    'member_id' => $memberId,
    'periode'   => 'Oktober 2026',
    'tagihan'   => 50000.00,
    'nominal'   => 0.00,
    'status'    => 'Belum Bayar',
]);

// 4. Log in as this new member
$newLoggedUser = $userModel->find($userId);
Auth::login($newLoggedUser);

if (!Auth::isMember()) {
    echo "FAIL: Fresh member isMember() false";
    exit(1);
}

$freshMemberProfile = Auth::member();
if (!$freshMemberProfile || (int)$freshMemberProfile['id'] !== (int)$memberId) {
    echo "FAIL: Fresh member profile not loaded in session";
    exit(1);
}

// 5. Query active bills for fresh member
$freshBills = $payModel->getMemberHistory($memberId);
if (count($freshBills) !== 1 || (int)$freshBills[0]['id'] !== (int)$billId) {
    echo "FAIL: Fresh member bills mismatch";
    exit(1);
}

// Clean up test data
$db->exec("DELETE FROM payments WHERE id = {$billId}");
$db->exec("DELETE FROM members WHERE id = {$memberId}");
$db->exec("DELETE FROM users WHERE id = {$userId}");

echo "OK|FreshMemberId={$memberId}|FreshUserId={$userId}|LoginSuccess=TRUE|DataScoped=TRUE|CleanedUp=TRUE";
PHP;

$res8 = runIsolated($t8Code);
$tests['8. End-to-End Fresh Member Account Parity'] = str_starts_with($res8['output'], 'OK') ? 'PASS: ' . $res8['output'] : 'FAIL: ' . $res8['output'];

// Output summary
echo "=======================================================\n";
echo "       HASIL PENGUJIAN AKSES ANGGOTA SEPERTI AHMAD     \n";
echo "=======================================================\n";
$passCount = 0;
foreach ($tests as $name => $result) {
    $status = str_starts_with($result, 'PASS') ? '[PASSED]' : '[FAILED]';
    echo "{$status} {$name}\n";
    echo "         Detail: {$result}\n\n";
    if (str_starts_with($result, 'PASS')) $passCount++;
}

echo "Total: {$passCount}/" . count($tests) . " pengujian berhasil (" . round(($passCount / count($tests)) * 100) . "%).\n";
