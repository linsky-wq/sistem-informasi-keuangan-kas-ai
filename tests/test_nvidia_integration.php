<?php
/**
 * Test NVIDIA AI Provider integration basics.
 * Does not perform real HTTP calls.
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
    foreach ($parts as $i => $part) {
        if ($i < count($parts) - 1) $parts[$i] = strtolower($part);
    }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\AiService;
use App\Services\Ai\NvidiaAiProvider;

$passed = 0;
$failed = 0;
function assertTest(string $desc, bool $cond, string $detail = '') {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] $desc\n";
        $passed++;
    } else {
        echo "  [FAIL] $desc -> $detail\n";
        $failed++;
    }
}

echo "=== NVIDIA INTEGRATION TESTS ===\n";

// Test 1: Provider disabled => fallback (Mock)
putenv('NVIDIA_ENABLED=false');
putenv('NVIDIA_API_KEY=');
putenv('GEMINI_API_KEY='); // ensure Gemini unavailable
$service1 = new AiService();
assertTest('Provider disabled falls back to Mock', str_contains($service1->getProvider()->getProviderName(), 'Local Analytical') || str_contains($service1->getProvider()->getProviderName(), 'Mock'), $service1->getProvider()->getProviderName());

// Test 2: Enabled but missing key => fallback
putenv('NVIDIA_ENABLED=true');
putenv('NVIDIA_API_KEY=');
$service2 = new AiService();
assertTest('Enabled without key falls back', $service2->getProvider()->getProviderName() !== 'NVIDIA', $service2->getProvider()->getProviderName());

// Test 3: Enabled with dummy key => isAvailable true
putenv('NVIDIA_ENABLED=true');
putenv('NVIDIA_API_KEY=dummy_key');
$provider = new NvidiaAiProvider();
assertTest('Provider reports available with key', $provider->isAvailable() === true);

// Summary
echo "\nRESULTS: $passed passed | $failed failed\n";
exit($failed > 0 ? 1 : 0);
?>
