<?php
/**
 * Generic HTTP WhatsApp Provider
 * Compatible with standard REST / JSON WhatsApp gateways (e.g. Fonnte, Wablas, Custom API)
 */

declare(strict_types=1);

namespace App\Services\Whatsapp;

class GenericHttpWhatsAppProvider implements WhatsAppProviderInterface {
    private string $apiUrl;
    private string $apiToken;
    private string $senderNumber;

    public function __construct(string $apiUrl, string $apiToken, string $senderNumber = '') {
        $this->apiUrl = $apiUrl;
        $this->apiToken = $apiToken;
        $this->senderNumber = $senderNumber;
    }

    public function sendMessage(string $phone, string $message): array {
        if (!$this->isConfigured()) {
            return [
                'success'    => false,
                'message_id' => null,
                'status'     => 'failed',
                'response'   => null,
                'error'      => 'Kredensial WhatsApp API (URL atau Token) belum dikonfigurasi.',
            ];
        }

        // Clean & normalize token (strip any "Bearer " prefix if user added it)
        $token = trim($this->apiToken);
        if (str_starts_with(strtolower($token), 'bearer ')) {
            $token = trim(substr($token, 7));
        }

        // Standard payload for FONNTE and compatible gateways
        $payload = [
            'target'      => $phone,
            'message'     => $message,
            'countryCode' => '62',
            'phone'       => $phone,
        ];
        if (!empty($this->senderNumber)) {
            $payload['sender'] = $this->senderNumber;
        }

        $headers = [
            'Authorization: ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        // Prepare cURL request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $maxAttempts = 2; // initial try + one retry
        $attempt = 0;
        $rawResponse = '';
        $curlError = '';
        $httpCode = 0;
        while ($attempt < $maxAttempts) {
            $rawResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            if ($curlError) {
                $attempt++;
                if ($attempt < $maxAttempts) {
                    // brief pause before retry
                    usleep(500000); // 0.5 seconds
                    continue;
                }
                // no more retries
                break;
            }
            // successful request, exit loop
            break;
        }
        curl_close($ch);

        if ($curlError) {
            return [
                'success'    => false,
                'message_id' => null,
                'status'     => 'failed',
                'response'   => ['curl_error' => $curlError],
                'error'      => 'Gagal terhubung ke WhatsApp Gateway: ' . $curlError,
            ];
        }

        $decoded = json_decode((string)$rawResponse, true);
        
        // FONNTE returns "status" (bool true) and an "id" array or string on success
        $isSuccess = ($httpCode >= 200 && $httpCode < 300) && (
            (isset($decoded['status']) && $decoded['status'] === true) ||
            (isset($decoded['success']) && $decoded['success'] === true)
        );

        // Extract message ID cleanly without Array-to-string notice
        $rawId = $decoded['id'] ?? $decoded['message_id'] ?? ($decoded['data']['id'] ?? null);
        if (is_array($rawId)) {
            $messageId = !empty($rawId) ? (string)reset($rawId) : null;
        } elseif ($rawId !== null) {
            $messageId = (string)$rawId;
        } else {
            $messageId = null;
        }

        // Extract error message cleanly with priority for Fonnte 'reason'
        $errorMessage = null;
        if (!$isSuccess) {
            $err = $decoded['reason'] ?? $decoded['message'] ?? $decoded['detail'] ?? $decoded['error'] ?? null;
            if (is_array($err)) {
                $errorMessage = json_encode($err, JSON_UNESCAPED_UNICODE);
            } elseif ($err !== null && $err !== '') {
                $errorMessage = (string)$err;
            } else {
                $errorMessage = "HTTP Status {$httpCode}" . ($rawResponse ? ": " . substr((string)$rawResponse, 0, 150) : '');
            }
        }

        return [
            'success'    => $isSuccess,
            'message_id' => $messageId,
            'status'     => $isSuccess ? 'sent' : 'failed',
            'response'   => $decoded ?? $rawResponse,
            'error'      => $errorMessage,
        ];
    }

    /**
     * Inspect device connectivity and profile directly from Fonnte API (read-only, does not send messages)
     */
    public function checkDeviceStatus(): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status'  => 'unconfigured',
                'error'   => 'Kredensial WhatsApp API belum dikonfigurasi.',
            ];
        }

        $token = trim($this->apiToken);
        if (str_starts_with(strtolower($token), 'bearer ')) {
            $token = trim(substr($token, 7));
        }

        $deviceUrl = str_contains($this->apiUrl, 'fonnte.com') 
            ? 'https://api.fonnte.com/device' 
            : rtrim($this->apiUrl, '/') . '/device';

        $ch = curl_init($deviceUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'status'  => 'error',
                'error'   => 'cURL Error: ' . $curlError,
            ];
        }

        $decoded = json_decode((string)$rawResponse, true);
        if (is_array($decoded) && isset($decoded['status']) && $decoded['status'] === true) {
            return [
                'success'       => true,
                'device'        => $decoded['device'] ?? null,
                'device_status' => $decoded['device_status'] ?? 'connect',
                'name'          => $decoded['name'] ?? null,
                'quota'         => $decoded['quota'] ?? null,
                'package'       => $decoded['package'] ?? null,
                'expired'       => $decoded['expired'] ?? null,
                'raw'           => $decoded,
            ];
        }

        return [
            'success' => false,
            'status'  => 'disconnected',
            'error'   => is_array($decoded) ? ($decoded['reason'] ?? $decoded['message'] ?? 'Device tidak terhubung') : "HTTP {$httpCode}",
            'raw'     => $decoded ?? $rawResponse,
        ];
    }

    public function isConfigured(): bool {
        return !empty($this->apiUrl) && !empty($this->apiToken);
    }

    public function getProviderName(): string {
        if (str_contains($this->apiUrl, 'fonnte.com')) {
            return 'Fonnte WhatsApp API (api.fonnte.com)';
        }
        return 'WhatsApp REST Gateway (' . (parse_url($this->apiUrl, PHP_URL_HOST) ?? 'HTTP') . ')';
    }
}
