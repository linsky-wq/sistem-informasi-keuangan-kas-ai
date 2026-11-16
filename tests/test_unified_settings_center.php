<?php
/**
 * Test Suite: Unified Configuration Center (AI Financial Assistant & WhatsApp Gateway)
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
echo " TEST SUITE: PUSAT PENGATURAN TERPADU (AI & WHATSAPP)\n";
echo "=================================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $label, string &$output = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$label}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$label}\n";
    }
}

// -------------------------------------------------------------
// SECTION 1: AI SETTING MODEL & DB PERSISTENCE
// -------------------------------------------------------------
echo "--- SECTION 1: AI SETTING MODEL & DB PERSISTENCE ---\n";

$aiSetting = new \App\Models\AiSetting();
assertTest($aiSetting !== null, "AiSetting model initializes successfully");

// Test default fallback
$defaultProvider = $aiSetting->get('ai_provider');
assertTest(in_array($defaultProvider, ['auto', 'gemini', 'groq', 'nvidia', 'mock'], true), "AiSetting gets valid ai_provider: {$defaultProvider}");

// Test set & get in DB
$testVal = 'gemini';
$setRes = $aiSetting->set('ai_provider', $testVal);
assertTest($setRes === true, "AiSetting successfully saves 'ai_provider' to database");
assertTest($aiSetting->get('ai_provider') === 'gemini', "AiSetting retrieves saved 'ai_provider' from database");

// Test token masking
$dummyKey = 'AIzaSyDummyTestKeyForVerification123456';
$aiSetting->set('gemini_api_key', $dummyKey);
$masked = $aiSetting->getMaskedKey('gemini_api_key');
assertTest(str_contains($masked, '***'), "AiSetting correctly masks API key: {$masked}");
assertTest(str_starts_with($masked, 'AIz'), "Masked key retains prefix: AIz");
assertTest(str_ends_with($masked, '456'), "Masked key retains suffix: 456");

// Reset back to auto
$aiSetting->set('ai_provider', 'auto');
$aiSetting->set('gemini_api_key', '');

// -------------------------------------------------------------
// SECTION 2: GROQ AI PROVIDER
// -------------------------------------------------------------
echo "\n--- SECTION 2: GROQ AI PROVIDER ---\n";

$groqNoKey = new \App\Services\Ai\GroqAiProvider('', 'llama-3.3-70b-versatile');
assertTest($groqNoKey->isAvailable() === false, "Groq reports not available when API key is empty");
assertTest(str_contains($groqNoKey->getProviderName(), 'llama-3.3-70b-versatile'), "Groq getProviderName() returns correct model string");

$groqWithKey = new \App\Services\Ai\GroqAiProvider('gsk_dummy_test_key_123', 'llama-3.1-8b-instant');
assertTest($groqWithKey->isAvailable() === true, "Groq reports available when API key is supplied");
assertTest(str_contains($groqWithKey->getProviderName(), 'llama-3.1-8b-instant'), "Groq includes custom model name");

// -------------------------------------------------------------
// SECTION 3: AI SERVICE DYNAMIC RESOLUTION & FALLBACK
// -------------------------------------------------------------
echo "\n--- SECTION 3: AI SERVICE DYNAMIC RESOLUTION & FALLBACK ---\n";

// Explicit mock provider selection
$aiSetting->set('ai_provider', 'mock');
$aiServiceMock = new \App\Services\AiService();
assertTest($aiServiceMock->getProvider() instanceof \App\Services\Ai\MockAiProvider, "AiService resolves MockAiProvider when ai_provider is set to 'mock'");

// Explicit Groq provider selection (with unavailable key -> falls back safely)
$aiSetting->set('ai_provider', 'groq');
$aiSetting->set('groq_api_key', '');
$aiServiceGroqFallback = new \App\Services\AiService();
assertTest($aiServiceGroqFallback->getProvider() instanceof \App\Services\Ai\MockAiProvider, "AiService gracefully falls back to MockAiProvider when Groq key is empty");

// Explicit Groq provider selection (with available key)
$aiSetting->set('groq_api_key', 'gsk_mock_valid_key_for_test');
$aiServiceGroq = new \App\Services\AiService();
assertTest($aiServiceGroq->getProvider() instanceof \App\Services\Ai\GroqAiProvider, "AiService resolves GroqAiProvider when Groq key is configured");

// Clean up Groq key & reset provider to auto
$aiSetting->set('groq_api_key', '');
$aiSetting->set('ai_provider', 'auto');
$aiServiceAuto = new \App\Services\AiService();
assertTest($aiServiceAuto->getProvider() instanceof \App\Services\Ai\AiProviderInterface, "AiService resolves valid provider in 'auto' mode");

// -------------------------------------------------------------
// SECTION 4: WHATSAPP SETTINGS & FONNTE INTEGRATION COEXISTENCE
// -------------------------------------------------------------
echo "\n--- SECTION 4: WHATSAPP SETTINGS & FONNTE INTEGRATION ---\n";

$waSetting = new \App\Models\WhatsAppSetting();
assertTest($waSetting !== null, "WhatsAppSetting initializes successfully");
assertTest($waSetting->get('wa_provider') !== null, "WhatsAppSetting gets wa_provider");

$waService = new \App\Services\WhatsAppService($waSetting);
assertTest($waService->isEnabled() === true, "WhatsAppService isEnabled() returns true");
assertTest($waService->getProvider() instanceof \App\Services\Whatsapp\WhatsAppProviderInterface, "WhatsAppService resolves active provider");

// Test masking preservation logic in updateWa
$rawWaToken = 'fonnte_test_token_secret_12345';
$waSetting->set('wa_api_token', $rawWaToken);
$maskedWa = $waSetting->getMaskedToken();
assertTest(str_contains($maskedWa, '***'), "WhatsApp token correctly masked in UI: {$maskedWa}");

// Reset test token
$waSetting->set('wa_api_token', '');

// -------------------------------------------------------------
// SECTION 5: SETTINGS CONTROLLER RBAC & UNIFIED VIEW
// -------------------------------------------------------------
echo "\n--- SECTION 5: SETTINGS CONTROLLER RBAC & UNIFIED VIEW ---\n";

$userModel = new \App\Models\User();
$adminUser = $userModel->findByUsernameOrEmail('admin');
\App\Core\Session::destroy();
\App\Core\Auth::login($adminUser);

assertTest(\App\Core\Auth::isSuperUser() === true, "Authenticated user is recognized as Super User");

// Test SettingsController index rendering
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/settings';
$_GET['tab'] = 'ai';

ob_start();
$settingsCtrl = new \App\Controllers\SettingsController();
$settingsCtrl->index();
$htmlOutput = ob_get_clean();

assertTest(str_contains($htmlOutput, 'Pusat Konfigurasi Terpadu'), "Settings view contains title 'Pusat Konfigurasi Terpadu'");
assertTest(str_contains($htmlOutput, 'AI Financial Assistant'), "Settings view contains Tab 1 'AI Financial Assistant'");
assertTest(str_contains($htmlOutput, 'WhatsApp Gateway'), "Settings view contains Tab 2 'WhatsApp Gateway'");
assertTest(str_contains($htmlOutput, 'Sistem &amp; Keamanan') || str_contains($htmlOutput, 'Sistem & Keamanan'), "Settings view contains Tab 3 'Sistem & Keamanan'");
assertTest(str_contains($htmlOutput, 'FONNTE'), "Settings view contains Fonnte gateway options");
assertTest(str_contains($htmlOutput, 'Groq'), "Settings view contains Groq provider options");
assertTest(str_contains($htmlOutput, 'Google Gemini'), "Settings view contains Google Gemini provider options");
assertTest(str_contains($htmlOutput, 'NVIDIA NIM'), "Settings view contains NVIDIA NIM provider options");

// Test Member access block
$memberUser = $userModel->findByUsernameOrEmail('ahmad');
if ($memberUser) {
    \App\Core\Session::destroy();
    \App\Core\Auth::login($memberUser);
    
    // In CLI environment, Auth::requireSuperUser() will trigger 403 and exit or render error
    // Verify Auth::isSuperUser() is false for member
    assertTest(\App\Core\Auth::isSuperUser() === false, "Member role is strictly blocked from isSuperUser()");
    assertTest(\App\Core\Auth::canManageWhatsApp() === false, "Member role is strictly blocked from canManageWhatsApp()");
}

// Relogin as admin for final cleanup
\App\Core\Session::destroy();
\App\Core\Auth::login($adminUser);

echo "\n=================================================================\n";
echo " RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed === 0) {
    echo "✓ ALL UNIFIED SETTINGS CENTER TESTS PASSED SUCCESSFULLY!\n";
} else {
    exit(1);
}
