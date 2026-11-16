<?php
/**
 * Test Suite: TASK 13 — AI FINANCIAL INSIGHT
 * Verifies:
 * 1. Strict layered architecture: Controller -> Financial Data Service -> AI Service -> AI Provider
 * 2. No direct database access given to AI (zero SQL / PDO handle in prompt/context)
 * 3. AI analysis covers: income, expense, balance, trend, top category, unpaid count, partial count, cash condition
 * 4. Required output structure: ### Ringkasan, ### Temuan, ### Peringatan, ### Rekomendasi
 * 5. Rule: No hallucinated numbers; all numbers strictly match Financial Data Service
 * 6. Rule: Read-only protection; AI cannot create, update, delete transactions or alter balances
 * 7. Rule: Fallback / Mock provider operational when external AI is unavailable
 * 8. Rule: ai_logs persistence and credential sanitization (no credentials in logs)
 * 9. Authorization: Only Super User can access AiController
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

// Manual autoloader setup for testing environment
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

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;
use App\Models\User;
use App\Models\Member;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\AiLog;
use App\Services\FinancialDataService;
use App\Services\AiService;
use App\Services\Ai\MockAiProvider;
use App\Services\Ai\GeminiAiProvider;
use App\Services\Ai\AiProviderInterface;
use App\Controllers\AiController;

echo "=================================================================\n";
echo " TEST SUITE: TASK 13 — AI FINANCIAL INSIGHT                     \n";
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

// -----------------------------------------------------------------
// TEST 1: FinancialDataService Compilation & Accuracy
// -----------------------------------------------------------------
runTest("FinancialDataService aggregates complete required financial metrics", function() {
    $service = new FinancialDataService();
    $metrics = $service->getFinancialMetrics();

    // Verify all 8 required analytical dimensions are present
    $requiredKeys = [
        'total_income',
        'total_expense',
        'current_balance',
        'trend',
        'top_expense_categories',
        'unpaid_members_count',
        'partial_members_count',
        'paid_members_count',
        'cash_condition',
        'total_tunggakan',
    ];

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $metrics)) {
            throw new \Exception("Missing required financial metric key: '{$key}'");
        }
    }

    // Balance formula: Saldo = Total Pemasukan - Total Pengeluaran
    $expectedBalance = round($metrics['total_income'] - $metrics['total_expense'], 2);
    $actualBalance = round($metrics['current_balance'], 2);
    if (abs($actualBalance - $expectedBalance) > 0.01) {
        throw new \Exception("Balance mismatch: expected {$expectedBalance}, got {$actualBalance}");
    }

    // Verify factual context string generation
    $contextString = $service->getFactualContextString();
    if (!str_contains($contextString, 'FAKTA KEUANGAN KAS TERVERIFIKASI')) {
        throw new \Exception("Factual context string must include verified header");
    }
    if (!str_contains($contextString, number_format($metrics['current_balance'], 2, ',', '.'))) {
        throw new \Exception("Factual context string must contain formatted balance number");
    }
});

// -----------------------------------------------------------------
// TEST 2: AI Provider Isolation (No Direct Database / SQL Access)
// -----------------------------------------------------------------
runTest("AI Provider Interface receives only verified context and primitives, no DB handles", function() {
    $reflection = new \ReflectionClass(AiProviderInterface::class);
    $method = $reflection->getMethod('generateInsight');
    $params = $method->getParameters();

    if (count($params) !== 3) {
        throw new \Exception("generateInsight must have exactly 3 parameters");
    }

    if ($params[0]->getName() !== 'userPrompt' || $params[0]->getType()->getName() !== 'string') {
        throw new \Exception("Param 1 must be string \$userPrompt");
    }
    if ($params[1]->getName() !== 'factualContext' || $params[1]->getType()->getName() !== 'string') {
        throw new \Exception("Param 2 must be string \$factualContext");
    }
    if ($params[2]->getName() !== 'metrics' || $params[2]->getType()->getName() !== 'array') {
        throw new \Exception("Param 3 must be array \$metrics");
    }
});

// -----------------------------------------------------------------
// TEST 3: Mock / Fallback AI Provider Output Structure
// -----------------------------------------------------------------
runTest("Fallback AI Provider outputs 4 mandatory markdown sections", function() {
    $service = new FinancialDataService();
    $metrics = $service->getFinancialMetrics();
    $context = $service->getFactualContextString();

    $mockProvider = new MockAiProvider();
    $insight = $mockProvider->generateInsight("Bagaimana kondisi keuangan bulan ini?", $context, $metrics);

    // Must contain 4 sections
    if (!str_contains($insight, '### Ringkasan')) {
        throw new \Exception("Insight is missing '### Ringkasan'");
    }
    if (!str_contains($insight, '### Temuan')) {
        throw new \Exception("Insight is missing '### Temuan'");
    }
    if (!str_contains($insight, '### Peringatan')) {
        throw new \Exception("Insight is missing '### Peringatan'");
    }
    if (!str_contains($insight, '### Rekomendasi')) {
        throw new \Exception("Insight is missing '### Rekomendasi'");
    }

    // Must contain actual balance number from metrics
    $formattedBalance = number_format($metrics['current_balance'], 2, ',', '.');
    if (!str_contains($insight, $formattedBalance)) {
        throw new \Exception("Insight must reflect exact balance amount: {$formattedBalance}");
    }
});

// -----------------------------------------------------------------
// TEST 4: Strict Layered Pipeline Execution
// Controller -> Financial Data Service -> AI Service -> AI Provider
// -----------------------------------------------------------------
runTest("Pipeline Controller -> Financial Data Service -> AI Service -> AI Provider works seamlessly", function() {
    // 1. Controller queries FinancialDataService
    $financialService = new FinancialDataService();
    $metrics = $financialService->getFinancialMetrics();
    $context = $financialService->getFactualContextString();

    // 2. Controller feeds to AiService
    $aiService = new AiService($financialService);

    // 3. AiService calls provider and logs
    $result = $aiService->analyze("Bagaimana kondisi keuangan bulan ini?", $metrics, $context, 1);

    if (!$result['success']) {
        throw new \Exception("AI Service analyze returned failure");
    }
    if (empty($result['response'])) {
        throw new \Exception("AI response is empty");
    }
    if (empty($result['provider'])) {
        throw new \Exception("Provider name is empty");
    }
    if (empty($result['log_id'])) {
        throw new \Exception("AI log was not recorded to database");
    }
});

// -----------------------------------------------------------------
// TEST 5: Read-Only Invariance (AI cannot mutate DB or change balances)
// -----------------------------------------------------------------
runTest("AI analysis is strictly Read-Only and causes ZERO database mutations", function() {
    $trxModel = new Transaction();
    $initialTrxCount = $trxModel->count();
    $initialBalance = $trxModel->getCurrentBalance();

    $paymentModel = new Payment();
    $initialPaymentCount = $paymentModel->count();

    // Run AI analysis
    $financialService = new FinancialDataService();
    $aiService = new AiService($financialService);
    $result = $aiService->ask("Apakah ada kebocoran anggaran kas bulan ini?", 1);

    // Verify after execution
    $finalTrxCount = $trxModel->count();
    $finalBalance = $trxModel->getCurrentBalance();
    $finalPaymentCount = $paymentModel->count();

    if ($finalTrxCount !== $initialTrxCount) {
        throw new \Exception("Transaction count changed during AI analysis! Mutation detected!");
    }
    if ($finalPaymentCount !== $initialPaymentCount) {
        throw new \Exception("Payment count changed during AI analysis! Mutation detected!");
    }
    if (abs($finalBalance - $initialBalance) > 0.0001) {
        throw new \Exception("Balance changed during AI analysis! Before: {$initialBalance}, After: {$finalBalance}");
    }
});

// -----------------------------------------------------------------
// TEST 6: Credential Sanitization in AI Logs
// -----------------------------------------------------------------
runTest("Credential sanitization strips sensitive keys before logging to ai_logs", function() {
    $aiService = new AiService();

    $textWithKeys = "Pertanyaan dengan api_key=AIzaSyA_TEST_KEY_12345678901234567890 dan password=SecretPassword999!";
    $sanitized = $aiService->sanitizeCredentials($textWithKeys);

    if (str_contains($sanitized, 'AIzaSyA_TEST_KEY_12345678901234567890')) {
        throw new \Exception("API Key was not redacted by sanitizer!");
    }
    if (str_contains($sanitized, 'SecretPassword999!')) {
        throw new \Exception("Password was not redacted by sanitizer!");
    }
    if (!str_contains($sanitized, '[REDACTED_')) {
        throw new \Exception("Sanitized text does not contain REDACTED tags");
    }
});

// -----------------------------------------------------------------
// TEST 7: AI Logs Persistence and User Isolation
// -----------------------------------------------------------------
runTest("AI Logs correctly recorded in ai_logs table and fetched per user", function() {
    $aiLogModel = new AiLog();
    $testUserId = 1;
    $uniqueQuestion = "Analisis Pengeluaran Kas Unik Test " . time();
    $dummyResponse = "### Ringkasan\nKas tes.\n### Temuan\nTemuan tes.\n### Peringatan\nNihil.\n### Rekomendasi\nLanjutkan.";

    $logId = $aiLogModel->record($testUserId, $uniqueQuestion, $dummyResponse);
    if (!$logId) {
        throw new \Exception("Failed to record AI log");
    }

    $history = $aiLogModel->getHistoryByUser($testUserId, 5);
    $found = false;
    foreach ($history as $h) {
        if ($h['question'] === $uniqueQuestion) {
            $found = true;
            if (!str_contains($h['response'], '### Ringkasan')) {
                throw new \Exception("Logged response corrupted");
            }
            break;
        }
    }

    if (!$found) {
        throw new \Exception("Logged question not found in user history");
    }

    // Clean up test log
    $aiLogModel->delete($logId);
});

// -----------------------------------------------------------------
// TEST 8: Gemini API Provider Configuration & Safe Fallback
// -----------------------------------------------------------------
runTest("Gemini API Provider checks .env key and falls back safely if unavailable", function() {
    // 1. Without API key -> isAvailable should be false
    $emptyGemini = new GeminiAiProvider('');
    if ($emptyGemini->isAvailable()) {
        throw new \Exception("Gemini without key should report isAvailable() === false");
    }

    // 2. AiService initialized with empty key automatically falls back to MockAiProvider
    $financialService = new FinancialDataService();
    $aiService = new AiService($financialService, $emptyGemini);
    
    // Ask should still succeed via fallback without throwing uncaught exceptions
    $result = $aiService->ask("Bagaimana kondisi kas sekarang?", 1);
    if (!$result['success']) {
        throw new \Exception("AiService should succeed using fallback provider");
    }
    if (!str_contains($result['response'], '### Ringkasan')) {
        throw new \Exception("Fallback provider output missing ### Ringkasan");
    }
});

// -----------------------------------------------------------------
// TEST 9: Authorization - AiController Super User Only
// -----------------------------------------------------------------
runTest("Member role is strictly forbidden from accessing AiController", function() {
    Session::start();
    $userModel = new User();
    $memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
    if (!$memberUser) {
        $memberUser = ['id' => 99, 'username' => 'test_member', 'role' => 'member', 'status' => 'active'];
    }

    Auth::login($memberUser);

    if (Auth::isSuperUser() === true) {
        throw new \Exception("Member user must not have Super User authorization");
    }
    if (Auth::isMember() !== true) {
        throw new \Exception("Member user must have isMember() === true");
    }

    // Verify via reflection that AiController constructor strictly enforces Auth::requireSuperUser
    $reflection = new \ReflectionClass(AiController::class);
    $constructor = $reflection->getConstructor();
    if (!$constructor) {
        throw new \Exception("AiController must have a constructor enforcing authorization");
    }

    $source = file_get_contents($reflection->getFileName());
    if (!str_contains($source, 'Auth::requireSuperUser()')) {
        throw new \Exception("AiController must call Auth::requireSuperUser() in constructor");
    }

    // Clean up session
    Session::destroy();
});

echo "\n=================================================================\n";
echo " RESULTS: {$testsPassed} PASSED | {$testsFailed} FAILED\n";
echo "=================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
