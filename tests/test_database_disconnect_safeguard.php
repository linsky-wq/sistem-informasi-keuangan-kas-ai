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

echo "=== TESTING DATABASE DISCONNECTION SAFEGUARD ===\n\n";

// 1. Normal State (DB Active)
$finServiceNormal = new FinancialDataService();
$aiNormal = new AiService($finServiceNormal);
$resNormal = $aiNormal->ask('Ada berapa anggota?', 1, 'super_user');

echo "Test 1: Normal State (DB Active)\n";
echo "Response: " . $resNormal['response'] . "\n";
$normalPass = str_contains($resNormal['response'], '12 orang');
echo "Result: " . ($normalPass ? "[PASS]" : "[FAIL]") . "\n\n";

// 2. Disconnected DB State (Simulating DB connection loss using reflection)
$finServiceDown = new FinancialDataService();
$refProp = new ReflectionProperty(FinancialDataService::class, 'db');
$refProp->setValue($finServiceDown, null);

$aiDown = new AiService($finServiceDown);
$resDown = $aiDown->ask('Ada berapa anggota?', 1, 'super_user');

echo "Test 2: Disconnected DB State (DB Down / Connection lost)\n";
echo "Response: " . $resDown['response'] . "\n";
$downPass = str_contains($resDown['response'], 'koneksi database') && !str_contains($resDown['response'], '12 orang');
echo "Result: " . ($downPass ? "[PASS]" : "[FAIL]") . "\n\n";

if ($normalPass && $downPass) {
    echo "SAFEGUARD STATUS: 100% OPERATIONAL & VERIFIED\n";
} else {
    echo "SAFEGUARD STATUS: FAILED\n";
    exit(1);
}

