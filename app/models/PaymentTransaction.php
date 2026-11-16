<?php
/**
 * PaymentTransaction Model
 * Represents individual payment submissions and transactions for member dues
 * Handles verification, rejection, and automatic Buku Kas general ledger integration
 */

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PaymentTransaction extends Model {
    protected string $table = 'payment_transactions';

    // Payment Methods
    public const METHOD_TRANSFER_BANK = 'transfer_bank';
    public const METHOD_TUNAI         = 'tunai';
    public const METHOD_QRIS          = 'qris';
    public const METHOD_EWALLET        = 'ewallet';
    public const METHOD_LAINNYA       = 'lainnya';

    // Payment Statuses
    public const STATUS_MENUNGGU_PEMBAYARAN = 'menunggu_pembayaran';
    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';
    public const STATUS_BERHASIL            = 'berhasil';
    public const STATUS_DITOLAK             = 'ditolak';
    public const STATUS_GAGAL               = 'gagal';
    public const STATUS_KEDALUWARSA         = 'kedaluwarsa';

    /**
     * Get label for method
     */
    public static function getMethodLabel(string $method): string {
        return match ($method) {
            self::METHOD_TRANSFER_BANK => 'Transfer Bank',
            self::METHOD_TUNAI         => 'Pembayaran Tunai',
            self::METHOD_QRIS          => 'QRIS',
            self::METHOD_EWALLET        => 'E-Wallet',
            self::METHOD_LAINNYA       => 'Metode Lainnya',
            default                    => ucfirst($method),
        };
    }

    /**
     * Get label for status
     */
    public static function getStatusLabel(string $status): string {
        return match ($status) {
            self::STATUS_MENUNGGU_PEMBAYARAN => 'Menunggu Pembayaran',
            self::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::STATUS_BERHASIL            => 'Berhasil / Terverifikasi',
            self::STATUS_DITOLAK             => 'Ditolak',
            self::STATUS_GAGAL               => 'Gagal',
            self::STATUS_KEDALUWARSA         => 'Kedaluwarsa',
            default                          => ucfirst($status),
        };
    }

    /**
     * Get badge HTML class for status
     */
    public static function getStatusBadgeClass(string $status): string {
        return match ($status) {
            self::STATUS_MENUNGGU_PEMBAYARAN => 'bg-warning-subtle text-warning border border-warning-subtle',
            self::STATUS_MENUNGGU_VERIFIKASI => 'bg-info-subtle text-info border border-info-subtle',
            self::STATUS_BERHASIL            => 'bg-success-subtle text-success border border-success-subtle',
            self::STATUS_DITOLAK             => 'bg-danger-subtle text-danger border border-danger-subtle',
            self::STATUS_GAGAL               => 'bg-danger text-white',
            self::STATUS_KEDALUWARSA         => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            default                          => 'bg-light text-dark border',
        };
    }

    /**
     * Generate unique payment transaction reference (e.g. PAY-20261013-0001)
     */
    public function generateKode(?string $date = null): string {
        $dateStr = $date ? date('Ymd', strtotime($date)) : date('Ymd');
        $prefix = "PAY-$dateStr";
        $pattern = "$prefix-%";

        $sql = "SELECT kode_transaksi FROM `{$this->table}` WHERE kode_transaksi LIKE :pat ORDER BY id DESC LIMIT 1";
        $latest = $this->fetchOne($sql, ['pat' => $pattern]);

        if ($latest && !empty($latest['kode_transaksi'])) {
            $lastNumber = (int)substr($latest['kode_transaksi'], -4);
            $nextNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "$prefix-$nextNumber";
    }

    /**
     * Get paginated transactions with search, filter, and relations
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 10): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(pt.kode_transaksi LIKE :s1 OR m.nama LIKE :s2 OR m.nomor_anggota LIKE :s3 OR pt.channel LIKE :s4 OR p.periode LIKE :s5)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
            $params['s5'] = $search;
        }

        if (!empty($filters['member_id'])) {
            $where[] = "pt.member_id = :mid";
            $params['mid'] = (int)$filters['member_id'];
        }

        if (!empty($filters['payment_id'])) {
            $where[] = "pt.payment_id = :pid";
            $params['pid'] = (int)$filters['payment_id'];
        }

        if (!empty($filters['metode'])) {
            $where[] = "pt.metode = :metode";
            $params['metode'] = $filters['metode'];
        }

        if (!empty($filters['status'])) {
            $where[] = "pt.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) as total 
                     FROM `{$this->table}` pt 
                     JOIN `members` m ON pt.member_id = m.id 
                     JOIN `payments` p ON pt.payment_id = p.id 
                     WHERE {$whereSql}";
        $countRes = $this->fetchOne($countSql, $params);
        $total = (int)($countRes['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $sql = "SELECT pt.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas, m.no_hp as member_phone,
                       p.periode as payment_periode, p.tagihan as bill_tagihan, p.status as bill_status,
                       u.username as verifier_name,
                       t.kode_transaksi as trx_kas_kode
                FROM `{$this->table}` pt 
                JOIN `members` m ON pt.member_id = m.id 
                JOIN `payments` p ON pt.payment_id = p.id 
                LEFT JOIN `users` u ON pt.verified_by = u.id 
                LEFT JOIN `transactions` t ON pt.transaction_id = t.id 
                WHERE {$whereSql} 
                ORDER BY pt.id DESC 
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
     * Get transaction detail with all relations
     */
    public function getDetailWithRelations(int $id): ?array {
        $sql = "SELECT pt.*, 
                       m.nomor_anggota, m.nis, m.nama as member_name, m.kelas, m.email as member_email, m.no_hp as member_phone,
                       p.periode as payment_periode, p.tagihan as bill_tagihan, p.nominal as bill_paid, p.status as bill_status,
                       u.username as verifier_name,
                       t.kode_transaksi as trx_kas_kode, t.tanggal as trx_kas_tanggal
                FROM `{$this->table}` pt 
                JOIN `members` m ON pt.member_id = m.id 
                JOIN `payments` p ON pt.payment_id = p.id 
                LEFT JOIN `users` u ON pt.verified_by = u.id 
                LEFT JOIN `transactions` t ON pt.transaction_id = t.id 
                WHERE pt.id = :id LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Get count of transactions awaiting verification
     */
    public function getPendingCount(): int {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE status = :st";
        $res = $this->fetchOne($sql, ['st' => self::STATUS_MENUNGGU_VERIFIKASI]);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Get payment transaction history for a specific member
     */
    public function getMemberHistory(int $memberId): array {
        $sql = "SELECT pt.*, 
                       p.periode as payment_periode, p.tagihan as bill_tagihan,
                       u.username as verifier_name
                FROM `{$this->table}` pt 
                JOIN `payments` p ON pt.payment_id = p.id 
                LEFT JOIN `users` u ON pt.verified_by = u.id 
                WHERE pt.member_id = :mid 
                ORDER BY pt.id DESC";
        return $this->fetchAll($sql, ['mid' => $memberId]);
    }

    /**
     * Get all transactions for a specific payment bill ID
     */
    public function getByPaymentId(int $paymentId): array {
        $sql = "SELECT pt.*, u.username as verifier_name 
                FROM `{$this->table}` pt 
                LEFT JOIN `users` u ON pt.verified_by = u.id 
                WHERE pt.payment_id = :pid 
                ORDER BY pt.id ASC";
        return $this->fetchAll($sql, ['pid' => $paymentId]);
    }

    /**
     * Sum of verified/successful payments for a specific bill
     */
    public function getVerifiedTotalForPayment(int $paymentId): float {
        $sql = "SELECT COALESCE(SUM(nominal), 0) as total 
                FROM `{$this->table}` 
                WHERE payment_id = :pid AND status = :st";
        $res = $this->fetchOne($sql, [
            'pid' => $paymentId,
            'st'  => self::STATUS_BERHASIL
        ]);
        return (float)($res['total'] ?? 0.0);
    }

    /**
     * Create member payment submission
     */
    public function createSubmission(array $data): int|false {
        $kode = !empty($data['kode_transaksi']) ? $data['kode_transaksi'] : $this->generateKode();

        $insertData = [
            'payment_id'        => (int)$data['payment_id'],
            'member_id'         => (int)$data['member_id'],
            'kode_transaksi'    => $kode,
            'metode'            => $data['metode'] ?? self::METHOD_TRANSFER_BANK,
            'channel'           => !empty($data['channel']) ? trim((string)$data['channel']) : null,
            'nominal'           => (float)$data['nominal'],
            'status'            => $data['status'] ?? self::STATUS_MENUNGGU_VERIFIKASI,
            'bukti_transfer'    => !empty($data['bukti_transfer']) ? trim((string)$data['bukti_transfer']) : null,
            'catatan'           => !empty($data['catatan']) ? trim((string)$data['catatan']) : null,
            'gateway_reference' => !empty($data['gateway_reference']) ? trim((string)$data['gateway_reference']) : null,
            'gateway_payload'   => !empty($data['gateway_payload']) ? $data['gateway_payload'] : null,
        ];

        return $this->create($insertData);
    }

    /**
     * Atomic Verification Process:
     * 1. Validates transaction status
     * 2. Inserts income record into transactions (Buku Kas Umum)
     * 3. Updates payment_transactions status to 'berhasil'
     * 4. Updates parent payment record (payments) total nominal and recalculated status
     */
    public function verifyPayment(int $trxId, int $verifierUserId, ?string $notes = null): array {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            // 1. Fetch current transaction record with FOR UPDATE lock
            $sql = "SELECT pt.*, m.nama as member_name, p.periode, p.tagihan, p.id as p_id 
                    FROM `{$this->table}` pt 
                    JOIN `members` m ON pt.member_id = m.id 
                    JOIN `payments` p ON pt.payment_id = p.id 
                    WHERE pt.id = :id FOR UPDATE";
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $trxId]);
            $trx = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$trx) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Data transaksi pembayaran tidak ditemukan.'];
            }

            // Prevent duplicate verification
            if ($trx['status'] === self::STATUS_BERHASIL && !empty($trx['transaction_id'])) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Transaksi pembayaran ini sudah berstatus Berhasil dan telah tercatat di Buku Kas.'];
            }

            $paymentId = (int)$trx['payment_id'];
            $nominal = (float)$trx['nominal'];
            $paymentDate = date('Y-m-d');

            // 2. Resolve Income Category (Iuran Wajib Mingguan or fallback to any active pemasukan)
            $catStmt = $db->query("SELECT id FROM categories WHERE tipe = 'pemasukan' AND nama LIKE '%Iuran%' AND status = 'aktif' LIMIT 1");
            $catId = $catStmt->fetchColumn();
            if (!$catId) {
                $catStmt2 = $db->query("SELECT id FROM categories WHERE tipe = 'pemasukan' AND status = 'aktif' LIMIT 1");
                $catId = $catStmt2->fetchColumn();
            }
            if (!$catId) {
                $catId = 1; // Fallback to category 1
            }

            // Generate Kode Transaksi Kas
            $trxModel = new Transaction();
            $kodeKas = $trxModel->generateKode(Transaction::TYPE_PEMASUKAN, $paymentDate);

            $methodLabel = self::getMethodLabel($trx['metode']);
            $channelText = !empty($trx['channel']) ? " ({$trx['channel']})" : "";
            $keteranganKas = "Penerimaan Iuran Kas Anggota [{$trx['member_name']}] - Periode {$trx['periode']} via {$methodLabel}{$channelText} [Ref: {$trx['kode_transaksi']}]";

            // Insert into `transactions` table (Buku Kas Umum)
            $insTrxSql = "INSERT INTO `transactions` (kode_transaksi, tanggal, tipe, category_id, nominal, keterangan, created_by, created_at) 
                          VALUES (:kode, :tgl, 'pemasukan', :cat, :nom, :ket, :creator, CURRENT_TIMESTAMP)";
            $insStmt = $db->prepare($insTrxSql);
            $insStmt->execute([
                'kode'    => $kodeKas,
                'tgl'     => $paymentDate,
                'cat'     => $catId,
                'nom'     => $nominal,
                'ket'     => $keteranganKas,
                'creator' => $verifierUserId,
            ]);
            $newCashTrxId = (int)$db->lastInsertId();

            // 3. Update payment_transactions status
            $updTrxSql = "UPDATE `{$this->table}` 
                          SET status = :st, 
                              transaction_id = :trx_id, 
                              verified_by = :v_by, 
                              verified_at = CURRENT_TIMESTAMP, 
                              catatan = COALESCE(:notes, catatan) 
                          WHERE id = :id";
            $updStmt = $db->prepare($updTrxSql);
            $updStmt->execute([
                'st'     => self::STATUS_BERHASIL,
                'trx_id' => $newCashTrxId,
                'v_by'   => $verifierUserId,
                'notes'  => $notes,
                'id'     => $trxId,
            ]);

            // 4. Recalculate and update parent `payments` record
            $sumSql = "SELECT COALESCE(SUM(nominal), 0) as total_paid 
                       FROM `{$this->table}` 
                       WHERE payment_id = :pid AND status = :st";
            $sumStmt = $db->prepare($sumSql);
            $sumStmt->execute([
                'pid' => $paymentId,
                'st'  => self::STATUS_BERHASIL,
            ]);
            $totalPaid = (float)$sumStmt->fetchColumn();

            $tagihan = (float)$trx['tagihan'];
            $newPaymentStatus = Payment::calculateStatus($totalPaid, $tagihan);

            $updPaymentSql = "UPDATE `payments` 
                              SET nominal = :nom, 
                                  status = :st, 
                                  tanggal_pembayaran = :tgl, 
                                  verified_by = :v_by, 
                                  verified_at = CURRENT_TIMESTAMP 
                              WHERE id = :pid";
            $updPaymentStmt = $db->prepare($updPaymentSql);
            $updPaymentStmt->execute([
                'nom'  => $totalPaid,
                'st'   => $newPaymentStatus,
                'tgl'  => $paymentDate,
                'v_by' => $verifierUserId,
                'pid'  => $paymentId,
            ]);

            $db->commit();

            return [
                'success'           => true,
                'cash_trx_id'       => $newCashTrxId,
                'cash_trx_kode'     => $kodeKas,
                'total_paid'        => $totalPaid,
                'payment_status'    => $newPaymentStatus,
                'payment_id'        => $paymentId,
                'member_id'         => (int)$trx['member_id'],
                'kode_transaksi'    => $trx['kode_transaksi'],
            ];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Reject Payment Submission with Reason
     */
    public function rejectPayment(int $trxId, int $verifierUserId, string $reason): array {
        $trx = $this->find($trxId);
        if (!$trx) {
            return ['success' => false, 'error' => 'Data transaksi pembayaran tidak ditemukan.'];
        }

        if ($trx['status'] === self::STATUS_BERHASIL) {
            return ['success' => false, 'error' => 'Transaksi yang sudah berhasil tidak dapat ditolak.'];
        }

        $cleanReason = trim($reason);
        if (empty($cleanReason)) {
            return ['success' => false, 'error' => 'Alasan penolakan pembayaran wajib diisi.'];
        }

        $updated = $this->update($trxId, [
            'status'           => self::STATUS_DITOLAK,
            'alasan_penolakan' => $cleanReason,
            'verified_by'      => $verifierUserId,
            'verified_at'      => date('Y-m-d H:i:s'),
        ]);

        if ($updated) {
            return [
                'success'        => true,
                'payment_id'     => (int)$trx['payment_id'],
                'member_id'      => (int)$trx['member_id'],
                'kode_transaksi' => $trx['kode_transaksi'],
            ];
        }

        return ['success' => false, 'error' => 'Gagal memperbarui status penolakan transaksi.'];
    }

    /**
     * Direct Cash Receipt Recorded by Bendahara
     */
    public function recordDirectCash(array $data, int $bendaharaUserId): array {
        $nominal = (float)$data['nominal'];
        $paymentId = (int)$data['payment_id'];
        $memberId = (int)$data['member_id'];
        $catatan = trim((string)($data['catatan'] ?? ''));

        if ($nominal <= 0) {
            return ['success' => false, 'error' => 'Nominal pembayaran tunai harus lebih besar dari 0.'];
        }

        $kode = $this->generateKode();

        // 1. Create payment transaction with status menunggu_verifikasi initially
        $createdId = $this->create([
            'payment_id'     => $paymentId,
            'member_id'      => $memberId,
            'kode_transaksi' => $kode,
            'metode'         => self::METHOD_TUNAI,
            'channel'        => 'Kas Tunai',
            'nominal'        => $nominal,
            'status'         => self::STATUS_MENUNGGU_VERIFIKASI,
            'catatan'        => $catatan,
        ]);

        if (!$createdId) {
            return ['success' => false, 'error' => 'Gagal membuat transaksi penerimaan tunai.'];
        }

        // 2. Immediately verify and record to Buku Kas
        return $this->verifyPayment($createdId, $bendaharaUserId, $catatan);
    }
}
