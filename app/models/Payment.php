<?php
/**
 * Payment Model
 * Represents the `payments` table (Iuran & Tagihan Kas Anggota)
 */

namespace App\Models;

use App\Core\Model;

class Payment extends Model {
    protected string $table = 'payments';

    public const STATUS_BELUM_BAYAR = 'Belum Bayar';
    public const STATUS_SEBAGIAN    = 'Sebagian';
    public const STATUS_LUNAS       = 'Lunas';

    /**
     * Core Business Calculation: Status based on Payment vs Bill
     * 0 pembayaran -> BELUM_BAYAR
     * 0 < pembayaran < tagihan -> SEBAGIAN
     * pembayaran >= tagihan -> LUNAS
     */
    public static function calculateStatus(float $nominalPaid, float $tagihan): string {
        if ($nominalPaid <= 0.0) {
            return self::STATUS_BELUM_BAYAR;
        }
        if ($nominalPaid < $tagihan) {
            return self::STATUS_SEBAGIAN;
        }
        return self::STATUS_LUNAS;
    }

    /**
     * Calculate Sisa Tunggakan
     * Sisa = Tagihan - Total Pembayaran (min 0)
     */
    public static function calculateSisa(float $nominalPaid, float $tagihan): float {
        return max(0.0, $tagihan - $nominalPaid);
    }

    /**
     * Get distinct periods in database
     */
    public function getDistinctPeriods(): array {
        $sql = "SELECT DISTINCT periode FROM `{$this->table}` WHERE periode IS NOT NULL AND periode != '' ORDER BY periode DESC";
        $rows = $this->fetchAll($sql);
        return array_column($rows, 'periode');
    }

