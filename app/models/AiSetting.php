<?php
/**
 * AI Setting Model
 * Manages configuration for AI Financial Assistant providers (Gemini, Groq, NVIDIA, Mock)
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AiSetting extends Model {
    protected string $table = 'ai_settings';
    protected string $primaryKey = 'id';

    /**
     * Get setting value by key, with default fallback to environment variables
     */
    public function get(string $key, mixed $default = null): mixed {
        try {
            $record = $this->findBy('setting_key', $key);
            if ($record !== null && $record['setting_value'] !== null && trim((string)$record['setting_value']) !== '') {
                $val = $record['setting_value'];
                if ($val === '1' || $val === 'true') return true;
                if ($val === '0' || $val === 'false') return false;
                return $val;
            }
        } catch (\Throwable $e) {
            // Graceful fallback if table is unavailable
        }

        // Fallback to constants or .env
        return match ($key) {
            'ai_enabled'      => (bool)env('AI_ENABLED', true),
            'ai_provider'     => (string)env('AI_PROVIDER', 'auto'),
            'gemini_api_key'  => defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '' ? GEMINI_API_KEY : (string)env('GEMINI_API_KEY', ''),
            'gemini_model'    => defined('GEMINI_MODEL') && GEMINI_MODEL !== '' ? GEMINI_MODEL : (string)env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'groq_api_key'    => defined('GROQ_API_KEY') && GROQ_API_KEY !== '' ? GROQ_API_KEY : (string)env('GROQ_API_KEY', ''),
            'groq_model'      => defined('GROQ_MODEL') && GROQ_MODEL !== '' ? GROQ_MODEL : (string)env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'groq_api_url'    => defined('GROQ_API_URL') && GROQ_API_URL !== '' ? GROQ_API_URL : (string)env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
            'nvidia_enabled'  => defined('NVIDIA_ENABLED') ? (bool)NVIDIA_ENABLED : (bool)env('NVIDIA_ENABLED', false),
            'nvidia_api_key'  => defined('NVIDIA_API_KEY') && NVIDIA_API_KEY !== '' ? NVIDIA_API_KEY : (string)env('NVIDIA_API_KEY', ''),
            'nvidia_api_url'  => defined('NVIDIA_API_URL') && NVIDIA_API_URL !== '' ? NVIDIA_API_URL : (string)env('NVIDIA_API_URL', 'https://integrate.api.nvidia.com/v1/chat/completions'),
            'nvidia_model'    => defined('NVIDIA_MODEL') && NVIDIA_MODEL !== '' ? NVIDIA_MODEL : (string)env('NVIDIA_MODEL', 'meta/llama-3.1-70b-instruct'),
            default           => $default,
        };
    }

    /**
     * Check whether an API key originates from environment (.env)
     */
    public function isKeyFromEnv(string $key): bool {
        try {
            $record = $this->findBy('setting_key', $key);
            $dbVal = $record !== null ? trim((string)$record['setting_value']) : '';
        } catch (\Throwable $e) {
            $dbVal = '';
        }

        $envVal = match ($key) {
            'gemini_api_key' => defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '' ? GEMINI_API_KEY : (string)env('GEMINI_API_KEY', ''),
            'groq_api_key'   => defined('GROQ_API_KEY') && GROQ_API_KEY !== '' ? GROQ_API_KEY : (string)env('GROQ_API_KEY', ''),
            'nvidia_api_key' => defined('NVIDIA_API_KEY') && NVIDIA_API_KEY !== '' ? NVIDIA_API_KEY : (string)env('NVIDIA_API_KEY', ''),
            default          => '',
        };

        return empty($dbVal) && !empty($envVal);
    }

    /**
     * Set or update setting value by key
     */
    public function set(string $key, mixed $value): bool {
        $strValue = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        try {
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
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get all settings as an associative key => value array
     */
    public function getAllSettings(): array {
        $keys = [
            'ai_enabled',
            'ai_provider',
            'gemini_api_key',
            'gemini_model',
            'groq_api_key',
            'groq_model',
            'groq_api_url',
            'nvidia_enabled',
            'nvidia_api_key',
            'nvidia_api_url',
            'nvidia_model',
        ];

        $settings = [];
        foreach ($keys as $k) {
            $settings[$k] = $this->get($k);
        }

        return $settings;
    }

    /**
     * Get masked key for secure UI display
     */
    public function getMaskedKey(string $key): string {
        $val = (string)$this->get($key, '');
        if (empty($val)) {
            return '';
        }
        $len = strlen($val);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($val, 0, 3) . str_repeat('*', max(4, $len - 6)) . substr($val, -3);
    }
}

