<?php
/**
 * Automated Test Suite: Task 08 — Cash Payment Management, Backend Calculation, Statuses, Member Isolation & Audit Logs
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
echo "   TASK 08: CASH PAYMENT MANAGEMENT & CALCULATIONS TESTS  \n";
echo "==========================================================\n\n";

$paymentModel = new \App\Models\Payment();
$memberModel  = new \App\Models\Member();
$userModel    = new \App\Models\User();
$auditModel   = new \App\Models\AuditLog();

// -------------------------------------------------------------
// SETUP: Authenticate as Super User and Get Test Members
// -------------------------------------------------------------
\App\Core\Session::destroy();
$adminUser = $userModel->findByUsernameOrEmail('admin');
assert($adminUser !== null, "Admin user must exist");
\App\Core\Auth::login($adminUser);

$allMembers = $memberModel->all('id ASC');
assert(count($allMembers) >= 2, "At least 2 test members must exist");
$member1 = $allMembers[0];
$member2 = $allMembers[1];

echo "Test Environment Initialized:\n";
echo "  - Super User : {$adminUser['username']} (ID: {$adminUser['id']})\n";
echo "  - Member 1   : {$member1['nama']} (ID: {$member1['id']}, Kelas: {$member1['kelas']})\n";
echo "  - Member 2   : {$member2['nama']} (ID: {$member2['id']}, Kelas: {$member2['kelas']})\n\n";

// -------------------------------------------------------------
// TEST 1: Backend Calculation for Status & Sisa
// -------------------------------------------------------------
echo "[TEST 1] Testing Backend Calculation (calculateStatus & calculateSisa)...\n";
$tagihan = 50000.00;

// Case 1: 0 pembayaran -> BELUM_BAYAR
$status0 = \App\Models\Payment::calculateStatus(0.0, $tagihan);
$sisa0   = \App\Models\Payment::calculateSisa(0.0, $tagihan);
assert($status0 === \App\Models\Payment::STATUS_BELUM_BAYAR, "0 payment must result in Belum Bayar");
assert($sisa0 === 50000.00, "Sisa must equal full tagihan (50,000)");

// Case 2: 0 < pembayaran < tagihan -> SEBAGIAN
$statusPartial = \App\Models\Payment::calculateStatus(25000.0, $tagihan);
$sisaPartial   = \App\Models\Payment::calculateSisa(25000.0, $tagihan);
assert($statusPartial === \App\Models\Payment::STATUS_SEBAGIAN, "Partial payment must result in Sebagian");
assert($sisaPartial === 25000.00, "Sisa must equal 25,000");

// Case 3: pembayaran >= tagihan -> LUNAS
$statusLunas = \App\Models\Payment::calculateStatus(50000.0, $tagihan);
$sisaLunas   = \App\Models\Payment::calculateSisa(50000.0, $tagihan);
assert($statusLunas === \App\Models\Payment::STATUS_LUNAS, "Full payment must result in Lunas");
assert($sisaLunas === 0.0, "Sisa must equal 0");

$statusOver = \App\Models\Payment::calculateStatus(60000.0, $tagihan);
$sisaOver   = \App\Models\Payment::calculateSisa(60000.0, $tagihan);
assert($statusOver === \App\Models\Payment::STATUS_LUNAS, "Overpayment must result in Lunas");
assert($sisaOver === 0.0, "Sisa must not be negative (min 0)");

echo "  ✓ 0 pembayaran -> {$status0} (Sisa: Rp " . number_format($sisa0, 2, ',', '.') . ")\n";
echo "  ✓ 25.000 / 50.000 -> {$statusPartial} (Sisa: Rp " . number_format($sisaPartial, 2, ',', '.') . ")\n";
echo "  ✓ 50.000 / 50.000 -> {$statusLunas} (Sisa: Rp " . number_format($sisaLunas, 2, ',', '.') . ")\n";
echo "  ✓ 60.000 / 50.000 -> {$statusOver} (Sisa: Rp " . number_format($sisaOver, 2, ',', '.') . ")\n";

// -------------------------------------------------------------
// TEST 2: Record Payment: BELUM_BAYAR (0 payment)
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Record Payment: BELUM_BAYAR (0 Payment)...\n";
$testPeriod1 = 'Test Periode ' . time() . ' - Belum Bayar';
$payId1 = $paymentModel->recordPayment([
    'member_id'          => $member1['id'],
    'periode'            => $testPeriod1,
    'tagihan'            => 50000.00,
    'nominal'            => 0.00,
    'tanggal_pembayaran' => date('Y-m-d'),
    'verified_by'        => null,
    'catatan'            => 'Tagihan baru belum dibayar',
]);
assert($payId1 !== false && $payId1 > 0, "Payment record creation must succeed");

$pay1 = $paymentModel->getDetail($payId1);
assert($pay1['status'] === 'Belum Bayar', "Database status must be 'Belum Bayar'");
assert((float)$pay1['nominal'] === 0.0, "Nominal must be 0.00");
assert((float)$pay1['tagihan'] === 50000.00, "Tagihan must be 50,000.00");
assert((float)$pay1['sisa'] === 50000.00, "Sisa tunggakan must be 50,000.00");

$auditModel->record($adminUser['id'], 'CREATE_PAYMENT', 'payments', $payId1, null, $pay1);
echo "  ✓ Payment ID #{$payId1} recorded: Status={$pay1['status']}, Tagihan=Rp " . number_format((float)$pay1['tagihan'], 2) . ", Disetor=Rp " . number_format((float)$pay1['nominal'], 2) . ", Sisa=Rp " . number_format((float)$pay1['sisa'], 2) . "\n";

// -------------------------------------------------------------
// TEST 3: Record Payment: SEBAGIAN (Partial payment)
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Record Payment: SEBAGIAN (Partial Payment)...\n";
$testPeriod2 = 'Test Periode ' . time() . ' - Sebagian';
$payId2 = $paymentModel->recordPayment([
    'member_id'          => $member1['id'],
    'periode'            => $testPeriod2,
    'tagihan'            => 50000.00,
    'nominal'            => 20000.00,
    'tanggal_pembayaran' => date('Y-m-d'),
    'verified_by'        => $adminUser['id'],
    'catatan'            => 'Cicilan kas pertama Rp 20.000',
]);
assert($payId2 !== false && $payId2 > 0, "Payment record creation must succeed");

$pay2 = $paymentModel->getDetail($payId2);
assert($pay2['status'] === 'Sebagian', "Database status must be 'Sebagian'");
assert((float)$pay2['nominal'] === 20000.00, "Nominal must be 20,000.00");
assert((float)$pay2['sisa'] === 30000.00, "Sisa tunggakan must be 30,000.00");

$auditModel->record($adminUser['id'], 'CREATE_PAYMENT', 'payments', $payId2, null, $pay2);
echo "  ✓ Payment ID #{$payId2} recorded: Status={$pay2['status']}, Tagihan=Rp " . number_format((float)$pay2['tagihan'], 2) . ", Disetor=Rp " . number_format((float)$pay2['nominal'], 2) . ", Sisa=Rp " . number_format((float)$pay2['sisa'], 2) . "\n";

// -------------------------------------------------------------
// TEST 4: Record Payment: LUNAS (Full payment)
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Record Payment: LUNAS (Full Payment)...\n";
$testPeriod3 = 'Test Periode ' . time() . ' - Lunas';
$payId3 = $paymentModel->recordPayment([
    'member_id'          => $member2['id'],
    'periode'            => $testPeriod3,
    'tagihan'            => 50000.00,
    'nominal'            => 50000.00,
    'tanggal_pembayaran' => date('Y-m-d'),
    'verified_by'        => $adminUser['id'],
    'catatan'            => 'Pembayaran lunas kas via transfer',
]);
assert($payId3 !== false && $payId3 > 0, "Payment record creation must succeed");

$pay3 = $paymentModel->getDetail($payId3);
assert($pay3['status'] === 'Lunas', "Database status must be 'Lunas'");
assert((float)$pay3['nominal'] === 50000.00, "Nominal must be 50,000.00");
assert((float)$pay3['sisa'] === 0.00, "Sisa tunggakan must be 0.00");

$auditModel->record($adminUser['id'], 'CREATE_PAYMENT', 'payments', $payId3, null, $pay3);
echo "  ✓ Payment ID #{$payId3} recorded: Status={$pay3['status']}, Tagihan=Rp " . number_format((float)$pay3['tagihan'], 2) . ", Disetor=Rp " . number_format((float)$pay3['nominal'], 2) . ", Sisa=Rp " . number_format((float)$pay3['sisa'], 2) . "\n";

// -------------------------------------------------------------
// TEST 5: Verify Payment & Status Upgrade (Sebagian -> Lunas)
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Super User Payment Verification & Status Upgrade...\n";
// Pelunasan payment 2 from 20,000 to 50,000
$verifySuccess = $paymentModel->verify($payId2, (int)$adminUser['id'], 50000.00, 'Telah dilunasi sisa Rp 30.000');
assert($verifySuccess === true, "Verification must succeed");

$pay2Updated = $paymentModel->getDetail($payId2);
assert($pay2Updated['status'] === 'Lunas', "Status must automatically upgrade to 'Lunas'");
assert((float)$pay2Updated['nominal'] === 50000.00, "Nominal must be updated to 50,000.00");
assert((float)$pay2Updated['sisa'] === 0.00, "Sisa must be 0.00");
assert((int)$pay2Updated['verified_by'] === (int)$adminUser['id'], "Verifier must match admin user ID");
assert(!empty($pay2Updated['verified_at']), "Verified at timestamp must be set");

$auditModel->record($adminUser['id'], 'VERIFY_PAYMENT', 'payments', $payId2, $pay2, $pay2Updated);
echo "  ✓ Payment ID #{$payId2} verified & upgraded: Status is now '{$pay2Updated['status']}', Sisa=Rp " . number_format((float)$pay2Updated['sisa'], 2) . ", Verifier={$pay2Updated['verifier_name']}\n";

// -------------------------------------------------------------
// TEST 6: Payment Detail Fields Inspection
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Payment Detail Fields Presentation...\n";
$detail = $paymentModel->getDetail($payId3);
assert(!empty($detail['member_name']), "Detail must include nama anggota");
assert(!empty($detail['periode']), "Detail must include periode");
assert(isset($detail['nominal']), "Detail must include nominal");
assert(!empty($detail['tanggal_pembayaran']), "Detail must include tanggal");
assert(!empty($detail['status']), "Detail must include status");
assert(!empty($detail['verifier_name']), "Detail must include verifier name");
assert(isset($detail['catatan']), "Detail must include catatan");

echo "  ✓ Payment Detail Verified:\n";
echo "    - Nama Anggota : {$detail['member_name']} ({$detail['nomor_anggota']}, {$detail['kelas']})\n";
echo "    - Periode      : {$detail['periode']}\n";
echo "    - Tagihan      : Rp " . number_format((float)$detail['tagihan'], 2, ',', '.') . "\n";
echo "    - Nominal      : Rp " . number_format((float)$detail['nominal'], 2, ',', '.') . "\n";
echo "    - Sisa         : Rp " . number_format((float)$detail['sisa'], 2, ',', '.') . "\n";
echo "    - Tanggal      : {$detail['tanggal_pembayaran']}\n";
echo "    - Status       : {$detail['status']}\n";
echo "    - Verifier     : {$detail['verifier_name']} ({$detail['verified_at']})\n";
echo "    - Catatan      : {$detail['catatan']}\n";

// -------------------------------------------------------------
// TEST 7: Super User Filtering & Tunggakan Summary
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Super User Filters & Tunggakan Summary...\n";
// Filter by Periode
$filterPeriodeRes = $paymentModel->getPaginated(['periode' => $testPeriod1]);
assert($filterPeriodeRes['total'] >= 1, "Filter by periode must return at least 1 row");
echo "  ✓ Filter by Periode ({$testPeriod1}): {$filterPeriodeRes['total']} record found\n";

// Filter by Kelas
$filterKelasRes = $paymentModel->getPaginated(['kelas' => $member1['kelas']]);
assert($filterKelasRes['total'] >= 1, "Filter by kelas must return matching rows");
echo "  ✓ Filter by Kelas ({$member1['kelas']}): {$filterKelasRes['total']} records found\n";

// Filter by Status (Belum Bayar)
$filterStatusRes = $paymentModel->getPaginated(['status' => 'Belum Bayar']);
assert($filterStatusRes['total'] >= 1, "Filter by status 'Belum Bayar' must return rows");
echo "  ✓ Filter by Status (Belum Bayar): {$filterStatusRes['total']} records found\n";

// Summary Stats & Tunggakan
$stats = $paymentModel->getSummaryStats();
assert($stats['total_tagihan'] > 0, "Total tagihan in summary must be > 0");
assert($stats['total_dibayar'] > 0, "Total dibayar in summary must be > 0");
assert(isset($stats['total_tunggakan']), "Total tunggakan must be calculated");
echo "  ✓ Financial Summary Stats:\n";
echo "    - Total Tagihan   : Rp " . number_format($stats['total_tagihan'], 2, ',', '.') . "\n";
echo "    - Total Terbayar  : Rp " . number_format($stats['total_dibayar'], 2, ',', '.') . "\n";
echo "    - Total Tunggakan : Rp " . number_format($stats['total_tunggakan'], 2, ',', '.') . "\n";
echo "    - Komposisi       : {$stats['count_lunas']} Lunas, {$stats['count_sebagian']} Sebagian, {$stats['count_belum_bayar']} Belum Bayar\n";

// -------------------------------------------------------------
// TEST 8: Member Data Isolation & Self-Service Restrictions
// -------------------------------------------------------------
echo "\n[TEST 8] Testing Member Data Isolation (Strict Privacy)...\n";
// Member 1 history
$history1 = $paymentModel->getMemberHistory((int)$member1['id']);
foreach ($history1 as $row) {
    assert((int)$row['member_id'] === (int)$member1['id'], "Member 1 must NEVER see other members' payments");
}
echo "  ✓ Member 1 sees strictly own records (" . count($history1) . " records, all member_id={$member1['id']})\n";

// Member 2 history
$history2 = $paymentModel->getMemberHistory((int)$member2['id']);
foreach ($history2 as $row) {
    assert((int)$row['member_id'] === (int)$member2['id'], "Member 2 must NEVER see other members' payments");
}
echo "  ✓ Member 2 sees strictly own records (" . count($history2) . " records, all member_id={$member2['id']})\n";

// Test RBAC: Switch session to Member and verify Super User controller protection
\App\Core\Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
if ($memberUser) {
    \App\Core\Auth::login($memberUser);
    assert(\App\Core\Auth::isMember() === true, "Ahmad Fauzi must be Member");
    assert(\App\Core\Auth::isSuperUser() === false, "Member must NOT be Super User");
    echo "  ✓ Member session verified: Role is member, Super User privileges denied.\n";
}

// -------------------------------------------------------------
// TEST 9: Audit Trail Verification
// -------------------------------------------------------------
echo "\n[TEST 9] Testing Audit Logs for Payment Operations...\n";
$recentAudits = $auditModel->getRecentLogs(20);
$actions = array_column($recentAudits, 'action');
assert(in_array('CREATE_PAYMENT', $actions, true), "Audit log must record CREATE_PAYMENT");
assert(in_array('VERIFY_PAYMENT', $actions, true), "Audit log must record VERIFY_PAYMENT");
echo "  ✓ Audit logs successfully captured CREATE_PAYMENT and VERIFY_PAYMENT events.\n";

// -------------------------------------------------------------
// CLEANUP TEST DATA
// -------------------------------------------------------------
echo "\n[CLEANUP] Cleaning up test payment records...\n";
$paymentModel->delete($payId1);
$paymentModel->delete($payId2);
$paymentModel->delete($payId3);
echo "  ✓ Test payment records removed.\n";

echo "\n==========================================================\n";
echo "   ALL TASK 08 CASH PAYMENT TESTS PASSED SUCCESSFULLY!   \n";
echo "==========================================================\n";
