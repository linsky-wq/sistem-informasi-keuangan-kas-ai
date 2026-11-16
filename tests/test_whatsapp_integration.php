<?php
/**
 * Automated Test Suite: Integrasi WhatsApp (WA) sebagai External Supporting Service
 * Coverage: Database Schema, Models, Service, Providers, Templates, Normalization, RBAC, and Payment Triggers
 */

declare(strict_types=1);

require_once dirname(__DIR__) . "/app/config/config.php";

spl_autoload_register(function (string $class) {
    $prefix = "App\\";
    $baseDir = dirname(__DIR__) . "/app/";
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode("\\", $relativeClass);
    if (count($parts) > 1) $parts[0] = strtolower($parts[0]);
    $file = $baseDir . implode("/", $parts) . ".php";
    if (file_exists($file)) require_once $file;
});

use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\Member;
use App\Models\Payment;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use App\Services\Whatsapp\MockWhatsAppProvider;
use App\Services\Whatsapp\GenericHttpWhatsAppProvider;

$totalTests = 0;
$passedTests = 0;
$failedTests = [];

function assertTest(string $desc, bool $result, string $cat = ""): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    $prefix = $cat ? "[$cat] " : "";
    if ($result) {
        $passedTests++;
        echo "  [PASS] {$prefix}{$desc}\n";
    } else {
        $failedTests[] = "{$prefix}{$desc}";
        echo "  [FAIL] {$prefix}{$desc}\n";
    }
}

echo "================================================================\n";
echo "  TEST SUITE: INTEGRASI WHATSAPP (EXTERNAL SUPPORTING SERVICE)\n";
echo "================================================================\n\n";

$db = Database::getInstance()->getConnection();
$settingModel = new WhatsAppSetting();
$logModel = new WhatsAppLog();
$userModel = new User();
$memberModel = new Member();
$paymentModel = new Payment();

// ================================================================
// SECTION 1: DATABASE SCHEMA & CONFIGURATION TABLE
// ================================================================
echo "--- SECTION 1: DATABASE SCHEMA & CONFIGURATION TABLE ---\n";

$tableCheck1 = $db->query("SHOW TABLES LIKE 'whatsapp_settings'")->fetch();
assertTest("Table whatsapp_settings exists in database", !empty($tableCheck1), "DB");

$tableCheck2 = $db->query("SHOW TABLES LIKE 'whatsapp_logs'")->fetch();
assertTest("Table whatsapp_logs exists in database", !empty($tableCheck2), "DB");

// Check required setting keys
$waEnabled = $settingModel->get('wa_enabled', null);
assertTest("Setting 'wa_enabled' is readable and boolean-compatible", $waEnabled !== null, "CONFIG");

$waProvider = $settingModel->get('wa_provider', null);
assertTest("Setting 'wa_provider' defaults to a valid provider (mock)", in_array($waProvider, ['mock', 'fonnte', 'wablas', 'generic']), "CONFIG");

$senderNum = $settingModel->get('wa_sender_number', null);
assertTest("Setting 'wa_sender_number' is defined", !empty($senderNum), "CONFIG");

// Test Setting updates and masked token
$settingModel->set('wa_api_token', 'test_secret_token_123456');
$savedToken = $settingModel->get('wa_api_token');
assertTest("Setting 'wa_api_token' saves and retrieves properly", $savedToken === 'test_secret_token_123456', "CONFIG");

$maskedToken = $settingModel->getMaskedToken();
assertTest("Masked token conceals raw secret (contains asterisks)", str_contains($maskedToken, '***'), "CONFIG");
assertTest("Masked token does not expose full secret in plaintext", $maskedToken !== 'test_secret_token_123456', "CONFIG");

// Restore test token to empty so environment configuration (.env) remains active in production
$settingModel->set('wa_api_token', '');

// ================================================================
// SECTION 2: PHONE NUMBER NORMALIZATION & SERVICE CORE
// ================================================================
echo "\n--- SECTION 2: PHONE NORMALIZATION & SERVICE CORE ---\n";

assertTest("Normalizes '081234567890' to '6281234567890'", WhatsAppService::normalizePhoneNumber('081234567890') === '6281234567890', "PHONE");
assertTest("Normalizes '+62812-3456-7890' to '6281234567890'", WhatsAppService::normalizePhoneNumber('+62812-3456-7890') === '6281234567890', "PHONE");
assertTest("Normalizes '81234567890' to '6281234567890'", WhatsAppService::normalizePhoneNumber('81234567890') === '6281234567890', "PHONE");
assertTest("Validates valid phone number '081234567890'", WhatsAppService::isValidPhoneNumber('081234567890'), "PHONE");
assertTest("Validates valid phone number '6281234567890'", WhatsAppService::isValidPhoneNumber('6281234567890'), "PHONE");
assertTest("Rejects invalid short phone number '0812'", !WhatsAppService::isValidPhoneNumber('0812'), "PHONE");
assertTest("Rejects non-numeric phone string 'abc1234'", !WhatsAppService::isValidPhoneNumber('abc1234'), "PHONE");

