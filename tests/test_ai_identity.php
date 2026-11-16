<?php
/**
 * Test Suite: PROMPT 30 — IDENTITAS DAN PERKENALAN ASISTEN AI
 * Verifies:
 * 1. Pertanyaan 1: "Siapa kamu?"
 * 2. Pertanyaan 2: "Apa tugas kamu?"
 * 3. Pertanyaan 3: "Apa yang bisa kamu bantu di sistem ini?"
 * 4. Pertanyaan 4: "Apakah kamu bendahara?"
 * 5. Pertanyaan 5: "Perkenalkan dirimu."
 * 6. Variasi pertanyaan identitas lainnya:
 *    - "Kamu itu siapa?"
 *    - "Apa nama kamu?"
 *    - "Kamu bisa membantu apa?"
 *    - "Kamu AI apa?"
 *    - "Kamu dibuat untuk apa?"
 *    - "Apa fungsi kamu di aplikasi ini?"
 *    - "Apa yang bisa kamu lakukan?"
 *    - "Apakah kamu AI Sistem Keuangan Kas?"
 * 7. Invariansi: AI Financial Insight tetap berfungsi normal dengan 4 heading
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
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\FinancialDataService;
use App\Services\AiService;
use App\Services\Ai\MockAiProvider;
use App\Services\Ai\GeminiAiProvider;

echo "=================================================================\n";
echo " TEST SUITE: PROMPT 30 — IDENTITAS DAN PERKENALAN ASISTEN AI     \n";
echo "=================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function runTest(string $description, callable $fn): void {
    global $testsPassed, $testsFailed;
    echo "• Testing: {$description} ... ";
    try {
        $fn();
        echo "[\033[32mPASSED\033[0m]\n";
        $testsPassed++;
    } catch (\Throwable $e) {
        echo "[\033[31mFAILED\033[0m]\n";
        echo "  Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
        $testsFailed++;
    }
}

$expectedIdentitySnippet = "Halo! Saya Asisten AI pada Sistem Keuangan Kas. Saya adalah asisten virtual yang dirancang untuk membantu Anda memahami informasi dan fitur dalam sistem keuangan kas ini.";
$expectedTreasurerSnippet = "Saya bukan bendahara dan tidak melakukan transaksi keuangan secara mandiri.";

$financialService = new FinancialDataService();
$metrics = $financialService->getFinancialMetrics();
$context = $financialService->getFactualContextString();
$mockProvider = new MockAiProvider();
$aiService = new AiService($financialService);

// -----------------------------------------------------------------
// 1. Pengujian 5 Pertanyaan Utama Sesuai Prompt 30
// -----------------------------------------------------------------
runTest("1. Pertanyaan: 'Siapa kamu?'", function() use ($mockProvider, $context, $metrics, $expectedIdentitySnippet, $expectedTreasurerSnippet) {
    $response = $mockProvider->generateInsight("Siapa kamu?", $context, $metrics);
    if (!str_contains($response, $expectedIdentitySnippet)) {
        throw new \Exception("Response does not contain canonical identity introduction. Got: " . $response);
    }
    if (!str_contains($response, $expectedTreasurerSnippet)) {
        throw new \Exception("Response must clarify not being a treasurer. Got: " . $response);
    }
});

runTest("2. Pertanyaan: 'Apa tugas kamu?'", function() use ($mockProvider, $context, $metrics, $expectedIdentitySnippet, $expectedTreasurerSnippet) {
    $response = $mockProvider->generateInsight("Apa tugas kamu?", $context, $metrics);
    if (!str_contains($response, $expectedIdentitySnippet)) {
        throw new \Exception("Response does not contain canonical identity introduction. Got: " . $response);
    }
    if (!str_contains($response, $expectedTreasurerSnippet)) {
        throw new \Exception("Response must clarify not being a treasurer. Got: " . $response);
    }
});

runTest("3. Pertanyaan: 'Apa yang bisa kamu bantu di sistem ini?'", function() use ($mockProvider, $context, $metrics, $expectedIdentitySnippet, $expectedTreasurerSnippet) {
    $response = $mockProvider->generateInsight("Apa yang bisa kamu bantu di sistem ini?", $context, $metrics);
    if (!str_contains($response, $expectedIdentitySnippet)) {
        throw new \Exception("Response does not contain canonical identity introduction. Got: " . $response);
    }
    if (!str_contains($response, $expectedTreasurerSnippet)) {
        throw new \Exception("Response must clarify not being a treasurer. Got: " . $response);
    }
});

runTest("4. Pertanyaan: 'Apakah kamu bendahara?'", function() use ($mockProvider, $context, $metrics, $expectedTreasurerSnippet) {
    $response = $mockProvider->generateInsight("Apakah kamu bendahara?", $context, $metrics);
    if (!str_contains($response, "Bukan, saya bukan bendahara.")) {
        throw new \Exception("Response does not explicitly deny being a treasurer. Got: " . $response);
    }
    if (!str_contains($response, "Asisten AI pada Sistem Keuangan Kas")) {
        throw new \Exception("Response does not state system AI identity. Got: " . $response);
    }
    if (!str_contains($response, $expectedTreasurerSnippet)) {
        throw new \Exception("Response must clarify not being a treasurer. Got: " . $response);
    }
});

runTest("5. Pertanyaan: 'Perkenalkan dirimu.'", function() use ($mockProvider, $context, $metrics, $expectedIdentitySnippet, $expectedTreasurerSnippet) {
    $response = $mockProvider->generateInsight("Perkenalkan dirimu.", $context, $metrics);
    if (!str_contains($response, $expectedIdentitySnippet)) {
        throw new \Exception("Response does not contain canonical identity introduction. Got: " . $response);
    }
    if (!str_contains($response, $expectedTreasurerSnippet)) {
        throw new \Exception("Response must clarify not being a treasurer. Got: " . $response);
    }
});

// -----------------------------------------------------------------
// 2. Pengujian Variasi Pertanyaan Identitas
// -----------------------------------------------------------------
$variations = [
    "Kamu itu siapa?",
    "Apa nama kamu?",
    "Kamu bisa membantu apa?",
    "Kamu AI apa?",
    "Kamu dibuat untuk apa?",
    "Apa fungsi kamu di aplikasi ini?",
    "Apa yang bisa kamu lakukan?",
    "Apakah kamu AI Sistem Keuangan Kas?",
];

foreach ($variations as $varQuestion) {
    runTest("Variasi: '{$varQuestion}'", function() use ($mockProvider, $context, $metrics, $varQuestion, $expectedIdentitySnippet) {
        $response = $mockProvider->generateInsight($varQuestion, $context, $metrics);
        if (!str_contains($response, $expectedIdentitySnippet)) {
            throw new \Exception("Variation '{$varQuestion}' failed to return expected identity. Got: " . $response);
        }
    });
}

// -----------------------------------------------------------------
// 3. Pengujian Pipeline AiService & Logging
// -----------------------------------------------------------------
runTest("Pipeline AiService::analyze() successfully delivers identity response and logs it", function() use ($aiService, $expectedIdentitySnippet) {
    $result = $aiService->ask("Siapa kamu?", 1);
    if (!$result['success']) {
        throw new \Exception("AI Service failed to process question");
    }
    if (!str_contains($result['response'], $expectedIdentitySnippet)) {
        throw new \Exception("AiService response does not contain expected identity snippet");
    }
    if (empty($result['log_id'])) {
        throw new \Exception("AiService did not log interaction to ai_logs");
    }
});

// -----------------------------------------------------------------
// 4. Invariansi: Fitur AI Financial Insight Tetap Berfungsi Normal
// -----------------------------------------------------------------
runTest("Invariansi: Pertanyaan kondisi keuangan umum tetap menghasilkan 4 heading markdown", function() use ($aiService, $metrics) {
    $result = $aiService->ask("Bagaimana kondisi keuangan bulan ini?", 1);
    $resp = $result['response'];
    if (!str_contains($resp, '### Ringkasan') ||
        !str_contains($resp, '### Temuan') ||
        !str_contains($resp, '### Peringatan') ||
        !str_contains($resp, '### Rekomendasi')) {
        throw new \Exception("Financial analysis output format corrupted. Missing mandatory sections.");
    }

    $formattedBalance = number_format($metrics['current_balance'], 2, ',', '.');
    if (!str_contains($resp, $formattedBalance)) {
        throw new \Exception("Financial analysis does not contain accurate balance: " . $formattedBalance);
    }
});

echo "\n=================================================================\n";
echo " RESULTS: {$testsPassed} PASSED | {$testsFailed} FAILED\n";
echo "=================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
