<?php
/**
 * Member Controller (Pengguna/Anggota)
 * Scoped strictly to the logged-in member's personal data.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentSetting;
use App\Models\AuditLog;
use App\Models\WhatsAppLog;
use App\Services\PaymentGatewayService;

class MemberController extends Controller {
    private Member $memberModel;
    private Payment $paymentModel;
    private PaymentTransaction $payTrxModel;
    private PaymentSetting $paySettingModel;
    private PaymentGatewayService $gatewayService;
    private AuditLog $auditModel;
    private WhatsAppLog $logModel;

    public function __construct() {
        // Enforce RBAC: Member role only
        Auth::requireMember();

        $this->memberModel = new Member();
        $this->paymentModel = new Payment();
        $this->payTrxModel = new PaymentTransaction();
        $this->paySettingModel = new PaymentSetting();
        $this->gatewayService = new PaymentGatewayService($this->paySettingModel);
        $this->auditModel = new AuditLog();
        $this->logModel = new WhatsAppLog();
    }

    /**
     * Get the authenticated member profile or abort
     */
    private function getCurrentMember(): array {
        $userId = Auth::id();
        $member = $this->memberModel->findByUserId($userId);

        if (!$member) {
            Auth::render403('Profil data anggota Anda tidak ditemukan pada sistem.');
        }

        return $member;
    }

    /**
     * Member Personal Dashboard
     */
    public function dashboard(): void {
        $member = $this->getCurrentMember();
        $payments = $this->paymentModel->getMemberHistory((int)$member['id']);

        // Calculate personal payment stats & summaries
        $totalTagihan = 0.0;
        $totalPaid = 0.0;
        $totalSisa = 0.0;
        $unpaidCount = 0;
        $partialCount = 0;
        $lunasCount = 0;

        foreach ($payments as $p) {
            $tagihan = (float)($p['tagihan'] ?? 50000.00);
            $nominal = (float)$p['nominal'];
            $sisa = (float)($p['sisa'] ?? max(0.0, $tagihan - $nominal));

            $totalTagihan += $tagihan;
            $totalPaid += $nominal;
            $totalSisa += $sisa;

            if ($p['status'] === Payment::STATUS_LUNAS) {
                $lunasCount++;
            } elseif ($p['status'] === Payment::STATUS_SEBAGIAN) {
                $partialCount++;
            } else {
                $unpaidCount++;
            }
        }

        // Active/latest payment period
        $activePayment = !empty($payments) ? $payments[0] : null;

        // Fetch notifications for the member
        $unreadNotifCount = $this->logModel->getUnreadCountByMember((int)$member['id']);
        $recentNotifs = $this->logModel->getMemberNotifications((int)$member['id'], [], 1, 3);

        $this->view('member.dashboard', [
            'title'               => 'Dashboard Anggota — ' . APP_NAME,
            'member'              => $member,
            'activePayment'       => $activePayment,
            'recentPayments'      => array_slice($payments, 0, 5),
            'payments'            => $payments,
            'totalTagihan'        => $totalTagihan,
            'totalPaid'           => $totalPaid,
            'totalSisa'           => $totalSisa,
            'lunasCount'          => $lunasCount,
            'partialCount'        => $partialCount,
            'unpaidCount'         => $unpaidCount,
            'unreadNotifCount'    => $unreadNotifCount,
            'recentNotifications' => $recentNotifs['data'],
        ]);
    }

    /**
     * Member Personal Profile
     */
    public function profile(): void {
        $member = $this->getCurrentMember();

        if ($this->isPost()) {
            if (!$this->validateCsrf()) {
                Session::setFlash('danger', 'Sesi keamanan tidak valid. Silakan coba lagi.');
                $this->redirect('member/profile');
                return;
            }

            $phone = trim((string)$this->post('no_hp', ''));
            $oldData = $member;

            $updated = $this->memberModel->update((int)$member['id'], [
                'no_hp' => $phone,
            ]);

            if ($updated) {
                Auth::refreshMemberProfile();
                $this->auditModel->record(
                    Auth::id(),
                    'UPDATE_PROFILE',
                    'members',
                    (int)$member['id'],
                    $oldData,
                    ['no_hp' => $phone]
                );
                Session::setFlash('success', 'Profil Anda berhasil diperbarui.');
            } else {
                Session::setFlash('danger', 'Gagal memperbarui profil.');
            }

            $this->redirect('member/profile');
            return;
        }

        $this->view('member.profile', [
            'title'  => 'Profil Anggota — ' . APP_NAME,
            'member' => $member,
            'user'   => Auth::user(),
        ]);
    }

    /**
     * Member Personal Payments List & Bills
     */
    public function payments(): void {
        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];
        $payments = $this->paymentModel->getMemberHistory($memberId);

        $totalTagihan = 0.0;
        $totalPaid = 0.0;
        $totalTunggakan = 0.0;
        $activeBills = [];

        foreach ($payments as $p) {
            $tagihan = (float)($p['tagihan'] ?? 50000.00);
            $nominal = (float)$p['nominal'];
            $sisa = (float)($p['sisa'] ?? max(0.0, $tagihan - $nominal));

            $totalTagihan += $tagihan;
            $totalPaid += $nominal;
            $totalTunggakan += $sisa;

            if ($sisa > 0 && $p['status'] !== Payment::STATUS_LUNAS) {
                $activeBills[] = $p;
            }
        }

        // Submissions history for this member
        $submissions = $this->payTrxModel->getMemberHistory($memberId);

        // Bank Accounts & Gateway configuration
        $bankAccounts = $this->paySettingModel->getBankAccounts();
        $gatewayConfig = $this->paySettingModel->getGatewayConfig();

        $this->view('member.payments', [
            'title'          => 'Tagihan & Status Pembayaran Kas — ' . APP_NAME,
            'member'         => $member,
            'payments'       => $payments,
            'activeBills'    => $activeBills,
            'submissions'    => $submissions,
            'bankAccounts'   => $bankAccounts,
            'gatewayConfig'  => $gatewayConfig,
            'totalTagihan'   => $totalTagihan,
            'totalPaid'      => $totalPaid,
            'totalTunggakan' => $totalTunggakan,
        ]);
    }

    /**
     * Member Submit Payment (Transfer Bank, Tunai, QRIS, E-Wallet)
     */
    public function payBill(): void {
        if (!$this->isPost()) {
            $this->redirect('member/payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('member/payments');
            return;
        }

        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];

        $paymentId = (int)$this->post('payment_id', 0);
        $metode = trim((string)$this->post('metode', PaymentTransaction::METHOD_TRANSFER_BANK));
        $channel = trim((string)$this->post('channel', ''));
        $nominalRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('nominal', '0'));
        $catatan = trim((string)$this->post('catatan', ''));

        // 1. Validate payment bill existence & ownership
        $bill = $this->paymentModel->find($paymentId);
        if (!$bill || (int)$bill['member_id'] !== $memberId) {
            Session::setFlash('danger', 'Data tagihan kas tidak valid atau bukan milik Anda.');
            $this->redirect('member/payments');
            return;
        }

        $tagihan = (float)($bill['tagihan'] ?? 50000.00);
        $totalPaid = (float)$bill['nominal'];
        $sisa = max(0.0, $tagihan - $totalPaid);

        if ($sisa <= 0.0 || $bill['status'] === Payment::STATUS_LUNAS) {
            Session::setFlash('warning', "Tagihan periode [{$bill['periode']}] sudah lunas. Tidak diperlukan pembayaran lagi.");
            $this->redirect('member/payments');
            return;
        }

        // Prevent duplicate pending submission for the same bill
        $pendingTrx = $this->payTrxModel->fetchOne(
            "SELECT id, kode_transaksi FROM payment_transactions WHERE payment_id = :pid AND status = :st LIMIT 1",
            ['pid' => $paymentId, 'st' => PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI]
        );
        if ($pendingTrx) {
            Session::setFlash('warning', "Tagihan periode [{$bill['periode']}] sedang memiliki pengajuan pembayaran yang menunggu verifikasi Bendahara ({$pendingTrx['kode_transaksi']}). Harap tunggu proses verifikasi selesai.");
            $this->redirect('member/payments');
            return;
        }

        // 2. Validate nominal
        if (!is_numeric($nominalRaw)) {
            Session::setFlash('danger', 'Nominal pembayaran tidak valid.');
            $this->redirect('member/payments');
            return;
        }

        $nominal = (float)$nominalRaw;
        if ($nominal <= 0.0) {
            Session::setFlash('danger', 'Nominal pembayaran harus lebih besar dari Rp 0.');
            $this->redirect('member/payments');
            return;
        }

        if ($nominal > $sisa) {
            Session::setFlash('danger', 'Nominal pembayaran (Rp ' . number_format($nominal, 0, ',', '.') . ') melebihi sisa tunggakan tagihan (Rp ' . number_format($sisa, 0, ',', '.') . ').');
            $this->redirect('member/payments');
            return;
        }

        // 3. Process according to method
        $buktiFile = null;
        $status = PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI;

        if ($metode === PaymentTransaction::METHOD_TRANSFER_BANK) {
            if (empty($channel)) {
                Session::setFlash('danger', 'Silakan pilih rekening bank tujuan transfer.');
                $this->redirect('member/payments');
                return;
            }

            // Validate uploaded file
            if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_NO_FILE) {
                Session::setFlash('danger', 'Bukti transfer wajib diunggah untuk metode Transfer Bank.');
                $this->redirect('member/payments');
                return;
            }

            $file = $_FILES['bukti_transfer'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                Session::setFlash('danger', 'Gagal mengunggah berkas bukti transfer (Kode Error: ' . $file['error'] . ').');
                $this->redirect('member/payments');
                return;
            }

            // File size validation (max 2MB)
            $maxBytes = 2 * 1024 * 1024;
            if ($file['size'] > $maxBytes) {
                Session::setFlash('danger', 'Ukuran berkas bukti transfer terlalu besar (Maksimal 2 MB).');
                $this->redirect('member/payments');
                return;
            }

            // File extension validation
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (!in_array($ext, $allowedExts, true)) {
                Session::setFlash('danger', 'Format berkas bukti transfer tidak didukung. Harap gunakan format JPG, PNG, WEBP, atau PDF.');
                $this->redirect('member/payments');
                return;
            }

            // MIME type check
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (!in_array($mime, $allowedMimes, true)) {
                Session::setFlash('danger', 'Tipe MIME berkas bukti transfer tidak valid.');
                $this->redirect('member/payments');
                return;
            }

            $uploadDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payments';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                Session::setFlash('danger', 'Gagal menyimpan berkas bukti transfer ke server.');
                $this->redirect('member/payments');
                return;
            }

            $buktiFile = $newFileName;

        } elseif ($metode === PaymentTransaction::METHOD_TUNAI) {
            $channel = 'Kas Tunai';
            $status = PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI;

        } elseif ($metode === PaymentTransaction::METHOD_QRIS) {
            if (!$this->gatewayService->isQrisAvailable()) {
                Session::setFlash('warning', 'Metode pembayaran QRIS belum aktif pada sistem. Kredensial payment gateway resmi belum dikonfigurasi oleh Administrator.');
                $this->redirect('member/payments');
                return;
            }
        } elseif ($metode === PaymentTransaction::METHOD_EWALLET) {
            if (!$this->gatewayService->isEwalletAvailable()) {
                Session::setFlash('warning', 'Metode pembayaran E-Wallet belum aktif pada sistem. Kredensial payment gateway resmi belum dikonfigurasi oleh Administrator.');
                $this->redirect('member/payments');
                return;
            }
        } else {
            Session::setFlash('danger', 'Metode pembayaran yang dipilih tidak valid.');
            $this->redirect('member/payments');
            return;
        }

        // 4. Create submission in payment_transactions
        $kodeTrx = $this->payTrxModel->generateKode();
        $createdId = $this->payTrxModel->createSubmission([
            'payment_id'     => $paymentId,
            'member_id'      => $memberId,
            'kode_transaksi' => $kodeTrx,
            'metode'         => $metode,
            'channel'        => $channel,
            'nominal'        => $nominal,
            'status'         => $status,
            'bukti_transfer' => $buktiFile,
            'catatan'        => $catatan,
        ]);

        if ($createdId) {
            $this->auditModel->record(
                Auth::id(),
                'SUBMIT_PAYMENT',
                'payment_transactions',
                $createdId,
                null,
                [
                    'kode_transaksi' => $kodeTrx,
                    'periode'        => $bill['periode'],
                    'metode'         => $metode,
                    'nominal'        => $nominal,
                    'status'         => $status,
                ]
            );

            $methodName = PaymentTransaction::getMethodLabel($metode);
            Session::setFlash('success', "✓ Pengajuan pembayaran iuran kas sebesar Rp " . number_format($nominal, 0, ',', '.') . " via {$methodName} berhasil dikirim! Menunggu verifikasi oleh Bendahara.");
        } else {
            Session::setFlash('danger', 'Gagal memproses pengajuan pembayaran kas.');
        }

        $this->redirect('member/payments');
    }

    /**
     * Get Submission JSON for AJAX Detail Modal
     */
    public function submissionDetail(): void {
        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];
        $id = (int)$this->get('id', 0);

        $detail = $this->payTrxModel->getDetailWithRelations($id);
        if (!$detail || (int)$detail['member_id'] !== $memberId) {
            $this->json(['status' => 'error', 'message' => 'Data pengajuan pembayaran tidak ditemukan atau bukan milik Anda.'], 403);
            return;
        }

        $this->json(['status' => 'success', 'data' => $detail]);
    }


    /**
     * Member Personal Payment History
     */
    public function history(): void {
        $member = $this->getCurrentMember();
        $payments = $this->paymentModel->getMemberHistory((int)$member['id']);

        $totalPaid = 0.0;
        foreach ($payments as $p) {
            $totalPaid += (float)$p['nominal'];
        }

        $this->view('member.history', [
            'title'     => 'Histori Pembayaran Kas — ' . APP_NAME,
            'member'    => $member,
            'payments'  => $payments,
            'totalPaid' => $totalPaid,
        ]);
    }

    /**
     * Member Personal Notifications List
     */
    public function notifications(): void {
        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];

        $search = trim((string)$this->get('search', ''));
        $readStatus = trim((string)$this->get('read_status', ''));
        $type = trim((string)$this->get('notification_type', ''));
        $status = trim((string)$this->get('status', ''));
        $dateStart = trim((string)$this->get('date_start', ''));
        $dateEnd = trim((string)$this->get('date_end', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 10;

        $filters = [
            'search'            => $search,
            'read_status'       => $readStatus,
            'notification_type' => $type,
            'status'            => $status,
            'date_start'        => $dateStart,
            'date_end'          => $dateEnd,
        ];

        $paginated = $this->logModel->getMemberNotifications($memberId, $filters, $page, $perPage);
        $unreadCount = $this->logModel->getUnreadCountByMember($memberId);

        $this->view('member.notifications', [
            'title'         => 'Notifikasi & Pesan Saya — ' . APP_NAME,
            'member'        => $member,
            'notifications' => $paginated['data'],
            'total'         => $paginated['total'],
            'page'          => $paginated['page'],
            'perPage'       => $paginated['perPage'],
            'totalPages'    => $paginated['totalPages'],
            'unreadCount'   => $unreadCount,
            'filters'       => $filters,
        ]);
    }

    /**
     * Alias for notifications (Indonesian)
     */
    public function notifikasi(): void {
        $this->notifications();
    }

    /**
     * View Notification Detail
     * STRICT SERVER-SIDE AUTHORIZATION: Rejects access if message belongs to another member
     */
    public function notificationDetail(mixed $id = null): void {
        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];
        $logId = (int)$id;

        if ($logId <= 0) {
            $this->redirect('member/notifications');
            return;
        }

        // 1. Fetch raw log first to detect cross-member access attempts
        $rawLog = $this->logModel->find($logId);
        if (!$rawLog) {
            Session::setFlash('warning', 'Pesan tidak ditemukan pada sistem.');
            $this->redirect('member/notifications');
            return;
        }

        // 2. Strict server-side verification: Member A can never access Member B's notification
        if ((int)$rawLog['member_id'] !== $memberId) {
            Auth::render403("Akses Ditolak: Anda tidak memiliki izin untuk melihat pesan anggota lain.");
            return;
        }

        // 3. Mark as read upon viewing
        if (empty($rawLog['is_read'])) {
            $this->logModel->markAsRead($logId, $memberId);
            $rawLog['is_read'] = 1;
            $rawLog['read_at'] = date('Y-m-d H:i:s');
        }

        $rawLog['title'] = WhatsAppLog::formatTitle($rawLog['notification_type']);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (isset($_GET['format']) && $_GET['format'] === 'json');

        if ($isAjax) {
            $this->json([
                'success' => true,
                'data'    => $rawLog,
            ]);
            return;
        }

        $this->view('member.notification_detail', [
            'title'        => $rawLog['title'] . ' — ' . APP_NAME,
            'member'       => $member,
            'notification' => $rawLog,
        ]);
    }

    /**
     * Mark Notification as Read
     * STRICT SERVER-SIDE AUTHORIZATION
     */
    public function markNotificationRead(mixed $id = null): void {
        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];
        $logId = (int)($id ?? $this->post('id', 0));

        if ($logId <= 0) {
            $this->redirect('member/notifications');
            return;
        }

        $rawLog = $this->logModel->find($logId);
        if (!$rawLog || (int)$rawLog['member_id'] !== $memberId) {
            Auth::render403("Akses Ditolak: Anda tidak memiliki izin untuk mengubah status pesan ini.");
            return;
        }

        $this->logModel->markAsRead($logId, $memberId);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_GET['format']) && $_GET['format'] === 'json')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'Pesan berhasil ditandai sudah dibaca.',
            ]);
            return;
        }

        Session::setFlash('success', 'Pesan berhasil ditandai sudah dibaca.');
        $this->redirect('member/notifications');
    }

    /**
     * Mark all unread notifications as read for current member
     */
    public function markAllNotificationsRead(): void {
        $member = $this->getCurrentMember();
        $memberId = (int)$member['id'];

        $this->logModel->markAllAsRead($memberId);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_GET['format']) && $_GET['format'] === 'json');

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'Semua pesan berhasil ditandai sudah dibaca.',
            ]);
            return;
        }

        Session::setFlash('success', 'Semua pesan berhasil ditandai sudah dibaca.');
        $this->redirect('member/notifications');
    }
}