$waService = new WhatsAppService($settingModel, $logModel);
assertTest("WhatsAppService initializes correctly and isEnabled() returns true", $waService->isEnabled(), "SERVICE");

$provider = $waService->getProvider();
assertTest("Active provider implements WhatsAppProviderInterface", $provider instanceof \App\Services\Whatsapp\WhatsAppProviderInterface, "SERVICE");
assertTest("Active provider is configured", $provider->isConfigured(), "SERVICE");

// ================================================================
// SECTION 3: NOTIFICATION TEMPLATES & MOCK DISPATCH
// ================================================================
echo "\n--- SECTION 3: NOTIFICATION DISPATCH & TEMPLATES ---\n";

// Force mock provider for reliable testing
$mockProvider = new MockWhatsAppProvider();
$waService->setProvider($mockProvider);

// Test 1: Test Connection
$testResult = $waService->testConnection('081299990001', 'Uji coba pesan sistem');
assertTest("testConnection returns success = true", $testResult['success'] === true, "DISPATCH");
assertTest("testConnection returns non-null log_id", !empty($testResult['log_id']), "DISPATCH");
assertTest("testConnection returns message_id", !empty($testResult['message_id']), "DISPATCH");

// Verify log in database
$loggedItem = $logModel->find((int)$testResult['log_id']);
assertTest("Log row recorded in whatsapp_logs table", $loggedItem !== null, "LOGS");
assertTest("Log status marked as 'sent'", ($loggedItem['status'] ?? '') === 'sent', "LOGS");
assertTest("Log notification_type is 'test'", ($loggedItem['notification_type'] ?? '') === 'test', "LOGS");
assertTest("Log recipient_phone is normalized (starts with 62)", str_starts_with($loggedItem['recipient_phone'] ?? '', '62'), "LOGS");

// Test 2: Send Billing Info
$members = $memberModel->all();
$sampleMember = !empty($members) ? $members[0] : null;
assertTest("Sample member available for billing test", $sampleMember !== null, "TEMPLATE");

if ($sampleMember) {
    // Ensure member has phone number
    if (empty($sampleMember['no_hp'])) {
        $memberModel->update((int)$sampleMember['id'], ['no_hp' => '081234567801']);
        $sampleMember = $memberModel->find((int)$sampleMember['id']);
    }

    $billingResult = $waService->sendBillingInfo((int)$sampleMember['id'], 'Oktober 2026 (Minggu 1)', 50000.00);
    assertTest("sendBillingInfo returns success = true", $billingResult['success'] === true, "TEMPLATE");

    $billingLog = $logModel->find((int)$billingResult['log_id']);
    assertTest("Billing log recorded with type 'informasi_tagihan'", ($billingLog['notification_type'] ?? '') === 'informasi_tagihan', "LOGS");
    assertTest("Billing message contains member name", str_contains($billingLog['message'] ?? '', $sampleMember['nama']), "TEMPLATE");
    assertTest("Billing message contains correct currency format", str_contains($billingLog['message'] ?? '', '50.000'), "TEMPLATE");
}

// Test 3: Send Payment Reminder
$payments = $paymentModel->all();
$samplePayment = !empty($payments) ? $payments[0] : null;
assertTest("Sample payment record available for reminder test", $samplePayment !== null, "TEMPLATE");

if ($samplePayment) {
    $reminderResult = $waService->sendPaymentReminder((int)$samplePayment['id']);
    assertTest("sendPaymentReminder returns success = true", $reminderResult['success'] === true, "TEMPLATE");

    $reminderLog = $logModel->find((int)$reminderResult['log_id']);
    assertTest("Reminder log recorded with type 'pengingat_pembayaran'", ($reminderLog['notification_type'] ?? '') === 'pengingat_pembayaran', "LOGS");
    assertTest("Reminder message contains 'PENGINGAT PEMBAYARAN KAS'", str_contains($reminderLog['message'] ?? '', 'PENGINGAT PEMBAYARAN KAS'), "TEMPLATE");
}

// Test 4: Send Payment Confirmation
if ($samplePayment) {
    $confirmResult = $waService->sendPaymentConfirmation((int)$samplePayment['id']);
    assertTest("sendPaymentConfirmation returns success = true", $confirmResult['success'] === true, "TEMPLATE");

    $confirmLog = $logModel->find((int)$confirmResult['log_id']);
    assertTest("Confirmation log recorded with type 'konfirmasi_pembayaran'", ($confirmLog['notification_type'] ?? '') === 'konfirmasi_pembayaran', "LOGS");
}

// Test 5: Send Verification Status
if ($samplePayment) {
    $verifyResult = $waService->sendVerificationStatus((int)$samplePayment['id']);
    assertTest("sendVerificationStatus returns success = true", $verifyResult['success'] === true, "TEMPLATE");

    $verifyLog = $logModel->find((int)$verifyResult['log_id']);
    assertTest("Verification status log recorded with type 'status_verifikasi'", ($verifyLog['notification_type'] ?? '') === 'status_verifikasi', "LOGS");
}

