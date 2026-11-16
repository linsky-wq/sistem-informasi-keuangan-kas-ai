<?php
/**
 * AiLog Model
 * Represents the `ai_logs` table
 */

namespace App\Models;

use App\Core\Model;

class AiLog extends Model {
    protected string $table = 'ai_logs';

    /**
     * Record an AI interaction log
     */
    public function record(?int $userId, string $question, string $response): int|false {
        return $this->create([
            'user_id'  => $userId,
            'question' => $question,
            'response' => $response,
        ]);
    }

    /**
     * Get recent AI chat history for a user
     */
    public function getHistoryByUser(int $userId, int $limit = 20): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE user_id = :uid ORDER BY id DESC LIMIT {$limit}";
        return $this->fetchAll($sql, ['uid' => $userId]);
    }

    /**
     * Delete all history for a user
     */
    public function deleteByUser(int $userId): bool {
        $stmt = $this->query("DELETE FROM `{$this->table}` WHERE user_id = :uid", ['uid' => $userId]);
        return $stmt !== false;
    }

    /**
     * Get all AI logs with user info
     */
    public function getRecentLogs(int $limit = 50): array {
        $sql = "SELECT a.*, u.username, u.role 
                FROM `{$this->table}` a 
                LEFT JOIN `users` u ON a.user_id = u.id 
                ORDER BY a.id DESC LIMIT {$limit}";
        return $this->fetchAll($sql);
    }

    /**
     * Relationship: BelongsTo User
     */
    public function user(int $userId): ?array {
        $userModel = new User();
        return $userModel->find($userId);
    }
}
