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
use App\Services\FinancialDataService;

$db = App\Core\Database::getInstance()->getConnection();
$aiService = new AiService();
$finService = new FinancialDataService();

echo "=================================================================\n";
echo " TEST SUITE: MEMBER STATUS & IDENTITY VS COUNT BUG FIX\n";
echo "=================================================================\n\n";

$tests = [
    [
        'q' => 'Ada berapa anggota?',
        'expected_intent' => 'member_count',
        'must_contain' => ['12', 'aktif', 'nonaktif'],
        'must_not_contain' => ['Updated Siswa 1788877732682'],
        'desc' => '1. "Ada berapa anggota?" mengarah ke COUNT'
    ],
    [
        'q' => 'Siapa anggota yang nonaktif?',
        'expected_intent' => 'member_inactive_list',
        'must_contain' => ['Updated Siswa 1788877732682'],
        'must_not_contain' => ['Jumlah anggota terdaftar adalah 12 orang', 'Kondisi keuangan'],
        'desc' => '2. "Siapa anggota yang nonaktif?" mengarah ke DAFTAR NAMA nonaktif'
    ],
    [
        'q' => 'Berapa anggota yang nonaktif?',
        'expected_intent' => 'member_inactive_count',
        'must_contain' => ['1 orang', 'nonaktif'],
        'must_not_contain' => ['Updated Siswa 1788877732682'],
        'desc' => '3. "Berapa anggota yang nonaktif?" mengarah ke COUNT nonaktif'
    ],
    [
        'q' => 'Siapa saja anggota yang aktif?',
        'expected_intent' => 'member_active_list',
        'must_contain' => ['Ahmad Fauzi', 'Budi Santoso', '11 orang'],
        'must_not_contain' => ['Updated Siswa 1788877732682'],
        'desc' => '4. "Siapa saja anggota yang aktif?" mengarah ke DAFTAR NAMA aktif'
    ],
    [
        'q' => 'Sebutkan nama seluruh anggota.',
        'expected_intent' => 'member_list',
        'must_contain' => ['Ahmad Fauzi', 'Updated Siswa 1788877732682', '12 orang'],
        'must_not_contain' => ['Kondisi keuangan kas'],
        'desc' => '5. "Sebutkan nama seluruh anggota." mengarah ke DAFTAR NAMA seluruh anggota'
    ],
    [
        'q' => 'Berapa jumlah anggota aktif?',
        'expected_intent' => 'member_active_count',
        'must_contain' => ['11 orang'],
        'must_not_contain' => ['Ahmad Fauzi', 'Budi Santoso'],
        'desc' => '6. "Berapa jumlah anggota aktif?" mengarah ke COUNT aktif'
    ],
    [
        'q' => 'Sebutkan nama anggota yang nonaktif.',
        'expected_intent' => 'member_inactive_list',
        'must_contain' => ['Updated Siswa 1788877732682'],
        'must_not_contain' => ['Jumlah anggota terdaftar adalah 12 orang'],
        'desc' => '7. "Sebutkan nama anggota yang nonaktif." mengarah ke DAFTAR NAMA nonaktif'
    ],
    [
        'q' => 'Siapa saja anggota yang nonaktif?',
        'expected_intent' => 'member_inactive_list',
        'must_contain' => ['Updated Siswa 1788877732682'],
        'must_not_contain' => ['11 anggota aktif'],
        'desc' => '8. "Siapa saja anggota yang nonaktif?" mengarah ke DAFTAR NAMA nonaktif'
    ],
];

$passed = 0;
$failed = 0;

foreach ($tests as $idx => $t) {
    $q = $t['q'];
    $res = $aiService->ask($q, 1, 'super_user');
    $ans = $res['response'] ?? '';
    $intent = $res['metrics']['query_results']['intent'] ?? 'unknown';

    $ok = true;
    $errors = [];

    if ($intent !== $t['expected_intent']) {
        $ok = false;
        $errors[] = "Expected intent '{$t['expected_intent']}', got '{$intent}'";
    }

    foreach ($t['must_contain'] as $mc) {
        if (!str_contains($ans, $mc)) {
            $ok = false;
            $errors[] = "Missing expected text: '{$mc}'";
        }
    }

    foreach ($t['must_not_contain'] as $mnc) {
        if (str_contains($ans, $mnc)) {
            $ok = false;
            $errors[] = "Found forbidden text: '{$mnc}'";
        }
    }

    if ($ok) {
        echo "[PASS] {$t['desc']}\n";
        echo "       Q: '{$q}'\n";
        echo "       Intent: {$intent}\n";
        echo "       A: " . str_replace("\n", " ", substr($ans, 0, 110)) . "...\n\n";
        $passed++;
    } else {
        echo "[FAIL] {$t['desc']}\n";
        echo "       Q: '{$q}'\n";
        echo "       Intent: {$intent}\n";
        echo "       Errors: " . implode(', ', $errors) . "\n";
        echo "       A: {$ans}\n\n";
        $failed++;
    }
}

echo "=================================================================\n";
echo " RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed > 0) {
    exit(1);
}