// Test 6: Disabled Service Gracefully Aborts
$settingModel->set('wa_enabled', false);
$disabledService = new WhatsAppService($settingModel, $logModel);
$disabledResult = $disabledService->testConnection('081234567890');
assertTest("Disabled WhatsApp service cleanly rejects dispatch", $disabledResult['success'] === false, "SERVICE");
assertTest("Disabled service provides clear error explanation", str_contains($disabledResult['error'] ?? '', 'dinonaktifkan'), "SERVICE");
$settingModel->set('wa_enabled', true); // restore

// ================================================================
// SECTION 4: ROLE ACCESS CONTROL (RBAC) FOR WHATSAPP
// ================================================================
echo "\n--- SECTION 4: RBAC & PERMISSION CHECKS ---\n";

// Super User Permissions
Session::destroy();
$adminUser = $userModel->findByUsernameOrEmail('admin');
if ($adminUser) {
    Auth::login($adminUser);
    assertTest("Super User canManageWhatsApp() is TRUE", Auth::canManageWhatsApp(), "RBAC");
    assertTest("Super User canSendWhatsApp() is TRUE", Auth::canSendWhatsApp(), "RBAC");
}

// Bendahara Permissions
Session::destroy();
$bendaharaUser = [
    'id'       => 9991,
    'username' => 'bendahara_test',
    'email'    => 'bendahara@test.local',
    'role'     => 'bendahara',
    'status'   => 'active',
];
Auth::login($bendaharaUser);
assertTest("Bendahara canManageWhatsApp() is FALSE (no settings access)", !Auth::canManageWhatsApp(), "RBAC");
assertTest("Bendahara canSendWhatsApp() is TRUE (can send reminders & view logs)", Auth::canSendWhatsApp(), "RBAC");

// Pengawas Permissions
Session::destroy();
$pengawasUser = [
    'id'       => 9992,
    'username' => 'pengawas_test',
    'email'    => 'pengawas@test.local',
    'role'     => 'pengawas',
    'status'   => 'active',
];
Auth::login($pengawasUser);
assertTest("Pengawas canManageWhatsApp() is FALSE", !Auth::canManageWhatsApp(), "RBAC");
assertTest("Pengawas canSendWhatsApp() is FALSE (monitoring only)", !Auth::canSendWhatsApp(), "RBAC");

// Member Permissions
Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
if ($memberUser) {
    Auth::login($memberUser);
    assertTest("Member canManageWhatsApp() is FALSE (strictly forbidden)", !Auth::canManageWhatsApp(), "RBAC");
    assertTest("Member canSendWhatsApp() is FALSE (strictly forbidden)", !Auth::canSendWhatsApp(), "RBAC");
}

// External Actor Isolation
assertTest("WhatsApp is not a login role in Auth constants", !defined('\App\Core\Auth::ROLE_WHATSAPP'), "SECURITY");

// ================================================================
// SECTION 5: PAGINATION & STATS IN LOG MODEL
// ================================================================
echo "\n--- SECTION 5: LOG PAGINATION & AGGREGATE STATS ---\n";

$stats = $logModel->getStats();
assertTest("logModel->getStats() returns total >= 4", $stats['total'] >= 4, "STATS");
assertTest("logModel->getStats() returns success_count >= 1", $stats['success_count'] >= 1, "STATS");
assertTest("logModel->getStats() returns reminder_count >= 1", $stats['reminder_count'] >= 1, "STATS");

$paginated = $logModel->getPaginated([], 1, 10);
assertTest("logModel->getPaginated() returns array with 'data' and 'total'", isset($paginated['data'], $paginated['total']), "PAGINATION");
assertTest("logModel->getPaginated() contains items", count($paginated['data']) > 0, "PAGINATION");

// Filter by status 'sent'
$sentPaginated = $logModel->getPaginated(['status' => 'sent'], 1, 10);
assertTest("Filter by status 'sent' returns only sent records", !empty($sentPaginated['data']), "PAGINATION");

// Filter by type 'pengingat_pembayaran'
$typePaginated = $logModel->getPaginated(['notification_type' => 'pengingat_pembayaran'], 1, 10);
assertTest("Filter by type 'pengingat_pembayaran' returns matches", !empty($typePaginated['data']), "PAGINATION");

echo "\n================================================================\n";
echo "  WHATSAPP INTEGRATION RESULTS: {$passedTests} / {$totalTests} PASSED\n";
echo "================================================================\n";

if ($passedTests === $totalTests) {
    echo "✓ ALL WHATSAPP INTEGRATION TESTS PASSED WITH ZERO FAILURES!\n\n";
} else {
    echo "✗ SOME TESTS FAILED:\n";
    foreach ($failedTests as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}
