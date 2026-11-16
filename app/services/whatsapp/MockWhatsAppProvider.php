<?php
/**
 * Mock WhatsApp Provider
 * Simulates WhatsApp messaging for local testing, offline demo, and automated test suites
 */

declare(strict_types=1);

namespace App\Services\Whatsapp;

class MockWhatsAppProvider implements WhatsAppProviderInterface {
    private bool $simulateFailure = false;

    public function __construct(bool $simulateFailure = false) {
        $this->simulateFailure = $simulateFailure;
    }

    public function sendMessage(string $phone, string $message): array {
        // Normalize check
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        if ($this->simulateFailure || str_contains($cleanPhone, '000000')) {
            return [
                'success'    => false,
                'message_id' => null,
                'status'     => 'failed',
                'response'   => ['error' => 'Simulated delivery failure (mock)'],
                'error'      => 'Nomor telepon tujuan tidak aktif atau tidak terdaftar di WhatsApp.',
            ];
        }

        $mockId = 'mock_msg_' . bin2hex(random_bytes(6)) . '_' . time();

        return [
            'success'    => true,
            'message_id' => $mockId,
            'status'     => 'sent',
            'response'   => [
                'status'     => true,
                'id'         => $mockId,
                'target'     => $phone,
                'message'    => mb_substr($message, 0, 50) . '...',
                'mode'       => 'mock_simulator',
                'timestamp'  => date('Y-m-d H:i:s'),
            ],
            'error'      => null,
        ];
    }

    public function isConfigured(): bool {
        return true;
    }

    public function getProviderName(): string {
        return 'Mock WhatsApp Simulator (Local/Sandbox)';
    }
}
