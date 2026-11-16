<?php
/**
 * Test Suite: Total Income Breakdown on Dashboard (Iuran, Donasi, Sumbangan)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

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

echo "=================================================================\n";
echo " TEST SUITE: RINCIAN TOTAL PEMASUKAN PADA DASHBOARD\n";
echo "=================================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $label): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$label}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$label}\n";
    }
}

$trxModel = new \App\Models\Transaction();
$userModel = new \App\Models\User();

// -------------------------------------------------------------
// SECTION 1: BREAKDOWN CALCULATION & EXACT MATCH WITH TOTAL
// -------------------------------------------------------------
echo "--- SECTION 1: BREAKDOWN CALCULATION & EXACT MATCH ---\n";

$totalIncome = $trxModel->getTotalIncome();
$breakdown = $trxModel->getIncomeBreakdown();

echo "  Total Income: Rp " . number_format($totalIncome, 2, ',', '.') . "\n";
echo "  - Iuran    : Rp " . number_format($breakdown['iuran'], 2, ',', '.') . "\n";
echo "  - Donasi   : Rp " . number_format($breakdown['donasi'], 2, ',', '.') . "\n";
echo "  - Sumbangan: Rp " . number_format($breakdown['sumbangan'], 2, ',', '.') . "\n";
if (!empty($breakdown['lainnya'])) {
    echo "  - Lainnya  : Rp " . number_format($breakdown['lainnya'], 2, ',', '.') . "\n";
}

assertTest(isset($breakdown['iuran']), "Breakdown contains 'iuran' key");
assertTest(isset($breakdown['donasi']), "Breakdown contains 'donasi' key");
assertTest(isset($breakdown['sumbangan']), "Breakdown contains 'sumbangan' key");
assertTest(isset($breakdown['total']), "Breakdown contains 'total' key");

$sumOfParts = $breakdown['iuran'] + $breakdown['donasi'] + $breakdown['sumbangan'] + $breakdown['lainnya'];
assertTest(abs($sumOfParts - $totalIncome) < 0.001, "Sum of breakdown parts (" . number_format($sumOfParts, 2) . ") EXACTLY matches getTotalIncome() (" . number_format($totalIncome, 2) . ")");
assertTest(abs($breakdown['total'] - $totalIncome) < 0.001, "Breakdown total field matches getTotalIncome()");

// Specific actual database values
assertTest($breakdown['iuran'] > 0, "Total Iuran is positive from actual database: Rp " . number_format($breakdown['iuran'], 0, ',', '.'));
assertTest($breakdown['donasi'] > 0, "Total Donasi is positive from actual database: Rp " . number_format($breakdown['donasi'], 0, ',', '.'));

// -------------------------------------------------------------
// SECTION 2: DYNAMIC CALCULATION (INSERT, UPDATE, DELETE SIMULATION)
// -------------------------------------------------------------
echo "\n--- SECTION 2: DYNAMIC CALCULATION SIMULATION ---\n";

$pdo = \App\Core\Database::getInstance()->getConnection();
$pdo->beginTransaction();

try {
    // 2.1 Insert Sumbangan
    $stmt = $pdo->prepare("
        INSERT INTO transactions (kode_transaksi, tanggal, tipe, category_id, nominal, keterangan, created_by)
        VALUES ('TRX-TEST-SUMBANG-UNIT', '2026-11-14', 'pemasukan', 2, 75000.00, 'Sumbangan sukarela wali murid', 1)
    ");
    $stmt->execute();

    $bInsertSumbang = $trxModel->getIncomeBreakdown();
    assertTest(abs($bInsertSumbang['sumbangan'] - ($breakdown['sumbangan'] + 75000.0)) < 0.001, "Inserting Sumbangan transaction dynamically increases Total Sumbangan (+Rp 75.000)");
    assertTest(abs($bInsertSumbang['total'] - ($totalIncome + 75000.0)) < 0.001, "Total Pemasukan dynamically increases by same amount");

    // 2.2 Insert Donasi
    $stmt = $pdo->prepare("
        INSERT INTO transactions (kode_transaksi, tanggal, tipe, category_id, nominal, keterangan, created_by)
        VALUES ('TRX-TEST-DONASI-UNIT', '2026-11-14', 'pemasukan', 2, 125000.00, 'Donasi fasilitas kas', 1)
    ");
    $stmt->execute();

    $bInsertDonasi = $trxModel->getIncomeBreakdown();
    assertTest(abs($bInsertDonasi['donasi'] - ($breakdown['donasi'] + 125000.0)) < 0.001, "Inserting Donasi transaction dynamically increases Total Donasi (+Rp 125.000)");

    // 2.3 Insert Iuran
    $stmt = $pdo->prepare("
        INSERT INTO transactions (kode_transaksi, tanggal, tipe, category_id, nominal, keterangan, created_by)
        VALUES ('TRX-TEST-IURAN-UNIT', '2026-11-14', 'pemasukan', 1, 50000.00, 'Penerimaan Iuran Kas', 1)
    ");
    $stmt->execute();

    $bInsertIuran = $trxModel->getIncomeBreakdown();
    assertTest(abs($bInsertIuran['iuran'] - ($breakdown['iuran'] + 50000.0)) < 0.001, "Inserting Iuran transaction dynamically increases Total Iuran (+Rp 50.000)");

    // 2.4 Update Sumbangan
    $pdo->exec("UPDATE transactions SET nominal = 175000.00 WHERE kode_transaksi = 'TRX-TEST-SUMBANG-UNIT'");
    $bUpdateSumbang = $trxModel->getIncomeBreakdown();
    assertTest(abs($bUpdateSumbang['sumbangan'] - ($breakdown['sumbangan'] + 175000.0)) < 0.001, "Updating Sumbangan transaction to Rp 175.000 updates breakdown dynamically");

    // 2.5 Delete Sumbangan
    $pdo->exec("DELETE FROM transactions WHERE kode_transaksi = 'TRX-TEST-SUMBANG-UNIT'");
    $bDeleteSumbang = $trxModel->getIncomeBreakdown();
    assertTest(abs($bDeleteSumbang['sumbangan'] - $breakdown['sumbangan']) < 0.001, "Deleting Sumbangan transaction restores original Sumbangan balance");

} finally {
    $pdo->rollBack();
    echo "  [INFO] Database rolled back cleanly (Zero residual mutations).\n";
}

// -------------------------------------------------------------
// SECTION 3: DASHBOARD RENDERING & VIEW CONSISTENCY
// -------------------------------------------------------------
echo "\n--- SECTION 3: DASHBOARD RENDERING & VIEW CONSISTENCY ---\n";

// Login as Super User
$adminUser = $userModel->findByUsernameOrEmail('admin');
\App\Core\Session::destroy();
\App\Core\Auth::login($adminUser);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/dashboard';

ob_start();
$dashCtrl = new \App\Controllers\DashboardController();
$dashCtrl->index();
$htmlOutput = ob_get_clean();

assertTest(str_contains($htmlOutput, 'Total Pemasukan'), "Dashboard contains card header 'Total Pemasukan'");
assertTest(str_contains($htmlOutput, 'Rp ' . number_format($totalIncome, 0, ',', '.')), "Dashboard displays formatted Total Income: Rp " . number_format($totalIncome, 0, ',', '.'));
assertTest(str_contains($htmlOutput, 'Iuran'), "Dashboard displays breakdown row 'Iuran'");
assertTest(str_contains($htmlOutput, 'Donasi'), "Dashboard displays breakdown row 'Donasi'");
assertTest(str_contains($htmlOutput, 'Sumbangan'), "Dashboard displays breakdown row 'Sumbangan'");
assertTest(str_contains($htmlOutput, 'Rp ' . number_format($breakdown['iuran'], 0, ',', '.')), "Dashboard displays formatted Iuran nominal: Rp " . number_format($breakdown['iuran'], 0, ',', '.'));
assertTest(str_contains($htmlOutput, 'Rp ' . number_format($breakdown['donasi'], 0, ',', '.')), "Dashboard displays formatted Donasi nominal: Rp " . number_format($breakdown['donasi'], 0, ',', '.'));
assertTest(str_contains($htmlOutput, 'Rp ' . number_format($breakdown['sumbangan'], 0, ',', '.')), "Dashboard displays formatted Sumbangan nominal: Rp " . number_format($breakdown['sumbangan'], 0, ',', '.'));

// Login as Bendahara
$bendaharaUser = $userModel->findBy('role', 'bendahara');
if ($bendaharaUser) {
    \App\Core\Session::destroy();
    \App\Core\Auth::login($bendaharaUser);

    ob_start();
    $dashCtrl = new \App\Controllers\DashboardController();
    $dashCtrl->index();
    $htmlBendahara = ob_get_clean();

    assertTest(str_contains($htmlBendahara, 'Total Pemasukan'), "Bendahara Dashboard contains card 'Total Pemasukan'");
    assertTest(str_contains($htmlBendahara, 'Rp ' . number_format($breakdown['iuran'], 0, ',', '.')), "Bendahara Dashboard displays Iuran nominal");
    assertTest(str_contains($htmlBendahara, 'Rp ' . number_format($breakdown['donasi'], 0, ',', '.')), "Bendahara Dashboard displays Donasi nominal");
    assertTest(str_contains($htmlBendahara, 'Rp ' . number_format($breakdown['sumbangan'], 0, ',', '.')), "Bendahara Dashboard displays Sumbangan nominal");
}

// Relogin as admin
\App\Core\Session::destroy();
\App\Core\Auth::login($adminUser);

echo "\n=================================================================\n";
echo " RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed === 0) {
    echo "✓ ALL INCOME BREAKDOWN TESTS PASSED SUCCESSFULLY!\n";
} else {
    exit(1);
}

