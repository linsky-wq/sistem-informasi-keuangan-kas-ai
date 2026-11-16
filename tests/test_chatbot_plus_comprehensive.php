<?php
/**
 * Test Suite: CHATBOT++ COMPREHENSIVE DATABASE-DRIVEN AI TEST
 * Tests all requirements from PROMPT CHATBOT++:
 * 1. Date queries ("pemasukan ditanggal 21 oct 2026 berapa?", "pengeluaran tanggal 21 Oktober 2026")
 * 2. Member queries ("pemasukan ahmad fauzi ada berapa?", "riwayat transaksi Ahmad Fauzi")
 * 3. Combination queries ("pembayaran Ahmad Fauzi pada tanggal 21 Oktober 2026")
 * 4. Category queries ("total iuran wajib mingguan bulan Oktober 2026")
 * 5. Period queries ("pemasukan bulan Oktober 2026", "rentang tanggal 1 sampai 21 Oktober 2026")
 * 6. Who paid queries ("siapa saja yang membayar pada tanggal 21 Oktober 2026")
 * 7. Multi-turn conversation context (Pemasukan 21 Okt -> Kalau pengeluarannya? -> Siapa yang bayar? -> Kalau Ahmad Fauzi?)
 * 8. Negative & Edge cases
 * 9. Comparison with direct database queries
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
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

use App\Services\AiService;
use App\Services\FinancialDataService;
use App\Core\Database;

$db = Database::getInstance()->getConnection();
$ai = new AiService();

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $desc\n";
        $passed++;
    } else {
        echo "  [FAIL] $desc -> $detail\n";
        $failed++;
    }
}

echo "=================================================================\n";
echo " TEST SUITE: CHATBOT++ PENINGKATAN KECERDASAN CHATBOT AI\n";
echo "=================================================================\n\n";

// --- SECTION 1: PERBAIKAN MASALAH SPESIFIK PENGGUNA ---
echo "--- 1. UJI PERBAIKAN MASALAH UTAMA (PROMPT SECTION 2) ---\n";

// Query 1.1: "pemasukan ditanggal 21 oct 2026 berapa?"
// Expected: NOT Rp 1.758.000,00! Must be Rp 158.000,00 from 7 transactions.
$res1 = $ai->ask("pemasukan ditanggal 21 oct 2026 berapa?", 1, 'super_user');
$dbTot1 = (float)$db->query("SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan' AND tanggal = '2026-10-21'")->fetchColumn();
assertTest(
    "1.1 Pertanyaan: 'pemasukan ditanggal 21 oct 2026 berapa?' tidak menjawab total keseluruhan",
    !str_contains($res1['response'], '1.758.000') && !str_contains($res1['response'], '1758000'),
    "Output: " . $res1['response']
);
assertTest(
    "1.1 Menjawab nominal akurat database (Rp 158.000) dan menyebutkan 7 transaksi",
    str_contains($res1['response'], '158.000') && str_contains($res1['response'], '7'),
    "Output: " . $res1['response'] . " | DB Total: $dbTot1"
);

// Query 1.2: "pemasukan ahmad fauzi ada berapa?"
// Expected: NOT Rp 1.758.000,00! Must be Ahmad Fauzi's specific transactions.
$res2 = $ai->ask("pemasukan ahmad fauzi ada berapa?", 1, 'super_user');
assertTest(
    "1.2 Pertanyaan: 'pemasukan ahmad fauzi ada berapa?' tidak menjawab total keseluruhan",
    !str_contains($res2['response'], '1.758.000') && !str_contains($res2['response'], '1758000'),
    "Output: " . $res2['response']
);
assertTest(
    "1.2 Menjawab data spesifik Ahmad Fauzi (Rp 803.000 / 23 transaksi kas)",
    str_contains($res2['response'], 'Ahmad Fauzi') && (str_contains($res2['response'], '803.000') || str_contains($res2['response'], '23')),
    "Output: " . $res2['response']
);

echo "\n--- 2. UJI 12 CONTOH PERTANYAAN WAJIB (PROMPT SECTION 9) ---\n";

// 1. Berapa total pemasukan kas saat ini?
$q1 = "Berapa total pemasukan kas saat ini?";
$r1 = $ai->ask($q1, 1, 'super_user');
$dbTotalIncome = (float)$db->query("SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan'")->fetchColumn();
assertTest(
    "2.1 '$q1' -> Menjawab total pemasukan kas riil Rp 1.758.000",
    str_contains($r1['response'], '1.758.000'),
    "Output: " . $r1['response']
);

// 2. Berapa pemasukan tanggal 21 Oktober 2026?
$q2 = "Berapa pemasukan tanggal 21 Oktober 2026?";
$r2 = $ai->ask($q2, 1, 'super_user');
assertTest(
    "2.2 '$q2' -> Menjawab Rp 158.000 dan 7 transaksi",
    str_contains($r2['response'], '158.000') && str_contains($r2['response'], '7'),
    "Output: " . $r2['response']
);

// 3. Berapa pengeluaran tanggal 21 Oktober 2026?
$q3 = "Berapa pengeluaran tanggal 21 Oktober 2026?";
$r3 = $ai->ask($q3, 1, 'super_user');
$dbExpense21 = (float)$db->query("SELECT COALESCE(SUM(nominal),0) FROM transactions WHERE tipe = 'pengeluaran' AND tanggal = '2026-10-21'")->fetchColumn();
assertTest(
    "2.3 '$q3' -> Menjawab Rp 0,00 secara jujur tanpa halusinasi",
    (str_contains($r3['response'], '0,00') || str_contains($r3['response'], 'Rp 0') || str_contains($r3['response'], 'Tidak ditemukan')) && !str_contains($r3['response'], '85.000'),
    "Output: " . $r3['response']
);

// 4. Pemasukan Ahmad Fauzi ada berapa?
$q4 = "Pemasukan Ahmad Fauzi ada berapa?";
$r4 = $ai->ask($q4, 1, 'super_user');
assertTest(
    "2.4 '$q4' -> Menyajikan data transaksi pemasukan Ahmad Fauzi",
    str_contains($r4['response'], 'Ahmad Fauzi') && str_contains($r4['response'], '803.000'),
    "Output: " . $r4['response']
);

// 5. Berapa pembayaran Ahmad Fauzi pada tanggal 21 Oktober 2026?
$q5 = "Berapa pembayaran Ahmad Fauzi pada tanggal 21 Oktober 2026?";
$r5 = $ai->ask($q5, 1, 'super_user');
assertTest(
    "2.5 '$q5' -> Menjawab nominal kombinasi Rp 103.000 dan 5 transaksi",
    str_contains($r5['response'], '103.000') && str_contains($r5['response'], '5'),
    "Output: " . $r5['response']
);

// 6. Tampilkan riwayat transaksi Ahmad Fauzi.
$q6 = "Tampilkan riwayat transaksi Ahmad Fauzi.";
$r6 = $ai->ask($q6, 1, 'super_user');
assertTest(
    "2.6 '$q6' -> Menampilkan daftar riwayat transaksi aktual",
    str_contains($r6['response'], 'TRX-IN') && str_contains($r6['response'], 'Ahmad Fauzi'),
    "Output: " . $r6['response']
);

// 7. Berapa pemasukan bulan Oktober 2026?
$q7 = "Berapa pemasukan bulan Oktober 2026?";
$r7 = $ai->ask($q7, 1, 'super_user');
$dbOctIncome = (float)$db->query("SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan' AND tanggal LIKE '2026-10%'")->fetchColumn();
assertTest(
    "2.7 '$q7' -> Menjawab Rp 1.008.000 (28 transaksi)",
    str_contains($r7['response'], '1.008.000') && str_contains($r7['response'], '28'),
    "Output: " . $r7['response']
);

// 8. Berapa total iuran wajib mingguan bulan Oktober 2026?
$q8 = "Berapa total iuran wajib mingguan bulan Oktober 2026?";
$r8 = $ai->ask($q8, 1, 'super_user');
assertTest(
    "2.8 '$q8' -> Menjawab kategori Iuran Wajib Mingguan Rp 1.008.000",
    str_contains($r8['response'], 'Iuran Wajib Mingguan') && str_contains($r8['response'], '1.008.000'),
    "Output: " . $r8['response']
);

// 9. Siapa saja yang membayar pada tanggal 21 Oktober 2026?
$q9 = "Siapa saja yang membayar pada tanggal 21 Oktober 2026?";
$r9 = $ai->ask($q9, 1, 'super_user');
assertTest(
    "2.9 '$q9' -> Menyebutkan nama Ahmad Fauzi dan Budi Santoso",
    str_contains($r9['response'], 'Ahmad Fauzi') && str_contains($r9['response'], 'Budi Santoso'),
    "Output: " . $r9['response']
);

// 10. Uji konteks percakapan bertingkat
echo "\n--- 3. UJI KONTEKS PERCAKAPAN BERTINGKAT (PROMPT SECTION 5) ---\n";
// Step A: Tanya Pemasukan 21 Okt
$turn1 = $ai->ask("Pemasukan tanggal 21 Oktober 2026 berapa?", 1, 'super_user');
$ctx1 = $turn1['metrics']['current_context'] ?? [];
assertTest(
    "3.1 Turn 1: Pemasukan tanggal 21 Oktober 2026 dijawab Rp 158.000",
    str_contains($turn1['response'], '158.000'),
    "Turn 1 Output: " . $turn1['response']
);

// Step B: Follow-up 1: "Kalau pengeluarannya?"
$turn2 = $ai->ask("Kalau pengeluarannya?", 1, 'super_user', $ctx1);
$ctx2 = $turn2['metrics']['current_context'] ?? [];
assertTest(
    "3.2 Turn 2: 'Kalau pengeluarannya?' mewarisi tanggal 21 Okt & menjawab Rp 0,00",
    (str_contains($turn2['response'], '0,00') || str_contains($turn2['response'], 'Rp 0') || str_contains($turn2['response'], 'Tidak ditemukan')) && !str_contains($turn2['response'], '85.000'),
    "Turn 2 Output: " . $turn2['response']
);

// Step C: Follow-up 2: "Siapa saja yang melakukan pembayaran?"
$turn3 = $ai->ask("Siapa saja yang melakukan pembayaran?", 1, 'super_user', $ctx2);
$ctx3 = $turn3['metrics']['current_context'] ?? [];
assertTest(
    "3.3 Turn 3: 'Siapa saja yang melakukan pembayaran?' mewarisi tanggal 21 Okt & menampilkan Ahmad Fauzi & Budi Santoso",
    str_contains($turn3['response'], 'Ahmad Fauzi') && str_contains($turn3['response'], 'Budi Santoso'),
    "Turn 3 Output: " . $turn3['response']
);

// Step D: Follow-up 3: "Kalau Ahmad Fauzi?"
$turn4 = $ai->ask("Kalau Ahmad Fauzi?", 1, 'super_user', $ctx3);
assertTest(
    "3.4 Turn 4: 'Kalau Ahmad Fauzi?' mewarisi tanggal 21 Okt & menampilkan Rp 103.000 (5 transaksi)",
    str_contains($turn4['response'], '103.000') && str_contains($turn4['response'], '5'),
    "Turn 4 Output: " . $turn4['response']
);

// 11. Tampilkan data anggota yang belum membayar.
echo "\n--- 4. UJI TUNGGAKAN DAN SALDO (PROMPT ITEMS 11 & 12) ---\n";
$q11 = "Tampilkan data anggota yang belum membayar.";
$r11 = $ai->ask($q11, 1, 'super_user');
assertTest(
    "4.1 '$q11' -> Menyebutkan anggota yang memiliki sisa tunggakan",
    str_contains($r11['response'], 'Citra Lestari') && str_contains($r11['response'], 'Ahmad Fauzi'),
    "Output: " . $r11['response']
);

// 12. Berapa saldo kas berdasarkan data terbaru?
$q12 = "Berapa saldo kas berdasarkan data terbaru?";
$r12 = $ai->ask($q12, 1, 'super_user');
$dbBal = (float)$db->query("SELECT (SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan') - (SELECT SUM(nominal) FROM transactions WHERE tipe = 'pengeluaran')")->fetchColumn();
assertTest(
    "4.2 '$q12' -> Menjawab saldo kas aktual Rp 1.673.000",
    str_contains($r12['response'], '1.673.000'),
    "Output: " . $r12['response']
);

echo "\n--- 5. UJI VARIASI PERTANYAAN TAMBAHAN (PROMPT SECTION 3) ---\n";

// 5.1 Rentang Tanggal: "Tampilkan pemasukan dari tanggal 1 sampai 21 Oktober 2026."
$rRange = $ai->ask("Tampilkan pemasukan dari tanggal 1 sampai 21 Oktober 2026.", 1, 'super_user');
assertTest(
    "5.1 Rentang tanggal 1-21 Okt: Rp 1.008.000 dari 28 transaksi",
    str_contains($rRange['response'], '1.008.000') && str_contains($rRange['response'], '28'),
    "Output: " . $rRange['response']
);

// 5.2 Pengeluaran minggu ini: "Berapa pengeluaran minggu ini?"
$rWeek = $ai->ask("Berapa pengeluaran minggu ini?", 1, 'super_user');
assertTest(
    "5.2 Pengeluaran minggu ini: Rp 0,00",
    str_contains($rWeek['response'], '0') && !str_contains($rWeek['response'], '85.000'),
    "Output: " . $rWeek['response']
);

// 5.3 Transaksi tahun 2026: "Berapa total transaksi tahun 2026?"
$rYear = $ai->ask("Berapa total transaksi tahun 2026?", 1, 'super_user');
assertTest(
    "5.3 Transaksi tahun 2026: 31 transaksi",
    str_contains($rYear['response'], '31') && str_contains($rYear['response'], '1.673.000'),
    "Output: " . $rYear['response']
);

// 5.4 Total tunggakan Ahmad Fauzi: "Berapa total tunggakan Ahmad Fauzi?"
$rTunggakan = $ai->ask("Berapa total tunggakan Ahmad Fauzi?", 1, 'super_user');
assertTest(
    "5.4 Total tunggakan Ahmad Fauzi: Rp 147.000",
    str_contains($rTunggakan['response'], '147.000') && str_contains($rTunggakan['response'], 'Ahmad Fauzi'),
    "Output: " . $rTunggakan['response']
);

// 5.5 Pembayaran bulan ini: "Apakah Ahmad Fauzi sudah membayar iuran bulan ini?"
$rPaidMonth = $ai->ask("Apakah Ahmad Fauzi sudah membayar iuran bulan ini?", 1, 'super_user');
assertTest(
    "5.5 Ahmad Fauzi sudah membayar iuran bulan ini (Oktober 2026)",
    str_contains($rPaidMonth['response'], 'sudah') && str_contains($rPaidMonth['response'], 'Oktober 2026'),
    "Output: " . $rPaidMonth['response']
);

echo "\n=================================================================\n";
echo " RESULTS: $passed PASSED | $failed FAILED\n";
echo "=================================================================\n";

exit($failed > 0 ? 1 : 0);

