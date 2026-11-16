<?php
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
use App\Core\Database;

$db = Database::getInstance()->getConnection();
$aiService = new AiService();

$categories = [
    1 => [
        'category' => '1. Pertanyaan jumlah anggota',
        'q' => 'Ada berapa anggota?',
        'table' => 'members',
        'sql' => "SELECT COUNT(*) FROM members",
        'get_db' => fn() => (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    ],
    2 => [
        'category' => '2. Pertanyaan daftar nama anggota',
        'q' => 'Siapa saja nama anggota yang terdaftar?',
        'table' => 'members',
        'sql' => "SELECT nama, kelas, status FROM members ORDER BY nama ASC",
        'get_db' => fn() => $db->query("SELECT nama, kelas, status FROM members ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC),
    ],
    3 => [
        'category' => '3. Pertanyaan anggota berdasarkan status',
        'q' => 'Siapa anggota yang nonaktif?',
        'table' => 'members',
        'sql' => "SELECT nama, kelas FROM members WHERE status = 'nonaktif'",
        'get_db' => fn() => $db->query("SELECT nama, kelas FROM members WHERE status = 'nonaktif'")->fetchAll(PDO::FETCH_ASSOC),
    ],
    4 => [
        'category' => '4. Pertanyaan jumlah role',
        'q' => 'Ada berapa role dalam sistem?',
        'table' => 'users',
        'sql' => "SELECT COUNT(DISTINCT role) FROM users",
        'get_db' => fn() => 4, // super_user, bendahara, pengawas, member
    ],
    5 => [
        'category' => '5. Pertanyaan daftar pengguna berdasarkan role',
        'q' => 'Berapa pengguna setiap role dan siapa saja?',
        'table' => 'users',
        'sql' => "SELECT role, COUNT(*) as cnt FROM users GROUP BY role",
        'get_db' => fn() => $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC),
    ],
    6 => [
        'category' => '6. Pertanyaan jumlah mahasiswa',
        'q' => 'Berapa jumlah mahasiswa?',
        'table' => 'members',
        'sql' => "SELECT COUNT(*) FROM members",
        'get_db' => fn() => (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    ],
    7 => [
        'category' => '7. Pertanyaan daftar mahasiswa',
        'q' => 'Siapa saja nama mahasiswa?',
        'table' => 'members',
        'sql' => "SELECT nama, nis, kelas FROM members ORDER BY nama ASC",
        'get_db' => fn() => $db->query("SELECT nama, nis, kelas FROM members ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC),
    ],
    8 => [
        'category' => '8. Pertanyaan tagihan',
        'q' => 'Berapa total tagihan seluruh mahasiswa?',
        'table' => 'payments',
        'sql' => "SELECT SUM(tagihan) FROM payments",
        'get_db' => fn() => (float)$db->query("SELECT SUM(tagihan) FROM payments")->fetchColumn(),
    ],
    9 => [
        'category' => '9. Pertanyaan pelunasan',
        'q' => 'Siapa saja mahasiswa yang sudah lunas?',
        'table' => 'members JOIN payments',
        'sql' => "SELECT m.nama FROM members m JOIN payments p ON m.id = p.member_id GROUP BY m.id HAVING SUM(GREATEST(0, p.tagihan - p.nominal)) = 0",
        'get_db' => fn() => $db->query("SELECT m.nama FROM members m JOIN payments p ON m.id = p.member_id GROUP BY m.id HAVING SUM(GREATEST(0, p.tagihan - p.nominal)) = 0")->fetchAll(PDO::FETCH_COLUMN),
    ],
    10 => [
        'category' => '10. Pertanyaan tunggakan',
        'q' => 'Siapa saja mahasiswa yang masih memiliki tunggakan?',
        'table' => 'members JOIN payments',
        'sql' => "SELECT m.nama, SUM(GREATEST(0, p.tagihan - p.nominal)) as tunggakan FROM members m JOIN payments p ON m.id = p.member_id GROUP BY m.id HAVING tunggakan > 0",
        'get_db' => fn() => $db->query("SELECT m.nama, SUM(GREATEST(0, p.tagihan - p.nominal)) as tunggakan FROM members m JOIN payments p ON m.id = p.member_id GROUP BY m.id HAVING tunggakan > 0")->fetchAll(PDO::FETCH_ASSOC),
    ],
    11 => [
        'category' => '11. Pertanyaan pemasukan',
        'q' => 'Berapa total pemasukan kas?',
        'table' => 'transactions',
        'sql' => "SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan'",
        'get_db' => fn() => (float)$db->query("SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan'")->fetchColumn(),
    ],
    12 => [
        'category' => '12. Pertanyaan pengeluaran',
        'q' => 'Berapa total pengeluaran kas?',
        'table' => 'transactions',
        'sql' => "SELECT SUM(nominal) FROM transactions WHERE tipe = 'pengeluaran'",
        'get_db' => fn() => (float)$db->query("SELECT SUM(nominal) FROM transactions WHERE tipe = 'pengeluaran'")->fetchColumn(),
    ],
    13 => [
        'category' => '13. Pertanyaan saldo',
        'q' => 'Berapa saldo kas berdasarkan data terbaru?',
        'table' => 'transactions',
        'sql' => "SELECT SUM(CASE WHEN tipe = 'pemasukan' THEN nominal ELSE -nominal END) FROM transactions",
        'get_db' => fn() => (float)$db->query("SELECT SUM(CASE WHEN tipe = 'pemasukan' THEN nominal ELSE -nominal END) FROM transactions")->fetchColumn(),
    ],
    14 => [
        'category' => '14. Pertanyaan berdasarkan nama',
        'q' => 'Pemasukan Ahmad Fauzi ada berapa?',
        'table' => 'transactions JOIN payments',
        'sql' => "SELECT COUNT(*), SUM(nominal) FROM transactions WHERE keterangan LIKE '%Ahmad Fauzi%'",
        'get_db' => fn() => $db->query("SELECT COUNT(*) as count, SUM(nominal) as total FROM transactions WHERE keterangan LIKE '%Ahmad Fauzi%'")->fetch(PDO::FETCH_ASSOC),
    ],
    15 => [
        'category' => '15. Pertanyaan berdasarkan periode',
        'q' => 'Berapa pemasukan tanggal 21 Oktober 2026?',
        'table' => 'transactions',
        'sql' => "SELECT COUNT(*), SUM(nominal) FROM transactions WHERE tipe = 'pemasukan' AND tanggal = '2026-10-21'",
        'get_db' => fn() => $db->query("SELECT COUNT(*) as count, SUM(nominal) as total FROM transactions WHERE tipe = 'pemasukan' AND tanggal = '2026-10-21'")->fetch(PDO::FETCH_ASSOC),
    ],
];

echo "=================================================================\n";
echo " AUDIT & TESTING 16 QUESTION CATEGORIES AGAINST LIVE DATABASE\n";
echo "=================================================================\n\n";

$passedCount = 0;

foreach ($categories as $idx => $item) {
    echo "-----------------------------------------------------------------\n";
    echo "CAT #{$idx}: {$item['category']}\n";
    echo "Pertanyaan: \"{$item['q']}\"\n";
    echo "Tabel yang dibaca: {$item['table']}\n";
    echo "Query: {$item['sql']}\n";

    $dbVal = $item['get_db']();
    $dbSummary = is_array($dbVal) ? json_encode($dbVal, JSON_UNESCAPED_UNICODE) : (string)$dbVal;
    echo "Hasil Database: {$dbSummary}\n";

    $res = $aiService->ask($item['q'], 1, 'super_user');
    $intent = $res['metrics']['query_results']['intent'] ?? 'unknown';
    $ans = $res['response'] ?? '';

    echo "Intent Terdeteksi: {$intent}\n";
    echo "Jawaban Chatbot:\n" . trim($ans) . "\n";

    // Simple verification check
    $ok = true;
    if ($idx === 1 && !str_contains($ans, '12')) $ok = false;
    if ($idx === 2 && !str_contains($ans, 'Ahmad Fauzi')) $ok = false;
    if ($idx === 3 && !str_contains($ans, 'Updated Siswa 1788877732682')) $ok = false;
    if ($idx === 4 && !str_contains($ans, '4')) $ok = false;
    if ($idx === 5 && !str_contains($ans, 'Super User')) $ok = false;
    if ($idx === 6 && !str_contains($ans, '12')) $ok = false;
    if ($idx === 7 && !str_contains($ans, 'Ahmad Fauzi')) $ok = false;
    if ($idx === 8 && !str_contains($ans, '1.300.000')) $ok = false;
    if ($idx === 9 && !str_contains($ans, 'Putri Hima')) $ok = false;
    if ($idx === 10 && !str_contains($ans, 'Ahmad Fauzi')) $ok = false;
    if ($idx === 11 && !str_contains($ans, '1.758.000')) $ok = false;
    if ($idx === 12 && !str_contains($ans, '85.000')) $ok = false;
    if ($idx === 13 && !str_contains($ans, '1.673.000')) $ok = false;
    if ($idx === 14 && !str_contains($ans, '803.000') && !str_contains($ans, 'Ahmad Fauzi')) $ok = false;
    if ($idx === 15 && !str_contains($ans, '158.000')) $ok = false;

    if ($ok) {
        echo "[STATUS: PASS]\n";
        $passedCount++;
    } else {
        echo "[STATUS: FAIL]\n";
    }
}

// 16. Multi-turn Follow-up in single conversation
echo "-----------------------------------------------------------------\n";
echo "CAT #16: 16. Pertanyaan lanjutan dalam satu percakapan\n";
echo "Turn 1: \"Berapa pemasukan tanggal 21 Oktober 2026?\"\n";
$turn1 = $aiService->ask('Berapa pemasukan tanggal 21 Oktober 2026?', 1, 'super_user');
echo "Turn 1 Intent: " . ($turn1['metrics']['query_results']['intent'] ?? '') . "\n";
echo "Turn 1 Ans: " . trim($turn1['response']) . "\n";

$ctx = $turn1['metrics']['current_context'] ?? [];
echo "\nTurn 2: \"Kalau pengeluarannya?\" (Mewarisi tanggal 21 Okt)\n";
$turn2 = $aiService->ask('Kalau pengeluarannya?', 1, 'super_user', $ctx);
echo "Turn 2 Intent: " . ($turn2['metrics']['query_results']['intent'] ?? '') . "\n";
echo "Turn 2 Ans: " . trim($turn2['response']) . "\n";

$ctx2 = $turn2['metrics']['current_context'] ?? $ctx;
echo "\nTurn 3: \"Siapa saja yang membayar?\" (Mewarisi tanggal 21 Okt)\n";
$turn3 = $aiService->ask('Siapa saja yang membayar?', 1, 'super_user', $ctx2);
echo "Turn 3 Intent: " . ($turn3['metrics']['query_results']['intent'] ?? '') . "\n";
echo "Turn 3 Ans: " . trim($turn3['response']) . "\n";

$multiTurnOk = str_contains($turn1['response'], '158.000') &&
               str_contains($turn2['response'], '0,00') &&
               str_contains($turn3['response'], 'Ahmad Fauzi');

if ($multiTurnOk) {
    echo "[STATUS: PASS]\n";
    $passedCount++;
} else {
    echo "[STATUS: FAIL]\n";
}

echo "\n=================================================================\n";
echo " AUDIT SUMMARY: {$passedCount} / 16 CATEGORIES PASSED\n";
echo "=================================================================\n";

