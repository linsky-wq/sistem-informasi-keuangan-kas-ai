<?php
/**
 * WhatsApp Provider Interface
 * Contract for external WhatsApp messaging services
 */

declare(strict_types=1);

namespace App\Services\Whatsapp;

interface WhatsAppProviderInterface {
    /**
     * Send a WhatsApp message to a target phone number
     *
     * @param string $phone Target phone number (E.164 or Indonesian format)
     * @param string $message Formatted text message
     * @return array ['success' => bool, 'message_id' => ?string, 'status' => string, 'response' => mixed, 'error' => ?string]
     */
    public function sendMessage(string $phone, string $message): array;

    /**
     * Check whether provider credentials and endpoints are properly configured
     */
    public function isConfigured(): bool;

    /**
     * Get human-readable provider name
     */
    public function getProviderName(): string;
}
