<?php
/**
 * WhatsApp Controller
 * External Supporting Service Orchestration & Log Viewer
 * RBAC: Super User (Admin) has full management access; Bendahara has send & view log permissions; Anggota blocked.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppLog;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Member;
use App\Services\WhatsAppService;

class WhatsappController extends Controller {
    private WhatsAppSetting $settingModel;
    private WhatsAppLog $logModel;
    private WhatsAppService $waService;
    private AuditLog $auditModel;

    public function __construct() {
        // Enforce RBAC: Member is strictly forbidden from accessing this controller
        Auth::requireLogin();
        if (!Auth::canSendWhatsApp()) {
            Auth::render403("Akses Ditolak: Anda tidak memiliki hak akses untuk mengelola atau melihat integrasi WhatsApp.");
        }

        $this->settingModel = new WhatsAppSetting();
        $this->logModel = new WhatsAppLog();
        $this->waService = new WhatsAppService($this->settingModel, $this->logModel);
        $this->auditModel = new AuditLog();
    }

    /**
     * WhatsApp Dashboard & Quick Send Overview
     */
    public function index(): void {
        $stats = $this->logModel->getStats();
        $recentLogs = $this->logModel->getRecent(6);
        $settings = $this->settingModel->getAllSettings();
        $provider = $this->waService->getProvider();

        $deviceInfo = null;
        if ($provider->isConfigured() && ($settings['wa_provider'] ?? '') === 'fonnte') {
            $deviceInfo = $this->waService->checkDeviceStatus();
        }

        $statusInfo = [
            'is_enabled'    => $this->waService->isEnabled(),
            'provider_name' => $provider->getProviderName(),
            'is_configured' => $provider->isConfigured(),
            'sender_number' => $settings['wa_sender_number'] ?? 'Belum Diatur',
            'api_url'       => $settings['wa_api_url'] ?? '-',
            'masked_token'  => $this->settingModel->getMaskedToken(),
            'is_env_token'  => $this->settingModel->isTokenFromEnv(),
            'can_manage'    => Auth::canManageWhatsApp(),
            'device_info'   => $deviceInfo,
        ];

        // Member list for quick manual send widget
        $memberModel = new Member();
        $members = $memberModel->all('nama ASC');

        $this->view('whatsapp.index', [
            'title'      => 'Integrasi WhatsApp — ' . APP_NAME,
            'statusInfo' => $statusInfo,
            'stats'      => $stats,
            'recentLogs' => $recentLogs,
            'members'    => $members,
            'canManage'  => Auth::canManageWhatsApp(),
        ]);
    }

    /**
     * WhatsApp Configuration Page (Super User Only)
     */
    public function settings(): void {
        if (!Auth::canManageWhatsApp()) {
            Auth::render403("Akses Ditolak: Hanya Super User (Admin) yang dapat mengubah konfigurasi gateway WhatsApp.");
        }

        $settings = $this->settingModel->getAllSettings();
        $maskedToken = $this->settingModel->getMaskedToken();
        $isEnvToken = $this->settingModel->isTokenFromEnv();
        $provider = $this->waService->getProvider();
        $deviceInfo = null;
        if ($provider->isConfigured() && ($settings['wa_provider'] ?? '') === 'fonnte') {
            $deviceInfo = $this->waService->checkDeviceStatus();
        }

        $this->view('whatsapp.settings', [
            'title'       => 'Pengaturan WhatsApp Gateway — ' . APP_NAME,
            'settings'    => $settings,
            'maskedToken' => $maskedToken,
            'isEnvToken'  => $isEnvToken,
            'deviceInfo'  => $deviceInfo,
        ]);
    }

    /**
     * Update WhatsApp Settings (Super User Only)
     */
    public function updateSettings(): void {
        if (!Auth::canManageWhatsApp()) {
            Auth::render403("Akses Ditolak: Hanya Super User (Admin) yang berwenang mengubah konfigurasi.");
        }

        if (!$this->isPost()) {
            $this->redirect('whatsapp/settings');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('whatsapp/settings');
            return;
        }

        $enabled = (bool)$this->post('wa_enabled', false);
        $provider = trim((string)$this->post('wa_provider', 'mock'));
        $apiUrl = trim((string)$this->post('wa_api_url', ''));
        $newToken = trim((string)$this->post('wa_api_token', ''));
        $sender = trim((string)$this->post('wa_sender_number', ''));
        $autoPayment = (bool)$this->post('wa_auto_notify_payment', false);
        $autoVerify = (bool)$this->post('wa_auto_notify_verification', false);

        if (!in_array($provider, ['mock', 'fonnte', 'wablas', 'generic'], true)) {
            $provider = 'mock';
        }

        $oldSettings = $this->settingModel->getAllSettings();

        $this->settingModel->set('wa_enabled', $enabled);
        $this->settingModel->set('wa_provider', $provider);
        $this->settingModel->set('wa_api_url', $apiUrl);
        $this->settingModel->set('wa_sender_number', $sender);
        $this->settingModel->set('wa_auto_notify_payment', $autoPayment);
        $this->settingModel->set('wa_auto_notify_verification', $autoVerify);

        // Only update token if user entered a new one (not asterisks or empty)
        if ($newToken !== '' && !str_contains($newToken, '***')) {
            $this->settingModel->set('wa_api_token', $newToken);
        }

        $this->auditModel->record(
            Auth::id(),
            'UPDATE_WA_SETTINGS',
            'whatsapp_settings',
            null,
            $oldSettings,
            $this->settingModel->getAllSettings()
        );

        Session::setFlash('success', 'Konfigurasi integrasi WhatsApp berhasil disimpan.');
        $this->redirect('whatsapp/settings');
    }

    /**
     * Test Gateway Delivery (Super User Only)
     */
    public function testSend(): void {
        if (!Auth::canManageWhatsApp()) {
            Auth::render403("Akses Ditolak: Hanya Super User yang dapat melakukan uji coba koneksi gateway.");
        }

        if (!$this->isPost()) {
            $this->redirect('whatsapp');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('whatsapp');
            return;
        }

        $phone = trim((string)$this->post('test_phone', ''));
        $customMsg = trim((string)$this->post('test_message', ''));

        if (empty($phone) || !WhatsAppService::isValidPhoneNumber($phone)) {
            Session::setFlash('danger', 'Nomor telepon tujuan tidak valid. Masukkan nomor WhatsApp aktif yang benar (contoh: 081234567890 atau 6281234567890).');
            $this->redirect('whatsapp');
            return;
        }

        $res = $this->waService->testConnection(
            $phone,
            $customMsg !== '' ? $customMsg : null,
            Auth::id()
        );

        if ($res['success']) {
            Session::setFlash('success', "✓ Tes pengiriman WhatsApp ke [{$phone}] BERHASIL via provider " . $this->waService->getProvider()->getProviderName() . ".");
        } else {
            Session::setFlash('danger', "✗ Tes pengiriman WhatsApp GAGAL: " . ($res['error'] ?? 'Terjadi kesalahan pada gateway.'));
        }

        $this->redirect('whatsapp');
    }

    /**
     * WhatsApp Delivery Logs (Super User & Bendahara)
     */
    public function logs(): void {
        $search = trim((string)$this->get('search', ''));
        $status = trim((string)$this->get('status', ''));
        $type = trim((string)$this->get('notification_type', ''));
        $dateStart = trim((string)$this->get('date_start', ''));
        $dateEnd = trim((string)$this->get('date_end', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 15;

        $filters = [
            'search'            => $search,
            'status'            => $status,
            'notification_type' => $type,
            'date_start'        => $dateStart,
            'date_end'          => $dateEnd,
        ];

        $paginated = $this->logModel->getPaginated($filters, $page, $perPage);
        $stats = $this->logModel->getStats();

        $this->view('whatsapp.logs', [
            'title'      => 'Riwayat Notifikasi WhatsApp — ' . APP_NAME,
            'logs'       => $paginated['data'],
            'total'      => $paginated['total'],
            'page'       => $paginated['page'],
            'perPage'    => $paginated['perPage'],
            'totalPages' => $paginated['totalPages'],
            'filters'    => $filters,
            'stats'      => $stats,
            'canManage'  => Auth::canManageWhatsApp(),
        ]);
    }

    /**
     * Send Payment Reminder via WhatsApp (Super User & Bendahara)
     */
    public function sendReminder(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments');
            return;
        }

        $paymentId = (int)$this->post('payment_id', 0);
        if ($paymentId <= 0) {
            Session::setFlash('danger', 'ID pembayaran tidak valid.');
            $this->redirect('payments');
            return;
        }

        $res = $this->waService->sendPaymentReminder($paymentId, Auth::id());

        if ($res['success']) {
            Session::setFlash('success', "✓ Pesan pengingat pembayaran berhasil dikirim via WhatsApp!");
        } else {
            Session::setFlash('warning', "Pemberitahuan WhatsApp: " . ($res['error'] ?? 'Gagal mengirim pengingat.'));
        }

        $redirect = $this->post('redirect', 'payments');
        $this->redirect($redirect === 'whatsapp' ? 'whatsapp' : ($redirect === 'logs' ? 'whatsapp/logs' : 'payments'));
    }

    /**
     * Send Billing Info to a Member (Super User & Bendahara)
     */
    public function sendBilling(): void {
        if (!$this->isPost()) {
            $this->redirect('whatsapp');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('whatsapp');
            return;
        }

        $memberId = (int)$this->post('member_id', 0);
        $periode = trim((string)$this->post('periode', ''));
        $tagihanRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('tagihan', '50000'));
        $tagihan = (float)$tagihanRaw;

        if ($memberId <= 0 || empty($periode) || $tagihan <= 0) {
            Session::setFlash('danger', 'Data anggota, periode, atau besaran tagihan tidak lengkap.');
            $this->redirect('whatsapp');
            return;
        }

        $res = $this->waService->sendBillingInfo($memberId, $periode, $tagihan, Auth::id());

        if ($res['success']) {
            Session::setFlash('success', "✓ Informasi tagihan kas berhasil dikirim ke nomor WhatsApp anggota!");
        } else {
            Session::setFlash('warning', "Pemberitahuan WhatsApp: " . ($res['error'] ?? 'Gagal mengirim informasi tagihan.'));
        }

        $this->redirect('whatsapp');
    }
}
