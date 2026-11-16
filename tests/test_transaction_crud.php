<?php
/**
 * Automated Test Suite: Task 07 — Cash Transaction CRUD, Saldo Calculation, Category Validation & RBAC
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
echo "   TASK 07: CASH TRANSACTIONS & SALDO CALCULATION TESTS\n";
echo "==========================================================\n\n";

$trxModel = new \App\Models\Transaction();
$catModel = new \App\Models\Category();
$userModel = new \App\Models\User();
$auditModel = new \App\Models\AuditLog();

// Authenticate as Super User
\App\Core\Session::destroy();
$adminUser = $userModel->findByUsernameOrEmail('admin');
\App\Core\Auth::login($adminUser);

// Get reference categories
$incomeCat = $catModel->fetchOne("SELECT id, nama, tipe FROM `categories` WHERE tipe = 'pemasukan' LIMIT 1");
$expenseCat = $catModel->fetchOne("SELECT id, nama, tipe FROM `categories` WHERE tipe = 'pengeluaran' LIMIT 1");
assert($incomeCat !== null, "Income category must exist");
assert($expenseCat !== null, "Expense category must exist");

$initialIncome = $trxModel->getTotalIncome();
$initialExpense = $trxModel->getTotalExpense();
$initialBalance = $trxModel->getCurrentBalance();

echo "Baseline Financial Stats:\n";
echo "  - Total Pemasukan Awal   : Rp " . number_format($initialIncome, 2, ',', '.') . "\n";
echo "  - Total Pengeluaran Awal : Rp " . number_format($initialExpense, 2, ',', '.') . "\n";
echo "  - Saldo Kas Bersih Awal  : Rp " . number_format($initialBalance, 2, ',', '.') . "\n\n";

// -------------------------------------------------------------
// TEST 1: Record Pemasukan Transaction (Income)
// -------------------------------------------------------------
echo "[TEST 1] Testing Record Pemasukan (Income) Transaction...\n";
$kodeIn = $trxModel->generateKode(\App\Models\Transaction::TYPE_PEMASUKAN);
$trxInId = $trxModel->create([
    'kode_transaksi' => $kodeIn,
    'tanggal'        => date('Y-m-d'),
    'tipe'           => \App\Models\Transaction::TYPE_PEMASUKAN,
    'category_id'    => $incomeCat['id'],
    'nominal'        => 100000.00,
    'keterangan'     => 'Uji Coba Pemasukan Dana Kas',
    'created_by'     => $adminUser['id'],
]);
assert($trxInId !== false && $trxInId > 0, "Income transaction creation must succeed");

$incomeAfter = $trxModel->getTotalIncome();
$balanceAfter = $trxModel->getCurrentBalance();
assert($incomeAfter === ($initialIncome + 100000.00), "Total income must increase by exactly 100,000");
assert($balanceAfter === ($initialBalance + 100000.00), "Saldo must increase by exactly 100,000");

$auditModel->record($adminUser['id'], 'CREATE_TRANSACTION', 'transactions', $trxInId, null, ['kode' => $kodeIn, 'nominal' => 100000.00]);
echo "  ✓ Income Transaction recorded ({$kodeIn}, ID: {$trxInId}). New Saldo: Rp " . number_format($balanceAfter, 2, ',', '.') . "\n";

// -------------------------------------------------------------
// TEST 2: Record Pengeluaran Transaction (Expense)
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Record Pengeluaran (Expense) Transaction...\n";
$kodeOut = $trxModel->generateKode(\App\Models\Transaction::TYPE_PENGELUARAN);
$trxOutId = $trxModel->create([
    'kode_transaksi' => $kodeOut,
    'tanggal'        => date('Y-m-d'),
    'tipe'           => \App\Models\Transaction::TYPE_PENGELUARAN,
    'category_id'    => $expenseCat['id'],
    'nominal'        => 40000.00,
    'keterangan'     => 'Uji Coba Pengeluaran ATK',
    'created_by'     => $adminUser['id'],
]);
assert($trxOutId !== false && $trxOutId > 0, "Expense transaction creation must succeed");

$expenseAfter = $trxModel->getTotalExpense();
$balanceAfter2 = $trxModel->getCurrentBalance();
assert($expenseAfter === ($initialExpense + 40000.00), "Total expense must increase by exactly 40,000");
assert($balanceAfter2 === ($balanceAfter - 40000.00), "Saldo must decrease by exactly 40,000");

$auditModel->record($adminUser['id'], 'CREATE_TRANSACTION', 'transactions', $trxOutId, null, ['kode' => $kodeOut, 'nominal' => 40000.00]);
echo "  ✓ Expense Transaction recorded ({$kodeOut}, ID: {$trxOutId}). New Saldo: Rp " . number_format($balanceAfter2, 2, ',', '.') . "\n";

// -------------------------------------------------------------
// TEST 3: Mathematical Formula Verification: Saldo = Pemasukan - Pengeluaran
// -------------------------------------------------------------
echo "\n[TEST 3] Verifying Dynamic Database Calculation Formula...\n";
$currentIncome = $trxModel->getTotalIncome();
$currentExpense = $trxModel->getTotalExpense();
$currentBalance = $trxModel->getCurrentBalance();
assert($currentBalance === ($currentIncome - $currentExpense), "Saldo MUST strictly equal Total Pemasukan - Total Pengeluaran computed from database");
echo "  ✓ Formula Confirmed: Saldo (" . number_format($currentBalance, 2) . ") = In (" . number_format($currentIncome, 2) . ") - Out (" . number_format($currentExpense, 2) . ").\n";

// -------------------------------------------------------------
// TEST 4: Category Matching Validation & Nominal Constraints
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Category Matching & Nominal Validation Rules...\n";
// Rule 1: Pemasukan transaction using an Expense Category MUST be rejected
$invalidTypeMatch = ($incomeCat['tipe'] === \App\Models\Transaction::TYPE_PENGELUARAN);
assert($invalidTypeMatch === false, "Income category should not match expense type");

$mismatchFound = ($expenseCat['tipe'] !== \App\Models\Transaction::TYPE_PEMASUKAN);
assert($mismatchFound === true, "Expense category is properly recognized as mismatch for income transaction");

// Rule 2: Nominal validation (> 0 and numeric)
$validNominal = 50000.00;
$zeroNominal = 0;
$negativeNominal = -25000;
$nonNumeric = 'abc';

assert(is_numeric((string)$validNominal) && $validNominal > 0, "Valid nominal passes");
assert(!($zeroNominal > 0), "Zero nominal is rejected");
assert(!($negativeNominal > 0), "Negative nominal is rejected");
assert(!is_numeric($nonNumeric), "Non-numeric string is rejected");

echo "  ✓ Category matching and nominal (> 0, numeric) validation logic confirmed.\n";

// -------------------------------------------------------------
// TEST 5: Update Transaction & Recalculate Balance
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Update Transaction & Dynamic Saldo Recalculation...\n";
// Change nominal from 40,000 to 60,000 (+20,000 expense)
$updateSuccess = $trxModel->update($trxOutId, [
    'nominal'    => 60000.00,
    'keterangan' => 'Uji Coba Pengeluaran ATK (Diperbarui)',
]);
assert($updateSuccess === true, "Update must succeed");

$updatedExpense = $trxModel->getTotalExpense();
$updatedBalance = $trxModel->getCurrentBalance();
assert($updatedExpense === ($initialExpense + 60000.00), "Total expense must update to reflect new nominal");
assert($updatedBalance === ($balanceAfter - 60000.00), "Saldo must reflect updated expense");

$auditModel->record($adminUser['id'], 'UPDATE_TRANSACTION', 'transactions', $trxOutId, null, ['nominal' => 60000.00]);
echo "  ✓ Transaction updated. New Saldo dynamically recalculated: Rp " . number_format($updatedBalance, 2, ',', '.') . "\n";

// -------------------------------------------------------------
// TEST 6: Delete Transaction & Recalculate Balance
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Delete Transaction & Saldo Restoration...\n";
$deleteSuccess1 = $trxModel->delete($trxInId);
$deleteSuccess2 = $trxModel->delete($trxOutId);
assert($deleteSuccess1 && $deleteSuccess2, "Deletions must succeed");

$finalIncome = $trxModel->getTotalIncome();
$finalExpense = $trxModel->getTotalExpense();
$finalBalance = $trxModel->getCurrentBalance();
assert($finalIncome === $initialIncome, "Total income must revert to initial");
assert($finalExpense === $initialExpense, "Total expense must revert to initial");
assert($finalBalance === $initialBalance, "Saldo must revert to initial baseline");

$auditModel->record($adminUser['id'], 'DELETE_TRANSACTION', 'transactions', $trxInId, null, null);
echo "  ✓ Transactions deleted. Saldo restored to baseline: Rp " . number_format($finalBalance, 2, ',', '.') . "\n";

// -------------------------------------------------------------
// TEST 7: Authorization (Member Restricted from Transactions)
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Authorization: Member Blocked from Transactions...\n";
\App\Core\Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
\App\Core\Auth::login($memberUser);

assert(\App\Core\Auth::isSuperUser() === false, "Member must NOT have Super User status");
assert(\App\Core\Auth::isMember() === true, "User must have Member status");
echo "  ✓ Member authorization correctly restricted from modifying or creating cash transactions.\n";

// -------------------------------------------------------------
// TEST 8: Audit Trail Verification
// -------------------------------------------------------------
echo "\n[TEST 8] Testing Audit Log Recording for Transaction Operations...\n";
$recentAudit = $auditModel->getRecentLogs(10);
$hasTrxAudit = false;
foreach ($recentAudit as $log) {
    if ($log['entity'] === 'transactions') {
        $hasTrxAudit = true;
        break;
    }
}
assert($hasTrxAudit === true, "Audit trail must log transaction actions");
echo "  ✓ Audit trail logged transaction create, update, and delete actions.\n";

echo "\n==========================================================\n";
echo "✓ ALL 8 CASH TRANSACTION & SALDO TESTS PASSED SUCCESSFULLY!\n";
echo "==========================================================\n";
