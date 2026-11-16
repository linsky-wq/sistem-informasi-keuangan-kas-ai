<?php
/**
 * AuditLog Model
 * Represents the `audit_logs` table
 */

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model {
    protected string $table = 'audit_logs';

    /**
     * Record an audit log entry
     */
    public function record(
        ?int $userId,
        string $action,
        string $entity,
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): int|false {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        return $this->create([
            'user_id'    => $userId,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'old_data'   => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            'new_data'   => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $ip,
        ]);
    }

    /**
     * Get recent audit logs with user info
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
