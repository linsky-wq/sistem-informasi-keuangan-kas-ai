<?php
/**
 * Automated Test Suite: Task 09 — Super User Dashboard Analytics, Summaries, Charts & Payment Alerts
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
echo "   TASK 09: SUPER USER DASHBOARD & ANALYTICS TESTS        \n";
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

// -------------------------------------------------------------
// TEST 1: Summary Cards Calculation & Financial Metrics
// -------------------------------------------------------------
echo "[TEST 1] Testing Summary Cards Data & Formula...\n";
$totalIncome = $trxModel->getTotalIncome();
$totalExpense = $trxModel->getTotalExpense();
$currentBalance = $trxModel->getCurrentBalance();
$totalMembers = $memberModel->count();

$paymentStats = $paymentModel->getSummaryStats();
$statusDistribution = $paymentModel->getStatusDistribution();

$countPaid = $statusDistribution['lunas'];
$countUnpaid = $statusDistribution['belumBayar'] + $statusDistribution['sebagian'];

assert($currentBalance === ($totalIncome - $totalExpense), "Saldo Kas formula must equal Total Income - Total Expense");
assert($totalMembers >= 0, "Total members must be numeric >= 0");
assert($countPaid >= 0, "Sudah Bayar count must be numeric >= 0");
assert($countUnpaid >= 0, "Belum Bayar count must be numeric >= 0");

echo "  ✓ Card 1 (Total Pemasukan)   : Rp " . number_format($totalIncome, 2, ',', '.') . "\n";
echo "  ✓ Card 2 (Total Pengeluaran) : Rp " . number_format($totalExpense, 2, ',', '.') . "\n";
echo "  ✓ Card 3 (Saldo Kas Bersih)  : Rp " . number_format($currentBalance, 2, ',', '.') . " (Formula: In - Out)\n";
echo "  ✓ Card 4 (Jumlah Anggota)    : {$totalMembers} Siswa\n";
echo "  ✓ Card 5 (Sudah Bayar)       : {$countPaid} Lunas\n";
echo "  ✓ Card 6 (Belum Bayar)       : {$countUnpaid} Pending (Belum / Sebagian)\n";

// -------------------------------------------------------------
// TEST 2: Grafik 1 — Pemasukan vs Pengeluaran (Monthly Comparison)
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Grafik 1: Pemasukan vs Pengeluaran Monthly Analytics...\n";
$monthly = $trxModel->getMonthlyComparison(6);
assert(isset($monthly['labels']), "Monthly analytics must include labels array");
assert(isset($monthly['income']), "Monthly analytics must include income array");
assert(isset($monthly['expense']), "Monthly analytics must include expense array");
assert(isset($monthly['hasData']), "Monthly analytics must include hasData flag");

echo "  ✓ Monthly data points retrieved: " . count($monthly['labels']) . " months.\n";
foreach ($monthly['labels'] as $idx => $label) {
    echo "    - {$label}: In = Rp " . number_format($monthly['income'][$idx], 2, ',', '.') . " | Out = Rp " . number_format($monthly['expense'][$idx], 2, ',', '.') . "\n";
}

// -------------------------------------------------------------
// TEST 3: Grafik 2 — Trend Saldo Kas (Cumulative Progression)
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Grafik 2: Trend Saldo Kas Cumulative Progression...\n";
$trend = $trxModel->getBalanceTrend(15);
assert(isset($trend['labels']), "Trend analytics must include labels array");
assert(isset($trend['data']), "Trend analytics must include data array");
assert(isset($trend['hasData']), "Trend analytics must include hasData flag");

echo "  ✓ Balance trend data points retrieved: " . count($trend['labels']) . " chronological points.\n";
if (!empty($trend['labels'])) {
    $firstIdx = 0;
    $lastIdx = count($trend['labels']) - 1;
    echo "    - First recorded balance ({$trend['labels'][$firstIdx]}): Rp " . number_format($trend['data'][$firstIdx], 2, ',', '.') . "\n";
    echo "    - Latest recorded balance ({$trend['labels'][$lastIdx]}): Rp " . number_format($trend['data'][$lastIdx], 2, ',', '.') . "\n";
}

// -------------------------------------------------------------
// TEST 4: Grafik 3 — Status Pembayaran (Doughnut / Pie Chart)
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Grafik 3: Status Pembayaran Distribution...\n";
assert(is_array($statusDistribution['labels']), "Status distribution must provide labels array");
assert(is_array($statusDistribution['data']), "Status distribution must provide data array");
assert(count($statusDistribution['labels']) === 3, "Labels must consist of 3 items: Lunas, Sebagian, Belum Bayar");
assert(count($statusDistribution['data']) === 3, "Data must consist of 3 counts");

echo "  ✓ Status Distribution Data:\n";
echo "    - Lunas       : {$statusDistribution['lunas']}\n";
echo "    - Sebagian    : {$statusDistribution['sebagian']}\n";
echo "    - Belum Bayar : {$statusDistribution['belumBayar']}\n";
echo "    - Total Iuran : {$statusDistribution['total']}\n";

// -------------------------------------------------------------
// TEST 5: Transaksi Terbaru (5 Recent Transactions)
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Transaksi Terbaru (5 Recent Transactions)...\n";
$recentTransactions = $trxModel->getAllWithRelations(['limit' => 5]);
assert(count($recentTransactions) <= 5, "Recent transactions must be at most 5");

if (!empty($recentTransactions)) {
    echo "  ✓ Retrieved " . count($recentTransactions) . " recent transactions:\n";
    foreach ($recentTransactions as $t) {
        // Validate required columns
        assert(!empty($t['tanggal']), "Transaction must have tanggal");
        assert(!empty($t['kode_transaksi']), "Transaction must have kode_transaksi");
        assert(!empty($t['tipe']), "Transaction must have tipe");
        assert(isset($t['nominal']), "Transaction must have nominal");
        assert(isset($t['keterangan']), "Transaction must have keterangan");

        echo "    - [{$t['tanggal']}] {$t['kode_transaksi']} ({$t['tipe']}): Rp " . number_format((float)$t['nominal'], 2, ',', '.') . " — {$t['keterangan']}\n";
    }
} else {
    echo "  ✓ No transactions recorded yet (Empty state gracefully handled)\n";
}

// -------------------------------------------------------------
echo "    - Jumlah Pengguna/Anggota Belum Bayar : {$unpaidMembersCount} siswa\n";
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Payment Alert (Unpaid Members & Tunggakan)...\n";
$unpaidMembersCount = $paymentModel->getUnpaidMembersCount();
$totalTunggakan = $paymentStats['total_tunggakan'];

assert($unpaidMembersCount >= 0, "Unpaid members count must be >= 0");
assert($totalTunggakan >= 0, "Total tunggakan must be >= 0");

echo "  ✓ Payment Alert Data:\n";
echo "    - Jumlah Pengguna/Anggota Belum Bayar : {$unpaidMembersCount} siswa\n";
echo "    - Total Tunggakan Kas              : Rp " . number_format($totalTunggakan, 2, ',', '.') . "\n";

// -------------------------------------------------------------
// TEST 7: Dashboard HTML Rendering & Structure Verification
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Dashboard View Structure & Components...\n";
$viewPath = dirname(__DIR__) . '/app/views/dashboard/index.php';
assert(file_exists($viewPath), "Dashboard view file must exist");
$viewContent = file_get_contents($viewPath);

assert(strpos($viewContent, 'chartIncomeExpense') !== false, "View must contain chartIncomeExpense element");
assert(strpos($viewContent, 'chartBalanceTrend') !== false, "View must contain chartBalanceTrend element");
assert(strpos($viewContent, 'chartPaymentStatus') !== false, "View must contain chartPaymentStatus element");
assert(strpos($viewContent, 'Peringatan Pembayaran Kas (Payment Alert)') !== false, "View must contain Payment Alert element");
assert(strpos($viewContent, 'Total Pemasukan') !== false, "View must contain Total Pemasukan card");
assert(strpos($viewContent, 'Total Pengeluaran') !== false, "View must contain Total Pengeluaran card");
assert(strpos($viewContent, 'Saldo Kas Bersih') !== false, "View must contain Saldo Kas Bersih card");
assert(strpos($viewContent, 'Jumlah Anggota') !== false, "View must contain Jumlah Anggota card");
assert(strpos($viewContent, 'Sudah Bayar') !== false, "View must contain Sudah Bayar card");
assert(strpos($viewContent, 'Belum Bayar') !== false, "View must contain Belum Bayar card");

echo "  ✓ Dashboard HTML structure & Chart.js instances verified successfully.\n";

echo "\n==========================================================\n";
echo "   ALL TASK 09 SUPER USER DASHBOARD TESTS PASSED!        \n";
echo "==========================================================\n";
