<?php
/**
 * User Model
 * Represents the `users` table
 */

namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected string $table = 'users';

    /**
     * Find user by username or email
     */
    public function findByUsernameOrEmail(string $identifier): ?array {
        $identifier = trim($identifier);
        $sql = "SELECT * FROM `{$this->table}` WHERE (username = :user OR email = :email) LIMIT 1";
        return $this->fetchOne($sql, ['user' => $identifier, 'email' => $identifier]);
    }

    public function findByUsername(string $username): ?array {
        return $this->findBy('username', trim($username));
    }

    public function findByEmail(string $email): ?array {
        return $this->findBy('email', trim($email));
    }

    /**
     * Authenticate user credentials
     */
    public function authenticate(string $identifier, string $plainPassword): ?array {
        $user = $this->findByUsernameOrEmail($identifier);
        if (!$user) {
            return null;
        }

        // Check if user is active
        if ($user['status'] !== 'active') {
            return null;
        }

        // Verify hashed password
        if (password_verify($plainPassword, $user['password'])) {
            return $user;
        }

        return null;
    }

    /**
     * Create user with hashed password
     */
    public function register(array $data): int|false {
        $insertData = [
            'username' => $data['username'],
            'nama'     => $data['nama'] ?? null,
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'     => $data['role'] ?? 'member',
            'status'   => $data['status'] ?? 'active',
        ];
        return $this->create($insertData);
    }

    /**
     * Relationship: HasOne Member
     */
    public function member(int $userId): ?array {
        $memberModel = new Member();
        return $memberModel->findBy('user_id', $userId);
    }

    /**
     * Relationship: HasMany Transactions (created_by)
     */
    public function transactions(int $userId): array {
        $sql = "SELECT * FROM `transactions` WHERE created_by = :uid ORDER BY id DESC";
        return $this->fetchAll($sql, ['uid' => $userId]);
    }

    /**
     * Relationship: HasMany Payments (verified_by)
     */
    public function verifiedPayments(int $userId): array {
        $sql = "SELECT * FROM `payments` WHERE verified_by = :uid ORDER BY id DESC";
        return $this->fetchAll($sql, ['uid' => $userId]);
    }

    /**
     * Relationship: HasMany AI Logs
     */
    public function aiLogs(int $userId): array {
        $sql = "SELECT * FROM `ai_logs` WHERE user_id = :uid ORDER BY id DESC";
        return $this->fetchAll($sql, ['uid' => $userId]);
    }

    /**
     * Relationship: HasMany Audit Logs
     */
    public function auditLogs(int $userId): array {
        $sql = "SELECT * FROM `audit_logs` WHERE user_id = :uid ORDER BY id DESC";
        return $this->fetchAll($sql, ['uid' => $userId]);
    }
}
