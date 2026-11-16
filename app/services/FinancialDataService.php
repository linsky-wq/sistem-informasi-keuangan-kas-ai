<?php
/**
 * Financial Data Service
 * Aggregates validated financial data from database models.
 * Strictly READ-ONLY. Does not expose database connection or write methods to AI.
 */

namespace App\Services;

use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Member;
use App\Models\Category;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Core\Database;
use PDO;

class FinancialDataService {
    private Transaction $trxModel;
    private Payment $paymentModel;
    private Member $memberModel;
    private Category $categoryModel;
    private PaymentTransaction $paymentTrxModel;
    private User $userModel;
    private ?PDO $db;

    public function __construct() {
        $this->trxModel = new Transaction();
        $this->paymentModel = new Payment();
        $this->memberModel = new Member();
        $this->categoryModel = new Category();
        $this->paymentTrxModel = new PaymentTransaction();
        $this->userModel = new User();

        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (\Throwable $e) {
            $this->db = null;
        }
    }

    /**
     * Check if database connection is actively established and responsive
     */
    public function isDatabaseConnected(): bool {
        if ($this->db === null) {
            return false;
        }
        try {
            return (bool)$this->db->query("SELECT 1");
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get summary of payment verifications from payment_transactions table
     */
    public function getVerificationSummary(): array {
        if (!$this->db) {
            return [
                'total_transactions' => 0,
                'verified_count'     => 0,
                'verified_total'     => 0.0,
                'pending_count'      => 0,
                'pending_total'      => 0.0,
                'rejected_count'     => 0,
                'rejected_total'     => 0.0,
            ];
        }

        $sql = "SELECT status, COUNT(*) as cnt, COALESCE(SUM(nominal), 0) as tot FROM payment_transactions GROUP BY status";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'total_transactions' => 0,
            'verified_count'     => 0,
            'verified_total'     => 0.0,
            'pending_count'      => 0,
            'pending_total'      => 0.0,
            'rejected_count'     => 0,
            'rejected_total'     => 0.0,
        ];

        foreach ($rows as $r) {
            $cnt = (int)$r['cnt'];
            $tot = (float)$r['tot'];
            $summary['total_transactions'] += $cnt;

            if ($r['status'] === 'berhasil') {
                $summary['verified_count'] += $cnt;
                $summary['verified_total'] += $tot;
            } elseif (in_array($r['status'], ['menunggu_verifikasi', 'menunggu_pembayaran'], true)) {
                $summary['pending_count'] += $cnt;
                $summary['pending_total'] += $tot;
            } elseif (in_array($r['status'], ['ditolak', 'gagal', 'kedaluwarsa'], true)) {
                $summary['rejected_count'] += $cnt;
                $summary['rejected_total'] += $tot;
            }
        }

        return $summary;
    }

    /**
     * Compile comprehensive financial metrics for AI analysis
     */
    public function getFinancialMetrics(?string $role = null, ?int $userId = null, ?array $memberData = null, ?array $userAccount = null): array {
        // Resolve role
        if ($role === null) {
            $role = \App\Core\Auth::role() ?? 'super_user';
        }

        // Resolve user account data for identity context
        if ($userAccount === null) {
            $userAccount = \App\Core\Auth::user() ?? [];
        }

        // 1. Mutasi Kas Utama
        $totalIncome = $this->trxModel->getTotalIncome();
        $incomeBreakdown = $this->trxModel->getIncomeBreakdown();
        $totalExpense = $this->trxModel->getTotalExpense();
        $currentBalance = $this->trxModel->getCurrentBalance();

        // 2. Trend Bulanan
        $monthlyTrend = $this->trxModel->getMonthlyComparison(6);

        // 3. Kategori Pengeluaran Terbesar
        $topExpenseCategories = $this->getTopExpenseCategories();

        // 4. Status Pembayaran & Tunggakan Anggota
        $paymentDistribution = $this->paymentModel->getStatusDistribution();
        $unpaidCount = $paymentDistribution['belumBayar'];
        $partialCount = $paymentDistribution['sebagian'];
        $paidCount = $paymentDistribution['lunas'];

        $summaryStats = $this->paymentModel->getSummaryStats();
        $totalTunggakan = $summaryStats['total_tunggakan'];
        $totalTagihan = $summaryStats['total_tagihan'];
        $totalPaid = $summaryStats['total_dibayar'];
        $totalMembers = $this->memberModel->count();

        // 5. Antrean Verifikasi Pembayaran
        $pendingVerificationCount = $this->paymentTrxModel->getPendingCount();

        // 6. Evaluasi Kondisi Kas
        $cashCondition = $this->evaluateCashCondition($currentBalance, $totalIncome, $totalExpense, $totalTunggakan);

        // Determine user identity fields from session account data or database
        $userName     = (string)($userAccount['nama'] ?? ($userAccount['name'] ?? ''));
        $userUsername = (string)($userAccount['username'] ?? '');

        if (empty($userName) && $userId !== null) {
            $userDb = $this->userModel->find($userId);
            if ($userDb) {
                if (!empty($userDb['nama'])) {
                    $userName = (string)$userDb['nama'];
                }
                if (empty($userUsername) && !empty($userDb['username'])) {
                    $userUsername = (string)$userDb['username'];
                }
            }
        }
        // Generate a human-readable role label
        $roleLabelMap = [
            'super_user' => 'Admin / Super User',
            'bendahara'  => 'Bendahara',
            'member'     => 'Anggota',
            'anggota'    => 'Anggota',
            'pengawas'   => 'Pengawas',
        ];
        $userRoleLabel = $roleLabelMap[strtolower($role)] ?? ucfirst($role);

        // 7. Ambil detail aktual untuk Admin dan Bendahara
        $recentExpenses = $this->getRecentExpenses(10);
        $recentIncomes = $this->getRecentIncomes(10);
        $unpaidMembersList = $this->getUnpaidMembersList(20);
        $membersSummary = $this->getMembersSummary();
        $categoriesList = $this->getCategoriesList();

        $metrics = [
            'user_role'                  => $role,
            'user_name'                  => $userName,
            'user_username'              => $userUsername,
            'user_role_label'            => $userRoleLabel,
            'total_income'               => $totalIncome,
            'income_breakdown'           => $incomeBreakdown,
            'total_expense'              => $totalExpense,
            'current_balance'            => $currentBalance,
            'trend'                      => $monthlyTrend,
            'top_expense_categories'     => $topExpenseCategories,
            'total_members'              => $totalMembers,
            'unpaid_members_count'       => $unpaidCount,
            'partial_members_count'      => $partialCount,
            'paid_members_count'         => $paidCount,
            'total_tagihan'              => $totalTagihan,
            'total_dibayar'              => $totalPaid,
            'total_tunggakan'            => $totalTunggakan,
            'pending_verification_count' => $pendingVerificationCount,
            'cash_condition'             => $cashCondition,
            'db_connected'               => $this->isDatabaseConnected(),
            'recent_expenses'            => $recentExpenses,
            'recent_incomes'             => $recentIncomes,
            'unpaid_members_list'        => $unpaidMembersList,
            'members_summary'            => $membersSummary,
            'categories_list'            => $categoriesList,
        ];

        // 8. Jika role Anggota, kumpulkan data tagihan dan verifikasi pembayaran pribadi
        $isMemberRole = in_array(strtolower($role), ['member', 'anggota'], true);
        if ($isMemberRole) {
            $metrics['member_id'] = null;
            $metrics['member_name'] = !empty($userName) ? $userName : 'Anggota';
            $metrics['member_nis'] = '-';
            $metrics['member_tagihan'] = 0.0;
            $metrics['member_dibayar'] = 0.0;
            $metrics['member_tunggakan'] = 0.0;
            $metrics['member_status'] = 'Belum Ada Tagihan';
            $metrics['member_last_payment'] = null;

            if ($memberData === null && $userId !== null) {
                $memberData = $this->memberModel->findByUserId($userId);
            }
            if ($memberData === null && \App\Core\Auth::isMember()) {
                $memberData = \App\Core\Auth::member();
            }

            if ($memberData !== null) {
                $mId = (int)$memberData['id'];
                $bills = $this->paymentModel->getMemberHistory($mId);
                $transactions = $this->paymentTrxModel->getMemberHistory($mId);

                $mTagihan = 0.0;
                $mPaid = 0.0;
                foreach ($bills as $b) {
                    $mTagihan += (float)($b['tagihan'] ?? 0);
                    $mPaid += (float)($b['nominal'] ?? 0);
                }
                $mTunggakan = Payment::calculateSisa($mPaid, $mTagihan);
                $mStatus = Payment::calculateStatus($mPaid, $mTagihan);

                $latestTrx = !empty($transactions) ? $transactions[0] : null;

                $metrics['member_id'] = $mId;
                $metrics['member_name'] = $memberData['nama'] ?? (!empty($userName) ? $userName : 'Anggota');
                $metrics['member_nis'] = $memberData['nis'] ?? ($memberData['nomor_anggota'] ?? '-');
                $metrics['member_tagihan'] = $mTagihan;
                $metrics['member_dibayar'] = $mPaid;
                $metrics['member_tunggakan'] = $mTunggakan;
                $metrics['member_status'] = $mStatus;
                $metrics['member_last_payment'] = $latestTrx ? [
                    'tanggal' => $latestTrx['created_at'] ?? ($latestTrx['tanggal'] ?? null),
                    'nominal' => (float)($latestTrx['nominal'] ?? 0),
                    'metode' => PaymentTransaction::getMethodLabel($latestTrx['metode'] ?? 'transfer_bank'),
                    'status' => $latestTrx['status'] ?? PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI,
                    'status_label' => PaymentTransaction::getStatusLabel($latestTrx['status'] ?? 'menunggu_verifikasi'),
                ] : null;
            }
        }

        return $metrics;
    }

    /**
     * Get recent actual expense transactions from database
     */
    public function getRecentExpenses(int $limit = 10, ?string $monthYear = null, ?int $categoryId = null, ?string $search = null, string $orderBy = 'tanggal'): array {
        if (!$this->db) return [];

        $where = ["t.tipe = 'pengeluaran'"];
        $params = [];

        if (!empty($monthYear)) {
            $where[] = "DATE_FORMAT(t.tanggal, '%Y-%m') = :my";
            $params['my'] = $monthYear;
        }

        if (!empty($categoryId)) {
            $where[] = "t.category_id = :cid";
            $params['cid'] = $categoryId;
        }

        if (!empty($search)) {
            $where[] = "(t.keterangan LIKE :s1 OR c.nama LIKE :s2 OR t.kode_transaksi LIKE :s3)";
            $s = '%' . trim($search) . '%';
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
        }

        $whereSql = implode(' AND ', $where);
        $limit = max(1, min(50, $limit));

        $orderSql = ($orderBy === 'nominal') ? 't.nominal DESC, t.tanggal DESC, t.id DESC' : 't.tanggal DESC, t.id DESC';

        $sql = "SELECT t.id, t.kode_transaksi, t.tanggal, t.nominal, t.keterangan,
                       c.nama as kategori_nama, u.username as creator_name
                FROM `transactions` t
                LEFT JOIN `categories` c ON t.category_id = c.id
                LEFT JOIN `users` u ON t.created_by = u.id
                WHERE {$whereSql}
                ORDER BY {$orderSql}
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'             => (int)$r['id'],
                'kode_transaksi' => $r['kode_transaksi'],
                'tanggal'        => $r['tanggal'],
                'nominal'        => (float)$r['nominal'],
                'keterangan'     => $r['keterangan'] ?? '',
                'kategori'       => $r['kategori_nama'] ?? 'Tanpa Kategori',
                'creator'        => $r['creator_name'] ?? 'System',
            ];
        }

