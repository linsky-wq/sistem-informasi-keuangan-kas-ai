<?php
/**
 * WhatsApp Setting Model
 * Manages configuration for external WhatsApp gateway integration
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WhatsAppSetting extends Model {
    protected string $table = 'whatsapp_settings';
    protected string $primaryKey = 'id';

    /**
     * Get setting value by key, with default fallback to environment variables
     */
    public function get(string $key, mixed $default = null): mixed {
        $record = $this->findBy('setting_key', $key);
        if ($record !== null && $record['setting_value'] !== null && trim((string)$record['setting_value']) !== '') {
            $val = $record['setting_value'];
            if ($val === '1' || $val === 'true') return true;
            if ($val === '0' || $val === 'false') return false;
            return $val;
        }

        // Fallback to constants or env
        return match ($key) {
            'wa_enabled'                  => defined('WA_ENABLED') ? WA_ENABLED : (bool)env('WA_ENABLED', true),
            'wa_provider'                 => defined('WA_PROVIDER') && WA_PROVIDER !== '' ? WA_PROVIDER : (string)env('WA_PROVIDER', 'mock'),
            'wa_api_url'                  => defined('WA_API_URL') && WA_API_URL !== '' ? WA_API_URL : (string)env('WA_API_URL', 'https://api.fonnte.com/send'),
            'wa_api_token'                => defined('WA_API_TOKEN') && WA_API_TOKEN !== '' ? WA_API_TOKEN : (string)env('WA_API_TOKEN', ''),
            'wa_sender_number'            => defined('WA_SENDER_NUMBER') && WA_SENDER_NUMBER !== '' ? WA_SENDER_NUMBER : (string)env('WA_SENDER_NUMBER', '081234567890'),
            'wa_auto_notify_payment'      => defined('WA_AUTO_NOTIFY_PAYMENT') ? WA_AUTO_NOTIFY_PAYMENT : (bool)env('WA_AUTO_NOTIFY_PAYMENT', true),
            'wa_auto_notify_verification' => defined('WA_AUTO_NOTIFY_VERIFICATION') ? WA_AUTO_NOTIFY_VERIFICATION : (bool)env('WA_AUTO_NOTIFY_VERIFICATION', true),
            default                       => $default,
        };
    }

    /**
     * Check whether the active WhatsApp API token originates from environment (.env)
     */
    public function isTokenFromEnv(): bool {
        $record = $this->findBy('setting_key', 'wa_api_token');
        $dbToken = $record !== null ? trim((string)$record['setting_value']) : '';
        $envToken = defined('WA_API_TOKEN') && WA_API_TOKEN !== '' ? WA_API_TOKEN : (string)env('WA_API_TOKEN', '');
        return empty($dbToken) && !empty($envToken);
    }

    /**
     * Set or update setting value by key
     */
    public function set(string $key, mixed $value): bool {
        $strValue = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        $existing = $this->findBy('setting_key', $key);

        if ($existing) {
            return $this->update((int)$existing['id'], [
                'setting_value' => $strValue,
            ]);
        }

        return (bool)$this->create([
            'setting_key'   => $key,
            'setting_value' => $strValue,
        ]);
    }

    /**
     * Get all settings as an associative key => value array
     */
    public function getAllSettings(): array {
        $keys = [
            'wa_enabled',
            'wa_provider',
            'wa_api_url',
            'wa_api_token',
            'wa_sender_number',
            'wa_auto_notify_payment',
            'wa_auto_notify_verification',
        ];

        $settings = [];
        foreach ($keys as $k) {
            $settings[$k] = $this->get($k);
        }

        return $settings;
    }

    /**
     * Get masked token for secure UI display
     */
    public function getMaskedToken(): string {
        $token = (string)$this->get('wa_api_token', '');
        if (empty($token)) {
            return '';
        }
        $len = strlen($token);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($token, 0, 3) . str_repeat('*', max(4, $len - 6)) . substr($token, -3);
    }
}
