<?php
/**
 * Payment Controller (Manajemen Pembayaran Kas Peserta Didik)
 * Super User Only — Handles Payment Records, Verifications, and Tunggakan Monitoring
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentSetting;
use App\Models\Member;
use App\Models\AuditLog;
use App\Models\WhatsAppSetting;
use App\Services\WhatsAppService;
use App\Services\PaymentGatewayService;

class PaymentController extends Controller {
    private Payment $paymentModel;
    private PaymentTransaction $payTrxModel;
    private PaymentSetting $paySettingModel;
    private PaymentGatewayService $gatewayService;
    private Member $memberModel;
    private AuditLog $auditModel;
    private WhatsAppService $waService;
    private WhatsAppSetting $waSetting;

    public function __construct() {
        // Enforce RBAC: Super User and Bendahara have access
        Auth::requireRole(Auth::ROLE_SUPER_USER, Auth::ROLE_BENDAHARA);

        $this->paymentModel = new Payment();
        $this->payTrxModel = new PaymentTransaction();
        $this->paySettingModel = new PaymentSetting();
        $this->gatewayService = new PaymentGatewayService($this->paySettingModel);
        $this->memberModel = new Member();
        $this->auditModel = new AuditLog();
        $this->waSetting = new WhatsAppSetting();
        $this->waService = new WhatsAppService($this->waSetting);
    }

    /**
     * View All Payments, Filter Periode, Kelas, Status, and View Tunggakan
     */
    public function index(): void {
        $search = trim((string)$this->get('search', ''));
        $periode = trim((string)$this->get('periode', ''));
        $kelas = trim((string)$this->get('kelas', ''));
        $status = trim((string)$this->get('status', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 10;

        $filters = [
            'search'  => $search,
            'periode' => $periode,
            'kelas'   => $kelas,
            'status'  => $status,
        ];

        // 1. Paginated Payments
        $paginated = $this->paymentModel->getPaginated($filters, $page, $perPage);

        // 2. Summary & Tunggakan Stats
        $summary = $this->paymentModel->getSummaryStats($filters);

        // 3. Distinct filters options
        $classes = $this->memberModel->getDistinctClasses();
        $periods = $this->paymentModel->getDistinctPeriods();
        $members = $this->memberModel->all('nama ASC');

        // 4. Tabs & Payment Transactions Data
        $activeTab = trim((string)$this->get('tab', 'tagihan'));
        if (!in_array($activeTab, ['tagihan', 'verifikasi', 'transaksi', 'pengaturan'], true)) {
            $activeTab = 'tagihan';
        }

        $pendingCount = $this->payTrxModel->getPendingCount();
        $pendingSubmissions = $this->payTrxModel->getPaginated([
            'status' => PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI,
            'search' => $search,
        ], max(1, (int)$this->get('pending_page', 1)), 15);

        $allTransactions = $this->payTrxModel->getPaginated([
            'search' => $search,
            'status' => $this->get('trx_status', ''),
            'metode' => $this->get('trx_metode', ''),
        ], max(1, (int)$this->get('trx_page', 1)), 15);

        $bankAccounts = $this->paySettingModel->getBankAccounts();
        $gatewayConfig = $this->paySettingModel->getGatewayConfig();

        $this->view('payments.index', [
            'title'              => 'Manajemen Pembayaran Kas — ' . APP_NAME,
            'payments'           => $paginated['data'],
            'total'              => $paginated['total'],
            'page'               => $paginated['page'],
            'perPage'            => $paginated['perPage'],
            'totalPages'         => $paginated['totalPages'],
            'summary'            => $summary,
            'classes'            => $classes,
            'periods'            => $periods,
            'members'            => $members,
            'filters'            => $filters,
            'activeTab'          => $activeTab,
            'pendingCount'       => $pendingCount,
            'pendingSubmissions' => $pendingSubmissions,
            'allTransactions'    => $allTransactions,
            'bankAccounts'       => $bankAccounts,
            'gatewayConfig'      => $gatewayConfig,
            'todayDate'          => date('Y-m-d'),
        ]);
    }

    /**
     * Record a New Cash Payment
     */
    public function create(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('payments');
            return;
        }

        $memberId = (int)$this->post('member_id', 0);
        $periode = trim((string)$this->post('periode', ''));
        $tanggal = trim((string)$this->post('tanggal_pembayaran', date('Y-m-d')));
        $tagihanRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('tagihan', '50000'));
        $nominalRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('nominal', '0'));
        $catatan = trim((string)$this->post('catatan', ''));
        $isVerified = (bool)$this->post('auto_verify', true);

        $tagihan = (float)$tagihanRaw;
        $nominal = (float)$nominalRaw;

        // Validations
        if ($memberId <= 0) {
            Session::setFlash('danger', 'Peserta didik / anggota wajib dipilih.');
            $this->redirect('payments');
            return;
        }

        $member = $this->memberModel->find($memberId);
        if (!$member) {
            Session::setFlash('danger', 'Data anggota tidak ditemukan.');
            $this->redirect('payments');
            return;
        }

        if ($periode === '') {
            Session::setFlash('danger', 'Periode tagihan/pembayaran kas wajib diisi.');
            $this->redirect('payments');
            return;
        }

        if (!is_numeric($tagihanRaw) || $tagihan <= 0 || $tagihan > 100000000.0) {
            Session::setFlash('danger', 'Nilai tagihan harus bernilai numerik positif lebih besar dari 0 (maksimal Rp 100.000.000).');
            $this->redirect('payments');
            return;
        }

        if (!is_numeric($nominalRaw) || $nominal < 0 || $nominal > 100000000.0) {
            Session::setFlash('danger', 'Nominal pembayaran tidak boleh bernilai negatif dan maksimal Rp 100.000.000.');
            $this->redirect('payments');
            return;
        }

        if (empty($tanggal) || !strtotime($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $createdId = $this->paymentModel->recordPayment([
            'member_id'          => $memberId,
            'periode'            => $periode,
            'tagihan'            => $tagihan,
            'nominal'            => $nominal,
            'tanggal_pembayaran' => $tanggal,
            'verified_by'        => $isVerified ? Auth::id() : null,
            'verified_at'        => $isVerified ? date('Y-m-d H:i:s') : null,
            'catatan'            => $catatan,
        ]);

        if ($createdId) {
            $status = Payment::calculateStatus($nominal, $tagihan);
            $this->auditModel->record(
                Auth::id(),
                'CREATE_PAYMENT',
                'payments',
                $createdId,
                null,
                [
                    'member_name' => $member['nama'],
                    'periode'     => $periode,
                    'tagihan'     => $tagihan,
                    'nominal'     => $nominal,
                    'status'      => $status,
                ]
            );

            // Trigger automatic WhatsApp notification if enabled
            if ($this->waService->isEnabled() && (bool)$this->waSetting->get('wa_auto_notify_payment', true)) {
                $waResult = $this->waService->sendPaymentConfirmation($createdId, Auth::id());
                if ($waResult['success']) {
                    Session::setFlash('success', "Pembayaran kas untuk [{$member['nama']}] ({$periode}) berhasil dicatat (Status: {$status}) & notifikasi WhatsApp terkirim.");
                } else {
                    Session::setFlash('success', "Pembayaran kas untuk [{$member['nama']}] berhasil dicatat. (Catatan WA: " . ($waResult['error'] ?? 'tidak terkirim') . ")");
                }
            } else {
                Session::setFlash('success', "Pembayaran kas untuk [{$member['nama']}] ({$periode}) sebesar Rp " . number_format($nominal, 2, ',', '.') . " berhasil dicatat dengan status: {$status}.");
            }
        } else {
            Session::setFlash('danger', 'Gagal mencatat pembayaran kas.');
        }

        $this->redirect('payments');
    }

    /**
     * Verify Payment Record
     */
    public function verify(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments');
            return;
        }

        $id = (int)$this->post('id', 0);
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            Session::setFlash('danger', 'Data pembayaran tidak ditemukan.');
            $this->redirect('payments');
            return;
        }

        $notes = trim((string)$this->post('catatan', $payment['catatan'] ?? ''));
        $nominalRaw = $this->post('nominal', null);
        $nominal = null;
        if ($nominalRaw !== null) {
            $cleanStr = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$nominalRaw);
            if (!is_numeric($cleanStr) || (float)$cleanStr < 0 || (float)$cleanStr > 100000000.0) {
                Session::setFlash('danger', 'Nominal verifikasi tidak valid (harus >= 0 dan maksimal Rp 100.000.000).');
                $this->redirect('payments');
                return;
            }
            $nominal = (float)$cleanStr;
        }

        $verified = $this->paymentModel->verify($id, (int)Auth::id(), $nominal, $notes);

        if ($verified) {
            $updatedPayment = $this->paymentModel->find($id);
            $this->auditModel->record(
                Auth::id(),
                'VERIFY_PAYMENT',
                'payments',
                $id,
                $payment,
                $updatedPayment
            );

            // Trigger automatic WhatsApp verification status notification if enabled
            if ($this->waService->isEnabled() && (bool)$this->waSetting->get('wa_auto_notify_verification', true)) {
                $waResult = $this->waService->sendVerificationStatus($id, Auth::id());
                if ($waResult['success']) {
                    Session::setFlash('success', "Pembayaran ID #{$id} berhasil diverifikasi (Status: {$updatedPayment['status']}) & notifikasi WhatsApp terkirim.");
                } else {
                    Session::setFlash('success', "Pembayaran ID #{$id} berhasil diverifikasi. Status: {$updatedPayment['status']}. (Catatan WA: " . ($waResult['error'] ?? 'tidak terkirim') . ")");
                }
            } else {
                Session::setFlash('success', "Pembayaran ID #{$id} berhasil diverifikasi. Status saat ini: {$updatedPayment['status']}.");
            }
        } else {
            Session::setFlash('danger', 'Gagal memverifikasi pembayaran.');
        }

        $this->redirect('payments');
    }

    /**
     * Send WhatsApp Payment Reminder Directly from Payments Table
     */
    public function sendWaReminder(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments');
            return;
        }

        $id = (int)$this->post('id', 0);
        if ($id <= 0) {
            Session::setFlash('danger', 'ID pembayaran tidak valid.');
            $this->redirect('payments');
            return;
        }

        $result = $this->waService->sendPaymentReminder($id, Auth::id());
        if ($result['success']) {
            Session::setFlash('success', '✓ Notifikasi pengingat pembayaran kas berhasil dikirim via WhatsApp!');
        } else {
            Session::setFlash('warning', 'Pemberitahuan WhatsApp: ' . ($result['error'] ?? 'Gagal mengirim pesan pengingat.'));
        }

        $this->redirect('payments');
    }

    /**
     * Update Payment Record
     */
    public function update(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments');
            return;
        }

        $id = (int)$this->post('id', 0);
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            Session::setFlash('danger', 'Data pembayaran tidak ditemukan.');
            $this->redirect('payments');
            return;
        }

        $periode = trim((string)$this->post('periode', $payment['periode']));
        $tanggal = trim((string)$this->post('tanggal_pembayaran', $payment['tanggal_pembayaran']));
        $tagihanRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('tagihan', (string)$payment['tagihan']));
        $nominalRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('nominal', (string)$payment['nominal']));
        $catatan = trim((string)$this->post('catatan', ''));

        if ($periode === '') {
            Session::setFlash('danger', 'Periode pembayaran kas wajib diisi.');
            $this->redirect('payments');
            return;
        }

        if (!is_numeric($tagihanRaw) || (float)$tagihanRaw <= 0 || (float)$tagihanRaw > 100000000.0) {
            Session::setFlash('danger', 'Nilai tagihan harus lebih besar dari 0 dan maksimal Rp 100.000.000.');
            $this->redirect('payments');
            return;
        }

        if (!is_numeric($nominalRaw) || (float)$nominalRaw < 0 || (float)$nominalRaw > 100000000.0) {
            Session::setFlash('danger', 'Nominal yang disetor tidak boleh bernilai negatif dan maksimal Rp 100.000.000.');
            $this->redirect('payments');
            return;
        }

        if (empty($tanggal) || !strtotime($tanggal)) {
            $tanggal = $payment['tanggal_pembayaran'] ?? date('Y-m-d');
        }

        $tagihan = (float)$tagihanRaw;
        $nominal = (float)$nominalRaw;

        // Backend status calculation
        $status = Payment::calculateStatus($nominal, $tagihan);

        $updateData = [
            'periode'            => $periode,
            'tanggal_pembayaran' => $tanggal,
            'tagihan'            => $tagihan,
            'nominal'            => $nominal,
            'status'             => $status,
            'catatan'            => $catatan,
        ];

        $updated = $this->paymentModel->update($id, $updateData);

        if ($updated) {
            $this->auditModel->record(
                Auth::id(),
                'UPDATE_PAYMENT',
                'payments',
                $id,
                $payment,
                $updateData
            );
            Session::setFlash('success', "Data pembayaran #{$id} berhasil diperbarui (Status: {$status}).");
        } else {
            Session::setFlash('danger', 'Gagal memperbarui pembayaran.');
        }

        $this->redirect('payments');
    }

    /**
     * Delete Payment Record
     */
    public function delete(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments');
            return;
        }

        $id = (int)$this->post('id', 0);
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            Session::setFlash('danger', 'Data pembayaran tidak ditemukan.');
            $this->redirect('payments');
            return;
        }

        $deleted = $this->paymentModel->delete($id);

        if ($deleted) {
            $this->auditModel->record(
                Auth::id(),
                'DELETE_PAYMENT',
                'payments',
                $id,
                $payment,
                null
            );
            Session::setFlash('success', "Data pembayaran #{$id} berhasil dihapus.");
        } else {
            Session::setFlash('danger', 'Gagal menghapus pembayaran.');
        }

        $this->redirect('payments');
    }

    /**
     * Return Payment Detail JSON for AJAX Modals
     */
    public function getJson(): void {
        $id = (int)$this->get('id', 0);
        $detail = $this->paymentModel->getDetail($id);

        if (!$detail) {
            $this->json(['status' => 'error', 'message' => 'Pembayaran tidak ditemukan'], 404);
            return;
        }

        $this->json(['status' => 'success', 'data' => $detail]);
    }

    /**
     * Verify Submitted Payment Transaction (Transfer Bank / Tunai)
     */
    public function verifyTransaction(): void {
        if (!$this->isPost()) {
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        $id = (int)$this->post('id', 0);
        $notes = trim((string)$this->post('catatan', ''));

        if ($id <= 0) {
            Session::setFlash('danger', 'ID transaksi pembayaran tidak valid.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        $trx = $this->payTrxModel->find($id);
        if (!$trx) {
            Session::setFlash('danger', 'Data transaksi pembayaran tidak ditemukan.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        $result = $this->payTrxModel->verifyPayment($id, (int)Auth::id(), $notes);

        if ($result['success']) {
            $this->auditModel->record(
                Auth::id(),
                'VERIFY_PAYMENT_TRANSACTION',
                'payment_transactions',
                $id,
                $trx,
                $result
            );

            // Optional WhatsApp notification
            if ($this->waService->isEnabled() && (bool)$this->waSetting->get('wa_auto_notify_verification', true)) {
                $this->waService->sendVerificationStatus($result['payment_id'], Auth::id());
            }

            Session::setFlash('success', "✓ Pembayaran [{$result['kode_transaksi']}] berhasil diverifikasi! Pemasukan kas telah dicatat ke Buku Kas Umum dengan nomor transaksi: {$result['cash_trx_kode']}.");
        } else {
            Session::setFlash('danger', 'Gagal memverifikasi pembayaran: ' . ($result['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        $this->redirect('payments?tab=verifikasi');
    }

    /**
     * Reject Submitted Payment Transaction with Reason
     */
    public function rejectTransaction(): void {
        if (!$this->isPost()) {
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        $id = (int)$this->post('id', 0);
        $reason = trim((string)$this->post('alasan_penolakan', ''));

        if ($id <= 0) {
            Session::setFlash('danger', 'ID transaksi pembayaran tidak valid.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        if (empty($reason)) {
            Session::setFlash('danger', 'Alasan penolakan pembayaran wajib diisi agar anggota memahami kendala pembayarannya.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        $trx = $this->payTrxModel->find($id);
        if (!$trx) {
            Session::setFlash('danger', 'Data transaksi pembayaran tidak ditemukan.');
            $this->redirect('payments?tab=verifikasi');
            return;
        }

        $result = $this->payTrxModel->rejectPayment($id, (int)Auth::id(), $reason);

        if ($result['success']) {
            $this->auditModel->record(
                Auth::id(),
                'REJECT_PAYMENT_TRANSACTION',
                'payment_transactions',
                $id,
                $trx,
                ['alasan_penolakan' => $reason]
            );

            Session::setFlash('warning', "Pengajuan pembayaran [{$result['kode_transaksi']}] berhasil ditolak dengan alasan: \"{$reason}\".");
        } else {
            Session::setFlash('danger', 'Gagal menolak pembayaran: ' . ($result['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        $this->redirect('payments?tab=verifikasi');
    }

    /**
     * Bendahara Record Cash Receipt Directly
     */
    public function recordCashReceipt(): void {
        if (!$this->isPost()) {
            $this->redirect('payments');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Expired). Silakan coba lagi.');
            $this->redirect('payments');
            return;
        }

        $memberId = (int)$this->post('member_id', 0);
        $paymentId = (int)$this->post('payment_id', 0);
        $nominalRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('nominal', '0'));
        $catatan = trim((string)$this->post('catatan', ''));

        if ($memberId <= 0) {
            Session::setFlash('danger', 'Anggota wajib dipilih.');
            $this->redirect('payments');
            return;
        }

        $nominal = (float)$nominalRaw;
        if (!is_numeric($nominalRaw) || $nominal <= 0.0) {
            Session::setFlash('danger', 'Nominal penerimaan uang tunai harus lebih besar dari Rp 0.');
            $this->redirect('payments');
            return;
        }

        // If specific payment bill was not selected, find active bill for this member
        if ($paymentId <= 0) {
            $sql = "SELECT id, tagihan, nominal FROM payments WHERE member_id = :mid AND status != 'Lunas' ORDER BY id ASC LIMIT 1";
            $activeBill = $this->paymentModel->fetchOne($sql, ['mid' => $memberId]);
            if ($activeBill) {
                $paymentId = (int)$activeBill['id'];
            } else {
                // Create bill for current month
                $periode = date('F Y');
                $paymentId = $this->paymentModel->create([
                    'member_id'          => $memberId,
                    'periode'            => $periode,
                    'tagihan'            => 50000.00,
                    'nominal'            => 0.00,
                    'tanggal_pembayaran' => date('Y-m-d'),
                    'status'             => 'Belum Bayar',
                ]);
            }
        }

        $result = $this->payTrxModel->recordDirectCash([
            'payment_id' => $paymentId,
            'member_id'  => $memberId,
            'nominal'    => $nominal,
            'catatan'    => $catatan,
        ], (int)Auth::id());

        if ($result['success']) {
            $member = $this->memberModel->find($memberId);
            $memberName = $member['nama'] ?? 'Anggota';

            $this->auditModel->record(
                Auth::id(),
                'RECORD_DIRECT_CASH',
                'payment_transactions',
                $result['cash_trx_id'],
                null,
                [
                    'member_name'    => $memberName,
                    'nominal'        => $nominal,
                    'kode_transaksi' => $result['kode_transaksi'],
                    'cash_trx_kode'  => $result['cash_trx_kode'],
                ]
            );

            // Optional WA Notification
            if ($this->waService->isEnabled() && (bool)$this->waSetting->get('wa_auto_notify_payment', true)) {
                $this->waService->sendPaymentConfirmation($result['payment_id'], Auth::id());
            }

            Session::setFlash('success', "✓ Penerimaan uang tunai sebesar Rp " . number_format($nominal, 0, ',', '.') . " dari [{$memberName}] berhasil dicatat & diverifikasi langsung ke Buku Kas ({$result['cash_trx_kode']})!");
        } else {
            Session::setFlash('danger', 'Gagal mencatat penerimaan tunai: ' . ($result['error'] ?? 'Terjadi kesalahan sistem.'));
        }

        $this->redirect('payments?tab=verifikasi');
    }

    /**
     * Save Destination Bank Accounts Configuration
     */
    public function saveBankAccounts(): void {
        if (!$this->isPost()) {
            $this->redirect('payments?tab=pengaturan');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments?tab=pengaturan');
            return;
        }

        $bankNames = $_POST['bank_name'] ?? [];
        $accountNumbers = $_POST['account_number'] ?? [];
        $accountHolders = $_POST['account_holder'] ?? [];
        $instructions = $_POST['instructions'] ?? [];

        $accounts = [];
        if (is_array($bankNames)) {
            foreach ($bankNames as $i => $name) {
                if (!empty(trim((string)$name)) && !empty(trim((string)($accountNumbers[$i] ?? '')))) {
                    $accounts[] = [
                        'bank_name'      => trim((string)$name),
                        'account_number' => trim((string)$accountNumbers[$i]),
                        'account_holder' => trim((string)($accountHolders[$i] ?? 'Kas Anggota RPL')),
                        'instructions'   => trim((string)($instructions[$i] ?? '')),
                    ];
                }
            }
        }

        $saved = $this->paySettingModel->saveBankAccounts($accounts);
        if ($saved) {
            $this->auditModel->record(
                Auth::id(),
                'UPDATE_BANK_ACCOUNTS',
                'payment_settings',
                null,
                null,
                ['count' => count($accounts)]
            );
            Session::setFlash('success', '✓ Konfigurasi rekening bank tujuan transfer berhasil disimpan.');
        } else {
            Session::setFlash('danger', 'Gagal menyimpan konfigurasi rekening bank.');
        }

        $this->redirect('payments?tab=pengaturan');
    }

    /**
     * Save Payment Gateway Settings (Midtrans QRIS / E-Wallet)
     */
    public function saveGatewaySettings(): void {
        if (!$this->isPost()) {
            $this->redirect('payments?tab=pengaturan');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('payments?tab=pengaturan');
            return;
        }

        $provider = trim((string)$this->post('payment_gateway_provider', 'midtrans'));
        $env = trim((string)$this->post('payment_gateway_env', 'sandbox'));
        $clientKey = trim((string)$this->post('midtrans_client_key', ''));
        $serverKey = trim((string)$this->post('midtrans_server_key', ''));
        $qrisEnabled = (int)$this->post('qris_enabled', 0);
        $ewalletEnabled = (int)$this->post('ewallet_enabled', 0);

        $this->paySettingModel->set('payment_gateway_provider', $provider);
        $this->paySettingModel->set('payment_gateway_env', $env);
        $this->paySettingModel->set('midtrans_client_key', $clientKey);
        if (!empty($serverKey)) {
            $this->paySettingModel->set('midtrans_server_key', $serverKey);
        }
        $this->paySettingModel->set('qris_enabled', $qrisEnabled);
        $this->paySettingModel->set('ewallet_enabled', $ewalletEnabled);

        $this->auditModel->record(
            Auth::id(),
            'UPDATE_GATEWAY_SETTINGS',
            'payment_settings',
            null,
            null,
            ['env' => $env, 'qris' => $qrisEnabled, 'ewallet' => $ewalletEnabled]
        );

        Session::setFlash('success', '✓ Pengaturan integrasi payment gateway berhasil disimpan.');
        $this->redirect('payments?tab=pengaturan');
    }

    /**
     * Return Payment Transaction Detail JSON for AJAX Modals
     */
    public function getTrxJson(): void {
        $id = (int)$this->get('id', 0);
        $detail = $this->payTrxModel->getDetailWithRelations($id);

        if (!$detail) {
            $this->json(['status' => 'error', 'message' => 'Transaksi pembayaran tidak ditemukan'], 404);
            return;
        }

        $this->json(['status' => 'success', 'data' => $detail]);
    }
}