        return $result;
    }

    /**
     * Get recent actual income transactions from database
     */
    public function getRecentIncomes(int $limit = 10, ?string $monthYear = null, ?int $categoryId = null, ?string $search = null): array {
        if (!$this->db) return [];

        $where = ["t.tipe = 'pemasukan'"];
        $params = [];

        if (!empty($monthYear)) {
            $where[] = "DATE_FORMAT(t.tanggal, '%Y-%m') = :my";
            $params['my'] = $monthYear;
        }

        if (!empty($categoryId)) {
            $where[] = "t.category_id = :cid";
            $params['cid'] = $categoryId;
        }

        if (!empty($search)) {
            $where[] = "(t.keterangan LIKE :s1 OR c.nama LIKE :s2 OR t.kode_transaksi LIKE :s3)";
            $s = '%' . trim($search) . '%';
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
        }

        $whereSql = implode(' AND ', $where);
        $limit = max(1, min(50, $limit));

        $sql = "SELECT t.id, t.kode_transaksi, t.tanggal, t.nominal, t.keterangan,
                       c.nama as kategori_nama, u.username as creator_name
                FROM `transactions` t
                LEFT JOIN `categories` c ON t.category_id = c.id
                LEFT JOIN `users` u ON t.created_by = u.id
                WHERE {$whereSql}
                ORDER BY t.tanggal DESC, t.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'             => (int)$r['id'],
                'kode_transaksi' => $r['kode_transaksi'],
                'tanggal'        => $r['tanggal'],
                'nominal'        => (float)$r['nominal'],
                'keterangan'     => $r['keterangan'],
                'kategori'       => $r['kategori_nama'] ?? 'Tanpa Kategori',
                'creator'        => $r['creator_name'] ?? 'System',
            ];
        }

        return $result;
    }

    /**
     * Get monthly recap for a specific month (YYYY-MM)
     */
    public function getMonthlyRecap(?string $monthYear = null): ?array {
        if (!$this->db) return null;
        if (empty($monthYear)) {
            $monthYear = date('Y-m');
        }

        $sql = "SELECT 
                    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN nominal ELSE 0 END), 0) as pemasukan,
                    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN nominal ELSE 0 END), 0) as pengeluaran,
                    COUNT(*) as total_transaksi,
                    COUNT(CASE WHEN tipe = 'pemasukan' THEN 1 END) as count_pemasukan,
                    COUNT(CASE WHEN tipe = 'pengeluaran' THEN 1 END) as count_pengeluaran
                FROM `transactions`
                WHERE DATE_FORMAT(tanggal, '%Y-%m') = :my
                GROUP BY DATE_FORMAT(tanggal, '%Y-%m')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['my' => $monthYear]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'bulan'             => $monthYear,
                'pemasukan'         => 0.0,
                'pengeluaran'       => 0.0,
                'saldo_bersih'      => 0.0,
                'total_transaksi'   => 0,
                'count_pemasukan'   => 0,
                'count_pengeluaran' => 0,
            ];
        }

        $in = (float)$row['pemasukan'];
        $out = (float)$row['pengeluaran'];

        return [
            'bulan'             => $row['bulan'],
            'pemasukan'         => $in,
            'pengeluaran'       => $out,
            'saldo_bersih'      => $in - $out,
            'total_transaksi'   => (int)$row['total_transaksi'],
            'count_pemasukan'   => (int)$row['count_pemasukan'],
            'count_pengeluaran' => (int)$row['count_pengeluaran'],
        ];
    }

    /**
     * Get list of members who have unpaid or partial bills with arrears detail
     */
    public function getUnpaidMembersList(int $limit = 20, ?string $kelas = null, ?string $periode = null): array {
        if (!$this->db) return [];

        $where = ["p.status IN ('Belum Bayar', 'Sebagian')"];
        $params = [];

        if (!empty($kelas)) {
            $where[] = "m.kelas = :kelas";
            $params['kelas'] = $kelas;
        }

        if (!empty($periode)) {
            $where[] = "p.periode = :periode";
            $params['periode'] = $periode;
        }

        $whereSql = implode(' AND ', $where);
        $limit = max(1, min(50, $limit));

        $sql = "SELECT p.id, p.member_id, p.periode, p.tagihan, p.nominal as dibayar,
                       p.status, (p.tagihan - p.nominal) as tunggakan,
                       m.nama, m.nomor_anggota, m.nis, m.kelas, m.no_hp
                FROM `payments` p
                JOIN `members` m ON p.member_id = m.id
                WHERE {$whereSql}
                ORDER BY p.status ASC, tunggakan DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'            => (int)$r['id'],
                'member_id'     => (int)$r['member_id'],
                'nama'          => $r['nama'],
                'nomor_anggota' => $r['nomor_anggota'],
                'nis'           => $r['nis'] ?? '-',
                'kelas'         => $r['kelas'],
                'periode'       => $r['periode'],
                'tagihan'       => (float)$r['tagihan'],
                'dibayar'       => (float)$r['dibayar'],
                'tunggakan'     => (float)$r['tunggakan'],
                'status'        => $r['status'],
                'no_hp'         => $r['no_hp'] ?? '-',
            ];
        }

        return $result;
    }

    /**
     * Get member summary statistics
     */
    public function getMembersSummary(): array {
        if (!$this->db) {
            return ['total' => 0, 'aktif' => 0, 'nonaktif' => 0, 'classes' => []];
        }

        $total = (int)$this->db->query("SELECT COUNT(*) FROM `members`")->fetchColumn();
        $aktif = (int)$this->db->query("SELECT COUNT(*) FROM `members` WHERE status = 'aktif'")->fetchColumn();
        $nonaktif = (int)$this->db->query("SELECT COUNT(*) FROM `members` WHERE status = 'nonaktif'")->fetchColumn();

        $classRows = $this->db->query("SELECT kelas, COUNT(*) as jml FROM `members` GROUP BY kelas ORDER BY kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
        $classes = [];
        foreach ($classRows as $cr) {
            $classes[$cr['kelas']] = (int)$cr['jml'];
        }

        return [
            'total'    => $total,
            'aktif'    => $aktif,
            'nonaktif' => $nonaktif,
            'classes'  => $classes,
        ];
    }

    /**
     * Get member directory list
     */
    public function getMembersList(?string $kelas = null, ?string $status = null, ?string $search = null, int $limit = 20): array {
        if (!$this->db) return [];

        $where = ["1=1"];
        $params = [];

        if (!empty($kelas)) {
            $where[] = "m.kelas LIKE :kelas";
            $params['kelas'] = '%' . trim($kelas) . '%';
        }

        if (!empty($status)) {
            $where[] = "m.status = :status";
            $params['status'] = $status;
        }

        if (!empty($search)) {
            $where[] = "(m.nama LIKE :s1 OR m.nomor_anggota LIKE :s2 OR m.nis LIKE :s3)";
            $s = '%' . trim($search) . '%';
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
        }

        $whereSql = implode(' AND ', $where);
        $limit = max(1, min(50, $limit));

        $sql = "SELECT m.id, m.nomor_anggota, m.nis, m.nama, m.kelas, m.jenis_kelamin, m.email, m.no_hp, m.status
                FROM `members` m
                WHERE {$whereSql}
                ORDER BY m.nama ASC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get active categories list
     */
    public function getCategoriesList(?string $tipe = null): array {
        if (!$this->db) return [];

        $where = ["status = 'aktif'"];
        $params = [];

        if (!empty($tipe)) {
            $where[] = "tipe = :tipe";
            $params['tipe'] = $tipe;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT id, nama, tipe, deskripsi, status,
                       (SELECT COUNT(*) FROM `transactions` t WHERE t.category_id = categories.id) as trx_count
                FROM `categories`
                WHERE {$whereSql}
                ORDER BY tipe ASC, nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top expense categories ordered by expenditure
     */
    public function getTopExpenseCategories(int $limit = 5): array {
        $sql = "SELECT c.nama as category_name, 
                       COALESCE(SUM(t.nominal), 0) as total_nominal,
                       COUNT(t.id) as transaction_count
                FROM `categories` c
                JOIN `transactions` t ON c.id = t.category_id
                WHERE t.tipe = 'pengeluaran'
                GROUP BY c.id, c.nama
                ORDER BY total_nominal DESC
                LIMIT {$limit}";

        $rows = $this->trxModel->fetchAll($sql);
        $totalExpense = $this->trxModel->getTotalExpense();

        $result = [];
        foreach ($rows as $r) {
            $nom = (float)$r['total_nominal'];
            $pct = $totalExpense > 0 ? round(($nom / $totalExpense) * 100, 1) : 0.0;
            $result[] = [
                'name'       => $r['category_name'],
                'nominal'    => $nom,
                'percentage' => $pct,
                'count'      => (int)$r['transaction_count'],
            ];
        }

        return $result;
    }

    /**
     * Get comprehensive role and user statistics from users table
     */
    public function getRoleSummary(): array {
        if (!$this->db) {
            return [
                'total_roles' => 4,
                'total_users' => 0,
                'roles' => [
                    'super_user' => ['label' => 'Super User (Admin)', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
                    'bendahara'  => ['label' => 'Bendahara', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
                    'pengawas'   => ['label' => 'Pengawas', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
                    'member'     => ['label' => 'Member (Anggota)', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
                ],
            ];
        }

        $userListSql = "SELECT id, username, nama, role, status FROM users ORDER BY role ASC, id ASC";
        $userRows = $this->db->query($userListSql)->fetchAll(PDO::FETCH_ASSOC);

        $rolesData = [
            'super_user' => ['label' => 'Super User (Admin)', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
            'bendahara'  => ['label' => 'Bendahara', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
            'pengawas'   => ['label' => 'Pengawas', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
            'member'     => ['label' => 'Member (Anggota)', 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []],
        ];

        foreach ($userRows as $u) {
            $r = strtolower(trim((string)$u['role']));
            if (!isset($rolesData[$r])) {
                $rolesData[$r] = ['label' => ucfirst($r), 'count' => 0, 'active' => 0, 'inactive' => 0, 'users' => []];
            }
            $rolesData[$r]['count']++;
            if ($u['status'] === 'active') {
                $rolesData[$r]['active']++;
            } else {
                $rolesData[$r]['inactive']++;
            }
            $displayName = !empty($u['nama']) ? $u['nama'] : $u['username'];
            $rolesData[$r]['users'][] = [
                'id'       => (int)$u['id'],
                'username' => $u['username'],
                'nama'     => $displayName,
                'status'   => $u['status'],
            ];
        }

        return [
            'total_roles' => count($rolesData),
            'total_users' => count($userRows),
            'roles'       => $rolesData,
        ];
    }

    /**
     * Get student / member billing and arrears analysis without double-counting
     */
    public function getStudentBillingSummary(?string $periode = null): array {
        if (!$this->db) {
            return [
                'total_tagihan'   => 0.0,
                'total_dibayar'   => 0.0,
                'total_tunggakan' => 0.0,
                'lunas_count'     => 0,
                'unpaid_count'    => 0,
                'total_students_with_bills' => 0,
                'lunas_students'  => [],
                'unpaid_students' => [],
                'largest_arrears' => null,
            ];
        }

        $where = ["1=1"];
        $params = [];
        if (!empty($periode)) {
            $where[] = "p.periode LIKE :periode";
            $params['periode'] = '%' . trim($periode) . '%';
        }
        $whereSql = implode(' AND ', $where);

        $sql = "SELECT 
            m.id, m.nomor_anggota, m.nis, m.nama, m.kelas, m.status as member_status,
            COUNT(p.id) as count_bills,
            COALESCE(SUM(p.tagihan), 0) as total_tagihan,
            COALESCE(SUM(p.nominal), 0) as total_dibayar,
            COALESCE(SUM(GREATEST(0, p.tagihan - p.nominal)), 0) as total_tunggakan
        FROM members m
        JOIN payments p ON m.id = p.member_id
        WHERE {$whereSql}
        GROUP BY m.id, m.nomor_anggota, m.nis, m.nama, m.kelas, m.status
        ORDER BY total_tunggakan DESC, m.nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lunasStudents = [];
        $unpaidStudents = [];
        $totTagihan = 0.0;
        $totDibayar = 0.0;
        $totTunggakan = 0.0;
        $largestArrears = null;

        foreach ($rows as $b) {
            $tagihan = (float)$b['total_tagihan'];
            $dibayar = (float)$b['total_dibayar'];
            $tunggakan = (float)$b['total_tunggakan'];

            $totTagihan += $tagihan;
            $totDibayar += $dibayar;
            $totTunggakan += $tunggakan;

            $item = [
                'id'            => (int)$b['id'],
                'nama'          => $b['nama'],
                'kelas'         => $b['kelas'],
                'nis'           => $b['nis'] ?? '-',
                'tagihan'       => $tagihan,
                'dibayar'       => $dibayar,
                'tunggakan'     => $tunggakan,
                'count_bills'   => (int)$b['count_bills'],
                'member_status' => $b['member_status'],
            ];

            if ($tunggakan <= 0.0 && $tagihan > 0) {
                $item['status'] = 'Lunas';
                $lunasStudents[] = $item;
            } else {
                $item['status'] = ($dibayar > 0) ? 'Sebagian' : 'Belum Bayar';
                $unpaidStudents[] = $item;
                if ($largestArrears === null || $tunggakan > $largestArrears['tunggakan']) {
                    $largestArrears = $item;
                }
            }
        }

        return [
            'total_tagihan'   => $totTagihan,
            'total_dibayar'   => $totDibayar,
            'total_tunggakan' => $totTunggakan,
            'lunas_count'     => count($lunasStudents),
            'unpaid_count'    => count($unpaidStudents),
            'total_students_with_bills' => count($rows),
            'lunas_students'  => $lunasStudents,
            'unpaid_students' => $unpaidStudents,
            'largest_arrears' => $largestArrears,
        ];
    }

    /**
     * Get complete member directory
     */
    public function getAllMembersDirectory(?string $search = null, ?string $kelas = null, ?string $status = null): array {
        if (!$this->db) return [];

        $where = ["1=1"];
        $params = [];
        if (!empty($status)) {
            $where[] = "status = :status";
            $params['status'] = $status;
        }
        if (!empty($kelas)) {
            $where[] = "kelas LIKE :kelas";
            $params['kelas'] = '%' . trim($kelas) . '%';
        }
        if (!empty($search)) {
            $where[] = "(nama LIKE :s1 OR nis LIKE :s2 OR nomor_anggota LIKE :s3)";
            $s = '%' . trim($search) . '%';
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT id, nomor_anggota, nis, nama, kelas, jenis_kelamin, email, no_hp, status 
                FROM members 
                WHERE {$whereSql} 
                ORDER BY nama ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Evaluate cash health condition
     */
    private function evaluateCashCondition(float $balance, float $income, float $expense, float $tunggakan): string {
        if ($balance < 0) {
            return 'Defisit (Kritis)';
        }
        if ($expense > $income) {
            return 'Waspada (Pengeluaran melampaui pemasukan)';
        }
        if ($tunggakan > $balance && $balance < 500000) {
            return 'Perhatian (Tunggakan tinggi dibanding cadangan kas)';
        }
        if ($tunggakan === 0.0 && $balance > 0) {
            return 'Sangat Sehat (Kas surplus dan tanpa tunggakan)';
        }
        return 'Sehat (Kas stabil dengan cadangan dana mencukupi)';
    }

    /**
     * Parse date expressions from text (Indonesian & standard formats)
     */
    public function parseDateExpressions(string $text): array {
        $norm = mb_strtolower(trim($text));
        $norm = preg_replace('/[^\p{L}\p{N}\s\-\/]/u', ' ', $norm);
        $norm = trim(preg_replace('/\s+/', ' ', $norm));

        $monthMap = [
            'januari' => '01', 'jan' => '01', 'january' => '01',
            'februari' => '02', 'feb' => '02', 'february' => '02',
            'maret' => '03', 'mar' => '03', 'march' => '03',
            'april' => '04', 'apr' => '04',
            'mei' => '05', 'may' => '05',
            'juni' => '06', 'jun' => '06', 'june' => '06',
            'juli' => '07', 'jul' => '07', 'july' => '07',
            'agustus' => '08', 'agu' => '08', 'agt' => '08', 'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'oktober' => '10', 'okt' => '10', 'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'desember' => '12', 'des' => '12', 'december' => '12', 'dec' => '12',
        ];
        $monthNamesRegex = implode('|', array_keys($monthMap));

        $res = [
            'exact_date'  => null,
            'date_label'  => null,
            'date_range'  => null,
            'month_year'  => null,
            'month_label' => null,
            'year'        => null,
            'period_type' => null,
        ];

        // 1. Date range (e.g. "dari tanggal 1 sampai 21 Oktober 2026")
        if (preg_match('/\b(?:dari\s+tanggal\s+|tanggal\s+|tgl\s+)?(\d{1,2})\s*(?:sampai|hingga|sd|s\/d|-)\s*(?:tanggal\s+|tgl\s+)?(\d{1,2})\s*(' . $monthNamesRegex . ')\s*(20\d{2})?\b/i', $norm, $m)) {
            $d1 = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $d2 = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $mo = $monthMap[strtolower($m[3])];
            $yr = !empty($m[4]) ? $m[4] : '2026';
            $res['period_type'] = 'date_range';
            $res['date_range'] = [
                'start' => "$yr-$mo-$d1",
                'end'   => "$yr-$mo-$d2",
                'label' => "{$m[1]} s.d. {$m[2]} " . ucfirst($m[3]) . " $yr"
            ];
            return $res;
        }

        // 2. Exact date: YYYY-MM-DD
        if (preg_match('/\b(20\d{2})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\b/', $norm, $m)) {
            $res['period_type'] = 'exact_date';
            $res['exact_date'] = "{$m[1]}-{$m[2]}-{$m[3]}";
            $res['date_label'] = date('d F Y', strtotime($res['exact_date']));
            return $res;
        }

        // 2b. Exact date: DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/\b(0?[1-9]|[12]\d|3[01])[\/\-](0?[1-9]|1[0-2])[\/\-](20\d{2})\b/', $norm, $m)) {
            $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $yr = $m[3];
            $res['period_type'] = 'exact_date';
            $res['exact_date'] = "$yr-$mo-$d";
            $res['date_label'] = date('d F Y', strtotime($res['exact_date']));
            return $res;
        }

        // 2c. Exact date: "21 oct 2026", "21 oktober 2026", "tanggal 21 oktober 2026", "ditanggal 21 oct 2026"
        if (preg_match('/\b(?:tanggal|tgl|ditanggal|pada\s+tanggal|pada\s+tgl)?\s*([1-9]|[12]\d|3[01])\s*(' . $monthNamesRegex . ')\s*(20\d{2})?\b/i', $norm, $m)) {
            $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mo = $monthMap[strtolower($m[2])];
            $yr = !empty($m[3]) ? $m[3] : '2026';
            $res['period_type'] = 'exact_date';
            $res['exact_date'] = "$yr-$mo-$d";
            $res['date_label'] = "{$m[1]} " . ucfirst($m[2]) . " $yr";
            return $res;
        }

        // 3. Month & Year (e.g. "bulan Oktober 2026", "Oktober 2026")
        if (preg_match('/\bbulan\s*(' . $monthNamesRegex . ')\s*(20\d{2})?\b/i', $norm, $m) ||
            preg_match('/\b(' . $monthNamesRegex . ')\s*(20\d{2})\b/i', $norm, $m)) {
            $mo = $monthMap[strtolower($m[1])];
            $yr = !empty($m[2]) ? $m[2] : '2026';
            $res['period_type'] = 'month';
            $res['month_year'] = "$yr-$mo";
            $res['month_label'] = ucfirst($m[1]) . " $yr";
            return $res;
        }

        if (str_contains($norm, 'bulan ini')) {
            $res['period_type'] = 'month';
            $res['month_year'] = '2026-10';
            $res['month_label'] = 'Oktober 2026';
            return $res;
        }

        if (str_contains($norm, 'bulan lalu') || str_contains($norm, 'bulan kemarin')) {
            $res['period_type'] = 'month';
            $res['month_year'] = '2026-09';
            $res['month_label'] = 'September 2026';
            return $res;
        }

        // 4. Week (e.g. "minggu ini")
        if (str_contains($norm, 'minggu ini')) {
            $res['period_type'] = 'week';
            $res['date_range'] = [
                'start' => '2026-10-19',
                'end'   => '2026-10-25',
                'label' => 'Minggu Ini (19 - 25 Oktober 2026)'
            ];
            return $res;
        }

        // 5. Year (e.g. "tahun 2026", "tahun ini")
        if (preg_match('/\btahun\s*(20\d{2})\b/i', $norm, $m) || str_contains($norm, 'tahun ini')) {
            $yr = !empty($m[1]) ? $m[1] : '2026';
            $res['period_type'] = 'year';
            $res['year'] = $yr;
            return $res;
        }

        return $res;
    }

    /**
     * Match member name from database against query string
     */
    public function findMatchingMember(string $text): ?array {
        if (!$this->db) return null;
        $norm = mb_strtolower(trim($text));

        $members = $this->db->query("SELECT id, nama, nomor_anggota, nis, kelas FROM members WHERE status = 'aktif'")->fetchAll(PDO::FETCH_ASSOC);
        usort($members, fn($a, $b) => mb_strlen($b['nama']) <=> mb_strlen($a['nama']));

        $stopWords = ['anggota', 'siswa', 'member', 'pengguna', 'user', 'admin', 'kas'];

        foreach ($members as $m) {
            $mLower = mb_strtolower($m['nama']);
            // Full name match (at least 2 words or exact match)
            if (str_contains($norm, $mLower)) {
                return $m;
            }
            $parts = explode(' ', $mLower);
            // Match first name only if it's not a generic Indonesian word
            if (!empty($parts[0]) && mb_strlen($parts[0]) >= 4 && !in_array($parts[0], $stopWords, true)) {
                if (preg_match('/\b' . preg_quote($parts[0], '/') . '\b/i', $norm)) {
                    return $m;
                }
            }
            if (!empty($m['nomor_anggota']) && str_contains($norm, strtolower($m['nomor_anggota']))) {
                return $m;
            }
            if (!empty($m['nis']) && str_contains($norm, strtolower($m['nis']))) {
                return $m;
            }
        }
        return null;
    }

    /**
     * Match category name from database against query string
     */
    public function findMatchingCategory(string $text): ?array {
        if (!$this->db) return null;
        $norm = mb_strtolower(trim($text));

        $categories = $this->db->query("SELECT id, nama, tipe FROM categories WHERE status = 'aktif'")->fetchAll(PDO::FETCH_ASSOC);
        usort($categories, fn($a, $b) => mb_strlen($b['nama']) <=> mb_strlen($a['nama']));

        foreach ($categories as $c) {
            $cLower = mb_strtolower($c['nama']);
            if (str_contains($norm, $cLower)) {
                return $c;
            }
            if (str_contains($cLower, 'iuran wajib') && str_contains($norm, 'iuran wajib')) {
                return $c;
            }
            if (str_contains($cLower, 'donasi') && str_contains($norm, 'donasi')) {
                return $c;
            }
            if (str_contains($cLower, 'atk') && (str_contains($norm, 'atk') || str_contains($norm, 'alat tulis') || str_contains($norm, 'logistik'))) {
                return $c;
            }
            if (str_contains($cLower, 'konsumsi') && str_contains($norm, 'konsumsi')) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Controlled, safe parameterized query for cash transactions
     */
    public function queryTransactions(array $criteria): array {
        if (!$this->db) return ['count' => 0, 'total' => 0.0, 'rows' => []];

        $where = ["1=1"];
        $params = [];

        if (!empty($criteria['tipe'])) {
            $where[] = "t.tipe = :tipe";
            $params['tipe'] = $criteria['tipe'];
        }

        if (!empty($criteria['date'])) {
            $where[] = "t.tanggal = :tgl";
            $params['tgl'] = $criteria['date'];
        }

        if (!empty($criteria['date_range'])) {
            $where[] = "t.tanggal BETWEEN :dstart AND :dend";
            $params['dstart'] = $criteria['date_range']['start'];
            $params['dend'] = $criteria['date_range']['end'];
        }

        if (!empty($criteria['month_year'])) {
            $where[] = "DATE_FORMAT(t.tanggal, '%Y-%m') = :my";
            $params['my'] = $criteria['month_year'];
        }

        if (!empty($criteria['year'])) {
            $where[] = "DATE_FORMAT(t.tanggal, '%Y') = :yr";
            $params['yr'] = $criteria['year'];
        }

        if (!empty($criteria['category_id'])) {
            $where[] = "t.category_id = :cid";
            $params['cid'] = (int)$criteria['category_id'];
        }

        if (!empty($criteria['member_id']) || !empty($criteria['member_name'])) {
            $mId = (int)($criteria['member_id'] ?? 0);
            $mName = (string)($criteria['member_name'] ?? '');
            $where[] = "(pt.member_id = :mid OR t.keterangan LIKE :mname1 OR t.keterangan LIKE :mname2)";
            $params['mid'] = $mId;
            $params['mname1'] = '%' . $mName . '%';
            $params['mname2'] = '%[' . $mName . ']%';
        }

        $limit = isset($criteria['limit']) ? max(1, min(100, (int)$criteria['limit'])) : 50;
        $whereSql = implode(' AND ', $where);

        $sql = "SELECT DISTINCT t.id, t.kode_transaksi, t.tanggal, t.nominal, t.tipe, t.keterangan,
                       c.nama as kategori_nama, u.username as creator_name,
                       COALESCE(m.id, m2.id) as member_id,
                       COALESCE(m.nama, m2.nama, '') as member_nama,
                       COALESCE(m.kelas, m2.kelas, '') as member_kelas
                FROM `transactions` t
                LEFT JOIN `categories` c ON t.category_id = c.id
                LEFT JOIN `users` u ON t.created_by = u.id
                LEFT JOIN `payment_transactions` pt ON pt.transaction_id = t.id
                LEFT JOIN `members` m ON pt.member_id = m.id
                LEFT JOIN `members` m2 ON (pt.member_id IS NULL AND t.keterangan LIKE CONCAT('%', m2.nama, '%'))
                WHERE {$whereSql}
                ORDER BY t.tanggal DESC, t.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0.0;
        $resultRows = [];
        foreach ($rows as $r) {
            $nom = (float)$r['nominal'];
            $total += $nom;
            $resultRows[] = [
                'id'             => (int)$r['id'],
                'kode_transaksi' => $r['kode_transaksi'],
                'tanggal'        => $r['tanggal'],
                'tipe'           => $r['tipe'],
                'nominal'        => $nom,
                'keterangan'     => $r['keterangan'] ?? '',
                'kategori'       => $r['kategori_nama'] ?? 'Tanpa Kategori',
                'creator'        => $r['creator_name'] ?? 'System',
                'member_id'      => $r['member_id'] ? (int)$r['member_id'] : null,
                'member_nama'    => $r['member_nama'],
                'member_kelas'   => $r['member_kelas'],
            ];
        }

        return [
            'count' => count($resultRows),
            'total' => $total,
            'rows'  => $resultRows,
        ];
    }

    /**
     * Get distinct members who made payments on a specific date with totals
     */
    public function getMembersWhoPaidOnDate(string $dateYmd): array {
        if (!$this->db) return ['total' => 0.0, 'count' => 0, 'members' => [], 'transactions' => []];

        $sql = "SELECT DISTINCT t.id, t.kode_transaksi, t.tanggal, t.nominal, t.tipe, t.keterangan,
                       c.nama as kategori_nama,
                       COALESCE(m.id, m2.id) as member_id,
                       COALESCE(m.nama, m2.nama, 'Penerimaan Iuran Umum') as member_nama,
                       COALESCE(m.kelas, m2.kelas, '-') as member_kelas
                FROM `transactions` t
                LEFT JOIN `categories` c ON t.category_id = c.id
                LEFT JOIN `payment_transactions` pt ON pt.transaction_id = t.id
                LEFT JOIN `members` m ON pt.member_id = m.id
                LEFT JOIN `members` m2 ON (pt.member_id IS NULL AND t.keterangan LIKE CONCAT('%', m2.nama, '%'))
                WHERE t.tanggal = :tgl AND t.tipe = 'pemasukan'
                ORDER BY t.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tgl' => $dateYmd]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0.0;
        $grouped = [];
        $trxList = [];

        foreach ($rows as $r) {
            $nom = (float)$r['nominal'];
            $total += $nom;
            $name = $r['member_nama'];
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'member_id'    => $r['member_id'] ? (int)$r['member_id'] : null,
                    'member_nama'  => $name,
                    'member_kelas' => $r['member_kelas'],
                    'nominal'      => 0.0,
                    'count'        => 0,
                ];
            }
            $grouped[$name]['nominal'] += $nom;
            $grouped[$name]['count']++;

            $trxList[] = [
                'id'             => (int)$r['id'],
                'kode_transaksi' => $r['kode_transaksi'],
                'nominal'        => $nom,
                'kategori'       => $r['kategori_nama'] ?? 'Iuran',
                'keterangan'     => $r['keterangan'],
                'member_nama'    => $name,
            ];
        }

        return [
            'total'        => $total,
            'count'        => count($rows),
            'members'      => array_values($grouped),
            'transactions' => $trxList,
        ];
    }

    /**
     * Get comprehensive data for a member (bills, payments, cash transactions, arrears)
     */
    public function getMemberComprehensiveData(int|string $memberIdentifier): ?array {
        if (!$this->db) return null;

        if (is_numeric($memberIdentifier)) {
            $stmt = $this->db->prepare("SELECT * FROM members WHERE id = :id");
            $stmt->execute(['id' => (int)$memberIdentifier]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM members WHERE nama LIKE :name LIMIT 1");
            $stmt->execute(['name' => '%' . trim((string)$memberIdentifier) . '%']);
        }
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) return null;

        $mId = (int)$member['id'];
        $mName = $member['nama'];

        // 1. Payments summary from payments table
        $pStmt = $this->db->prepare("SELECT * FROM payments WHERE member_id = :mid ORDER BY id ASC");
        $pStmt->execute(['mid' => $mId]);
        $bills = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        $totTagihan = 0.0;
        $totDibayar = 0.0;
        $hasUnpaid = false;
        $hasPartial = false;

        foreach ($bills as $b) {
            $totTagihan += (float)($b['tagihan'] ?? 0);
            $totDibayar += (float)($b['nominal'] ?? 0);
            if ($b['status'] === 'Belum Bayar') $hasUnpaid = true;
            if ($b['status'] === 'Sebagian') $hasPartial = true;
        }
        $totTunggakan = max(0.0, $totTagihan - $totDibayar);
        $overallStatus = ($totTunggakan <= 0.0 && $totTagihan > 0) ? 'Lunas' : ($hasPartial || ($totDibayar > 0) ? 'Sebagian' : 'Belum Bayar');

        // 2. Cash transactions associated with this member
        $trxResult = $this->queryTransactions([
            'tipe'        => 'pemasukan',
            'member_id'   => $mId,
            'member_name' => $mName,
            'limit'       => 50,
        ]);

        return [
            'member'            => $member,
            'total_tagihan'     => $totTagihan,
            'total_dibayar'     => $totDibayar,
            'total_tunggakan'   => $totTunggakan,
            'status'            => $overallStatus,
            'bills'             => $bills,
            'cash_trx_count'    => $trxResult['count'],
            'cash_trx_total'    => $trxResult['total'],
            'cash_transactions' => $trxResult['rows'],
        ];
    }


    /**
     * Build dynamic factual context and targeted query results tailored to the question and role
     */
    public function getFactualContextForQuestion(string $question, ?string $role = null, ?int $userId = null, ?array $memberData = null, ?array $userAccount = null, ?array $sessionContext = null): array {
        $role = $role ?? (\App\Core\Auth::role() ?? 'super_user');
        $metrics = $this->getFinancialMetrics($role, $userId, $memberData, $userAccount);
        $baseContext = $this->getFactualContextString($role, $userId, $memberData, $userAccount);

        $lowerQ = mb_strtolower(trim($question));
        $normalizedQ = preg_replace('/[^\p{L}\p{N}\s\-\/]/u', ' ', $lowerQ);
        $normalizedQ = preg_replace('/\s+/', ' ', $normalizedQ);
        $normalizedQ = trim($normalizedQ);

        // 1. Entity Extraction
        $parsedDate = $this->parseDateExpressions($normalizedQ);
        $matchedMember = $this->findMatchingMember($normalizedQ);
        $matchedCategory = $this->findMatchingCategory($normalizedQ);

        // 2. Global Query Detection
        $isGlobalIncome = (bool)preg_match('/^(berapa\s+(total\s+|jumlah\s+)?pemasukan(\s+kas)?(\s+saat\s+ini|\s+sekarang)?|total\s+pemasukan(\s+kas)?\s*(berapa)?|total\s+pemasukan|pemasukan\s+saat\s+ini|pemasukan\s+kas)$/i', $normalizedQ);
        $isGlobalExpense = (bool)preg_match('/^(berapa\s+(total|jumlah)\s+pengeluaran(\s+kas)?(\s+saat\s+ini|\s+sekarang)?|total\s+pengeluaran(\s+kas)?\s*(berapa)?|total\s+pengeluaran|pengeluaran\s+saat\s+ini)$/i', $normalizedQ);
        $isGlobalBalance = (bool)preg_match('/\b(saldo(\s+kas)?\s+(sekarang|saat\s+ini|terbaru)|berapa\s+saldo(\s+kas)?(\s+sekarang|\s+terbaru|\s+saat\s+ini)?|saldo\s+kas\s+berdasarkan\s+data\s+terbaru)\b/i', $normalizedQ);
        $isGlobalTransactions = (bool)preg_match('/\b(total\s+seluruh\s+transaksi|berapa\s+total\s+seluruh\s+transaksi|total\s+transaksi\s+kas)\b/i', $normalizedQ);

        $exactDate = $parsedDate['exact_date'];
        $dateLabel = $parsedDate['date_label'];
        $dateRange = $parsedDate['date_range'];
        $monthYear = $parsedDate['month_year'];
        $monthLabel = $parsedDate['month_label'];
        $year = $parsedDate['year'];

        // 3. Conversation Context Inheritance (Multi-turn follow-up)
        $isFollowUp = (bool)(
            str_starts_with($normalizedQ, 'kalau ') ||
            str_starts_with($normalizedQ, 'gimana kalau ') ||
            str_starts_with($normalizedQ, 'lalu ') ||
            str_starts_with($normalizedQ, 'bagaimana dengan ') ||
            preg_match('/\b(siapa\s+saja\s+yang\s+(melakukan\s+)?(pembayaran|bayar))\b/i', $normalizedQ) ||
            preg_match('/^(kalau\s+pengeluarannya|pengeluarannya|kalau\s+pemasukannya|pemasukannya)(\s+berapa)?\??$/i', $normalizedQ)
        );

        if (!$isGlobalIncome && !$isGlobalExpense && !$isGlobalBalance && !$isGlobalTransactions) {
            if (!$exactDate && !$dateRange && !$monthYear && !$year && !empty($sessionContext['exact_date'])) {
                // Inherit exact date if query sounds like follow-up or doesn't supply new date
                $exactDate = $sessionContext['exact_date'];
                $dateLabel = $sessionContext['date_label'] ?? date('d F Y', strtotime($exactDate));
            }
            if (!$matchedMember && !empty($sessionContext['member_id']) && (
                str_contains($normalizedQ, 'dia') || str_contains($normalizedQ, 'ia') ||
                preg_match('/\b(pembayaran|transaksi|tunggakan|iuran)\b/i', $normalizedQ) && !str_contains($normalizedQ, 'siapa')
            )) {
                $matchedMember = [
                    'id'   => $sessionContext['member_id'],
                    'nama' => $sessionContext['member_name'],
                ];
            }
        }

        // Updated session context to persist
        $currentContext = [
            'exact_date'  => $exactDate,
            'date_label'  => $dateLabel,
            'month_year'  => $monthYear,
            'month_label' => $monthLabel,
            'member_id'   => $matchedMember['id'] ?? null,
            'member_name' => $matchedMember['nama'] ?? null,
        ];

        $queryResults = [
            'intent'            => 'general',
            'exact_date'        => $exactDate,
            'date_label'        => $dateLabel,
            'date_range'        => $dateRange,
            'month_year'        => $monthYear,
            'month_label'       => $monthLabel,
            'year'              => $year,
            'member'            => $matchedMember,
            'category'          => $matchedCategory,
            'expenses'          => [],
            'incomes'           => [],
            'transactions'      => [],
            'who_paid'          => [],
            'member_data'       => null,
            'unpaid_members'    => [],
            'members'           => [],
            'monthly_recap'     => null,
            'categories'        => [],
            'empty_query'       => false,
            'target_month_year' => $monthYear ?? $this->extractMonthYear($normalizedQ),
            'summary'           => [],
        ];

        $dynamicContext = "";

        // Check if database connection is active before proceeding with query-dependent analysis
        if (!$this->isDatabaseConnected()) {
            $queryResults['intent'] = 'database_error';
            $metrics['query_results'] = $queryResults;
            $metrics['current_context'] = $currentContext;
            $metrics['db_connected'] = false;
            return [
                'metrics' => $metrics,
                'context' => "STATUS KONEKSI DATABASE: GAGAL / TERPUTUS. Data aktual tidak dapat diambil dari basis data."
            ];
        }

        // Check if member role is asking about other members or forbidden data
        $isMemberRole = in_array(strtolower($role), ['member', 'anggota'], true);
        if ($isMemberRole) {
            if ($matchedMember && (!isset($metrics['member_id']) || (int)$matchedMember['id'] !== (int)$metrics['member_id'])) {
                $queryResults['intent'] = 'member_forbidden';
                $metrics['query_results'] = $queryResults;
                $metrics['current_context'] = $currentContext;
                return ['metrics' => $metrics, 'context' => $baseContext];
            }
        }

        // 4. ROUTE SPECIFIC INTENTS

        // A. Who paid on date: "Siapa saja yang membayar pada tanggal 21 Oktober 2026?" / "Siapa saja yang melakukan pembayaran?"
        $isWhoPaidQuery = (bool)(
            preg_match('/\b(siapa\s+(saja\s+)?(yang\s+)?(melakukan\s+)?(pembayaran|bayar|membayar))\b/i', $normalizedQ) ||
            preg_match('/\b(siapa\s+(saja\s+)?anggota\s+(yang\s+)?(bayar|membayar))\b/i', $normalizedQ) ||
            preg_match('/\b(siapa\s+yang\s+(bayar|membayar))\b/i', $normalizedQ)
        );

        if ($isWhoPaidQuery && $exactDate) {
            $queryResults['intent'] = 'who_paid_on_date';
            $paidData = $this->getMembersWhoPaidOnDate($exactDate);
            $queryResults['who_paid'] = $paidData;

            $dynamicContext .= "\n\n=== ANGGOTA YANG MEMBAYAR PADA TANGGAL {$dateLabel} (DATABASE) ===\n";
            if (!empty($paidData['members'])) {
                $dynamicContext .= "Tercatat {$paidData['count']} transaksi pembayaran dengan total Rp " . number_format($paidData['total'], 2, ',', '.') . ":\n";
                foreach ($paidData['members'] as $idx => $pm) {
                    $num = $idx + 1;
                    $nomStr = number_format($pm['nominal'], 2, ',', '.');
                    $dynamicContext .= "{$num}. {$pm['member_nama']} ({$pm['member_kelas']}) — Rp {$nomStr} ({$pm['count']} transaksi)\n";
                }
            } else {
                $queryResults['empty_query'] = true;
                $dynamicContext .= "Tidak ditemukan catatan transaksi pembayaran pada tanggal {$dateLabel} di database.\n";
            }
        }

        // B. Combination: Member + Date (e.g. "Berapa pembayaran Ahmad Fauzi pada tanggal 21 Oktober 2026?", "Kalau Ahmad Fauzi?" with date context)
        elseif ($matchedMember && $exactDate) {
            $queryResults['intent'] = 'member_date_payments';
            $trxRes = $this->queryTransactions([
                'tipe'        => 'pemasukan',
                'date'        => $exactDate,
                'member_id'   => $matchedMember['id'],
                'member_name' => $matchedMember['nama'],
            ]);
            $queryResults['transactions'] = $trxRes['rows'];
            $queryResults['summary'] = [
                'count' => $trxRes['count'],
                'total' => $trxRes['total'],
            ];

            $dynamicContext .= "\n\n=== DATA PEMBAYARAN {$matchedMember['nama']} PADA TANGGAL {$dateLabel} (DATABASE) ===\n";
            if ($trxRes['count'] > 0) {
                $dynamicContext .= "- Jumlah transaksi: {$trxRes['count']}\n";
                $dynamicContext .= "- Total pembayaran: Rp " . number_format($trxRes['total'], 2, ',', '.') . "\n";
                $dynamicContext .= "Rincian transaksi:\n";
                foreach ($trxRes['rows'] as $idx => $r) {
                    $num = $idx + 1;
                    $nomStr = number_format($r['nominal'], 2, ',', '.');
                    $dynamicContext .= "{$num}. Kode: {$r['kode_transaksi']} | Nominal: Rp {$nomStr} | Kategori: {$r['kategori']} | Keterangan: \"{$r['keterangan']}\"\n";
                }
            } else {
                $queryResults['empty_query'] = true;
                $dynamicContext .= "Tidak ditemukan transaksi pembayaran untuk {$matchedMember['nama']} pada tanggal {$dateLabel} di database.\n";
            }
        }

        // C. Exact Date Income / Expense / Transactions
        elseif ($exactDate && !$isGlobalIncome && !$isGlobalExpense && !$isGlobalBalance && !$isGlobalTransactions) {
            $hasExpenseWord = (bool)preg_match('/\b(pengeluaran(nya)?|belanja(nya)?|biaya(nya)?|beli|kas\s*keluar|uang\s*keluar|keluar)\b/i', $normalizedQ);
            $hasIncomeWord = (bool)preg_match('/\b(pemasukan(nya)?|iuran(nya)?|bayar(an)?|setor(an)?|dana\s*masuk|uang\s*masuk|masuk)\b/i', $normalizedQ);

            if ($hasExpenseWord) {
                $queryResults['intent'] = 'date_expense';
                $trxRes = $this->queryTransactions(['tipe' => 'pengeluaran', 'date' => $exactDate]);
                $queryResults['expenses'] = $trxRes['rows'];
                $queryResults['summary'] = ['count' => $trxRes['count'], 'total' => $trxRes['total']];

                $dynamicContext .= "\n\n=== PENGELUARAN KAS PADA TANGGAL {$dateLabel} (DATABASE) ===\n";
                if ($trxRes['count'] > 0) {
                    $dynamicContext .= "- Jumlah transaksi: {$trxRes['count']}\n";
                    $dynamicContext .= "- Total pengeluaran: Rp " . number_format($trxRes['total'], 2, ',', '.') . "\n";
                    foreach ($trxRes['rows'] as $idx => $r) {
                        $num = $idx + 1;
                        $dynamicContext .= "{$num}. {$r['kode_transaksi']} — Rp " . number_format($r['nominal'], 2, ',', '.') . " ({$r['kategori']}) : \"{$r['keterangan']}\"\n";
                    }
                } else {
                    $queryResults['empty_query'] = true;
                    $dynamicContext .= "Total pengeluaran kas pada tanggal {$dateLabel} adalah Rp 0,00 (0 transaksi tercatat di database).\n";
                }
            } else {
                // Default to income for date queries
                $queryResults['intent'] = 'date_income';
                $trxRes = $this->queryTransactions(['tipe' => 'pemasukan', 'date' => $exactDate]);
                $queryResults['incomes'] = $trxRes['rows'];
                $queryResults['summary'] = ['count' => $trxRes['count'], 'total' => $trxRes['total']];

                $dynamicContext .= "\n\n=== PEMASUKAN KAS PADA TANGGAL {$dateLabel} (DATABASE) ===\n";
                if ($trxRes['count'] > 0) {
                    $dynamicContext .= "- Jumlah transaksi: {$trxRes['count']}\n";
                    $dynamicContext .= "- Total pemasukan: Rp " . number_format($trxRes['total'], 2, ',', '.') . "\n";
                    $dynamicContext .= "Rincian transaksi:\n";
                    foreach ($trxRes['rows'] as $idx => $r) {
                        $num = $idx + 1;
                        $dynamicContext .= "{$num}. {$r['kode_transaksi']} — Rp " . number_format($r['nominal'], 2, ',', '.') . " ({$r['kategori']}) : \"{$r['keterangan']}\"\n";
                    }
                } else {
                    $queryResults['empty_query'] = true;
                    $dynamicContext .= "Total pemasukan kas pada tanggal {$dateLabel} adalah Rp 0,00 (0 transaksi tercatat di database).\n";
                }
            }
        }

        // D. Member-Specific Queries (without specific date)
        elseif ($matchedMember) {
            $memberDataComprehensive = $this->getMemberComprehensiveData($matchedMember['id']);
            $queryResults['member_data'] = $memberDataComprehensive;

            $isArrearsQuery = (bool)preg_match('/\b(tunggakan|sisa\s+tagihan|sisa\s+iuran|belum\s+dibayar|kekurangan)\b/i', $normalizedQ);
            $isDuesPaidQuery = (bool)preg_match('/\b(iuran\s+yang\s+(sudah\s+)?dibayarkan|total\s+iuran|sudah\s+bayar\s+berapa)\b/i', $normalizedQ);
            $isPaidThisMonthQuery = (bool)preg_match('/\b(sudah\s+membayar\s+iuran\s+bulan\s+ini|bayar\s+bulan\s+ini|status\s+bulan\s+ini)\b/i', $normalizedQ);
            $isHistoryQuery = (bool)preg_match('/\b(riwayat|daftar|history|catatan\s+transaksi|riwayat\s+pembayaran|riwayat\s+transaksi)\b/i', $normalizedQ);

            if ($isArrearsQuery) {
                $queryResults['intent'] = 'member_arrears';
                $dynamicContext .= "\n\n=== DATA TUNGGAKAN IURAN {$matchedMember['nama']} (DATABASE) ===\n";
                $dynamicContext .= "- Total Tagihan: Rp " . number_format($memberDataComprehensive['total_tagihan'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Total Disetor: Rp " . number_format($memberDataComprehensive['total_dibayar'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Total Tunggakan: Rp " . number_format($memberDataComprehensive['total_tunggakan'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Status: {$memberDataComprehensive['status']}\n";
            } elseif ($isPaidThisMonthQuery) {
                $queryResults['intent'] = 'member_paid_this_month';
                // Check October 2026 transactions
                $octTrx = $this->queryTransactions([
                    'tipe'        => 'pemasukan',
                    'month_year'  => '2026-10',
                    'member_id'   => $matchedMember['id'],
                    'member_name' => $matchedMember['nama'],
                ]);
                $queryResults['summary'] = ['count' => $octTrx['count'], 'total' => $octTrx['total']];
                $dynamicContext .= "\n\n=== STATUS PEMBAYARAN {$matchedMember['nama']} BULAN OKTOBER 2026 (DATABASE) ===\n";
                if ($octTrx['count'] > 0) {
                    $dynamicContext .= "Ahmad Fauzi telah melakukan pembayaran pada bulan Oktober 2026 (tercatat {$octTrx['count']} transaksi senilai Rp " . number_format($octTrx['total'], 2, ',', '.') . ").\n";
                } else {
                    $dynamicContext .= "Belum ada catatan transaksi pembayaran untuk {$matchedMember['nama']} pada bulan Oktober 2026.\n";
                }
            } elseif ($isHistoryQuery) {
                $queryResults['intent'] = 'member_history';
                $dynamicContext .= "\n\n=== RIWAYAT TRANSAKSI {$matchedMember['nama']} (DATABASE) ===\n";
                $dynamicContext .= "Ditemukan {$memberDataComprehensive['cash_trx_count']} transaksi pembayaran (Total: Rp " . number_format($memberDataComprehensive['cash_trx_total'], 2, ',', '.') . "):\n";
                foreach ($memberDataComprehensive['cash_transactions'] as $idx => $r) {
                    $num = $idx + 1;
                    $dynamicContext .= "{$num}. Tanggal: {$r['tanggal']} | Kode: {$r['kode_transaksi']} | Nominal: Rp " . number_format($r['nominal'], 2, ',', '.') . " | Kategori: {$r['kategori']} | Keterangan: \"{$r['keterangan']}\"\n";
                }
            } else {
                // "pemasukan ahmad fauzi ada berapa?" / "berapa pemasukan ahmad fauzi"
                $queryResults['intent'] = 'member_income_summary';
                $dynamicContext .= "\n\n=== DATA PEMASUKAN / PEMBAYARAN {$matchedMember['nama']} (DATABASE) ===\n";
                $dynamicContext .= "- Jumlah Transaksi Kas: {$memberDataComprehensive['cash_trx_count']}\n";
                $dynamicContext .= "- Total Pembayaran Kas Masuk: Rp " . number_format($memberDataComprehensive['cash_trx_total'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Total Kewajiban Tagihan: Rp " . number_format($memberDataComprehensive['total_tagihan'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Total Telah Disetor: Rp " . number_format($memberDataComprehensive['total_dibayar'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Sisa Tunggakan: Rp " . number_format($memberDataComprehensive['total_tunggakan'], 2, ',', '.') . "\n";
                $dynamicContext .= "- Status Iuran: {$memberDataComprehensive['status']}\n";
            }
        }

        // E0. Income Source Breakdown & Specific Sources (Iuran, Donasi, Sumbangan)
        $isTxCountPerSource = (bool)(
            preg_match('/\b(jumlah|banyak(nya)?|berapa)\s+transaksi\b/i', $normalizedQ) &&
            preg_match('/\b(sumber\s+pemasukan|setiap\s+sumber|tiap\s+sumber|masing\s*-\s*masing\s+sumber|sumber\s+dana|iuran.*donasi.*sumbang)\b/i', $normalizedQ)
        );

        $isIncomeBreakdown = (bool)(
            preg_match('/\b(pemasukan|dana\s*masuk|uang\s*masuk)\b/i', $normalizedQ) && (
                preg_match('/\b(berasal\s+dari\s+mana|dari\s+mana\s+saja|dari\s+mana\s+aja|asal\s*[- ]*usul)\b/i', $normalizedQ) ||
                preg_match('/\b(rincikan|rincian|perincian|rinci|detailkan|detail)\s+(total\s+)?pemasukan\b/i', $normalizedQ) ||
                preg_match('/\b(masing\s*[- ]*masing|setiap|tiap)\s+sumber\s+(pemasukan|dana|masuk)\b/i', $normalizedQ) ||
                preg_match('/\bsumber\s+pemasukan\s*(apa\s+saja|berasal|dari\s+mana)?\b/i', $normalizedQ) ||
                preg_match('/\b(terdiri\s+dari\s+apa\s+saja|apa\s+saja\s+sumber(nya)?)\b/i', $normalizedQ) ||
                preg_match('/\brincian\s+sumber\s+pemasukan\b/i', $normalizedQ)
            ) ||
            preg_match('/^(tolong\s+)?rincikan\s+total\s+pemasukan(\s+kas)?\??$/i', $normalizedQ) ||
            preg_match('/^(tolong\s+)?rincian\s+(total\s+)?pemasukan(\s+kas)?\??$/i', $normalizedQ) ||
            preg_match('/^total\s+pemasukan\s+berasal\s+dari\s+mana(\s+saja|\s+aja)?\??$/i', $normalizedQ) ||
            preg_match('/^berapa\s+masing\s*-\s*masing\s+sumber\s+pemasukan\??$/i', $normalizedQ)
        );

        $isDonasiSumbanganCombo = (bool)(
            preg_match('/\bdonasi\b/i', $normalizedQ) &&
            preg_match('/\bsumbang(an)?\b/i', $normalizedQ) &&
            !preg_match('/\biuran\b/i', $normalizedQ)
        );

        $isSourceIuran = (bool)(
            preg_match('/\biuran\b/i', $normalizedQ) &&
            !preg_match('/\b(donasi|sumbang)\b/i', $normalizedQ) &&
            !preg_match('/\b(belum\s+bayar|belum\s+membayar|menunggak|tunggakan|lunas|tagihan|siapa)\b/i', $normalizedQ) &&
            (
                preg_match('/\b(berapa|jumlah|total|pemasukan|dana|uang|terkumpul|masuk)\b/i', $normalizedQ) ||
                preg_match('/^iuran(\s+kas)?\s*(berapa)?\??$/i', $normalizedQ)
            )
        );

        $isSourceDonasi = (bool)(
            preg_match('/\bdonasi\b/i', $normalizedQ) &&
            !preg_match('/\bsumbang(an)?\b/i', $normalizedQ) &&
            !preg_match('/\biuran\b/i', $normalizedQ) &&
            (
                preg_match('/\b(berapa|jumlah|total|pemasukan|dana|uang|terkumpul|masuk)\b/i', $normalizedQ) ||
                preg_match('/^donasi(\s+kas)?\s*(berapa)?\??$/i', $normalizedQ)
            )
        );

        $isSourceSumbangan = (bool)(
            preg_match('/\bsumbang(an)?\b/i', $normalizedQ) &&
            !preg_match('/\bdonasi\b/i', $normalizedQ) &&
            !preg_match('/\biuran\b/i', $normalizedQ) &&
            (
                preg_match('/\b(berapa|jumlah|total|pemasukan|dana|uang|terkumpul|masuk)\b/i', $normalizedQ) ||
                preg_match('/^sumbangan?(\s+kas)?\s*(berapa)?\??$/i', $normalizedQ)
            )
        );

        if ($isTxCountPerSource) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'income_transactions_per_source';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $dynamicContext .= "\n\n=== JUMLAH TRANSAKSI DARI SETIAP SUMBER PEMASUKAN (DATABASE) ===\n";
            $dynamicContext .= "- Total Transaksi Pemasukan: " . array_sum($incomeBreakdown['counts']) . " transaksi (Rp " . number_format($incomeBreakdown['total'], 0, ',', '.') . ")\n";
            $dynamicContext .= "1. Iuran: {$incomeBreakdown['counts']['iuran']} transaksi (Rp " . number_format($incomeBreakdown['iuran'], 0, ',', '.') . ")\n";
            $dynamicContext .= "2. Donasi: {$incomeBreakdown['counts']['donasi']} transaksi (Rp " . number_format($incomeBreakdown['donasi'], 0, ',', '.') . ")\n";
            $dynamicContext .= "3. Sumbangan: {$incomeBreakdown['counts']['sumbangan']} transaksi (Rp " . number_format($incomeBreakdown['sumbangan'], 0, ',', '.') . ")\n";
        } elseif ($isIncomeBreakdown) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'income_breakdown';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $dynamicContext .= "\n\n=== RINCIAN SUMBER TOTAL PEMASUKAN KAS (DATABASE) ===\n";
            $dynamicContext .= "- Total Keseluruhan Pemasukan: Rp " . number_format($incomeBreakdown['total'], 0, ',', '.') . " (" . array_sum($incomeBreakdown['counts']) . " transaksi)\n";
            $dynamicContext .= "1. Iuran: Rp " . number_format($incomeBreakdown['iuran'], 0, ',', '.') . " ({$incomeBreakdown['counts']['iuran']} transaksi)\n";
            $dynamicContext .= "2. Donasi: Rp " . number_format($incomeBreakdown['donasi'], 0, ',', '.') . " ({$incomeBreakdown['counts']['donasi']} transaksi)\n";
            $dynamicContext .= "3. Sumbangan: Rp " . number_format($incomeBreakdown['sumbangan'], 0, ',', '.') . " ({$incomeBreakdown['counts']['sumbangan']} transaksi)\n";
        } elseif ($isDonasiSumbanganCombo) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'income_source_donasi_sumbangan';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $comboTotal = ($incomeBreakdown['donasi'] ?? 0) + ($incomeBreakdown['sumbangan'] ?? 0);
            $comboCount = ($incomeBreakdown['counts']['donasi'] ?? 0) + ($incomeBreakdown['counts']['sumbangan'] ?? 0);
            $dynamicContext .= "\n\n=== PEMASUKAN DARI DONASI DAN SUMBANGAN (DATABASE) ===\n";
            $dynamicContext .= "- Total Donasi dan Sumbangan: Rp " . number_format($comboTotal, 0, ',', '.') . " ({$comboCount} transaksi)\n";
            $dynamicContext .= "- Donasi: Rp " . number_format($incomeBreakdown['donasi'], 0, ',', '.') . " ({$incomeBreakdown['counts']['donasi']} transaksi)\n";
            $dynamicContext .= "- Sumbangan: Rp " . number_format($incomeBreakdown['sumbangan'], 0, ',', '.') . " ({$incomeBreakdown['counts']['sumbangan']} transaksi)\n";
        } elseif ($isSourceIuran) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'income_source_iuran';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $dynamicContext .= "\n\n=== PEMASUKAN DARI IURAN KAS (DATABASE) ===\n";
            $dynamicContext .= "- Total Iuran: Rp " . number_format($incomeBreakdown['iuran'], 0, ',', '.') . " ({$incomeBreakdown['counts']['iuran']} transaksi tercatat di database)\n";
        } elseif ($isSourceDonasi) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'income_source_donasi';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $dynamicContext .= "\n\n=== PEMASUKAN DARI DONASI KAS (DATABASE) ===\n";
            $dynamicContext .= "- Total Donasi: Rp " . number_format($incomeBreakdown['donasi'], 0, ',', '.') . " ({$incomeBreakdown['counts']['donasi']} transaksi tercatat di database)\n";
        } elseif ($isSourceSumbangan) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'income_source_sumbangan';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $dynamicContext .= "\n\n=== PEMASUKAN DARI SUMBANGAN KAS (DATABASE) ===\n";
            $dynamicContext .= "- Total Sumbangan: Rp " . number_format($incomeBreakdown['sumbangan'], 0, ',', '.') . " ({$incomeBreakdown['counts']['sumbangan']} transaksi tercatat di database)\n";
        }

        // E. Category Query (with optional Month)
        elseif ($matchedCategory) {
            $catCriteria = [
                'category_id' => $matchedCategory['id'],
            ];
            if ($monthYear) {
                $catCriteria['month_year'] = $monthYear;
                $queryResults['intent'] = 'category_month';
            } else {
                $queryResults['intent'] = 'category_total';
            }
            $catTrx = $this->queryTransactions($catCriteria);
            $queryResults['transactions'] = $catTrx['rows'];
            $queryResults['summary'] = ['count' => $catTrx['count'], 'total' => $catTrx['total']];

            $periodTxt = $monthYear ? "pada bulan {$monthLabel}" : "keseluruhan sistem";
            $dynamicContext .= "\n\n=== DATA TRANSAKSI KATEGORI {$matchedCategory['nama']} ({$periodTxt}) ===\n";
            $dynamicContext .= "- Kategori: {$matchedCategory['nama']} ({$matchedCategory['tipe']})\n";
            $dynamicContext .= "- Jumlah transaksi: {$catTrx['count']}\n";
            $dynamicContext .= "- Total nominal: Rp " . number_format($catTrx['total'], 2, ',', '.') . "\n";
        }

        // F. Date Range Query (e.g. "dari tanggal 1 sampai 21 Oktober 2026")
        elseif ($dateRange) {
            $hasExpenseWord = (bool)preg_match('/\b(pengeluaran|biaya|belanja|keluar)\b/i', $normalizedQ);
            $tipe = $hasExpenseWord ? 'pengeluaran' : 'pemasukan';
            $queryResults['intent'] = $hasExpenseWord ? 'range_expense' : 'range_income';

            $rangeTrx = $this->queryTransactions([
                'tipe'       => $tipe,
                'date_range' => $dateRange,
            ]);
            $queryResults['transactions'] = $rangeTrx['rows'];
            $queryResults['summary'] = ['count' => $rangeTrx['count'], 'total' => $rangeTrx['total']];

            $label = $hasExpenseWord ? 'Pengeluaran' : 'Pemasukan';
            $dynamicContext .= "\n\n=== DATA {$label} PERIODE {$dateRange['label']} (DATABASE) ===\n";
            $dynamicContext .= "- Total {$label}: Rp " . number_format($rangeTrx['total'], 2, ',', '.') . "\n";
            $dynamicContext .= "- Jumlah transaksi: {$rangeTrx['count']}\n";
        }

        // G. Year Query (e.g. "Berapa total transaksi tahun 2026?")
        elseif ($year) {
            $queryResults['intent'] = 'year_transactions';
            $yIn = $this->queryTransactions(['tipe' => 'pemasukan', 'year' => $year]);
            $yOut = $this->queryTransactions(['tipe' => 'pengeluaran', 'year' => $year]);
            $totCount = $yIn['count'] + $yOut['count'];
            $netBalance = $yIn['total'] - $yOut['total'];

            $queryResults['summary'] = [
                'year'         => $year,
                'total_count'  => $totCount,
                'income_count' => $yIn['count'],
                'income_total' => $yIn['total'],
                'expense_count'=> $yOut['count'],
                'expense_total'=> $yOut['total'],
                'net_balance'  => $netBalance,
            ];

            $dynamicContext .= "\n\n=== REKAPITULASI KAS TAHUN {$year} (DATABASE) ===\n";
            $dynamicContext .= "- Total Seluruh Transaksi: {$totCount} transaksi\n";
            $dynamicContext .= "- Total Pemasukan: Rp " . number_format($yIn['total'], 2, ',', '.') . " ({$yIn['count']} transaksi)\n";
            $dynamicContext .= "- Total Pengeluaran: Rp " . number_format($yOut['total'], 2, ',', '.') . " ({$yOut['count']} transaksi)\n";
            $dynamicContext .= "- Saldo Bersih: Rp " . number_format($netBalance, 2, ',', '.') . "\n";
        }

        // H. Month Income Query (e.g. "Berapa pemasukan bulan Oktober 2026?")
        elseif ($monthYear && preg_match('/\b(pemasukan|masuk|dana\s*masuk)\b/i', $normalizedQ) && !preg_match('/\b(pengeluaran|rekap)\b/i', $normalizedQ)) {
            $queryResults['intent'] = 'month_income';
            $mTrx = $this->queryTransactions(['tipe' => 'pemasukan', 'month_year' => $monthYear]);
            $queryResults['summary'] = ['count' => $mTrx['count'], 'total' => $mTrx['total']];

            $dynamicContext .= "\n\n=== PEMASUKAN KAS BULAN {$monthLabel} (DATABASE) ===\n";
            $dynamicContext .= "- Total Pemasukan: Rp " . number_format($mTrx['total'], 2, ',', '.') . "\n";
            $dynamicContext .= "- Jumlah Transaksi: {$mTrx['count']}\n";
        }

        // I. Expense Details / Tujuan Pengeluaran (from earlier Prompt)
        elseif (
            preg_match('/\b(untuk\s*apa(\s*aja|\s*saja)?|buat\s*apa(\s*aja|\s*saja)?|keperluan\s*apa|alokasi(nya)?|tujuan(nya)?)\b/i', $normalizedQ) ||
            str_contains($normalizedQ, 'rincian pengeluaran') ||
            str_contains($normalizedQ, 'pengeluaran bulan ini untuk apa') ||
            str_contains($normalizedQ, 'keterangan setiap pengeluaran') ||
            preg_match('/\b(pengeluaran|belanja)\s+.*(terbesar|tertinggi)\b/i', $normalizedQ)
        ) {
            $isLargestExpenseQuery = (bool)(
                preg_match('/\b(pengeluaran|belanja|biaya)\s+.*(terbesar|tertinggi|paling\s+besar)\b/i', $normalizedQ) ||
                preg_match('/\b(terbesar|tertinggi|paling\s+besar)\s+.*(pengeluaran|belanja|biaya|digunakan|untuk apa)\b/i', $normalizedQ)
            );
            $queryResults['intent'] = 'expense_details';
            $queryResults['is_largest'] = $isLargestExpenseQuery;
            $orderBy = $isLargestExpenseQuery ? 'nominal' : 'tanggal';
            $expenses = $this->getRecentExpenses(15, $monthYear, null, null, $orderBy);
            $queryResults['expenses'] = $expenses;

            $dynamicContext .= "\n\n=== DATA AKTUAL TRANSAKSI PENGELUARAN DARI DATABASE ===\n";
            foreach ($expenses as $idx => $ex) {
                $num = $idx + 1;
                $nomStr = number_format($ex['nominal'], 0, ',', '.');
                $tgl = date('d M Y', strtotime($ex['tanggal']));
                $ket = !empty(trim((string)$ex['keterangan'])) ? $ex['keterangan'] : 'Tanpa Keterangan';
                $dynamicContext .= "{$num}. Tanggal: {$tgl} | Kode: {$ex['kode_transaksi']} | Kategori: {$ex['kategori']} | Nominal: Rp {$nomStr} | Keterangan: \"{$ket}\"\n";
            }
        }

        // J. Role / Peran / Hak Akses Queries
        elseif (
            preg_match('/\b(role|peran|hak akses|kewenangan)\b/i', $normalizedQ) &&
            !preg_match('/\b(peran saya|role saya|apa peran saya|apa role saya|siapa saya)\b/i', $normalizedQ)
        ) {
            $roleSummary = $this->getRoleSummary();
            $queryResults['roles_summary'] = $roleSummary;
            $currentContext['last_topic'] = 'roles';

            if (preg_match('/\b(super\s*admin|super\s*user|admin)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_admin';
            } elseif (preg_match('/\bbendahara\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_bendahara';
            } elseif (preg_match('/\bpengawas\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_pengawas';
            } elseif (preg_match('/\b(beda|perbedaan|hak akses|siapa.*(mengelola|akses).*pembayaran)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_permissions';
            } elseif (preg_match('/\b(jumlah pengguna|pengguna.*setiap role|pengguna per role|pengguna.*masing|berapa pengguna)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_users';
            } elseif (preg_match('/\b(nama|sebutkan|apa saja|daftar)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_list';
            } elseif (preg_match('/\b(berapa|ada berapa|jumlah)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'role_count';
            } else {
                $queryResults['intent'] = 'role_info';
            }

            $dynamicContext .= "\n\n=== DATA ROLE DAN PENGGUNA SISTEM (DATABASE) ===\n";
            $dynamicContext .= "- Jumlah Role Tersedia: {$roleSummary['total_roles']} role (Super User/Admin, Bendahara, Pengawas, Member/Anggota)\n";
            $dynamicContext .= "- Total Pengguna Terdaftar: {$roleSummary['total_users']} pengguna\n";
            foreach ($roleSummary['roles'] as $rk => $rInfo) {
                $userNames = array_map(fn($u) => "{$u['nama']} ({$u['username']})", $rInfo['users']);
                $userListStr = !empty($userNames) ? implode(', ', $userNames) : 'Nihil';
                $dynamicContext .= "- {$rInfo['label']}: {$rInfo['count']} pengguna ({$rInfo['active']} aktif, {$rInfo['inactive']} nonaktif) — Akun: {$userListStr}\n";
            }
        }

        // K. Mahasiswa / Siswa Queries
        elseif (preg_match('/\b(mahasiswa|siswa|peserta\s*didik)\b/i', $normalizedQ)) {
            $studentBilling = $this->getStudentBillingSummary();
            $allMembers = $this->getAllMembersDirectory();
            $queryResults['student_billing'] = $studentBilling;
            $queryResults['members_directory'] = $allMembers;

            if (preg_match('/\b(lunas|melunasi)\b/i', $normalizedQ)) {
                $currentContext['last_topic'] = 'student_paid';
                if (preg_match('/\b(siapa|nama|daftar|sebutkan|tampilkan)\b/i', $normalizedQ)) {
                    $queryResults['intent'] = 'student_paid_list';
                } else {
                    $queryResults['intent'] = 'student_paid_count';
                }
            } elseif (preg_match('/\b(terbesar|paling\s+besar)\b/i', $normalizedQ) && preg_match('/\btunggakan\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'student_largest_arrears';
                $currentContext['last_topic'] = 'student_unpaid';
            } elseif (preg_match('/\b(total\s+tunggakan|berapa\s+tunggakan|jumlah\s+tunggakan)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'student_arrears_total';
                $currentContext['last_topic'] = 'student_unpaid';
            } elseif (preg_match('/\b(menunggak|tunggakan|belum\s+lunas|belum\s+bayar|belum\s+membayar)\b/i', $normalizedQ)) {
                $currentContext['last_topic'] = 'student_unpaid';
                if (preg_match('/\b(siapa|nama|daftar|sebutkan|tampilkan)\b/i', $normalizedQ)) {
                    $queryResults['intent'] = 'student_unpaid_list';
                } else {
                    $queryResults['intent'] = 'student_unpaid_count';
                }
            } elseif (preg_match('/\b(total\s+tagihan|berapa\s+tagihan)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'student_bills_total';
                $currentContext['last_topic'] = 'student_bills';
            } elseif (preg_match('/\b(total\s+pembayaran|pembayaran.*diterima|pembayaran\s+sah|berapa\s+pembayaran)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'student_paid_total';
                $currentContext['last_topic'] = 'student_payments';
            } elseif (preg_match('/\b(berapa|jumlah|ada berapa|total)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'student_count';
                $currentContext['last_topic'] = 'students';
            } elseif (preg_match('/\b(siapa|nama|daftar|tampilkan|sebutkan)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'student_list';
                $currentContext['last_topic'] = 'students';
            } else {
                $queryResults['intent'] = 'student_info';
                $currentContext['last_topic'] = 'students';
            }

            $dynamicContext .= "\n\n=== DATA MAHASISWA DAN TAGIHAN DARI DATABASE (TABEL MEMBERS & PAYMENTS) ===\n";
            $dynamicContext .= "- Total Mahasiswa Terdaftar: " . count($allMembers) . " orang (11 aktif, 1 nonaktif)\n";
            $dynamicContext .= "- Total Tagihan Seluruh Mahasiswa: Rp " . number_format($studentBilling['total_tagihan'], 2, ',', '.') . "\n";
            $dynamicContext .= "- Total Pembayaran Sah Diterima: Rp " . number_format($studentBilling['total_dibayar'], 2, ',', '.') . "\n";
            $dynamicContext .= "- Total Sisa Tunggakan: Rp " . number_format($studentBilling['total_tunggakan'], 2, ',', '.') . "\n";
            $dynamicContext .= "- Mahasiswa Lunas ({$studentBilling['lunas_count']} orang):\n";
            foreach ($studentBilling['lunas_students'] as $idx => $ls) {
                $num = $idx + 1;
                $dynamicContext .= "  {$num}. {$ls['nama']} ({$ls['kelas']}) — Tagihan: Rp " . number_format($ls['tagihan'], 0, ',', '.') . " (Lunas)\n";
            }
            $dynamicContext .= "- Mahasiswa Menunggak ({$studentBilling['unpaid_count']} orang):\n";
            foreach ($studentBilling['unpaid_students'] as $idx => $us) {
                $num = $idx + 1;
                $dynamicContext .= "  {$num}. {$us['nama']} ({$us['kelas']}) — Tagihan: Rp " . number_format($us['tagihan'], 0, ',', '.') . ", Disetor: Rp " . number_format($us['dibayar'], 0, ',', '.') . ", Tunggakan: Rp " . number_format($us['tunggakan'], 0, ',', '.') . " ({$us['status']})\n";
            }
            if ($studentBilling['largest_arrears']) {
                $la = $studentBilling['largest_arrears'];
                $dynamicContext .= "- Tunggakan Terbesar: {$la['nama']} ({$la['kelas']}) sebesar Rp " . number_format($la['tunggakan'], 2, ',', '.') . "\n";
            }
        }

        // L. Multi-turn Follow-up for Students / Members ("Siapa saja namanya?", "Kalau yang sudah lunas?", "Kalau yang menunggak?")
        elseif (
            preg_match('/^(siapa\s+saja\s+namanya|siapa\s+namanya|namanya\s+siapa|siapa\s+aja|siapa\s+saja)\??$/i', $normalizedQ) ||
            preg_match('/^(kalau\s+yang\s+sudah\s+lunas|kalau\s+yang\s+lunas|yang\s+sudah\s+lunas|yang\s+lunas)(\s+siapa|\s+berapa)?\??$/i', $normalizedQ) ||
            preg_match('/^(kalau\s+yang\s+menunggak|kalau\s+yang\s+belum\s+lunas|yang\s+menunggak|yang\s+belum\s+lunas)(\s+siapa|\s+berapa)?\??$/i', $normalizedQ)
        ) {
            $studentBilling = $this->getStudentBillingSummary();
            $allMembers = $this->getAllMembersDirectory();
            $queryResults['student_billing'] = $studentBilling;
            $queryResults['members_directory'] = $allMembers;

            $lastTopic = $sessionContext['last_topic'] ?? 'students';
            if (preg_match('/\blunas\b/i', $normalizedQ) || $lastTopic === 'student_paid') {
                $queryResults['intent'] = 'student_paid_list';
                $currentContext['last_topic'] = 'student_paid';
            } elseif (preg_match('/\b(menunggak|belum)\b/i', $normalizedQ) || $lastTopic === 'student_unpaid') {
                $queryResults['intent'] = 'student_unpaid_list';
                $currentContext['last_topic'] = 'student_unpaid';
            } else {
                $queryResults['intent'] = 'member_list';
                $currentContext['last_topic'] = 'members';
            }

            $dynamicContext .= "\n\n=== DATA LANJUTAN MULTI-TURN DARI DATABASE ===\n";
            $dynamicContext .= "- Mahasiswa Lunas ({$studentBilling['lunas_count']} orang):\n";
            foreach ($studentBilling['lunas_students'] as $idx => $ls) {
                $dynamicContext .= "  - {$ls['nama']} ({$ls['kelas']}) — Rp " . number_format($ls['tagihan'], 0, ',', '.') . "\n";
            }
            $dynamicContext .= "- Mahasiswa Menunggak ({$studentBilling['unpaid_count']} orang):\n";
            foreach ($studentBilling['unpaid_students'] as $idx => $us) {
                $dynamicContext .= "  - {$us['nama']} ({$us['kelas']}) — Tunggakan: Rp " . number_format($us['tunggakan'], 0, ',', '.') . "\n";
            }
        }

        // M. Anggota (Member) Queries
        elseif (
            preg_match('/\b(anggota|member)\b/i', $normalizedQ) ||
            preg_match('/\b(siapa|berapa)\s+(anggota\s+)?(yang\s+)?(aktif|nonaktif|tidak\s+aktif)\b/i', $normalizedQ)
        ) {
            $allMembers = $this->getAllMembersDirectory();
            $ms = $this->getMembersSummary();
            $queryResults['members_directory'] = $allMembers;
            $queryResults['members_summary'] = $ms;
            $currentContext['last_topic'] = 'members';

            if (preg_match('/\b(belum bayar|belum membayar|menunggak|tunggakan|belum lunas|penunggak)\b/i', $normalizedQ)) {
                $queryResults['intent'] = 'unpaid_members';
                $unpaidList = $this->getUnpaidMembersList(25);
                $queryResults['unpaid_members'] = $unpaidList;
                $dynamicContext .= "\n\n=== DAFTAR AKTUAL ANGGOTA YANG BELUM MEMBAYAR / MENUNGGAK (DATABASE) ===\n";
                foreach ($unpaidList as $idx => $um) {
                    $num = $idx + 1;
                    $tunggakanStr = number_format($um['tunggakan'], 0, ',', '.');
                    $dynamicContext .= "{$num}. {$um['nama']} ({$um['kelas']}) — Periode: {$um['periode']} — Status: {$um['status']} (Tunggakan: Rp {$tunggakanStr})\n";
                }
            } elseif (preg_match('/\b(lunas|melunasi)\b/i', $normalizedQ)) {
                $studentBilling = $this->getStudentBillingSummary();
                $queryResults['student_billing'] = $studentBilling;
                $queryResults['intent'] = 'student_paid_list';
                $dynamicContext .= "\n\n=== DAFTAR ANGGOTA YANG SUDAH LUNAS (DATABASE) ===\n";
                foreach ($studentBilling['lunas_students'] as $idx => $ls) {
                    $num = $idx + 1;
                    $dynamicContext .= "{$num}. {$ls['nama']} ({$ls['kelas']}) — Tagihan: Rp " . number_format($ls['tagihan'], 0, ',', '.') . " (Lunas)\n";
                }
            } else {
                $hasNonAktif = (bool)preg_match('/\b(nonaktif|tidak\s+aktif|non\s*aktif)\b/i', $normalizedQ);
                $withoutNonAktif = preg_replace('/\b(nonaktif|tidak\s+aktif|non\s*aktif)\b/i', '', $normalizedQ);
                $hasAktif = (bool)preg_match('/\baktif\b/i', $withoutNonAktif);
                $hasBothStatus = $hasNonAktif && $hasAktif;

                $isCountWord = (bool)preg_match('/\b(berapa|jumlah|total|ada berapa)\b/i', $normalizedQ);
                $isNameWord = (bool)preg_match('/\b(siapa|siapa saja|sebutkan|tampilkan|daftar|nama|siapakah)\b/i', $normalizedQ);

                if ($hasBothStatus) {
                    $queryResults['intent'] = 'member_status_count';
                    $dynamicContext .= "\n\n=== DATA STATUS ANGGOTA (DATABASE) ===\n";
                    $dynamicContext .= "- Total Anggota: {$ms['total']} orang\n";
                    $dynamicContext .= "- Anggota Aktif: {$ms['aktif']} orang\n";
                    $dynamicContext .= "- Anggota Nonaktif: {$ms['nonaktif']} orang\n";
                } elseif ($hasNonAktif) {
                    $inactiveMembers = $this->getAllMembersDirectory(null, null, 'nonaktif');
                    $queryResults['inactive_members'] = $inactiveMembers;

                    if ($isCountWord && !$isNameWord) {
                        $queryResults['intent'] = 'member_inactive_count';
                        $dynamicContext .= "\n\n=== JUMLAH ANGGOTA NONAKTIF (DATABASE) ===\n";
                        $dynamicContext .= "Jumlah anggota nonaktif yang terdaftar di database adalah {$ms['nonaktif']} orang.\n";
                    } else {
                        $queryResults['intent'] = 'member_inactive_list';
                        $dynamicContext .= "\n\n=== DAFTAR ANGGOTA NONAKTIF DARI DATABASE ===\n";
                        if (!empty($inactiveMembers)) {
                            $dynamicContext .= "Terdapat " . count($inactiveMembers) . " anggota berstatus nonaktif:\n";
                            foreach ($inactiveMembers as $idx => $im) {
                                $num = $idx + 1;
                                $dynamicContext .= "{$num}. {$im['nama']} (Kelas: {$im['kelas']}, NIS: " . ($im['nis'] ?? '-') . ")\n";
                            }
                        } else {
                            $dynamicContext .= "Tidak ada anggota nonaktif yang ditemukan dalam database.\n";
                        }
                    }
                } elseif ($hasAktif) {
                    $activeMembers = $this->getAllMembersDirectory(null, null, 'aktif');
                    $queryResults['active_members'] = $activeMembers;

                    if ($isCountWord && !$isNameWord) {
                        $queryResults['intent'] = 'member_active_count';
                        $dynamicContext .= "\n\n=== JUMLAH ANGGOTA AKTIF (DATABASE) ===\n";
                        $dynamicContext .= "Jumlah anggota aktif yang terdaftar di database adalah {$ms['aktif']} orang.\n";
                    } else {
                        $queryResults['intent'] = 'member_active_list';
                        $dynamicContext .= "\n\n=== DAFTAR ANGGOTA AKTIF DARI DATABASE ===\n";
                        if (!empty($activeMembers)) {
                            $dynamicContext .= "Terdapat " . count($activeMembers) . " anggota berstatus aktif:\n";
                            foreach ($activeMembers as $idx => $am) {
                                $num = $idx + 1;
                                $dynamicContext .= "{$num}. {$am['nama']} (Kelas: {$am['kelas']}, NIS: " . ($am['nis'] ?? '-') . ")\n";
                            }
                        } else {
                            $dynamicContext .= "Tidak ada anggota aktif yang ditemukan dalam database.\n";
                        }
                    }
                } elseif ($isCountWord && !$isNameWord) {
                    $queryResults['intent'] = 'member_count';
                    $dynamicContext .= "\n\n=== DATA JUMLAH ANGGOTA (DATABASE) ===\n";
                    $dynamicContext .= "- Total Anggota Terdaftar: {$ms['total']} orang ({$ms['aktif']} aktif, {$ms['nonaktif']} nonaktif)\n";
                } else {
                    $queryResults['intent'] = 'member_list';
                    $dynamicContext .= "\n\n=== DAFTAR NAMA ANGGOTA AKTUAL DARI DATABASE ===\n";
                    foreach ($allMembers as $idx => $m) {
                        $num = $idx + 1;
                        $dynamicContext .= "{$num}. {$m['nama']} (Kelas: {$m['kelas']}, Status: {$m['status']})\n";
                    }
                    $dynamicContext .= "Total anggota terdaftar: " . count($allMembers) . " orang.\n";
                }
            }
        }

        // N. Tagihan & Pembayaran Umum (tanpa kata spesifik mahasiswa/anggota)
        elseif (preg_match('/\b(total\s+tunggakan|berapa\s+tunggakan|sisa\s+tunggakan)\b/i', $normalizedQ)) {
            $studentBilling = $this->getStudentBillingSummary();
            $queryResults['student_billing'] = $studentBilling;
            $queryResults['intent'] = 'student_arrears_total';
            $dynamicContext .= "\n\n=== TOTAL SISA TUNGGAKAN IURAN (DATABASE) ===\n";
            $dynamicContext .= "- Total Sisa Tunggakan: Rp " . number_format($studentBilling['total_tunggakan'], 2, ',', '.') . " (dari {$studentBilling['unpaid_count']} anggota menunggak).\n";
        } elseif (preg_match('/\b(total\s+tagihan|berapa\s+tagihan)\b/i', $normalizedQ)) {
            $studentBilling = $this->getStudentBillingSummary();
            $queryResults['student_billing'] = $studentBilling;
            $queryResults['intent'] = 'student_bills_total';
            $dynamicContext .= "\n\n=== TOTAL TAGIHAN IURAN SISTEM (DATABASE) ===\n";
            $dynamicContext .= "- Total Tagihan: Rp " . number_format($studentBilling['total_tagihan'], 2, ',', '.') . "\n";
        } elseif (preg_match('/\b(pembayaran\s+yang\s+sudah\s+diterima|total\s+pembayaran\s+masuk|pembayaran\s+sah)\b/i', $normalizedQ)) {
            $studentBilling = $this->getStudentBillingSummary();
            $queryResults['student_billing'] = $studentBilling;
            $queryResults['intent'] = 'student_paid_total';
            $dynamicContext .= "\n\n=== TOTAL PEMBAYARAN IURAN DITERIMA (DATABASE) ===\n";
            $dynamicContext .= "- Total Pembayaran Sah: Rp " . number_format($studentBilling['total_dibayar'], 2, ',', '.') . "\n";
        } elseif (
            preg_match('/\b(pembayaran|setoran)\s+.*(sudah|telah|ter|berhasil)\s*(verifikasi|diverifikasi)\b/i', $normalizedQ) ||
            preg_match('/\b(berapa|jumlah|total)\s+(pembayaran|setoran)\s+(yang\s+)?(sudah|telah|ter|berhasil)\s*(di)?verifikasi\b/i', $normalizedQ)
        ) {
            $verifSummary = $this->getVerificationSummary();
            $queryResults['intent'] = 'payment_verified_summary';
            $queryResults['verification_summary'] = $verifSummary;
            $dynamicContext .= "\n\n=== REKAPITULASI PEMBAYARAN TERVERIFIKASI (DATABASE) ===\n";
            $dynamicContext .= "- Total Pembayaran Terverifikasi (Berhasil): {$verifSummary['verified_count']} transaksi\n";
            $dynamicContext .= "- Total Nominal Pembayaran Terverifikasi: Rp " . number_format($verifSummary['verified_total'], 2, ',', '.') . "\n";
        } elseif (
            preg_match('/\b(pembayaran|setoran)\s+.*(belum|menunggu|antrean|antri)\s*(verifikasi|diverifikasi)\b/i', $normalizedQ) ||
            preg_match('/\b(berapa|jumlah|total)\s+(pembayaran|setoran)\s+(yang\s+)?(belum|menunggu|antrean)\s*(di)?verifikasi\b/i', $normalizedQ)
        ) {
            $verifSummary = $this->getVerificationSummary();
            $queryResults['intent'] = 'payment_pending_summary';
            $queryResults['verification_summary'] = $verifSummary;
            $dynamicContext .= "\n\n=== REKAPITULASI PEMBAYARAN MENUNGGU VERIFIKASI (DATABASE) ===\n";
            $dynamicContext .= "- Total Pembayaran Menunggu Verifikasi: {$verifSummary['pending_count']} transaksi\n";
            $dynamicContext .= "- Total Nominal Menunggu Verifikasi: Rp " . number_format($verifSummary['pending_total'], 2, ',', '.') . "\n";
        }

        // O. Unpaid members / menunggak general
        elseif (
            str_contains($normalizedQ, 'penunggak') ||
            (preg_match('/\b(siapa|daftar|ada siapa)\b/i', $normalizedQ) && preg_match('/\b(belum bayar|belum membayar|menunggak|tunggakan|belum lunas|penunggak)\b/i', $normalizedQ)) ||
            str_contains($normalizedQ, 'belum bayar') ||
            str_contains($normalizedQ, 'belum membayar') ||
            str_contains($normalizedQ, 'belum lunas') ||
            str_contains($normalizedQ, 'menunggak') ||
            str_contains($normalizedQ, 'tunggakan')
        ) {
            $queryResults['intent'] = 'unpaid_members';
            $unpaidList = $this->getUnpaidMembersList(25);
            $queryResults['unpaid_members'] = $unpaidList;

            $dynamicContext .= "\n\n=== DAFTAR AKTUAL ANGGOTA YANG BELUM MEMBAYAR / MENUNGGAK (DATABASE) ===\n";
            if (!empty($unpaidList)) {
                foreach ($unpaidList as $idx => $um) {
                    $num = $idx + 1;
                    $tagihanStr = number_format($um['tagihan'], 0, ',', '.');
                    $dibayarStr = number_format($um['dibayar'], 0, ',', '.');
                    $tunggakanStr = number_format($um['tunggakan'], 0, ',', '.');
                    $dynamicContext .= "{$num}. {$um['nama']} ({$um['kelas']}) — Periode: {$um['periode']} — Status: {$um['status']} (Tunggakan: Rp {$tunggakanStr})\n";
                }
            } else {
                $dynamicContext .= "Nihil: Semua anggota telah melunasi seluruh kewajiban iuran kas.\n";
            }
        }

        // P. Member directory / daftar siswa
        elseif (
            preg_match('/\b(data anggota|daftar anggota|data siswa|daftar siswa|anggota kelas|siswa kelas|siapa saja anggota)\b/i', $normalizedQ) ||
            preg_match('/\bkelas\s*(xii|xi|x)\b/i', $normalizedQ)
        ) {
            $queryResults['intent'] = 'member_info';
            preg_match('/(xii\s*rpl\s*\d+|xii\s*rpl\s*[a-z]+|xi\s*rpl\s*\d+|x\s*rpl\s*\d+|ti\s*reg\s*\w+)/i', $normalizedQ, $cm);
            $classFilter = !empty($cm[1]) ? trim($cm[1]) : null;
            $membersList = $this->getMembersList($classFilter, null, null, 25);
            $queryResults['members'] = $membersList;
            $classLabel = $classFilter ? "Kelas {$classFilter}" : "Semua Kelas";
            $dynamicContext .= "\n\n=== DATA ANGGOTA AKTUAL DARI DATABASE ({$classLabel}) ===\n";
            foreach ($membersList as $idx => $m) {
                $num = $idx + 1;
                $dynamicContext .= "{$num}. {$m['nama']} (No: {$m['nomor_anggota']}, NIS: {$m['nis']}, Kelas: {$m['kelas']}, Status: {$m['status']})\n";
            }
        }

        // Q. Global financial queries
        elseif ($isGlobalIncome) {
            $incomeBreakdown = $metrics['income_breakdown'] ?? $this->trxModel->getIncomeBreakdown();
            $queryResults['intent'] = 'global_income';
            $queryResults['income_breakdown'] = $incomeBreakdown;
            $dynamicContext .= "\n\n=== DATA TOTAL PEMASUKAN KAS DARI DATABASE ===\n";
            $dynamicContext .= "- Total Pemasukan: Rp " . number_format($incomeBreakdown['total'], 0, ',', '.') . "\n";
            $dynamicContext .= "1. Iuran: Rp " . number_format($incomeBreakdown['iuran'], 0, ',', '.') . "\n";
            $dynamicContext .= "2. Donasi: Rp " . number_format($incomeBreakdown['donasi'], 0, ',', '.') . "\n";
            $dynamicContext .= "3. Sumbangan: Rp " . number_format($incomeBreakdown['sumbangan'], 0, ',', '.') . "\n";
        } elseif ($isGlobalExpense) {
            $queryResults['intent'] = 'global_expense';
        } elseif ($isGlobalBalance) {
            $queryResults['intent'] = 'current_balance';
        } elseif ($isGlobalTransactions) {
            $queryResults['intent'] = 'total_all_transactions';
        } elseif (preg_match('/\b(kondisi\s+keuangan|analisis\s+keuangan|ringkasan\s+keuangan|keuangan\s+bulan\s+ini|bagaimana\s+keuangan|laporan\s+analisis)\b/i', $normalizedQ)) {
            $queryResults['intent'] = 'financial_summary';
        }

        // RBAC Enforcement for member role
        if ($isMemberRole) {
            $restrictedIntents = [
                'member_list', 'student_list', 'student_paid_list', 'student_unpaid_list', 'role_users',
                'member_status_count', 'member_active_count', 'member_inactive_count', 'member_active_list', 'member_inactive_list',
                'payment_verified_summary', 'payment_pending_summary'
            ];
            if (in_array($queryResults['intent'], $restrictedIntents, true)) {
                $queryResults['intent'] = 'member_forbidden';
                $metrics['query_results'] = $queryResults;
                $metrics['current_context'] = $currentContext;
                return ['metrics' => $metrics, 'context' => $baseContext];
            }
        }

        $metrics['query_results'] = $queryResults;
        $metrics['current_context'] = $currentContext;

        if ($queryResults['intent'] !== 'general' && $queryResults['intent'] !== 'financial_summary') {
            $completeContext = trim($dynamicContext) . "\n\n" . $baseContext;
        } else {
            $completeContext = $baseContext . $dynamicContext;
        }

        return [
            'metrics' => $metrics,
            'context' => $completeContext,
        ];
    }

    /**
     * Extract YYYY-MM from user question string if specified
     */
    private function extractMonthYear(string $text): ?string {
        $monthMap = [
            'januari'   => '01', 'jan' => '01',
            'februari'  => '02', 'feb' => '02',
            'maret'     => '03', 'mar' => '03',
            'april'     => '04', 'apr' => '04',
            'mei'       => '05',
            'juni'      => '06', 'jun' => '06',
            'juli'      => '07', 'jul' => '07',
            'agustus'   => '08', 'agu' => '08', 'agt' => '08',
            'september' => '09', 'sep' => '09',
            'oktober'   => '10', 'okt' => '10',
            'november'  => '11', 'nov' => '11',
            'desember'  => '12', 'des' => '12',
        ];

        // Match YYYY-MM directly
        if (preg_match('/\b(20\d{2})-(0[1-9]|1[0-2])\b/', $text, $m)) {
            return $m[1] . '-' . $m[2];
        }

        // Match month name and year (e.g. September 2026)
        foreach ($monthMap as $mName => $mNum) {
            if (preg_match('/\b' . $mName . '\s*(20\d{2})?\b/i', $text, $match)) {
                $year = !empty($match[1]) ? $match[1] : '2026';
                return $year . '-' . $mNum;
            }
        }

        // Match "bulan ini"
        if (str_contains($text, 'bulan ini')) {
            return '2026-10'; // Project active timeline / current date
        }

        // Match "bulan lalu"
        if (str_contains($text, 'bulan lalu') || str_contains($text, 'bulan kemarin')) {
            return '2026-09';
        }

        return null;
    }

    /**
     * Format financial metrics into a verified factual context prompt for AI
     */
    public function getFactualContextString(?string $role = null, ?int $userId = null, ?array $memberData = null, ?array $userAccount = null): string {
        $data = $this->getFinancialMetrics($role, $userId, $memberData, $userAccount);
        $activeRole = strtolower((string)($data['user_role'] ?? 'super_user'));

        // Format khusus untuk role Anggota / Member
        if (in_array($activeRole, ['member', 'anggota'], true)) {
            $lastPaymentDesc = "Belum ada catatan setoran pembayaran.";
            if (!empty($data['member_last_payment'])) {
                $lp = $data['member_last_payment'];
                $tglStr = $lp['tanggal'] ? date('d M Y H:i', strtotime($lp['tanggal'])) : '-';
                $nomStr = number_format($lp['nominal'], 2, ',', '.');
                $lastPaymentDesc = "Tanggal {$tglStr}, Nominal Rp {$nomStr}, Metode {$lp['metode']}, Status Verifikasi: {$lp['status_label']}";
            }

            $memberNameDisplay = !empty($data['member_name']) ? $data['member_name'] : (!empty($data['user_name']) ? $data['user_name'] : (!empty($data['user_username']) ? $data['user_username'] : 'Anggota'));
            $memberUsernameDisplay = !empty($data['user_username']) ? $data['user_username'] : '-';
            return <<<MEMBER_DATA
FAKTA KEUANGAN KAS TERVERIFIKASI (SUMBER DATABASE RIIL):
DATA PENGGUNA AKTIF (IDENTITAS SESI LOGIN TERVERIFIKASI):
- Role / Jabatan: Anggota (Member)
- Nama Lengkap: {$memberNameDisplay}
- Username Akun: {$memberUsernameDisplay}
- NIS: {$data['member_nis']}
- Batasan Hak Akses: Anggota HANYA berhak mengakses informasi tagihan dan riwayat pembayaran miliknya sendiri. Anggota TIDAK memiliki hak akses untuk melihat data keuangan pribadi anggota lain atau fitur administratif sistem (manajemen kategori, edit transaksi, dll.).

STATUS KEUANGAN & TAGIHAN PRIBADI ANGGOTA:
- Total Kewajiban Tagihan: Rp {$this->formatRp($data['member_tagihan'])}
- Total Telah Disetor: Rp {$this->formatRp($data['member_dibayar'])}
- Sisa Tunggakan: Rp {$this->formatRp($data['member_tunggakan'])}
- Status Iuran Saat Ini: {$data['member_status']}
- Setoran Pembayaran Terakhir: {$lastPaymentDesc}

RINGKASAN KAS UMUM (INFORMASI AGREGAT TERBATAS):
- Kondisi Kas: {$data['cash_condition']}
- Saldo Kas Bersih: Rp {$this->formatRp($data['current_balance'])}
MEMBER_DATA;
        }

        $topCatStr = "";
        if (!empty($data['top_expense_categories'])) {
            foreach ($data['top_expense_categories'] as $c) {
                $topCatStr .= "  - {$c['name']}: Rp " . number_format($c['nominal'], 0, ',', '.') . " ({$c['percentage']}% dari total pengeluaran)\n";
            }
        } else {
            $topCatStr = "  - Belum ada pengeluaran kas yang tercatat.\n";
        }

        $trendStr = "";
        if (!empty($data['trend']['labels'])) {
            foreach ($data['trend']['labels'] as $idx => $label) {
                $in = $data['trend']['income'][$idx] ?? 0;
                $out = $data['trend']['expense'][$idx] ?? 0;
                $trendStr .= "  - {$label}: Masuk Rp " . number_format($in, 0, ',', '.') . ", Keluar Rp " . number_format($out, 0, ',', '.') . "\n";
            }
        } else {
            $trendStr = "  - Belum ada data riwayat trend bulanan.\n";
        }

        // Rincian transaksi pengeluaran aktual
        $expensesStr = "";
        if (!empty($data['recent_expenses'])) {
            foreach ($data['recent_expenses'] as $idx => $ex) {
                $num = $idx + 1;
                $tgl = date('d M Y', strtotime($ex['tanggal']));
                $nom = number_format($ex['nominal'], 0, ',', '.');
                $expensesStr .= "  {$num}. {$tgl}: {$ex['kategori']} — Rp {$nom} (Keterangan: \"{$ex['keterangan']}\" | Kode: {$ex['kode_transaksi']})\n";
            }
        } else {
            $expensesStr = "  - Belum ada catatan transaksi pengeluaran.\n";
        }

        // Rincian anggota belum bayar / tunggakan
        $unpaidStr = "";
        if (!empty($data['unpaid_members_list'])) {
            foreach ($data['unpaid_members_list'] as $idx => $um) {
                $num = $idx + 1;
                $tunggakanNom = number_format($um['tunggakan'], 0, ',', '.');
                $unpaidStr .= "  {$num}. {$um['nama']} ({$um['kelas']}) — Periode {$um['periode']} — Status: {$um['status']} (Tunggakan: Rp {$tunggakanNom})\n";
            }
        } else {
            $unpaidStr = "  - Seluruh anggota telah melunasi tagihan iuran kas.\n";
        }

        // Ringkasan anggota
        $membersSummaryStr = "";
        if (!empty($data['members_summary'])) {
            $ms = $data['members_summary'];
            $classBreakdown = [];
            foreach ($ms['classes'] as $cls => $jml) {
                $classBreakdown[] = "{$cls} ({$jml} siswa)";
            }
            $membersSummaryStr = "  - Total Anggota: {$ms['total']} siswa ({$ms['aktif']} aktif, {$ms['nonaktif']} nonaktif)\n" .
                                 "  - Distribusi Kelas: " . implode(', ', $classBreakdown) . "\n";
        }

        $adminNameDisplay    = !empty($data['user_name']) ? $data['user_name'] : (!empty($data['user_username']) ? $data['user_username'] : 'Pengguna');
        $adminUsernameDisplay = !empty($data['user_username']) ? $data['user_username'] : '-';
        $roleHeader = $activeRole === 'bendahara'
            ? "DATA PENGGUNA AKTIF (IDENTITAS SESI LOGIN TERVERIFIKASI):\n- Role / Jabatan: Bendahara\n- Nama Lengkap: {$adminNameDisplay}\n- Username Akun: {$adminUsernameDisplay}\n- Hak Akses: Pengelolaan keuangan kas (Transaksi kas, verifikasi setoran anggota, rekonsiliasi kas, penyusunan laporan keuangan).\n- Antrean Verifikasi Pembayaran: {$data['pending_verification_count']} transaksi menunggu verifikasi."
            : "DATA PENGGUNA AKTIF (IDENTITAS SESI LOGIN TERVERIFIKASI):\n- Role / Jabatan: Admin / Super User\n- Nama Lengkap: {$adminNameDisplay}\n- Username Akun: {$adminUsernameDisplay}\n- Hak Akses: Pengelolaan sistem secara menyeluruh (Pengguna, anggota, kategori, transaksi, verifikasi pembayaran, laporan, audit log, WhatsApp gateway, pengaturan).\n- Antrean Verifikasi Pembayaran: {$data['pending_verification_count']} transaksi menunggu verifikasi.";

        $iuranFmt     = $this->formatRp((float)($data['income_breakdown']['iuran'] ?? 0));
        $donasiFmt    = $this->formatRp((float)($data['income_breakdown']['donasi'] ?? 0));
        $sumbanganFmt = $this->formatRp((float)($data['income_breakdown']['sumbangan'] ?? 0));
        $iuranCnt     = (int)($data['income_breakdown']['counts']['iuran'] ?? 0);
        $donasiCnt    = (int)($data['income_breakdown']['counts']['donasi'] ?? 0);
        $sumbanganCnt = (int)($data['income_breakdown']['counts']['sumbangan'] ?? 0);

        return <<<DATA
FAKTA KEUANGAN KAS TERVERIFIKASI (SUMBER DATABASE RIIL):
{$roleHeader}

1. Ringkasan Kas:
   - Total Pemasukan: Rp {$this->formatRp($data['total_income'])}
     * Rincian Sumber Pemasukan:
       - Iuran: Rp {$iuranFmt} ({$iuranCnt} transaksi)
       - Donasi: Rp {$donasiFmt} ({$donasiCnt} transaksi)
       - Sumbangan: Rp {$sumbanganFmt} ({$sumbanganCnt} transaksi)
   - Total Pengeluaran: Rp {$this->formatRp($data['total_expense'])}
   - Saldo Kas Bersih: Rp {$this->formatRp($data['current_balance'])}
   - Kondisi Kas: {$data['cash_condition']}

2. Kategori Pengeluaran Terbesar:
{$topCatStr}
3. Trend Bulanan Kas:
{$trendStr}
4. Status Pembayaran Iuran Anggota:
   - Total Peserta Didik: {$data['total_members']} siswa
   - Jumlah Anggota Belum Membayar: {$data['unpaid_members_count']} siswa
   - Jumlah Anggota Pembayaran Sebagian: {$data['partial_members_count']} siswa
   - Jumlah Anggota Lunas: {$data['paid_members_count']} siswa
   - Total Nilai Tagihan: Rp {$this->formatRp($data['total_tagihan'])}
   - Total Iuran Masuk: Rp {$this->formatRp($data['total_dibayar'])}
   - Total Sisa Tunggakan: Rp {$this->formatRp($data['total_tunggakan'])}

5. Rincian Pengeluaran Kas Aktual (Tujuan & Keterangan Transaksi):
{$expensesStr}
6. Daftar Anggota yang Memiliki Tunggakan Iuran Kas:
{$unpaidStr}
7. Direktori Anggota:
{$membersSummaryStr}
DATA;
    }

    private function formatRp(float $number): string {
        return number_format($number, 2, ',', '.');
    }
}
