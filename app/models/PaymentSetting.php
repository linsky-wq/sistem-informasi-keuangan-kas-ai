<?php
/**
 * PaymentSetting Model
 * Manages configuration for payment methods (Bank Accounts, QRIS, E-Wallet, Gateway)
 */

namespace App\Models;

use App\Core\Model;

class PaymentSetting extends Model {
    protected string $table = 'payment_settings';

    /**
     * Get a setting value by key with optional default
     */
    public function get(string $key, mixed $default = null): mixed {
        $sql = "SELECT setting_value FROM `{$this->table}` WHERE setting_key = :k LIMIT 1";
        $row = $this->fetchOne($sql, ['k' => $key]);
        if (!$row || $row['setting_value'] === null) {
            return $default;
        }
        return $row['setting_value'];
    }

    /**
     * Set / update a setting value
     */
    public function set(string $key, mixed $value): bool {
        $valStr = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $sql = "INSERT INTO `{$this->table}` (setting_key, setting_value) 
                VALUES (:k, :v) 
                ON DUPLICATE KEY UPDATE setting_value = :v2, updated_at = CURRENT_TIMESTAMP";
        return (bool)$this->query($sql, ['k' => $key, 'v' => $valStr, 'v2' => $valStr]);
    }

    /**
     * Get all settings as key-value array
     */
    public function getAll(): array {
        $sql = "SELECT setting_key, setting_value FROM `{$this->table}`";
        $rows = $this->fetchAll($sql);
        $result = [];
        foreach ($rows as $r) {
            $result[$r['setting_key']] = $r['setting_value'];
        }
        return $result;
    }

    /**
     * Get list of configured destination bank accounts
     */
    public function getBankAccounts(): array {
        $raw = $this->get('bank_accounts');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Save destination bank accounts
     */
    public function saveBankAccounts(array $accounts): bool {
        $cleanAccounts = [];
        foreach ($accounts as $acc) {
            if (!empty($acc['bank_name']) && !empty($acc['account_number'])) {
                $cleanAccounts[] = [
                    'bank_name'      => strtoupper(trim((string)$acc['bank_name'])),
                    'account_number' => trim((string)$acc['account_number']),
                    'account_holder' => trim((string)($acc['account_holder'] ?? 'Kas Anggota RPL')),
                    'instructions'   => trim((string)($acc['instructions'] ?? '')),
                ];
            }
        }
        return $this->set('bank_accounts', $cleanAccounts);
    }

    /**
     * Check whether QRIS payment gateway is officially configured and enabled
     */
    public function isQrisConfigured(): bool {
        $enabled = (int)$this->get('qris_enabled', 0);
        $serverKey = trim((string)$this->get('midtrans_server_key', ''));
        return ($enabled === 1) && !empty($serverKey);
    }

    /**
     * Check whether E-Wallet payment gateway is officially configured and enabled
     */
    public function isEwalletConfigured(): bool {
        $enabled = (int)$this->get('ewallet_enabled', 0);
        $serverKey = trim((string)$this->get('midtrans_server_key', ''));
        return ($enabled === 1) && !empty($serverKey);
    }

    /**
     * Get full payment gateway settings
     */
    public function getGatewayConfig(): array {
        return [
            'provider'           => $this->get('payment_gateway_provider', 'midtrans'),
            'environment'        => $this->get('payment_gateway_env', 'sandbox'),
            'client_key'         => $this->get('midtrans_client_key', ''),
            'server_key'         => $this->get('midtrans_server_key', ''),
            'has_server_key'     => !empty(trim((string)$this->get('midtrans_server_key', ''))),
            'qris_enabled'       => (int)$this->get('qris_enabled', 0) === 1,
            'ewallet_enabled'    => (int)$this->get('ewallet_enabled', 0) === 1,
            'is_qris_active'     => $this->isQrisConfigured(),
            'is_ewallet_active'  => $this->isEwalletConfigured(),
        ];
    }
}
