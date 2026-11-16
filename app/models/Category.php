<?php
/**
 * Category Model
 * Represents the `categories` table
 */

namespace App\Models;

use App\Core\Model;

class Category extends Model {
    protected string $table = 'categories';

    public const TYPE_PEMASUKAN   = 'pemasukan';
    public const TYPE_PENGELUARAN = 'pengeluaran';

    public const STATUS_AKTIF    = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    /**
     * Get categories by type ('pemasukan' or 'pengeluaran')
     */
    public function getByType(string $type, bool $activeOnly = true): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE tipe = :tipe";
        if ($activeOnly) {
            $sql .= " AND status = 'aktif'";
        }
        $sql .= " ORDER BY nama ASC";
        return $this->fetchAll($sql, ['tipe' => $type]);
    }

    /**
     * Get all active categories
     */
    public function getActive(): array {
        return $this->fetchAll("SELECT * FROM `{$this->table}` WHERE status = 'aktif' ORDER BY tipe ASC, nama ASC");
    }

    /**
     * Check if category name exists within the same type
     */
    public function isNameExists(string $name, string $type, ?int $excludeId = null): bool {
        $sql = "SELECT id FROM `{$this->table}` WHERE nama = :nama AND tipe = :tipe";
        $params = ['nama' => $name, 'tipe' => $type];
        if ($excludeId !== null) {
            $sql .= " AND id != :ex_id";
            $params['ex_id'] = $excludeId;
        }
        $res = $this->fetchOne($sql, $params);
        return $res !== null;
    }

    /**
     * Get transaction count linked to a category
     */
    public function getTransactionCount(int $categoryId): int {
        $sql = "SELECT COUNT(*) as total FROM `transactions` WHERE category_id = :cid";
        $res = $this->fetchOne($sql, ['cid' => $categoryId]);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Get all categories with transaction count and filter
     */
    public function getAllWithTransactionCount(array $filters = []): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(c.nama LIKE :search OR c.deskripsi LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['tipe']) && in_array($filters['tipe'], [self::TYPE_PEMASUKAN, self::TYPE_PENGELUARAN], true)) {
            $where[] = "c.tipe = :tipe";
            $params['tipe'] = $filters['tipe'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], [self::STATUS_AKTIF, self::STATUS_NONAKTIF], true)) {
            $where[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM `transactions` t WHERE t.category_id = c.id) as transaction_count,
                       (SELECT COALESCE(SUM(t.nominal), 0) FROM `transactions` t WHERE t.category_id = c.id) as total_nominal
                FROM `{$this->table}` c
                WHERE {$whereSql}
                ORDER BY c.tipe ASC, c.nama ASC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Toggle Category Active / Nonaktif Status
     */
    public function toggleStatus(int $categoryId): array|false {
        $category = $this->find($categoryId);
        if (!$category) return false;

        $newStatus = ($category['status'] === self::STATUS_AKTIF) ? self::STATUS_NONAKTIF : self::STATUS_AKTIF;

        $updated = $this->update($categoryId, ['status' => $newStatus]);
        if ($updated) {
            return [
                'id'         => $categoryId,
                'nama'       => $category['nama'],
                'tipe'       => $category['tipe'],
                'old_status' => $category['status'],
                'new_status' => $newStatus,
            ];
        }
        return false;
    }

    /**
     * Safe Delete: Check if category is used by transactions
     */
    public function safeDelete(int $categoryId): array {
        $category = $this->find($categoryId);
        if (!$category) {
            return ['success' => false, 'message' => 'Kategori tidak ditemukan.'];
        }

        $txCount = $this->getTransactionCount($categoryId);
        if ($txCount > 0) {
            return [
                'success' => false,
                'message' => "Kategori [{$category['nama']}] tidak dapat dihapus karena telah digunakan pada {$txCount} transaksi kas. Silakan nonaktifkan status kategori sebagai gantinya.",
                'is_in_use' => true,
            ];
        }

        $deleted = $this->delete($categoryId);
        return [
            'success' => $deleted,
            'message' => $deleted ? "Kategori [{$category['nama']}] berhasil dihapus." : "Gagal menghapus kategori.",
            'category' => $category,
        ];
    }

    /**
     * Relationship: HasMany Transactions
     */
    public function transactions(int $categoryId): array {
        $sql = "SELECT t.*, u.username as creator_name 
                FROM `transactions` t 
                JOIN `users` u ON t.created_by = u.id 
                WHERE t.category_id = :cid 
                ORDER BY t.tanggal DESC, t.id DESC";
        return $this->fetchAll($sql, ['cid' => $categoryId]);
    }
}
