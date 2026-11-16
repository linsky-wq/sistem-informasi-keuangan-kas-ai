<?php
/**
 * Settings Controller (Super User Only)
 * Unified Configuration Center for AI Financial Assistant, WhatsApp Gateway, and System Status
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\AiSetting;
use App\Models\WhatsAppSetting;
use App\Services\AiService;
use App\Services\WhatsAppService;
use App\Services\Ai\GeminiAiProvider;
use App\Services\Ai\GroqAiProvider;
use App\Services\Ai\NvidiaAiProvider;

class SettingsController extends Controller {
    private AuditLog $auditModel;
    private Member $memberModel;
    private Transaction $trxModel;
    private AiSetting $aiSettingModel;
    private WhatsAppSetting $waSettingModel;

    public function __construct() {
        Auth::requireSuperUser();

        $this->auditModel = new AuditLog();
        $this->memberModel = new Member();
        $this->trxModel = new Transaction();
        $this->aiSettingModel = new AiSetting();
        $this->waSettingModel = new WhatsAppSetting();
    }

    /**
     * Unified Settings & System Status Overview
     */
    public function index(): void {
        $aiService = new AiService();
        $activeProvider = $aiService->getProvider();
        $activeProviderName = $activeProvider->getProviderName();

        $aiSettings = $this->aiSettingModel->getAllSettings();
        $aiMaskedKeys = [
            'gemini_api_key' => $this->aiSettingModel->getMaskedKey('gemini_api_key'),
            'groq_api_key'   => $this->aiSettingModel->getMaskedKey('groq_api_key'),
            'nvidia_api_key' => $this->aiSettingModel->getMaskedKey('nvidia_api_key'),
        ];
        $aiEnvStatus = [
            'gemini_api_key' => $this->aiSettingModel->isKeyFromEnv('gemini_api_key'),
            'groq_api_key'   => $this->aiSettingModel->isKeyFromEnv('groq_api_key'),
            'nvidia_api_key' => $this->aiSettingModel->isKeyFromEnv('nvidia_api_key'),
        ];

        // Maintain exact backward compatibility for $aiStatus
        $gemini = new GeminiAiProvider();
        $aiStatus = [
            'provider'       => $gemini->isAvailable() ? 'Google Gemini AI (1.5 Flash)' : 'Mock Provider (Fallback Mode)',
            'is_configured'  => $gemini->isAvailable(),
            'model'          => defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash',
            'api_key_status' => !empty($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY')) ? 'Terkonfigurasi (Protected)' : 'Belum Dikonfigurasi (Fallback Aktif)',
        ];

        // WhatsApp settings
        $waService = new WhatsAppService($this->waSettingModel);
        $waSettings = $this->waSettingModel->getAllSettings();
        $waMaskedToken = $this->waSettingModel->getMaskedToken();
        $isWaEnvToken = $this->waSettingModel->isTokenFromEnv();
        $waProvider = $waService->getProvider();
        $waDeviceInfo = null;
        if ($waProvider->isConfigured() && ($waSettings['wa_provider'] ?? '') === 'fonnte') {
            $waDeviceInfo = $waService->checkDeviceStatus();
        }

        $systemInfo = [
            'app_name'         => APP_NAME,
            'app_env'          => APP_ENV,
            'app_version'      => '1.0.0 (Production Stable)',
            'base_url'         => BASE_URL,
            'php_version'      => PHP_VERSION,
            'server_software'  => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache / PHP-FPM',
            'timezone'         => date_default_timezone_get(),
            'current_time'     => date('d M Y, H:i:s T'),
            'session_timeout'  => '2 Jam (7200 detik)',
            'csrf_protection'  => 'Aktif (64-Char Hex Cryptographic Token)',
            'db_host'          => DB_HOST,
            'db_name'          => DB_NAME,
            'db_status'        => 'Terhubung (MySQL / PDO)',
            'total_audit_logs' => $this->auditModel->count(),
            'total_members'    => $this->memberModel->count(),
            'total_trx'        => $this->trxModel->count(),
        ];

        $activeTab = trim((string)($this->get('tab') ?? 'ai'));
        if (!in_array($activeTab, ['ai', 'whatsapp', 'system'], true)) {
            $activeTab = 'ai';
        }

        $this->view('settings.index', [
            'title'                => 'Pusat Pengaturan Integrasi & Sistem — ' . APP_NAME,
            'systemInfo'           => $systemInfo,
            'aiStatus'             => $aiStatus,
            'aiSettings'           => $aiSettings,
            'aiMaskedKeys'         => $aiMaskedKeys,
            'aiEnvStatus'          => $aiEnvStatus,
            'activeAiProviderName' => $activeProviderName,
            'activeAiIsAvailable'  => $activeProvider->isAvailable(),
            'waSettings'           => $waSettings,
            'waMaskedToken'        => $waMaskedToken,
            'isWaEnvToken'         => $isWaEnvToken,
            'waProvider'           => $waProvider,
            'waDeviceInfo'         => $waDeviceInfo,
            'waService'            => $waService,
            'activeTab'            => $activeTab,
        ]);
    }

    /**
     * Update AI Configuration (Super User Only)
     */
    public function updateAi(): void {
        if (!$this->isPost()) {
            $this->redirect('settings?tab=ai');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('settings?tab=ai');
            return;
        }

        $enabled = (bool)$this->post('ai_enabled', false);
        $provider = strtolower(trim((string)$this->post('ai_provider', 'auto')));
        if (!in_array($provider, ['auto', 'gemini', 'groq', 'nvidia', 'mock'], true)) {
            $provider = 'auto';
        }

        // Gemini params
        $geminiKey = trim((string)$this->post('gemini_api_key', ''));
        $geminiModel = trim((string)$this->post('gemini_model', 'gemini-1.5-flash'));

        // Groq params
        $groqKey = trim((string)$this->post('groq_api_key', ''));
        $groqModel = trim((string)$this->post('groq_model', 'llama-3.3-70b-versatile'));
        $groqUrl = trim((string)$this->post('groq_api_url', 'https://api.groq.com/openai/v1/chat/completions'));

        // NVIDIA params
        $nvidiaEnabled = (bool)$this->post('nvidia_enabled', false);
        $nvidiaKey = trim((string)$this->post('nvidia_api_key', ''));
        $nvidiaModel = trim((string)$this->post('nvidia_model', 'meta/llama-3.1-70b-instruct'));
        $nvidiaUrl = trim((string)$this->post('nvidia_api_url', 'https://integrate.api.nvidia.com/v1/chat/completions'));

        $oldSettings = $this->aiSettingModel->getAllSettings();

        $this->aiSettingModel->set('ai_enabled', $enabled);
        $this->aiSettingModel->set('ai_provider', $provider);
        $this->aiSettingModel->set('gemini_model', $geminiModel);
        $this->aiSettingModel->set('groq_model', $groqModel);
        $this->aiSettingModel->set('groq_api_url', $groqUrl);
        $this->aiSettingModel->set('nvidia_enabled', $nvidiaEnabled);
        $this->aiSettingModel->set('nvidia_model', $nvidiaModel);
        $this->aiSettingModel->set('nvidia_api_url', $nvidiaUrl);

        // Update keys only if new value provided and does not contain asterisks
        if ($geminiKey !== '' && !str_contains($geminiKey, '***')) {
            $this->aiSettingModel->set('gemini_api_key', $geminiKey);
        }
        if ($groqKey !== '' && !str_contains($groqKey, '***')) {
            $this->aiSettingModel->set('groq_api_key', $groqKey);
        }
        if ($nvidiaKey !== '' && !str_contains($nvidiaKey, '***')) {
            $this->aiSettingModel->set('nvidia_api_key', $nvidiaKey);
        }

        $this->auditModel->record(
            Auth::id(),
            'UPDATE_AI_SETTINGS',
            'ai_settings',
            null,
            $oldSettings,
            $this->aiSettingModel->getAllSettings()
        );

        Session::setFlash('success', 'Konfigurasi AI Financial Assistant berhasil disimpan.');
        $this->redirect('settings?tab=ai');
    }

    /**
     * Update WhatsApp Settings (Super User Only)
     */
    public function updateWa(): void {
        if (!$this->isPost()) {
            $this->redirect('settings?tab=whatsapp');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('settings?tab=whatsapp');
            return;
        }

        $enabled = (bool)$this->post('wa_enabled', false);
        $provider = trim((string)$this->post('wa_provider', 'mock'));
        $apiUrl = trim((string)$this->post('wa_api_url', 'https://api.fonnte.com/send'));
        $newToken = trim((string)$this->post('wa_api_token', ''));
        $sender = trim((string)$this->post('wa_sender_number', ''));
        $autoPayment = (bool)$this->post('wa_auto_notify_payment', false);
        $autoVerify = (bool)$this->post('wa_auto_notify_verification', false);

        if (!in_array($provider, ['mock', 'fonnte', 'wablas', 'generic'], true)) {
            $provider = 'mock';
        }

        $oldSettings = $this->waSettingModel->getAllSettings();

        $this->waSettingModel->set('wa_enabled', $enabled);
        $this->waSettingModel->set('wa_provider', $provider);
        $this->waSettingModel->set('wa_api_url', $apiUrl);
        $this->waSettingModel->set('wa_sender_number', $sender);
        $this->waSettingModel->set('wa_auto_notify_payment', $autoPayment);
        $this->waSettingModel->set('wa_auto_notify_verification', $autoVerify);

        if ($newToken !== '' && !str_contains($newToken, '***')) {
            $this->waSettingModel->set('wa_api_token', $newToken);
        }

        $this->auditModel->record(
            Auth::id(),
            'UPDATE_WA_SETTINGS',
            'whatsapp_settings',
            null,
            $oldSettings,
            $this->waSettingModel->getAllSettings()
        );

        Session::setFlash('success', 'Konfigurasi integrasi WhatsApp berhasil disimpan.');
        $this->redirect('settings?tab=whatsapp');
    }

    /**
     * Test Active AI Provider Connection
     */
    public function testAi(): void {
        if (!$this->isPost()) {
            $this->redirect('settings?tab=ai');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('settings?tab=ai');
            return;
        }

        try {
            $aiService = new AiService();
            $provider = $aiService->getProvider();
            $providerName = $provider->getProviderName();

            if (!$provider->isAvailable()) {
                Session::setFlash('warning', "Provider AI [{$providerName}] belum siap digunakan atau API key belum diisi.");
                $this->redirect('settings?tab=ai');
                return;
            }

            // Test generation with lightweight factual prompt
            $testPrompt = "Tes koneksi sistem kas.";
            $testContext = "KAS AKTIF: Total Saldo Rp 1.000.000. Sistem berjalan normal.";
            $testMetrics = ['total_saldo' => 1000000];
            $startTime = microtime(true);
            $res = $provider->generateInsight($testPrompt, $testContext, $testMetrics);
            $duration = round((microtime(true) - $startTime) * 1000);

            Session::setFlash('success', "✓ Koneksi AI berhasil ke [{$providerName}] dalam {$duration}ms! Respons singkat: " . substr(strip_tags($res), 0, 100) . '...');
        } catch (\Throwable $e) {
            Session::setFlash('danger', "✗ Tes koneksi AI gagal: " . $e->getMessage());
        }

        $this->redirect('settings?tab=ai');
    }

    /**
     * Test WhatsApp Delivery (from Settings page)
     */
    public function testWa(): void {
        if (!$this->isPost()) {
            $this->redirect('settings?tab=whatsapp');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('settings?tab=whatsapp');
            return;
        }

        $phone = trim((string)$this->post('test_phone', ''));
        $customMsg = trim((string)$this->post('test_message', ''));

        if (empty($phone) || !WhatsAppService::isValidPhoneNumber($phone)) {
            Session::setFlash('danger', 'Nomor telepon tujuan tidak valid. Masukkan nomor WhatsApp aktif (contoh: 081234567890).');
            $this->redirect('settings?tab=whatsapp');
            return;
        }

        $waService = new WhatsAppService($this->waSettingModel);
        $res = $waService->testConnection(
            $phone,
            $customMsg !== '' ? $customMsg : null,
            Auth::id()
        );

        if ($res['success']) {
            Session::setFlash('success', "✓ Tes pengiriman WhatsApp ke [{$phone}] BERHASIL via provider " . $waService->getProvider()->getProviderName() . ".");
        } else {
            Session::setFlash('danger', "✗ Tes pengiriman WhatsApp GAGAL: " . ($res['error'] ?? 'Terjadi kesalahan pada gateway.'));
        }

        $this->redirect('settings?tab=whatsapp');
    }

    /**
     * Redirect /settings/profile to Admin Profile
     */
    public function profile(): void {
        $this->redirect('admin/profile');
    }
}
