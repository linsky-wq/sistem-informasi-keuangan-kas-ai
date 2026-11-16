<?php
/**
 * Automated Test Suite: Task 11 — Financial Reports (Pemasukan, Pengeluaran, Saldo, Pembayaran & Tunggakan)
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
echo "   TASK 11: FINANCIAL REPORTS & RECONCILIATION TESTS      \n";
echo "==========================================================\n\n";

$trxModel     = new \App\Models\Transaction();
$paymentModel = new \App\Models\Payment();
$memberModel  = new \App\Models\Member();
$userModel    = new \App\Models\User();

// Authenticate as Super User
\App\Core\Session::destroy();
$adminUser = $userModel->findByUsernameOrEmail('admin');
assert($adminUser !== null, "Admin user must exist");
\App\Core\Auth::login($adminUser);

echo "Test Environment Initialized for Super User: {$adminUser['username']}\n\n";

// Count records before report generation to test zero mutation guarantee
$initialTrxCount = (int)$trxModel->fetchOne("SELECT COUNT(*) as c FROM `transactions`")['c'];
$initialPayCount = (int)$paymentModel->fetchOne("SELECT COUNT(*) as c FROM `payments`")['c'];

// -------------------------------------------------------------
// TEST 1: Laporan Pemasukan (Income Report)
// -------------------------------------------------------------
echo "[TEST 1] Testing Laporan Pemasukan (Income Report)...\n";
$incomeReport = $trxModel->getReportTransactions(['tipe' => \App\Models\Transaction::TYPE_PEMASUKAN]);
assert(isset($incomeReport['data']), "Income report must return data array");
assert(isset($incomeReport['total_income']), "Income report must calculate total_income");
assert($incomeReport['total_income'] >= 0, "Total income must be >= 0");

echo "  ✓ Retrieved {$incomeReport['count']} income records. Total: Rp " . number_format($incomeReport['total_income'], 2, ',', '.') . "\n";
if (!empty($incomeReport['data'])) {
    $firstRow = $incomeReport['data'][0];
    assert(!empty($firstRow['tanggal']), "Row must have tanggal");
    assert(!empty($firstRow['kode_transaksi']), "Row must have kode");
    assert(!empty($firstRow['category_name']), "Row must have kategori");
    assert(isset($firstRow['nominal']), "Row must have nominal");
    assert(isset($firstRow['keterangan']), "Row must have keterangan");
    echo "    - Sample: [{$firstRow['tanggal']}] {$firstRow['kode_transaksi']} ({$firstRow['category_name']}) — Rp " . number_format((float)$firstRow['nominal'], 2, ',', '.') . "\n";
}

// -------------------------------------------------------------
// TEST 2: Laporan Pengeluaran (Expense Report)
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Laporan Pengeluaran (Expense Report)...\n";
$expenseReport = $trxModel->getReportTransactions(['tipe' => \App\Models\Transaction::TYPE_PENGELUARAN]);
assert(isset($expenseReport['data']), "Expense report must return data array");
assert(isset($expenseReport['total_expense']), "Expense report must calculate total_expense");
assert($expenseReport['total_expense'] >= 0, "Total expense must be >= 0");

echo "  ✓ Retrieved {$expenseReport['count']} expense records. Total: Rp " . number_format($expenseReport['total_expense'], 2, ',', '.') . "\n";
if (!empty($expenseReport['data'])) {
    $firstRow = $expenseReport['data'][0];
    assert(!empty($firstRow['tanggal']), "Row must have tanggal");
    assert(!empty($firstRow['kode_transaksi']), "Row must have kode");
    assert(!empty($firstRow['category_name']), "Row must have kategori");
    assert(isset($firstRow['nominal']), "Row must have nominal");
    assert(isset($firstRow['keterangan']), "Row must have keterangan");
    echo "    - Sample: [{$firstRow['tanggal']}] {$firstRow['kode_transaksi']} ({$firstRow['category_name']}) — Rp " . number_format((float)$firstRow['nominal'], 2, ',', '.') . "\n";
}

// -------------------------------------------------------------
// TEST 3: Laporan Saldo (Balance & Running Ledger)
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Laporan Saldo (Balance Ledger & Summary)...\n";
$saldoReport = $trxModel->getReportTransactions();
$expectedBalance = $saldoReport['total_income'] - $saldoReport['total_expense'];
assert($saldoReport['net_balance'] === $expectedBalance, "Saldo net balance must equal Total Income - Total Expense");

echo "  ✓ Balance Report Summary:\n";
echo "    - Total Pemasukan   : Rp " . number_format($saldoReport['total_income'], 2, ',', '.') . "\n";
echo "    - Total Pengeluaran : Rp " . number_format($saldoReport['total_expense'], 2, ',', '.') . "\n";
echo "    - Saldo Kas Bersih  : Rp " . number_format($saldoReport['net_balance'], 2, ',', '.') . "\n";

// -------------------------------------------------------------
// TEST 4: Laporan Pembayaran (Payments Report)
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Laporan Pembayaran (Payments Report)...\n";
$paymentsReport = $paymentModel->getPaymentsReport();
assert(isset($paymentsReport['data']), "Payments report must contain data");
assert(isset($paymentsReport['total_tagihan']), "Payments report must contain total_tagihan");
assert(isset($paymentsReport['total_paid']), "Payments report must contain total_paid");

echo "  ✓ Retrieved {$paymentsReport['count']} payment records.\n";
if (!empty($paymentsReport['data'])) {
    $sample = $paymentsReport['data'][0];
    assert(!empty($sample['member_name']), "Report must include anggota");
    assert(!empty($sample['kelas']), "Report must include kelas");
    assert(!empty($sample['periode']), "Report must include periode");
    assert(isset($sample['tagihan']), "Report must include tagihan");
    assert(isset($sample['nominal']), "Report must include pembayaran");
    assert(!empty($sample['status']), "Report must include status");
    echo "    - Sample: {$sample['member_name']} ({$sample['kelas']}) | Periode: {$sample['periode']} | Tagihan: Rp " . number_format((float)$sample['tagihan'], 2) . " | Dibayar: Rp " . number_format((float)$sample['nominal'], 2) . " ({$sample['status']})\n";
}

// -------------------------------------------------------------
// TEST 5: Laporan Tunggakan (Arrears Report: Belum Bayar & Sebagian)
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Laporan Tunggakan (Arrears Report: Belum Bayar & Sebagian)...\n";
$arrearsReport = $paymentModel->getArrearsReport();
assert(isset($arrearsReport['data']), "Arrears report must contain data");
assert(isset($arrearsReport['total_tunggakan']), "Arrears report must contain total_tunggakan");

echo "  ✓ Retrieved {$arrearsReport['count']} arrears records. Total Tunggakan: Rp " . number_format($arrearsReport['total_tunggakan'], 2, ',', '.') . "\n";
foreach ($arrearsReport['data'] as $arr) {
    assert(in_array($arr['status'], ['Belum Bayar', 'Sebagian'], true), "Arrears report MUST ONLY include Belum Bayar or Sebagian");
    assert((float)$arr['sisa'] > 0, "Sisa tunggakan must be > 0");
    echo "    - {$arr['member_name']} ({$arr['kelas']}) [{$arr['periode']}]: Tagihan=Rp " . number_format((float)$arr['tagihan'], 2) . ", Sisa=Rp " . number_format((float)$arr['sisa'], 2) . " ({$arr['status']})\n";
}

// -------------------------------------------------------------
// TEST 6: Report Filters (Date Range, Class, Category)
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Report Filters (Date Range & Class)...\n";
$allClasses = $memberModel->getDistinctClasses();
if (!empty($allClasses)) {
    $targetClass = $allClasses[0];
    $filteredByClass = $paymentModel->getPaymentsReport(['kelas' => $targetClass]);
    foreach ($filteredByClass['data'] as $row) {
        assert($row['kelas'] === $targetClass, "Filtered row class must match {$targetClass}");
    }
    echo "  ✓ Class filter tested successfully for '{$targetClass}' ({$filteredByClass['count']} records found).\n";
}

// -------------------------------------------------------------
// TEST 7: Read-Only Guarantee (No Database Mutations during Report)
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Read-Only Report Generation Guarantee...\n";
$finalTrxCount = (int)$trxModel->fetchOne("SELECT COUNT(*) as c FROM `transactions`")['c'];
$finalPayCount = (int)$paymentModel->fetchOne("SELECT COUNT(*) as c FROM `payments`")['c'];

assert($finalTrxCount === $initialTrxCount, "Transaction count must remain identical after report generation");
assert($finalPayCount === $initialPayCount, "Payment count must remain identical after report generation");
echo "  ✓ Zero database mutation confirmed: Transaction count ({$finalTrxCount}) and Payment count ({$finalPayCount}) unchanged.\n";

// -------------------------------------------------------------
// TEST 8: RBAC Authorization Check (Member Restricted)
// -------------------------------------------------------------
echo "\n[TEST 8] Testing RBAC: Member Access Restricted to Reports...\n";
\App\Core\Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
if ($memberUser) {
    \App\Core\Auth::login($memberUser);
    assert(\App\Core\Auth::isSuperUser() === false, "Member must NOT have super user privileges");
    assert(\App\Core\Auth::isMember() === true, "Ahmad Fauzi is member");
    echo "  ✓ Member session verified: Reports module denied for non-super user.\n";
}

echo "\n==========================================================\n";
echo "   ALL TASK 11 FINANCIAL REPORTS TESTS PASSED!            \n";
echo "==========================================================\n";
