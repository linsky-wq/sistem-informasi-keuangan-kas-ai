<?php
/**
 * PaymentGatewayService
 * Manages official payment gateway integrations (Midtrans QRIS & E-Wallet)
 * Strictly adheres to official documentation, sandbox/production mode, and webhook signature verification
 */

namespace App\Services;

use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\AuditLog;

class PaymentGatewayService {
    private PaymentSetting $settingModel;
    private PaymentTransaction $payTrxModel;
    private AuditLog $auditModel;

    public function __construct(?PaymentSetting $settingModel = null) {
        $this->settingModel = $settingModel ?? new PaymentSetting();
        $this->payTrxModel = new PaymentTransaction();
        $this->auditModel = new AuditLog();
    }

    /**
     * Check if payment gateway credentials are configured
     */
    public function isConfigured(): bool {
        $serverKey = trim((string)$this->settingModel->get('midtrans_server_key', ''));
        return !empty($serverKey);
    }

    /**
     * Check if QRIS is active and properly configured
     */
    public function isQrisAvailable(): bool {
        return $this->settingModel->isQrisConfigured();
    }

    /**
     * Check if E-Wallet is active and properly configured
     */
    public function isEwalletAvailable(): bool {
        return $this->settingModel->isEwalletConfigured();
    }

    /**
     * Get Base API URL based on environment
     */
    public function getApiBaseUrl(): string {
        $env = strtolower((string)$this->settingModel->get('payment_gateway_env', 'sandbox'));
        return ($env === 'production')
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    /**
     * Get Snap API URL based on environment
     */
    public function getSnapApiUrl(): string {
        $env = strtolower((string)$this->settingModel->get('payment_gateway_env', 'sandbox'));
        return ($env === 'production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * Create Snap Transaction Token via Official Midtrans API
     */
    public function createSnapTransaction(array $params): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error'   => 'Layanan Payment Gateway belum aktif. Kredensial Server Key resmi belum dikonfigurasi oleh Administrator.',
            ];
        }

        $serverKey = trim((string)$this->settingModel->get('midtrans_server_key', ''));
        $apiUrl = $this->getSnapApiUrl();

        $payload = json_encode($params, JSON_UNESCAPED_SLASHES);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $serverKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'success' => false,
                'error'   => 'Koneksi ke gateway gagal: ' . $curlErr,
            ];
        }

        $resData = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && !empty($resData['token'])) {
            return [
                'success'      => true,
                'token'        => $resData['token'],
                'redirect_url' => $resData['redirect_url'] ?? '',
                'raw'          => $resData,
            ];
        }

        return [
            'success' => false,
            'error'   => $resData['error_messages'][0] ?? ('Gagal menghubungi provider gateway (HTTP ' . $httpCode . ')'),
            'raw'     => $resData,
        ];
    }

    /**
     * Handle and verify Official Midtrans Notification Webhook Callback
     */
    public function handleNotification(array $payload): array {
        if (!$this->isConfigured()) {
            return [
                'status'  => 'error',
                'message' => 'Payment gateway credentials are not configured.',
                'code'    => 400,
            ];
        }

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $receivedSignature = $payload['signature_key'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? '';

        if (empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($receivedSignature)) {
            return [
                'status'  => 'error',
                'message' => 'Payload notification callback tidak lengkap.',
                'code'    => 400,
            ];
        }

        // 1. Official SHA512 Signature Verification
        $serverKey = trim((string)$this->settingModel->get('midtrans_server_key', ''));
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($expectedSignature, $receivedSignature)) {
            return [
                'status'  => 'error',
                'message' => 'Invalid webhook signature key.',
                'code'    => 403,
            ];
        }

        // 2. Find Payment Transaction by kode_transaksi or gateway_reference
        $db = \App\Core\Database::getInstance()->getConnection();
        $sql = "SELECT * FROM payment_transactions WHERE kode_transaksi = :o1 OR gateway_reference = :o2 LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['o1' => $orderId, 'o2' => $orderId]);
        $trx = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$trx) {
            return [
                'status'  => 'error',
                'message' => "Order reference [$orderId] not found in database.",
                'code'    => 404,
            ];
        }

        $trxId = (int)$trx['id'];

        // 3. Process Status Transitions
        // Successful payment: settlement OR capture (with fraud_status = accept)
        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
            if ($trx['status'] === PaymentTransaction::STATUS_BERHASIL) {
                // Idempotency: Already processed successfully
                return [
                    'status'  => 'success',
                    'message' => 'Transaction already processed previously.',
                    'code'    => 200,
                ];
            }

            // System verification
            $systemUserId = 1; // Default super user ID
            $verifyResult = $this->payTrxModel->verifyPayment($trxId, $systemUserId, "Diverifikasi otomatis via Webhook Midtrans (Status: {$transactionStatus})");

            if ($verifyResult['success']) {
                // Update gateway reference & payload
                $this->payTrxModel->update($trxId, [
                    'gateway_reference' => $orderId,
                    'gateway_payload'   => json_encode($payload, JSON_UNESCAPED_SLASHES),
                ]);

                $this->auditModel->record(
                    $systemUserId,
                    'GATEWAY_PAYMENT_SUCCESS',
                    'payment_transactions',
                    $trxId,
                    null,
                    ['order_id' => $orderId, 'status' => $transactionStatus, 'amount' => $grossAmount]
                );

                return [
                    'status'  => 'success',
                    'message' => 'Payment verified and Buku Kas updated successfully.',
                    'code'    => 200,
                ];
            }

            return [
                'status'  => 'error',
                'message' => 'Failed to verify payment: ' . ($verifyResult['error'] ?? 'Unknown error'),
                'code'    => 500,
            ];
        } elseif ($transactionStatus === 'pending') {
            $this->payTrxModel->update($trxId, [
                'status'            => PaymentTransaction::STATUS_MENUNGGU_PEMBAYARAN,
                'gateway_reference' => $orderId,
                'gateway_payload'   => json_encode($payload, JSON_UNESCAPED_SLASHES),
            ]);
            return [
                'status'  => 'success',
                'message' => 'Status updated to pending payment.',
                'code'    => 200,
            ];
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
            $newStatus = ($transactionStatus === 'expire') ? PaymentTransaction::STATUS_KEDALUWARSA : PaymentTransaction::STATUS_DITOLAK;
            $this->payTrxModel->update($trxId, [
                'status'            => $newStatus,
                'alasan_penolakan'  => "Ditolak / Kedaluwarsa oleh penyedia pembayaran (Status: {$transactionStatus})",
                'gateway_reference' => $orderId,
                'gateway_payload'   => json_encode($payload, JSON_UNESCAPED_SLASHES),
            ]);
            return [
                'status'  => 'success',
                'message' => "Transaction marked as {$newStatus}.",
                'code'    => 200,
            ];
        }

        return [
            'status'  => 'success',
            'message' => "Notification acknowledged with status: {$transactionStatus}.",
            'code'    => 200,
        ];
    }
}