    /**
     * Get Paginated Payments with Member & Verifier Details
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 10): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        // Search (member name, nomor_anggota, nis, catatan)
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(m.nama LIKE :s1 OR m.nomor_anggota LIKE :s2 OR m.nis LIKE :s3 OR p.catatan LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
        }

        // Filter Member ID (for strict member scoping)
        if (!empty($filters['member_id'])) {
            $where[] = "p.member_id = :mid";
            $params['mid'] = (int)$filters['member_id'];
        }

        // Filter Periode
        if (!empty($filters['periode'])) {
            $where[] = "p.periode = :periode";
            $params['periode'] = $filters['periode'];
        }

        // Filter Kelas
        if (!empty($filters['kelas'])) {
            $where[] = "m.kelas = :kelas";
            $params['kelas'] = $filters['kelas'];
        }

        // Filter Status
        if (!empty($filters['status'])) {
            $statusVal = $filters['status'];
            // Normalize status filter
            if (in_array(strtoupper($statusVal), ['BELUM_BAYAR', 'BELUM BAYAR'], true)) {
                $where[] = "p.status = 'Belum Bayar'";
            } elseif (in_array(strtoupper($statusVal), ['SEBAGIAN'], true)) {
                $where[] = "p.status = 'Sebagian'";
            } elseif (in_array(strtoupper($statusVal), ['LUNAS'], true)) {
                $where[] = "p.status = 'Lunas'";
            } else {
                $where[] = "p.status = :status";
                $params['status'] = $statusVal;
            }
        }

        $whereSql = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) as total 
                     FROM `{$this->table}` p 
                     JOIN `members` m ON p.member_id = m.id 
                     WHERE {$whereSql}";
        $countRes = $this->fetchOne($countSql, $params);
        $total = (int)($countRes['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $perPage));

        // Rows
        $sql = "SELECT p.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas, m.email as member_email,
                       u.username as verifier_name,
                       GREATEST(0, (COALESCE(p.tagihan, 50000.00) - p.nominal)) as sisa
                FROM `{$this->table}` p 
                JOIN `members` m ON p.member_id = m.id 
                LEFT JOIN `users` u ON p.verified_by = u.id 
                WHERE {$whereSql} 
                ORDER BY p.tanggal_pembayaran DESC, p.id DESC 
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
     * Get Payment Summary Stats & Tunggakan
     */
    public function getSummaryStats(array $filters = []): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['periode'])) {
            $where[] = "p.periode = :periode";
            $params['periode'] = $filters['periode'];
        }
        if (!empty($filters['kelas'])) {
            $where[] = "m.kelas = :kelas";
            $params['kelas'] = $filters['kelas'];
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT 
                    COALESCE(SUM(COALESCE(p.tagihan, 50000.00)), 0) as total_tagihan,
                    COALESCE(SUM(p.nominal), 0) as total_dibayar,
                    COALESCE(SUM(GREATEST(0, (COALESCE(p.tagihan, 50000.00) - p.nominal))), 0) as total_tunggakan,
                    COUNT(CASE WHEN p.status = 'Lunas' THEN 1 END) as count_lunas,
                    COUNT(CASE WHEN p.status = 'Sebagian' THEN 1 END) as count_sebagian,
                    COUNT(CASE WHEN p.status = 'Belum Bayar' THEN 1 END) as count_belum_bayar,
                    COUNT(*) as count_total
                FROM `{$this->table}` p 
                JOIN `members` m ON p.member_id = m.id 
                WHERE {$whereSql}";

        $res = $this->fetchOne($sql, $params);
        return [
            'total_tagihan'     => (float)($res['total_tagihan'] ?? 0.0),
            'total_dibayar'     => (float)($res['total_dibayar'] ?? 0.0),
            'total_tunggakan'   => (float)($res['total_tunggakan'] ?? 0.0),
            'count_lunas'       => (int)($res['count_lunas'] ?? 0),
            'count_sebagian'    => (int)($res['count_sebagian'] ?? 0),
            'count_belum_bayar' => (int)($res['count_belum_bayar'] ?? 0),
            'count_total'       => (int)($res['count_total'] ?? 0),
        ];
    }

    /**
     * Record a new payment with automatic backend status calculation
     */
    public function recordPayment(array $data): int|false {
        $nominal = (float)($data['nominal'] ?? 0.0);
        $tagihan = (float)($data['tagihan'] ?? 50000.00);
        
        // Backend calculated status
        $status = self::calculateStatus($nominal, $tagihan);

        $insertData = [
            'member_id'          => (int)$data['member_id'],
            'periode'            => trim($data['periode']),
            'tagihan'            => $tagihan,
            'nominal'            => $nominal,
            'tanggal_pembayaran' => $data['tanggal_pembayaran'] ?? date('Y-m-d'),
            'status'             => $status,
            'verified_by'        => $data['verified_by'] ?? null,
            'verified_at'        => $data['verified_at'] ?? (!empty($data['verified_by']) ? date('Y-m-d H:i:s') : null),
            'catatan'            => !empty($data['catatan']) ? trim($data['catatan']) : null,
        ];

        return $this->create($insertData);
    }

    /**
     * Verify payment status
     */
    public function verify(int $paymentId, int $verifierUserId, ?float $adjustedNominal = null, ?string $notes = null): bool {
        $payment = $this->find($paymentId);
        if (!$payment) return false;

        $nominal = ($adjustedNominal !== null) ? $adjustedNominal : (float)$payment['nominal'];
        $tagihan = (float)($payment['tagihan'] ?? 50000.00);

        // Calculate status dynamically
        $status = self::calculateStatus($nominal, $tagihan);

        $data = [
            'nominal'     => $nominal,
            'status'      => $status,
            'verified_by' => $verifierUserId,
            'verified_at' => date('Y-m-d H:i:s'),
        ];
        if ($notes !== null) {
            $data['catatan'] = $notes;
        }

        return $this->update($paymentId, $data);
    }

    /**
     * Get payment full detail by ID
     */
    public function getDetail(int $id): ?array {
        $sql = "SELECT p.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas, m.email as member_email, m.no_hp as member_phone,
                       u.username as verifier_name,
                       GREATEST(0, (COALESCE(p.tagihan, 50000.00) - p.nominal)) as sisa
                FROM `{$this->table}` p 
                JOIN `members` m ON p.member_id = m.id 
                LEFT JOIN `users` u ON p.verified_by = u.id 
                WHERE p.id = :id LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Alias for getDetail
     */
    public function findWithMember(int $id): ?array {
        return $this->getDetail($id);
    }

    /**
     * Get payment history for a specific member
     */
    public function getMemberHistory(int $memberId): array {
        $sql = "SELECT p.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas,
                       u.username as verifier_name,
                       GREATEST(0, (COALESCE(p.tagihan, 50000.00) - p.nominal)) as sisa
                FROM `{$this->table}` p 
                JOIN `members` m ON p.member_id = m.id 
                LEFT JOIN `users` u ON p.verified_by = u.id 
                WHERE p.member_id = :mid 
                ORDER BY p.tanggal_pembayaran DESC, p.id DESC";
        return $this->fetchAll($sql, ['mid' => $memberId]);
    }

    /**
     * Get all payments with member info (backward compatibility)
     */
    public function getAllWithMember(array $filters = []): array {
        $result = $this->getPaginated($filters, 1, $filters['limit'] ?? 100);
        return $result['data'];
    }

    /**
     * Get payment status distribution for doughnut chart
     */
    public function getStatusDistribution(): array {
        $sql = "SELECT 
                    COUNT(CASE WHEN status = 'Lunas' THEN 1 END) as lunas,
                    COUNT(CASE WHEN status = 'Sebagian' THEN 1 END) as sebagian,
                    COUNT(CASE WHEN status = 'Belum Bayar' THEN 1 END) as belum_bayar,
                    COUNT(*) as total
                FROM `{$this->table}`";
        $res = $this->fetchOne($sql);

        $lunas = (int)($res['lunas'] ?? 0);
        $sebagian = (int)($res['sebagian'] ?? 0);
        $belumBayar = (int)($res['belum_bayar'] ?? 0);
        $total = (int)($res['total'] ?? 0);

        return [
            'labels'     => ['Lunas', 'Sebagian', 'Belum Bayar'],
            'data'       => [$lunas, $sebagian, $belumBayar],
            'lunas'      => $lunas,
            'sebagian'   => $sebagian,
            'belumBayar' => $belumBayar,
            'total'      => $total,
            'hasData'    => $total > 0,
        ];
    }

    /**
     * Get count of unique members who have unpaid/partial bills
     */
    public function getUnpaidMembersCount(): int {
        $sql = "SELECT COUNT(DISTINCT member_id) as total_unpaid FROM `{$this->table}` WHERE status != 'Lunas'";
        $res = $this->fetchOne($sql);
        return (int)($res['total_unpaid'] ?? 0);
    }

    /**
     * Get payments report with filtering by date range, class, status, period, and search
     */
    public function getPaymentsReport(array $filters = []): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['start_date'])) {
            $where[] = "p.tanggal_pembayaran >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "p.tanggal_pembayaran <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['kelas'])) {
            $where[] = "m.kelas = :kelas";
            $params['kelas'] = $filters['kelas'];
        }

        if (!empty($filters['periode'])) {
            $where[] = "p.periode = :periode";
            $params['periode'] = $filters['periode'];
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(m.nama LIKE :s1 OR m.nomor_anggota LIKE :s2 OR m.nis LIKE :s3 OR p.periode LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT p.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas, m.email as member_email,
                       u.username as verifier_name,
                       GREATEST(0, (COALESCE(p.tagihan, 50000.00) - p.nominal)) as sisa
                FROM `{$this->table}` p 
                JOIN `members` m ON p.member_id = m.id 
                LEFT JOIN `users` u ON p.verified_by = u.id 
                WHERE {$whereSql} 
                ORDER BY p.tanggal_pembayaran ASC, p.id ASC";

        $data = $this->fetchAll($sql, $params);

        $totalTagihan = 0.0;
        $totalPaid = 0.0;
        $totalSisa = 0.0;

        foreach ($data as &$row) {
            $tagihan = (float)($row['tagihan'] ?? 50000.00);
            $nominal = (float)$row['nominal'];
            $sisa = (float)($row['sisa'] ?? max(0.0, $tagihan - $nominal));

            $row['tagihan'] = $tagihan;
            $row['nominal'] = $nominal;
            $row['sisa'] = $sisa;

            $totalTagihan += $tagihan;
            $totalPaid += $nominal;
            $totalSisa += $sisa;
        }
        unset($row);

        return [
            'data'          => $data,
            'total_tagihan' => $totalTagihan,
            'total_paid'    => $totalPaid,
            'total_sisa'    => $totalSisa,
            'count'         => count($data),
        ];
    }

    /**
     * Get arrears report for members with status Belum Bayar or Sebagian
     */
    public function getArrearsReport(array $filters = []): array {
        $where = ["p.status IN ('Belum Bayar', 'Sebagian')"];
        $params = [];

        if (!empty($filters['start_date'])) {
            $where[] = "p.tanggal_pembayaran >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "p.tanggal_pembayaran <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['kelas'])) {
            $where[] = "m.kelas = :kelas";
            $params['kelas'] = $filters['kelas'];
        }

        if (!empty($filters['periode'])) {
            $where[] = "p.periode = :periode";
            $params['periode'] = $filters['periode'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(m.nama LIKE :s1 OR m.nomor_anggota LIKE :s2 OR m.nis LIKE :s3 OR p.periode LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT p.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas, m.email as member_email,
                       u.username as verifier_name,
                       GREATEST(0, (COALESCE(p.tagihan, 50000.00) - p.nominal)) as sisa
                FROM `{$this->table}` p 
                JOIN `members` m ON p.member_id = m.id 
                LEFT JOIN `users` u ON p.verified_by = u.id 
                WHERE {$whereSql} 
                HAVING sisa > 0
                ORDER BY sisa DESC, p.tanggal_pembayaran ASC";

        $data = $this->fetchAll($sql, $params);

        $totalTagihan = 0.0;
        $totalPaid = 0.0;
        $totalTunggakan = 0.0;

        foreach ($data as &$row) {
            $tagihan = (float)($row['tagihan'] ?? 50000.00);
            $nominal = (float)$row['nominal'];
            $sisa = (float)($row['sisa'] ?? max(0.0, $tagihan - $nominal));

            $row['tagihan'] = $tagihan;
            $row['nominal'] = $nominal;
            $row['sisa'] = $sisa;

            $totalTagihan += $tagihan;
            $totalPaid += $nominal;
            $totalTunggakan += $sisa;
        }
        unset($row);

        return [
            'data'            => $data,
            'total_tagihan'   => $totalTagihan,
            'total_paid'      => $totalPaid,
            'total_tunggakan' => $totalTunggakan,
            'count'           => count($data),
        ];
    }

    /**
     * Relationship: BelongsTo Member
     */
    public function member(int $memberId): ?array {
        $memberModel = new Member();
        return $memberModel->find($memberId);
    }

    /**
     * Relationship: BelongsTo User (verifier)
     */
    /**
     * Relationship: HasMany PaymentTransactions
     */
    public function paymentTransactions(int $paymentId): array {
        $trxModel = new PaymentTransaction();
        return $trxModel->getByPaymentId($paymentId);
    }

    /**
     * Get pending submission for payment if any
     */
    public function getPendingTransaction(int $paymentId): ?array {
        $sql = "SELECT * FROM `payment_transactions` 
                WHERE payment_id = :pid AND status = 'menunggu_verifikasi' 
                ORDER BY id DESC LIMIT 1";
        return $this->fetchOne($sql, ['pid' => $paymentId]);
    }
}

