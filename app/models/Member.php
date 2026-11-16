<?php
/**
 * Member Model
 * Represents the `members` table
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class Member extends Model {
    protected string $table = 'members';

    /**
     * Find member by nomor anggota
     */
    public function findByNomorAnggota(string $nomor): ?array {
        return $this->findBy('nomor_anggota', $nomor);
    }

    /**
     * Find member by NIS
     */
    public function findByNis(string $nis): ?array {
        return $this->findBy('nis', $nis);
    }

    /**
     * Find member by User ID
     */
    public function findByUserId(int $userId): ?array {
        return $this->findBy('user_id', $userId);
    }

    /**
     * Check if nomor anggota already exists
     */
    public function isNomorAnggotaExists(string $nomor, ?int $excludeMemberId = null): bool {
        $sql = "SELECT id FROM `{$this->table}` WHERE nomor_anggota = :nomor";
        $params = ['nomor' => $nomor];
        if ($excludeMemberId !== null) {
            $sql .= " AND id != :ex_id";
            $params['ex_id'] = $excludeMemberId;
        }
        $res = $this->fetchOne($sql, $params);
        return $res !== null;
    }

    /**
     * Check if NIS already exists
     */
    public function isNisExists(string $nis, ?int $excludeMemberId = null): bool {
        if (trim($nis) === '') return false;
        $sql = "SELECT id FROM `{$this->table}` WHERE nis = :nis";
        $params = ['nis' => $nis];
        if ($excludeMemberId !== null) {
            $sql .= " AND id != :ex_id";
            $params['ex_id'] = $excludeMemberId;
        }
        $res = $this->fetchOne($sql, $params);
        return $res !== null;
    }

    /**
     * Generate unique auto nomor anggota (e.g. ANG-006)
     */
    public function generateNomorAnggota(): string {
        $sql = "SELECT nomor_anggota FROM `{$this->table}` WHERE nomor_anggota LIKE 'ANG-%' ORDER BY id DESC LIMIT 1";
        $latest = $this->fetchOne($sql);
        if ($latest) {
            $lastNumber = (int)substr($latest['nomor_anggota'], 4);
            $nextNumber = str_pad((string)($lastNumber + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }
        return "ANG-$nextNumber";
    }

    /**
     * Get distinct list of classes
     */
    public function getDistinctClasses(): array {
        $sql = "SELECT DISTINCT kelas FROM `{$this->table}` WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC";
        $rows = $this->fetchAll($sql);
        return array_column($rows, 'kelas');
    }

    /**
     * Get paginated members with search and filter
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 10): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        // Search query (nama, nomor_anggota, nis, email)
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(m.nama LIKE :s1 OR m.nomor_anggota LIKE :s2 OR m.nis LIKE :s3 OR m.email LIKE :s4 OR u.username LIKE :s5)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
            $params['s5'] = $search;
        }

        // Filter Kelas
        if (!empty($filters['kelas'])) {
            $where[] = "m.kelas = :kelas";
            $params['kelas'] = $filters['kelas'];
        }

        // Filter Status
        if (!empty($filters['status']) && in_array($filters['status'], ['aktif', 'nonaktif'], true)) {
            $where[] = "m.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);

        // Count total matching records
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` m JOIN `users` u ON m.user_id = u.id WHERE {$whereSql}";
        $countRes = $this->fetchOne($countSql, $params);
        $total = (int)($countRes['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $perPage));

        // Fetch paginated rows
        $sql = "SELECT m.*, u.username, u.status as user_status, u.created_at as user_created_at 
                FROM `{$this->table}` m 
                JOIN `users` u ON m.user_id = u.id 
                WHERE {$whereSql} 
                ORDER BY m.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => $totalPages,
        ];
    }

    /**
     * Get full member detail by Member ID
     */
    public function getDetail(int $memberId): ?array {
        $sql = "SELECT m.*, u.username, u.role, u.status as user_status, u.created_at as user_created_at 
                FROM `{$this->table}` m 
                LEFT JOIN `users` u ON m.user_id = u.id 
                WHERE m.id = :id LIMIT 1";
        return $this->fetchOne($sql, ['id' => $memberId]);
    }

    /**
     * Create member and associated user account in single transaction
     */
    public function createMemberWithUser(array $data): int|false {
        if (!$this->db) return false;

        $userModel = new User();

        try {
            $this->db->beginTransaction();

            // 1. Create user account
            $username = !empty($data['username']) ? trim($data['username']) : strtolower(str_replace([' ', '-', '.'], '_', $data['nama'])) . '_' . rand(100, 999);
            
            // Check username uniqueness
            $existingUser = $userModel->findByUsername($username);
            if ($existingUser) {
                $username .= '_' . rand(10, 99);
            }

            $userEmail = !empty($data['email']) ? trim($data['email']) : $username . '@kas.local';
            $plainPassword = !empty($data['password']) ? $data['password'] : 'member123';

            $userSql = "INSERT INTO `users` (username, email, password, role, status, created_at, updated_at) 
                        VALUES (:username, :email, :password, 'member', :status, NOW(), NOW())";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'username' => $username,
                'email'    => $userEmail,
                'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
                'status'   => ($data['status'] ?? 'aktif') === 'aktif' ? 'active' : 'inactive',
            ]);
            $userId = (int)$this->db->lastInsertId();

            // 2. Create member record
            $nomorAnggota = !empty($data['nomor_anggota']) ? trim($data['nomor_anggota']) : $this->generateNomorAnggota();
            
            $memberSql = "INSERT INTO `{$this->table}` (user_id, nomor_anggota, nis, nama, kelas, jenis_kelamin, email, no_hp, status, created_at, updated_at) 
                          VALUES (:user_id, :nomor_anggota, :nis, :nama, :kelas, :jenis_kelamin, :email, :no_hp, :status, NOW(), NOW())";
            $memberStmt = $this->db->prepare($memberSql);
            $memberStmt->execute([
                'user_id'       => $userId,
                'nomor_anggota' => $nomorAnggota,
                'nis'           => !empty($data['nis']) ? trim($data['nis']) : null,
                'nama'          => trim($data['nama']),
                'kelas'         => trim($data['kelas']),
                'jenis_kelamin' => $data['jenis_kelamin'] ?? 'L',
                'email'         => $userEmail,
                'no_hp'         => !empty($data['no_hp']) ? trim($data['no_hp']) : null,
                'status'        => $data['status'] ?? 'aktif',
            ]);
            $memberId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $memberId;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error creating member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update member and sync with user account
     */
    public function updateMemberWithUser(int $memberId, array $data): bool {
        $member = $this->find($memberId);
        if (!$member || !$this->db) return false;

        try {
            $this->db->beginTransaction();

            // 1. Update Member
            $updateData = [
                'nama'          => trim($data['nama']),
                'kelas'         => trim($data['kelas']),
                'jenis_kelamin' => $data['jenis_kelamin'] ?? $member['jenis_kelamin'],
            ];

            if (isset($data['nomor_anggota'])) {
                $updateData['nomor_anggota'] = trim($data['nomor_anggota']);
            }
            if (isset($data['nis'])) {
                $updateData['nis'] = !empty($data['nis']) ? trim($data['nis']) : null;
            }
            if (isset($data['email'])) {
                $updateData['email'] = trim($data['email']);
            }
            if (isset($data['no_hp'])) {
                $updateData['no_hp'] = !empty($data['no_hp']) ? trim($data['no_hp']) : null;
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $fields = [];
            foreach ($updateData as $col => $val) {
                $fields[] = "`$col` = :$col";
            }
            $updateData['__id'] = $memberId;

            $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . ", `updated_at` = NOW() WHERE id = :__id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($updateData);

            // 2. Sync User email and status if changed
            $userUpdates = [];
            $userParams = ['uid' => (int)$member['user_id']];

            if (!empty($data['username'])) {
                $userUpdates[] = "`username` = :u_username";
                $userParams['u_username'] = trim($data['username']);
            }
            if (!empty($data['email'])) {
                $userUpdates[] = "`email` = :u_email";
                $userParams['u_email'] = trim($data['email']);
            }
            if (!empty($data['status'])) {
                $userUpdates[] = "`status` = :u_status";
                $userParams['u_status'] = $data['status'] === 'aktif' ? 'active' : 'inactive';
            }
            if (!empty($data['password'])) {
                $userUpdates[] = "`password` = :u_pass";
                $userParams['u_pass'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (!empty($userUpdates)) {
                $userSql = "UPDATE `users` SET " . implode(', ', $userUpdates) . ", `updated_at` = NOW() WHERE id = :uid";
                $uStmt = $this->db->prepare($userSql);
                $uStmt->execute($userParams);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error updating member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle Member Active/Inactive Status
     */
    public function toggleStatus(int $memberId): array|false {
        $member = $this->find($memberId);
        if (!$member || !$this->db) return false;

        $newStatus = ($member['status'] === 'aktif') ? 'nonaktif' : 'aktif';
        $newUserStatus = ($newStatus === 'aktif') ? 'active' : 'inactive';

        try {
            $this->db->beginTransaction();

            $this->db->prepare("UPDATE `{$this->table}` SET `status` = :st, `updated_at` = NOW() WHERE id = :id")
                     ->execute(['st' => $newStatus, 'id' => $memberId]);

            $this->db->prepare("UPDATE `users` SET `status` = :st, `updated_at` = NOW() WHERE id = :uid")
                     ->execute(['st' => $newUserStatus, 'uid' => $member['user_id']]);

            $this->db->commit();
            return [
                'id'         => $memberId,
                'old_status' => $member['status'],
                'new_status' => $newStatus,
                'nama'       => $member['nama'],
            ];
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /**
     * Relationship: BelongsTo User
     */
    public function user(int $userId): ?array {
        $userModel = new User();
        return $userModel->find($userId);
    }

    /**
     * Relationship: HasMany Payments
     */
    public function payments(int $memberId): array {
        $sql = "SELECT p.*, u.username as verifier_name 
                FROM `payments` p 
                LEFT JOIN `users` u ON p.verified_by = u.id 
                WHERE p.member_id = :mid 
                ORDER BY p.tanggal_pembayaran DESC, p.id DESC";
        return $this->fetchAll($sql, ['mid' => $memberId]);
    }

    /**
     * Link member with user account
     */
    public function linkUserAccount(int $memberId, int $userId): bool {
        return $this->update($memberId, ['user_id' => $userId]);
    }
}

