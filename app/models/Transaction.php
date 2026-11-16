<?php
/**
 * Transaction Model
 * Represents the `transactions` table (Buku Kas Umum)
 */

namespace App\Models;

use App\Core\Model;

class Transaction extends Model {
    protected string $table = 'transactions';

    public const TYPE_PEMASUKAN   = 'pemasukan';
    public const TYPE_PENGELUARAN = 'pengeluaran';

    /**
     * Generate unique transaction code (e.g. TRX-IN-20260905-0001 or TRX-OUT-20260905-0001)
     */
    public function generateKode(string $tipe, ?string $date = null): string {
        $prefix = ($tipe === self::TYPE_PEMASUKAN) ? 'TRX-IN' : 'TRX-OUT';
        $dateStr = $date ? date('Ymd', strtotime($date)) : date('Ymd');
        $pattern = "$prefix-$dateStr-%";

        $sql = "SELECT kode_transaksi FROM `{$this->table}` WHERE kode_transaksi LIKE :pat ORDER BY id DESC LIMIT 1";
        $latest = $this->fetchOne($sql, ['pat' => $pattern]);

        if ($latest) {
            $lastNumber = (int)substr($latest['kode_transaksi'], -4);
            $nextNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "$prefix-$dateStr-$nextNumber";
    }

    /**
     * Calculate Total Pemasukan dynamically from database
     */
    public function getTotalIncome(): float {
        $sql = "SELECT COALESCE(SUM(nominal), 0) as total FROM `{$this->table}` WHERE tipe = 'pemasukan'";
        $res = $this->fetchOne($sql);
        return (float)($res['total'] ?? 0.0);
    }

    /**
     * Get detailed breakdown of Total Pemasukan by source: Iuran, Donasi, and Sumbangan
     */
    public function getIncomeBreakdown(): array {
        $sql = "
            SELECT 
                CASE 
                    WHEN LOWER(c.nama) LIKE '%iuran%' THEN 'iuran'
                    WHEN LOWER(c.nama) LIKE '%donasi%' AND LOWER(c.nama) NOT LIKE '%sumbang%' THEN 'donasi'
                    WHEN LOWER(c.nama) LIKE '%sumbang%' AND LOWER(c.nama) NOT LIKE '%donasi%' THEN 'sumbangan'
                    WHEN (LOWER(c.nama) LIKE '%donasi%' AND LOWER(c.nama) LIKE '%sumbang%') THEN
                        CASE 
                            WHEN LOWER(t.keterangan) LIKE '%sumbang%' THEN 'sumbangan'
                            ELSE 'donasi'
                        END
                    WHEN LOWER(t.keterangan) LIKE '%iuran%' THEN 'iuran'
                    WHEN LOWER(t.keterangan) LIKE '%sumbang%' THEN 'sumbangan'
                    WHEN LOWER(t.keterangan) LIKE '%donasi%' THEN 'donasi'
                    ELSE 'lainnya'
                END AS source_type,
                COALESCE(SUM(t.nominal), 0) as total_nominal,
                COUNT(t.id) as total_count
            FROM `{$this->table}` t
            LEFT JOIN `categories` c ON t.category_id = c.id
            WHERE t.tipe = 'pemasukan'
            GROUP BY source_type
        ";

        $rows = $this->fetchAll($sql);

        $breakdown = [
            'iuran'     => 0.0,
            'donasi'    => 0.0,
            'sumbangan' => 0.0,
            'lainnya'   => 0.0,
            'counts'    => [
                'iuran'     => 0,
                'donasi'    => 0,
                'sumbangan' => 0,
                'lainnya'   => 0,
            ],
            'total'     => 0.0,
        ];

        foreach ($rows as $r) {
            $type = $r['source_type'];
            $nom = (float)$r['total_nominal'];
            $cnt = (int)$r['total_count'];

            if (isset($breakdown[$type])) {
                $breakdown[$type] = $nom;
                $breakdown['counts'][$type] = $cnt;
            }
            $breakdown['total'] += $nom;
        }

        return $breakdown;
    }

    /**
     * Calculate Total Pengeluaran dynamically from database
     */
    public function getTotalExpense(): float {
        $sql = "SELECT COALESCE(SUM(nominal), 0) as total FROM `{$this->table}` WHERE tipe = 'pengeluaran'";
        $res = $this->fetchOne($sql);
        return (float)($res['total'] ?? 0.0);
    }

    /**
     * Core Business Logic: Saldo Kas = Total Pemasukan - Total Pengeluaran
     */
    public function getCurrentBalance(): float {
        return $this->getTotalIncome() - $this->getTotalExpense();
    }

    /**
     * Get paginated transactions with search, filter, and relations
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 10): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        // Search (kode_transaksi, keterangan, category name, creator username)
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(t.kode_transaksi LIKE :s1 OR t.keterangan LIKE :s2 OR c.nama LIKE :s3 OR u.username LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
        }

        // Filter Tipe
        if (!empty($filters['tipe']) && in_array($filters['tipe'], [self::TYPE_PEMASUKAN, self::TYPE_PENGELUARAN], true)) {
            $where[] = "t.tipe = :tipe";
            $params['tipe'] = $filters['tipe'];
        }

        // Filter Category
        if (!empty($filters['category_id'])) {
            $where[] = "t.category_id = :cat_id";
            $params['cat_id'] = (int)$filters['category_id'];
        }

        // Filter Date Range
        if (!empty($filters['start_date'])) {
            $where[] = "t.tanggal >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "t.tanggal <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $whereSql = implode(' AND ', $where);

        // Total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM `{$this->table}` t 
                     JOIN `categories` c ON t.category_id = c.id 
                     JOIN `users` u ON t.created_by = u.id 
                     WHERE {$whereSql}";
        $countRes = $this->fetchOne($countSql, $params);
        $total = (int)($countRes['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $perPage));

        // Paginated rows
        $sql = "SELECT t.*, c.nama as category_name, c.tipe as category_type, u.username as creator_name 
                FROM `{$this->table}` t 
                JOIN `categories` c ON t.category_id = c.id 
                JOIN `users` u ON t.created_by = u.id 
                WHERE {$whereSql} 
                ORDER BY t.tanggal DESC, t.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'       => $data,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Get transactions with category and user creator details (feed / list)
     */
    public function getAllWithRelations(array $filters = []): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['tipe'])) {
            $where[] = "t.tipe = :tipe";
            $params['tipe'] = $filters['tipe'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "t.category_id = :cat_id";
            $params['cat_id'] = (int)$filters['category_id'];
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT t.*, c.nama as category_name, c.tipe as category_type, u.username as creator_name 
                FROM `{$this->table}` t 
                JOIN `categories` c ON t.category_id = c.id 
                JOIN `users` u ON t.created_by = u.id 
                WHERE {$whereSql} 
                ORDER BY t.tanggal DESC, t.id DESC";

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $sql .= " LIMIT {$limit}";
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get transaction detail with category and creator info
     */
    public function getDetail(int $id): ?array {
        $sql = "SELECT t.*, c.nama as category_name, c.tipe as category_type, u.username as creator_name 
                FROM `{$this->table}` t 
                JOIN `categories` c ON t.category_id = c.id 
                JOIN `users` u ON t.created_by = u.id 
                WHERE t.id = :id LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Get monthly income vs expense comparison for dashboard charts
     */
    public function getMonthlyComparison(int $months = 6): array {
        $sql = "SELECT 
                    DATE_FORMAT(tanggal, '%Y-%m') as month_key,
                    DATE_FORMAT(tanggal, '%b %Y') as month_label,
                    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN nominal ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN nominal ELSE 0 END), 0) as expense
                FROM `{$this->table}`
                GROUP BY DATE_FORMAT(tanggal, '%Y-%m'), DATE_FORMAT(tanggal, '%b %Y')
                ORDER BY month_key ASC
                LIMIT {$months}";

        $rows = $this->fetchAll($sql);

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        foreach ($rows as $r) {
            $labels[] = $r['month_label'];
            $incomeData[] = (float)$r['income'];
            $expenseData[] = (float)$r['expense'];
        }

        return [
            'labels'  => $labels,
            'income'  => $incomeData,
            'expense' => $expenseData,
            'hasData' => !empty($rows),
        ];
    }

    /**
     * Get cumulative balance trend over chronological dates
     */
    public function getBalanceTrend(int $limit = 15): array {
        $sql = "SELECT 
                    tanggal,
                    SUM(CASE WHEN tipe = 'pemasukan' THEN nominal ELSE -nominal END) as net_change
                FROM `{$this->table}`
                GROUP BY tanggal
                ORDER BY tanggal ASC
                LIMIT {$limit}";

        $rows = $this->fetchAll($sql);

        $labels = [];
        $data = [];
        $runningBalance = 0.0;

        foreach ($rows as $r) {
            $labels[] = date('d M', strtotime($r['tanggal']));
            $runningBalance += (float)$r['net_change'];
            $data[] = (float)$runningBalance;
        }

        return [
            'labels'  => $labels,
            'data'    => $data,
            'hasData' => !empty($rows),
        ];
    }

    /**
     * Get transactions report with comprehensive totals and running net balance
     */
    public function getReportTransactions(array $filters = []): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['tipe'])) {
            $where[] = "t.tipe = :tipe";
            $params['tipe'] = $filters['tipe'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "t.category_id = :cat_id";
            $params['cat_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "t.tanggal >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "t.tanggal <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(t.kode_transaksi LIKE :s1 OR t.keterangan LIKE :s2 OR c.nama LIKE :s3)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT t.*, c.nama as category_name, c.tipe as category_type, u.username as creator_name 
                FROM `{$this->table}` t 
                JOIN `categories` c ON t.category_id = c.id 
                JOIN `users` u ON t.created_by = u.id 
                WHERE {$whereSql} 
                ORDER BY t.tanggal ASC, t.id ASC";

        $data = $this->fetchAll($sql, $params);

        $totalIncome = 0.0;
        $totalExpense = 0.0;

        foreach ($data as &$row) {
            $nominal = (float)$row['nominal'];
            $row['nominal'] = $nominal;
            if ($row['tipe'] === self::TYPE_PEMASUKAN) {
                $totalIncome += $nominal;
            } else {
                $totalExpense += $nominal;
            }
        }
        unset($row);

        $netBalance = $totalIncome - $totalExpense;

        return [
            'data'          => $data,
            'total_income'  => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance'   => $netBalance,
            'count'         => count($data),
        ];
    }

    /**
     * Relationship: BelongsTo Category
     */
    public function category(int $categoryId): ?array {
        $catModel = new Category();
        return $catModel->find($categoryId);
    }

    /**
     * Relationship: BelongsTo User (creator)
     */
    public function creator(int $userId): ?array {
        $userModel = new User();
        return $userModel->find($userId);
    }
}
