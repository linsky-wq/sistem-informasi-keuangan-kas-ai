<?php
/**
 * WhatsApp Log Model
 * Tracks delivery records, recipients, status, and API responses for WhatsApp notifications
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WhatsAppLog extends Model {
    protected string $table = 'whatsapp_logs';
    protected string $primaryKey = 'id';

    /**
     * Map notification types to human-readable titles (Judul)
     */
    public static function formatTitle(string $type): string {
        return match ($type) {
            'pengingat_pembayaran'  => 'Pengingat Pembayaran Kas',
            'konfirmasi_pembayaran' => 'Konfirmasi Penerimaan Pembayaran',
            'status_verifikasi'     => 'Status Verifikasi Pembayaran',
            'informasi_tagihan'     => 'Informasi Tagihan Kas Baru',
            'transaksi'             => 'Notifikasi Transaksi Kas',
            'laporan_ringkasan'     => 'Ringkasan Laporan Keuangan',
            'test'                  => 'Uji Coba Sistem WhatsApp',
            default                 => ucwords(str_replace('_', ' ', $type)),
        };
    }

    /**
     * Record a new WhatsApp notification log
     */
    public function logMessage(array $data): int|false {
        return $this->create([
            'recipient_phone'     => $data['recipient_phone'],
            'recipient_name'      => $data['recipient_name'] ?? null,
            'member_id'           => !empty($data['member_id']) ? (int)$data['member_id'] : null,
            'notification_type'   => $data['notification_type'],
            'message'             => $data['message'],
            'status'              => $data['status'] ?? 'pending',
            'is_read'             => isset($data['is_read']) ? (int)$data['is_read'] : 0,
            'read_at'             => $data['read_at'] ?? null,
            'provider_message_id' => $data['provider_message_id'] ?? null,
            'delivered_at'        => $data['delivered_at'] ?? null,
            'error_message'       => $data['error_message'] ?? null,
            'response_payload'    => isset($data['response_payload']) ? (is_array($data['response_payload']) ? json_encode($data['response_payload']) : (string)$data['response_payload']) : null,
            'sent_by'             => !empty($data['sent_by']) ? (int)$data['sent_by'] : null,
            'sent_at'             => $data['sent_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update log status and provider response
     */
    public function updateStatus(int $id, string $status, ?string $responsePayload = null): bool {
        $data = ['status' => $status];
        if ($responsePayload !== null) {
            $data['response_payload'] = $responsePayload;
        }
        return $this->update($id, $data);
    }

    /**
     * Record delivery result with provider id, delivered timestamp, and error message
     */
    public function recordDeliveryResult(int $id, array $data): bool {
        $update = [];
        if (isset($data['status'])) {
            $update['status'] = $data['status'];
        }
        if (isset($data['provider_message_id'])) {
            $update['provider_message_id'] = $data['provider_message_id'];
        }
        if (isset($data['delivered_at'])) {
            $update['delivered_at'] = $data['delivered_at'];
        }
        if (isset($data['error_message'])) {
            $update['error_message'] = $data['error_message'];
        }
        if (isset($data['response_payload'])) {
            $update['response_payload'] = $data['response_payload'];
        }
        return $this->update($id, $update);
    }

    /**
     * Mark a notification as read (scoped to member for security)
     */
    public function markAsRead(int $id, int $memberId): bool {
        $sql = "UPDATE `{$this->table}` 
                SET `is_read` = 1, `read_at` = NOW() 
                WHERE `id` = :id AND `member_id` = :member_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id, 'member_id' => $memberId]);
    }

    /**
     * Mark all notifications as read for a member
     */
    public function markAllAsRead(int $memberId): bool {
        $sql = "UPDATE `{$this->table}` 
                SET `is_read` = 1, `read_at` = NOW() 
                WHERE `member_id` = :member_id AND `is_read` = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['member_id' => $memberId]);
    }

    /**
     * Count unread notifications for a member
     */
    public function getUnreadCountByMember(int $memberId): int {
        $sql = "SELECT COUNT(*) as unread_total 
                FROM `{$this->table}` 
                WHERE `member_id` = :member_id AND `is_read` = 0";
        $res = $this->fetchOne($sql, ['member_id' => $memberId]);
        return (int)($res['unread_total'] ?? 0);
    }

    /**
     * Get a single notification strictly belonging to a specific member
     */
    public function getMemberNotification(int $memberId, int $id): ?array {
        $sql = "SELECT l.*, 
                       m.nama as member_nama, m.nomor_anggota, m.kelas
                FROM `{$this->table}` l
                LEFT JOIN `members` m ON l.member_id = m.id
                WHERE l.id = :id AND l.member_id = :member_id
                LIMIT 1";
        $row = $this->fetchOne($sql, ['id' => $id, 'member_id' => $memberId]);
        if ($row) {
            $row['title'] = self::formatTitle($row['notification_type']);
        }
        return $row ?: null;
    }

    /**
     * Get paginated notifications strictly scoped to a logged-in member
     */
    public function getMemberNotifications(int $memberId, array $filters = [], int $page = 1, int $perPage = 10): array {
        // Enforce member_id strictly in server query
        $conditions = ['l.member_id = :member_id'];
        $params = ['member_id' => $memberId];

        if (!empty($filters['search'])) {
            $conditions[] = "(l.message LIKE :search OR l.notification_type LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (isset($filters['read_status']) && $filters['read_status'] !== '') {
            if ($filters['read_status'] === 'unread') {
                $conditions[] = "l.is_read = 0";
            } elseif ($filters['read_status'] === 'read') {
                $conditions[] = "l.is_read = 1";
            }
        }

        if (!empty($filters['notification_type'])) {
            $conditions[] = "l.notification_type = :type";
            $params['type'] = trim($filters['notification_type']);
        }

        if (!empty($filters['status'])) {
            $conditions[] = "l.status = :status";
            $params['status'] = trim($filters['status']);
        }

        if (!empty($filters['date_start'])) {
            $conditions[] = "DATE(l.created_at) >= :date_start";
            $params['date_start'] = trim($filters['date_start']);
        }

        if (!empty($filters['date_end'])) {
            $conditions[] = "DATE(l.created_at) <= :date_end";
            $params['date_end'] = trim($filters['date_end']);
        }

        $whereSql = implode(' AND ', $conditions);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` l WHERE {$whereSql}";
        $countRes = $this->fetchOne($countSql, $params);
        $total = (int)($countRes['total'] ?? 0);

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch paginated records
        $dataSql = "SELECT l.*, 
                           m.nama as member_nama, m.nomor_anggota, m.kelas
                    FROM `{$this->table}` l
                    LEFT JOIN `members` m ON l.member_id = m.id
                    WHERE {$whereSql}
                    ORDER BY l.id DESC
                    LIMIT {$perPage} OFFSET {$offset}";

        $rows = $this->fetchAll($dataSql, $params);

        foreach ($rows as &$row) {
            $row['title'] = self::formatTitle($row['notification_type']);
        }
        unset($row);

        return [
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => $totalPages,
            'unreadTotal' => $this->getUnreadCountByMember($memberId),
        ];
    }

    /**
     * Get paginated logs with dynamic filters (Admin/Bendahara view)
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 15): array {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(l.recipient_phone LIKE :search OR l.recipient_name LIKE :search OR l.message LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['status'])) {
            $conditions[] = "l.status = :status";
            $params['status'] = trim($filters['status']);
        }

        if (!empty($filters['notification_type'])) {
            $conditions[] = "l.notification_type = :type";
            $params['type'] = trim($filters['notification_type']);
        }

        if (!empty($filters['member_id'])) {
            $conditions[] = "l.member_id = :member_id";
            $params['member_id'] = (int)$filters['member_id'];
        }

        if (!empty($filters['date_start'])) {
            $conditions[] = "DATE(l.created_at) >= :date_start";
            $params['date_start'] = trim($filters['date_start']);
        }

        if (!empty($filters['date_end'])) {
            $conditions[] = "DATE(l.created_at) <= :date_end";
            $params['date_end'] = trim($filters['date_end']);
        }

        $whereSql = implode(' AND ', $conditions);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` l WHERE {$whereSql}";
        $countRes = $this->fetchOne($countSql, $params);
        $total = (int)($countRes['total'] ?? 0);

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch data joined with members and users (sender)
        $dataSql = "SELECT l.*, 
                           m.nama as member_nama, m.nomor_anggota, m.kelas,
                           u.username as sender_username, u.role as sender_role
                    FROM `{$this->table}` l
                    LEFT JOIN `members` m ON l.member_id = m.id
                    LEFT JOIN `users` u ON l.sent_by = u.id
                    WHERE {$whereSql}
                    ORDER BY l.id DESC
                    LIMIT {$perPage} OFFSET {$offset}";

        $rows = $this->fetchAll($dataSql, $params);

        foreach ($rows as &$row) {
            $row['title'] = self::formatTitle($row['notification_type']);
        }
        unset($row);

        return [
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => $totalPages,
        ];
    }

    /**
     * Get aggregate statistics for WhatsApp logs
     */
    public function getStats(): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN notification_type = 'pengingat_pembayaran' THEN 1 ELSE 0 END) as reminder_count,
                    SUM(CASE WHEN notification_type = 'konfirmasi_pembayaran' THEN 1 ELSE 0 END) as confirmation_count,
                    SUM(CASE WHEN notification_type = 'status_verifikasi' THEN 1 ELSE 0 END) as verification_count
                FROM `{$this->table}`";

        $res = $this->fetchOne($sql);
        return [
            'total'              => (int)($res['total'] ?? 0),
            'success_count'      => (int)($res['success_count'] ?? 0),
            'failed_count'       => (int)($res['failed_count'] ?? 0),
            'pending_count'      => (int)($res['pending_count'] ?? 0),
            'reminder_count'     => (int)($res['reminder_count'] ?? 0),
            'confirmation_count' => (int)($res['confirmation_count'] ?? 0),
            'verification_count' => (int)($res['verification_count'] ?? 0),
        ];
    }

    /**
     * Get recent logs (e.g. for dashboard widget)
     */
    public function getRecent(int $limit = 5): array {
        $sql = "SELECT l.*, m.nama as member_nama
                FROM `{$this->table}` l
                LEFT JOIN `members` m ON l.member_id = m.id
                ORDER BY l.id DESC
                LIMIT {$limit}";
        $rows = $this->fetchAll($sql);
        foreach ($rows as &$row) {
            $row['title'] = self::formatTitle($row['notification_type']);
        }
        unset($row);
        return $rows;
    }
}
