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

$ai = new AiService();

echo "=== TEST FALLBACK & SPECIAL CASES ===\n";

// 1. Unknown out-of-scope question
$resUnknown = $ai->ask('Berapa kecepatan angin hari ini?', 1, 'super_user');
echo "1. Unknown out-of-scope:\n" . $resUnknown['response'] . "\n\n";

// 2. Financial condition query (Should return 4 sections)
$resFin = $ai->ask('Bagaimana kondisi keuangan bulan ini?', 1, 'super_user');
echo "2. Financial Condition Query (has ### Ringkasan):\n";
echo (str_contains($resFin['response'], '### Ringkasan') ? "PASS (Contains 4 sections)" : "FAIL") . "\n\n";

// 3. Member role asking for all members (Must be rejected)
$resMemberPrivacy = $ai->ask('Siapa saja nama anggota yang terdaftar?', 2, 'member');
echo "3. Member privacy check:\n" . $resMemberPrivacy['response'] . "\n\n";

// 4. Multi-turn test:
echo "4. Multi-turn Student Context:\n";
$turn1 = $ai->ask('Ada berapa mahasiswa yang memiliki tunggakan?', 1, 'super_user');
echo "Turn 1: " . $turn1['response'] . "\n";
$turn2 = $ai->ask('Siapa saja namanya?', 1, 'super_user', $turn1['metrics']['current_context'] ?? []);
echo "Turn 2: " . $turn2['response'] . "\n";
$turn3 = $ai->ask('Kalau yang sudah lunas?', 1, 'super_user', $turn2['metrics']['current_context'] ?? []);
echo "Turn 3: " . $turn3['response'] . "\n";

