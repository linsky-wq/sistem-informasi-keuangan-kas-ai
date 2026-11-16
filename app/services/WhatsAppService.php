<?php
/**
 * WhatsApp Notification Service
 * Orchestrates WhatsApp message formatting, provider selection, and audit logging
 * External Supporting Actor / External Service
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\WhatsAppSetting;
use App\Models\WhatsAppLog;
use App\Models\Payment;
use App\Models\Member;
use App\Services\Whatsapp\WhatsAppProviderInterface;
use App\Services\Whatsapp\MockWhatsAppProvider;
use App\Services\Whatsapp\GenericHttpWhatsAppProvider;

class WhatsAppService {
    private WhatsAppSetting $settingModel;
    private WhatsAppLog $logModel;
    private ?WhatsAppProviderInterface $provider = null;

    public function __construct(?WhatsAppSetting $settingModel = null, ?WhatsAppLog $logModel = null) {
        $this->settingModel = $settingModel ?? new WhatsAppSetting();
        $this->logModel = $logModel ?? new WhatsAppLog();
    }

    /**
     * Check if WhatsApp service is globally enabled
     */
    public function isEnabled(): bool {
        return (bool)$this->settingModel->get('wa_enabled', true);
    }

    /**
     * Resolve active WhatsApp provider instance
     */
    public function getProvider(): WhatsAppProviderInterface {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $providerType = (string)$this->settingModel->get('wa_provider', 'mock');
        $apiUrl = (string)$this->settingModel->get('wa_api_url', 'https://api.fonnte.com/send');
        $apiToken = (string)$this->settingModel->get('wa_api_token', '');
        $sender = (string)$this->settingModel->get('wa_sender_number', '');

        if ($providerType === 'fonnte' || $providerType === 'generic' || $providerType === 'wablas') {
            $this->provider = new GenericHttpWhatsAppProvider($apiUrl, $apiToken, $sender);
        } else {
            $this->provider = new MockWhatsAppProvider();
        }

        return $this->provider;
    }

    /**
     * Override provider for testing/mocking
     */
    public function setProvider(WhatsAppProviderInterface $provider): void {
        $this->provider = $provider;
    }

    /**
     * Normalize phone number to standard Indonesian format (628xxx)
     */
    public static function normalizePhoneNumber(string $phone): string {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '08')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }
        return $cleaned;
    }

    /**
     * Validate whether phone number matches valid Indonesian mobile phone structure
     */
    public static function isValidPhoneNumber(string $phone): bool {
        $normalized = self::normalizePhoneNumber($phone);
        // Standard Indonesian mobile numbers start with 628 and have 10 to 15 digits total
        return (bool)preg_match('/^62[0-9]{8,13}$/', $normalized);
    }

    /**
     * Send raw notification with automatic logging
     */
    public function send(
        string $phone,
        string $message,
        string $type,
        ?int $memberId = null,
        ?string $recipientName = null,
        ?int $sentBy = null
    ): array {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'log_id'  => null,
                'error'   => 'Layanan WhatsApp sedang dinonaktifkan di pengaturan sistem.',
            ];
        }

        $normalizedPhone = self::normalizePhoneNumber($phone);
        if (!self::isValidPhoneNumber($phone)) {
            return [
                'success' => false,
                'log_id'  => null,
                'error'   => 'Format nomor WhatsApp tidak valid. Masukkan nomor telepon aktif yang benar (contoh: 081234567890 atau 6281234567890).',
            ];
        }

        // 1. Create initial pending log
        $logId = $this->logModel->logMessage([
            'recipient_phone'   => $normalizedPhone,
            'recipient_name'    => $recipientName,
            'member_id'         => $memberId,
            'notification_type' => $type,
            'message'           => $message,
            'status'            => 'pending',
            'sent_by'           => $sentBy,
            'sent_at'           => date('Y-m-d H:i:s'),
        ]);

        // 2. Dispatch via active provider
        $provider = $this->getProvider();
        $result = $provider->sendMessage($normalizedPhone, $message);

        // 3. Update log status
        $finalStatus = $result['success'] ? 'sent' : 'failed';
        if ($logId) {
            $this->logModel->recordDeliveryResult((int)$logId, [
                'status'              => $finalStatus,
                'provider_message_id' => $result['message_id'] ?? null,
                'error_message'       => $result['error'] ?? null,
                'response_payload'    => is_array($result['response']) ? json_encode($result['response']) : (string)$result['response'],
            ]);
        }

        return [
            'success'    => $result['success'],
            'log_id'     => $logId,
            'message_id' => $result['message_id'] ?? null,
            'status'     => $finalStatus,
            'error'      => $result['error'] ?? null,
        ];
    }

    /**
     * Send Payment Reminder (Pengingat Pembayaran Kas)
     */
    public function sendPaymentReminder(int $paymentId, ?int $sentBy = null): array {
        $paymentModel = new Payment();
        $payment = $paymentModel->findWithMember($paymentId);

        if (!$payment) {
            return ['success' => false, 'error' => 'Data pembayaran tidak ditemukan.'];
        }

        $phone = $payment['member_phone'] ?? $payment['no_hp'] ?? '';
        if (empty($phone)) {
            return ['success' => false, 'error' => "Nomor WhatsApp anggota [{$payment['member_name']}] belum terdaftar."];
        }

        $nama = $payment['member_name'];
        $kelas = $payment['kelas'] ?? '-';
        $periode = $payment['periode'];
        $tagihan = number_format((float)($payment['tagihan'] ?? 50000), 0, ',', '.');
        $dibayar = number_format((float)$payment['nominal'], 0, ',', '.');
        $sisa = number_format((float)$payment['sisa'], 0, ',', '.');
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas';

        $msg = "📢 *PENGINGAT PEMBAYARAN KAS*\n"
             . "Halo Sdr/i *{$nama}* ({$kelas}),\n\n"
             . "Berikut informasi kewajiban iuran kas Anda pada *{$appName}*:\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "📌 *Periode:* {$periode}\n"
             . "💵 *Total Tagihan:* Rp {$tagihan}\n"
             . "💳 *Telah Disetor:* Rp {$dibayar}\n"
             . "⚠️ *Sisa Tunggakan:* *Rp {$sisa}*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "Mohon untuk segera menyelesaikan penyetoran kas kepada Bendahara sebelum batas waktu yang ditentukan.\n\n"
             . "_Pesan otomatis dibuat oleh {$appName}._";

        return $this->send(
            $phone,
            $msg,
            'pengingat_pembayaran',
            (int)$payment['member_id'],
            $nama,
            $sentBy
        );
    }

    /**
     * Send Payment Confirmation (Konfirmasi Penerimaan Pembayaran)
     */
    public function sendPaymentConfirmation(int $paymentId, ?int $sentBy = null): array {
        $paymentModel = new Payment();
        $payment = $paymentModel->findWithMember($paymentId);

        if (!$payment) {
            return ['success' => false, 'error' => 'Data pembayaran tidak ditemukan.'];
        }

        $phone = $payment['member_phone'] ?? $payment['no_hp'] ?? '';
        if (empty($phone)) {
            return ['success' => false, 'error' => "Nomor WhatsApp anggota [{$payment['member_name']}] belum terdaftar."];
        }

        $nama = $payment['member_name'];
        $periode = $payment['periode'];
        $nominal = number_format((float)$payment['nominal'], 0, ',', '.');
        $sisa = number_format((float)$payment['sisa'], 0, ',', '.');
        $status = strtoupper($payment['status']);
        $tgl = date('d M Y', strtotime($payment['tanggal_pembayaran']));
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas';

        $msg = "✅ *KONFIRMASI PENERIMAAN PEMBAYARAN KAS*\n\n"
             . "Halo Sdr/i *{$nama}*,\n"
             . "Setoran pembayaran kas Anda telah berhasil dicatat dalam sistem.\n\n"
             . "📝 *Rincian Pembayaran:*\n"
             . "• Periode: *{$periode}*\n"
             . "• Tanggal Setor: {$tgl}\n"
             . "• Jumlah Setoran: *Rp {$nominal}*\n"
             . "• Sisa Tunggakan: Rp {$sisa}\n"
             . "• Status: *[{$status}]*\n\n"
             . "Terima kasih atas kontribusi Anda dalam pengelolaan kas yang transparan.\n\n"
             . "Salam,\n*Pengurus Kas & Bendahara*";

        return $this->send(
            $phone,
            $msg,
            'konfirmasi_pembayaran',
            (int)$payment['member_id'],
            $nama,
            $sentBy
        );
    }

    /**
     * Send Verification Status (Status Verifikasi Pembayaran)
     */
    public function sendVerificationStatus(int $paymentId, ?int $sentBy = null): array {
        $paymentModel = new Payment();
        $payment = $paymentModel->findWithMember($paymentId);

        if (!$payment) {
            return ['success' => false, 'error' => 'Data pembayaran tidak ditemukan.'];
        }

        $phone = $payment['member_phone'] ?? $payment['no_hp'] ?? '';
        if (empty($phone)) {
            return ['success' => false, 'error' => "Nomor WhatsApp anggota [{$payment['member_name']}] belum terdaftar."];
        }

        $nama = $payment['member_name'];
        $periode = $payment['periode'];
        $nominal = number_format((float)$payment['nominal'], 0, ',', '.');
        $status = strtoupper($payment['status']);
        $verifier = $payment['verifier_name'] ?? 'Petugas Bendahara';
        $verifiedAt = !empty($payment['verified_at']) ? date('d M Y, H:i', strtotime($payment['verified_at'])) : date('d M Y, H:i');
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas';

        $msg = "🛡️ *STATUS VERIFIKASI PEMBAYARAN KAS*\n\n"
             . "Halo Sdr/i *{$nama}*,\n"
             . "Setoran kas Anda telah *TERVERIFIKASI RESMI* oleh pengurus.\n\n"
             . "📋 *Data Verifikasi:*\n"
             . "• Periode: *{$periode}*\n"
             . "• Nominal Disahkan: *Rp {$nominal}*\n"
             . "• Status Akhir: *{$status}*\n"
             . "• Diverifikasi Oleh: *{$verifier}*\n"
             . "• Waktu Verifikasi: {$verifiedAt}\n\n"
             . "Bukti sah pembayaran kas telah tersimpan di sistem {$appName}.\n\n"
             . "_Catatan: Simpan notifikasi ini sebagai tanda bukti resmi._";

        return $this->send(
            $phone,
            $msg,
            'status_verifikasi',
            (int)$payment['member_id'],
            $nama,
            $sentBy
        );
    }

    /**
     * Send Billing Info (Informasi Tagihan Kas Baru)
     */
    public function sendBillingInfo(int $memberId, string $periode, float $tagihan, ?int $sentBy = null): array {
        $memberModel = new Member();
        $member = $memberModel->find($memberId);

        if (!$member) {
            return ['success' => false, 'error' => 'Data anggota tidak ditemukan.'];
        }

        $phone = $member['no_hp'] ?? '';
        if (empty($phone)) {
            return ['success' => false, 'error' => "Nomor WhatsApp anggota [{$member['nama']}] belum terdaftar."];
        }

        $nama = $member['nama'];
        $kelas = $member['kelas'] ?? '-';
        $tagihanFmt = number_format($tagihan, 0, ',', '.');
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas';

        $msg = "📑 *INFORMASI TAGIHAN KAS BARU*\n\n"
             . "Halo Sdr/i *{$nama}* ({$kelas}),\n"
             . "Tagihan iuran kas baru telah diterbitkan untuk periode *{$periode}*.\n\n"
             . "💰 *Besaran Iuran:* *Rp {$tagihanFmt}*\n"
             . "Penyetoran dapat dilakukan secara langsung kepada Bendahara atau melalui metode transfer yang disepakati.\n\n"
             . "Mari jaga ketertiban keuangan kas bersama!\n"
             . "_Dikelola dengan {$appName}_";

        return $this->send(
            $phone,
            $msg,
            'informasi_tagihan',
            $memberId,
            $nama,
            $sentBy
        );
    }

    /**
     * Test Gateway Connection
     */
    public function testConnection(string $targetPhone, ?string $customMessage = null, ?int $sentBy = null): array {
        $appName = defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas AI';
        $providerName = $this->getProvider()->getProviderName();

        $msg = $customMessage ?? (
            "🔔 *TES KONEKSI WHATSAPP GATEWAY*\n\n"
            . "Pesan ini adalah uji coba koneksi gateway sistem *{$appName}*.\n"
            . "• Provider: *{$providerName}*\n"
            . "• Status: *Aktif & Terhubung*\n"
            . "• Waktu Uji: " . date('d M Y, H:i:s T') . "\n\n"
            . "Integrasi notifikasi WhatsApp siap digunakan secara operasional."
        );

        return $this->send(
            $targetPhone,
            $msg,
            'test',
            null,
            'Uji Coba Sistem',
            $sentBy
        );
    }

    /**
     * Inspect gateway device connectivity status (read-only, does not send messages)
     */
    public function checkDeviceStatus(): array {
        $provider = $this->getProvider();
        if (method_exists($provider, 'checkDeviceStatus')) {
            return $provider->checkDeviceStatus();
        }
        return [
            'success' => $provider->isConfigured(),
            'status'  => $provider->isConfigured() ? 'ready' : 'unconfigured',
        ];
    }
}
