<?php
/**
 * Test Suite: AI Chatbot Income Breakdown & Source Queries
 * Tests all required question variations, dynamic reactivity to transactions,
 * specific source handling, and non-regression of existing chatbot capabilities.
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
echo " TEST SUITE: AI CHATBOT RINCIAN TOTAL PEMASUKAN\n";
echo "=================================================================\n\n";

$db = \App\Core\Database::getInstance()->getConnection();
$ai = new \App\Services\AiService();
$trxModel = new \App\Models\Transaction();

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$message}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$message}\n";
        if ($details !== '') {
            echo "         Details: {$details}\n";
        }
        $failed++;
    }
}

// -----------------------------------------------------------------
// SECTION 1: UJI 10 VARIASI PERTANYAAN WAJIB (PROMPT SECTION 3)
// -----------------------------------------------------------------
echo "--- SECTION 1: 10 PERTANYAAN WAJIB DARI PROMPT ---\n";

// Baseline database numbers
$bd = $trxModel->getIncomeBreakdown();
$expectedTotalStr = number_format($bd['total'], 0, ',', '.');
$expectedIuranStr = number_format($bd['iuran'], 0, ',', '.');
$expectedDonasiStr = number_format($bd['donasi'], 0, ',', '.');
$expectedSumbangStr = number_format($bd['sumbangan'], 0, ',', '.');
$expectedIuranCnt = (int)($bd['counts']['iuran'] ?? 0);
$expectedDonasiCnt = (int)($bd['counts']['donasi'] ?? 0);
$expectedSumbangCnt = (int)($bd['counts']['sumbangan'] ?? 0);

// Q1: Berapa total pemasukan?
$r1 = $ai->ask('Berapa total pemasukan?', 1, 'super_user');
assertTest(
    $r1['metrics']['query_results']['intent'] === 'global_income',
    "Q1 'Berapa total pemasukan?' terdeteksi sebagai intent global_income"
);
assertTest(
    str_contains($r1['response'], $expectedTotalStr) &&
    str_contains($r1['response'], $expectedIuranStr) &&
    str_contains($r1['response'], $expectedDonasiStr) &&
    str_contains($r1['response'], $expectedSumbangStr),
    "Q1 menampilkan total pemasukan dan rincian lengkap 3 sumber dana",
    $r1['response']
);

// Q2: Total pemasukan berasal dari mana saja?
$r2 = $ai->ask('Total pemasukan berasal dari mana saja?', 1, 'super_user');
assertTest(
    $r2['metrics']['query_results']['intent'] === 'income_breakdown',
    "Q2 'Total pemasukan berasal dari mana saja?' terdeteksi sebagai intent income_breakdown"
);
assertTest(
    str_contains($r2['response'], "Iuran: Rp{$expectedIuranStr}") &&
    str_contains($r2['response'], "Donasi: Rp{$expectedDonasiStr}") &&
    str_contains($r2['response'], "Sumbangan: Rp{$expectedSumbangStr}"),
    "Q2 menyajikan format rincian bernomor (1. Iuran, 2. Donasi, 3. Sumbangan)",
    $r2['response']
);

// Q3: Berapa jumlah iuran?
$r3 = $ai->ask('Berapa jumlah iuran?', 1, 'super_user');
assertTest(
    $r3['metrics']['query_results']['intent'] === 'income_source_iuran',
    "Q3 'Berapa jumlah iuran?' terdeteksi sebagai intent income_source_iuran"
);
assertTest(
    str_contains($r3['response'], $expectedIuranStr) && !str_contains($r3['response'], "2. Donasi"),
    "Q3 menjawab spesifik iuran tanpa ringkasan lain yang tidak diminta",
    $r3['response']
);

// Q4: Berapa total donasi?
$r4 = $ai->ask('Berapa total donasi?', 1, 'super_user');
assertTest(
    $r4['metrics']['query_results']['intent'] === 'income_source_donasi',
    "Q4 'Berapa total donasi?' terdeteksi sebagai intent income_source_donasi"
);
assertTest(
    str_contains($r4['response'], $expectedDonasiStr) && !str_contains($r4['response'], "1. Iuran"),
    "Q4 menjawab spesifik donasi tanpa ringkasan lain yang tidak diminta",
    $r4['response']
);

// Q5: Berapa total sumbangan?
$r5 = $ai->ask('Berapa total sumbangan?', 1, 'super_user');
assertTest(
    $r5['metrics']['query_results']['intent'] === 'income_source_sumbangan',
    "Q5 'Berapa total sumbangan?' terdeteksi sebagai intent income_source_sumbangan"
);
assertTest(
    str_contains($r5['response'], $expectedSumbangStr) && !str_contains($r5['response'], "di luar tugas utama"),
    "Q5 menjawab spesifik sumbangan (Rp0) secara jujur sesuai database",
    $r5['response']
);

// Q6: Berapa pemasukan dari iuran?
$r6 = $ai->ask('Berapa pemasukan dari iuran?', 1, 'super_user');
assertTest(
    str_contains($r6['response'], $expectedIuranStr) && !str_contains($r6['response'], $expectedTotalStr),
    "Q6 'Berapa pemasukan dari iuran?' menjawab nominal iuran (bukan total keseluruhan)",
    $r6['response']
);

// Q7: Berapa pemasukan dari donasi dan sumbangan?
$r7 = $ai->ask('Berapa pemasukan dari donasi dan sumbangan?', 1, 'super_user');
$comboExpStr = number_format($bd['donasi'] + $bd['sumbangan'], 0, ',', '.');
assertTest(
    $r7['metrics']['query_results']['intent'] === 'income_source_donasi_sumbangan',
    "Q7 terdeteksi sebagai intent income_source_donasi_sumbangan"
);
assertTest(
    str_contains($r7['response'], $comboExpStr) &&
    str_contains($r7['response'], $expectedDonasiStr) &&
    str_contains($r7['response'], $expectedSumbangStr),
    "Q7 menjawab kombinasi donasi dan sumbangan secara spesifik",
    $r7['response']
);

// Q8: Tolong rincikan total pemasukan.
$r8 = $ai->ask('Tolong rincikan total pemasukan.', 1, 'super_user');
assertTest(
    str_contains($r8['response'], "Total pemasukan kas saat ini sebesar Rp{$expectedTotalStr}") &&
    str_contains($r8['response'], "Total keseluruhan: Rp{$expectedTotalStr}"),
    "Q8 'Tolong rincikan total pemasukan.' memuat format jawaban lengkap",
    $r8['response']
);

// Q9: Berapa masing-masing sumber pemasukan?
$r9 = $ai->ask('Berapa masing-masing sumber pemasukan?', 1, 'super_user');
assertTest(
    str_contains($r9['response'], "1. Iuran: Rp{$expectedIuranStr}") &&
    str_contains($r9['response'], "2. Donasi: Rp{$expectedDonasiStr}") &&
    str_contains($r9['response'], "3. Sumbangan: Rp{$expectedSumbangStr}"),
    "Q9 'Berapa masing-masing sumber pemasukan?' merinci ketiga sumber pemasukan",
    $r9['response']
);

// Q10: Berapa jumlah transaksi dari setiap sumber pemasukan?
$r10 = $ai->ask('Berapa jumlah transaksi dari setiap sumber pemasukan?', 1, 'super_user');
assertTest(
    $r10['metrics']['query_results']['intent'] === 'income_transactions_per_source',
    "Q10 terdeteksi sebagai intent income_transactions_per_source"
);
assertTest(
    str_contains($r10['response'], "Iuran: {$expectedIuranCnt} transaksi") &&
    str_contains($r10['response'], "Donasi: {$expectedDonasiCnt} transaksi") &&
    str_contains($r10['response'], "Sumbangan: {$expectedSumbangCnt} transaksi"),
    "Q10 menyajikan jumlah transaksi masing-masing sumber dana kas",
    $r10['response']
);

// -----------------------------------------------------------------
// SECTION 2: VARIASI BAHASA SEHARI-HARI / KALIMAT TIDAK BAKU
// -----------------------------------------------------------------
echo "\n--- SECTION 2: VARIASI BAHASA SEHARI-HARI ---\n";

$colloquials = [
    'Pemasukan kas itu dari mana aja?' => 'income_breakdown',
    'Sumber pemasukan kas dari mana saja?' => 'income_breakdown',
    'Asal usul total pemasukan' => 'income_breakdown',
    'Iuran kas berapa?' => 'income_source_iuran',
    'Donasi kas ada berapa?' => 'income_source_donasi',
    'Sumbangan kas berapa?' => 'income_source_sumbangan',
    'Ada berapa uang donasi yang masuk?' => 'income_source_donasi',
    'Total uang dari iuran berapa?' => 'income_source_iuran',
    'Rincian pemasukan kas' => 'income_breakdown',
    'Donasi dan sumbangan berapa totalnya?' => 'income_source_donasi_sumbangan',
];

foreach ($colloquials as $q => $expectedIntent) {
    $res = $ai->ask($q, 1, 'super_user');
    assertTest(
        ($res['metrics']['query_results']['intent'] ?? '') === $expectedIntent,
        "Pertanyaan sehari-hari: '{$q}' -> Intent {$expectedIntent}"
    );
}

// -----------------------------------------------------------------
// SECTION 3: REAKTIVITAS DINAMIS TERHADAP MUTASI DATABASE
// -----------------------------------------------------------------
echo "\n--- SECTION 3: REAKTIVITAS DINAMIS TERHADAP TRANSAKSI BARU ---\n";

$db->beginTransaction();

try {
    // 1. Tambah transaksi sumbangan Rp 80.000
    $catId = (int)$db->query("SELECT id FROM categories WHERE nama = 'Donasi & Sumbangan' LIMIT 1")->fetchColumn();
    $sqlInsert = "INSERT INTO transactions (kode_transaksi, tanggal, tipe, category_id, nominal, keterangan, created_by, created_at)
                  VALUES ('TRX-TEST-SUMBANG', '2026-11-14', 'pemasukan', :cat, 80000.00, 'Sumbangan sukarela donatur kas', 1, NOW())";
    $stmt = $db->prepare($sqlInsert);
    $stmt->execute(['cat' => $catId]);

    // Uji AI setelah transaksi sumbangan ditambah
    $aiAfterInsert = new \App\Services\AiService();
    $resSumbangNew = $aiAfterInsert->ask('Berapa total sumbangan?');
    assertTest(
        str_contains($resSumbangNew['response'], '80.000'),
        "Setelah tambah transaksi sumbangan (+Rp 80.000), AI menjawab Rp 80.000 secara dinamis",
        $resSumbangNew['response']
    );

    $resBreakdownNew = $aiAfterInsert->ask('Tolong rincikan total pemasukan.');
    assertTest(
        str_contains($resBreakdownNew['response'], 'Sumbangan: Rp80.000') &&
        str_contains($resBreakdownNew['response'], '1.888.000'),
        "Rincian pemasukan merefleksikan penambahan sumbangan dan kenaikan total keseluruhan",
        $resBreakdownNew['response']
    );

    // 2. Rollback mutasi agar database bersih
    $db->rollBack();
    echo "  [INFO] Database transaction rolled back cleanly.\n";

    // Uji kembali setelah rollback
    $aiAfterRollback = new \App\Services\AiService();
    $resRestored = $aiAfterRollback->ask('Berapa total sumbangan?');
    assertTest(
        str_contains($resRestored['response'], 'Rp0'),
        "Setelah rollback, total sumbangan kembali ke Rp0",
        $resRestored['response']
    );

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}

// -----------------------------------------------------------------
// SECTION 4: NON-REGRESI FITUR LAIN CHATBOT
// -----------------------------------------------------------------
echo "\n--- SECTION 4: REGRESI FITUR CHATBOT LAINNYA ---\n";

// Anggota
$rMember = $ai->ask('Ada berapa anggota?', 1, 'super_user');
assertTest(
    str_contains($rMember['response'], '13 orang'),
    "Fitur Anggota: 'Ada berapa anggota?' tetap normal (13 orang)"
);

// Pengeluaran
$rExp = $ai->ask('Berapa total pengeluaran kas?', 1, 'super_user');
assertTest(
    str_contains($rExp['response'], '85.000'),
    "Fitur Pengeluaran: 'Berapa total pengeluaran kas?' tetap normal (Rp 85.000)"
);

// Pemasukan Tanggal Tertentu
$rDate = $ai->ask('Berapa pemasukan tanggal 21 Oktober 2026?', 1, 'super_user');
assertTest(
    str_contains($rDate['response'], '158.000') && str_contains($rDate['response'], '7'),
    "Fitur Filter Tanggal: 'Berapa pemasukan tanggal 21 Oktober 2026?' tetap normal"
);

// Riwayat Transaksi Anggota Tertentu
$rAhmad = $ai->ask('Pemasukan Ahmad Fauzi ada berapa?', 1, 'super_user');
assertTest(
    str_contains($rAhmad['response'], 'Ahmad Fauzi') && str_contains($rAhmad['response'], '803.000'),
    "Fitur Anggota Spesifik: 'Pemasukan Ahmad Fauzi ada berapa?' tetap normal"
);

// Role Count
$rRoles = $ai->ask('Ada berapa role dalam sistem?', 1, 'super_user');
assertTest(
    str_contains($rRoles['response'], '4 role'),
    "Fitur Role: 'Ada berapa role dalam sistem?' tetap normal"
);

echo "\n=================================================================\n";
echo " RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed === 0) {
    echo "✓ ALL AI INCOME BREAKDOWN TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED.\n";
    exit(1);
}

