<?php
/**
 * Transaction Controller (Buku Kas Umum)
 * Super User Only — Handles Income & Expense Cash Transactions
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\AuditLog;

class TransactionController extends Controller {
    private Transaction $trxModel;
    private Category $categoryModel;
    private AuditLog $auditModel;

    public function __construct() {
        // Enforce RBAC: Super User & Bendahara have access
        Auth::requireRole(Auth::ROLE_SUPER_USER, Auth::ROLE_BENDAHARA);

        $this->trxModel = new Transaction();
        $this->categoryModel = new Category();
        $this->auditModel = new AuditLog();
    }

    /**
     * Cash Transactions List View (Buku Kas Umum)
     */
    public function index(): void {
        $search = trim((string)$this->get('search', ''));
        $tipe = trim((string)$this->get('tipe', ''));
        $categoryId = (int)$this->get('category_id', 0);
        $startDate = trim((string)$this->get('start_date', ''));
        $endDate = trim((string)$this->get('end_date', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 10;

        $filters = [
            'search'      => $search,
            'tipe'        => $tipe,
            'category_id' => $categoryId > 0 ? $categoryId : '',
            'start_date'  => $startDate,
            'end_date'    => $endDate,
        ];

        // 1. Get Paginated Transactions
        $paginated = $this->trxModel->getPaginated($filters, $page, $perPage);

        // 2. Dynamic Real-time Balance from Database
        $totalIncome = $this->trxModel->getTotalIncome();
        $totalExpense = $this->trxModel->getTotalExpense();
        $currentBalance = $this->trxModel->getCurrentBalance();

        // 3. Master Categories for Filter and Modals
        $allCategories = $this->categoryModel->all('tipe ASC, nama ASC');
        $incomeCategories = $this->categoryModel->getByType(Category::TYPE_PEMASUKAN, true);
        $expenseCategories = $this->categoryModel->getByType(Category::TYPE_PENGELUARAN, true);

        $this->view('transactions.index', [
            'title'             => 'Buku Kas & Transaksi — ' . APP_NAME,
            'transactions'      => $paginated['data'],
            'total'             => $paginated['total'],
            'page'              => $paginated['page'],
            'perPage'           => $paginated['perPage'],
            'totalPages'        => $paginated['totalPages'],
            'totalIncome'       => $totalIncome,
            'totalExpense'      => $totalExpense,
            'currentBalance'    => $currentBalance,
            'categories'        => $allCategories,
            'incomeCategories'  => $incomeCategories,
            'expenseCategories' => $expenseCategories,
            'filters'           => $filters,
            'todayDate'         => date('Y-m-d'),
            'autoIncomeKode'    => $this->trxModel->generateKode(Transaction::TYPE_PEMASUKAN),
            'autoExpenseKode'   => $this->trxModel->generateKode(Transaction::TYPE_PENGELUARAN),
        ]);
    }

    /**
     * Create New Cash Transaction
     */
    public function create(): void {
        if (!$this->isPost()) {
            $this->redirect('transactions');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Token Expired). Silakan coba lagi.');
            $this->redirect('transactions');
            return;
        }

        $tipe = trim((string)$this->post('tipe', ''));
        $categoryId = (int)$this->post('category_id', 0);
        $tanggal = trim((string)$this->post('tanggal', date('Y-m-d')));
        $nominalRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('nominal', '0'));
        $nominal = (float)$nominalRaw;
        $keterangan = trim((string)$this->post('keterangan', ''));
        $kodeTransaksi = trim((string)$this->post('kode_transaksi', ''));

        // 1. Validation: Tipe
        if (!in_array($tipe, [Transaction::TYPE_PEMASUKAN, Transaction::TYPE_PENGELUARAN], true)) {
            Session::setFlash('danger', 'Tipe transaksi tidak valid (pilih Pemasukan atau Pengeluaran).');
            $this->redirect('transactions');
            return;
        }

        // 2. Validation: Nominal > 0 & <= 10.000.000.000
        if (!is_numeric($nominalRaw) || $nominal <= 0 || $nominal > 10000000000.0) {
            Session::setFlash('danger', 'Nominal transaksi wajib diisi dengan nilai numerik lebih besar dari 0 dan maksimal Rp 10.000.000.000.');
            $this->redirect('transactions');
            return;
        }

        // 3. Validation: Category Existence & Type Matching
        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            Session::setFlash('danger', 'Kategori kas yang dipilih tidak valid.');
            $this->redirect('transactions');
            return;
        }

        if ($category['tipe'] !== $tipe) {
            $labelTipe = ($tipe === Transaction::TYPE_PEMASUKAN) ? 'Pemasukan' : 'Pengeluaran';
            $labelCat = ($category['tipe'] === Transaction::TYPE_PEMASUKAN) ? 'Pemasukan' : 'Pengeluaran';
            Session::setFlash('danger', "Validasi Gagal: Transaksi {$labelTipe} tidak boleh menggunakan kategori {$labelCat} [{$category['nama']}].");
            $this->redirect('transactions');
            return;
        }

        // 4. Validation: Keterangan & Tanggal
        if ($keterangan === '') {
            Session::setFlash('danger', 'Keterangan transaksi wajib diisi.');
            $this->redirect('transactions');
            return;
        }

        if (mb_strlen($keterangan) > 500) {
            Session::setFlash('danger', 'Keterangan transaksi maksimal 500 karakter.');
            $this->redirect('transactions');
            return;
        }

        if (empty($tanggal) || !strtotime($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        // Auto generate kode if empty
        if ($kodeTransaksi === '') {
            $kodeTransaksi = $this->trxModel->generateKode($tipe, $tanggal);
        }

        $userId = Auth::id();

        // 5. Insert Transaction
        $createdId = $this->trxModel->create([
            'kode_transaksi' => $kodeTransaksi,
            'tanggal'        => $tanggal,
            'tipe'           => $tipe,
            'category_id'    => $categoryId,
            'nominal'        => $nominal,
            'keterangan'     => $keterangan,
            'created_by'     => $userId,
        ]);

        if ($createdId) {
            $this->auditModel->record(
                $userId,
                'CREATE_TRANSACTION',
                'transactions',
                $createdId,
                null,
                [
                    'kode_transaksi' => $kodeTransaksi,
                    'tipe'           => $tipe,
                    'nominal'        => $nominal,
                    'category_name'  => $category['nama'],
                    'tanggal'        => $tanggal,
                ]
            );

            $formattedNominal = number_format($nominal, 2, ',', '.');
            Session::setFlash('success', "Transaksi {$kodeTransaksi} sebesar Rp {$formattedNominal} ({$tipe}) berhasil dicatat.");
        } else {
            Session::setFlash('danger', 'Terjadi kesalahan sistem saat mencatat transaksi.');
        }

        $this->redirect('transactions');
    }

    /**
     * Update Existing Transaction
     */
    public function update(): void {
        if (!$this->isPost()) {
            $this->redirect('transactions');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('transactions');
            return;
        }

        $id = (int)$this->post('id', 0);
        $transaction = $this->trxModel->find($id);

        if (!$transaction) {
            Session::setFlash('danger', 'Data transaksi tidak ditemukan.');
            $this->redirect('transactions');
            return;
        }

        $tipe = trim((string)$this->post('tipe', ''));
        $categoryId = (int)$this->post('category_id', 0);
        $tanggal = trim((string)$this->post('tanggal', $transaction['tanggal']));
        $nominalRaw = str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], (string)$this->post('nominal', '0'));
        $nominal = (float)$nominalRaw;
        $keterangan = trim((string)$this->post('keterangan', ''));

        if (!in_array($tipe, [Transaction::TYPE_PEMASUKAN, Transaction::TYPE_PENGELUARAN], true)) {
            Session::setFlash('danger', 'Tipe transaksi tidak valid.');
            $this->redirect('transactions');
            return;
        }

        if (!is_numeric($nominalRaw) || $nominal <= 0 || $nominal > 10000000000.0) {
            Session::setFlash('danger', 'Nominal transaksi harus lebih besar dari 0 dan maksimal Rp 10.000.000.000.');
            $this->redirect('transactions');
            return;
        }

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            Session::setFlash('danger', 'Kategori kas tidak valid.');
            $this->redirect('transactions');
            return;
        }

        if ($category['tipe'] !== $tipe) {
            Session::setFlash('danger', "Validasi Gagal: Kategori [{$category['nama']}] tidak sesuai dengan tipe transaksi [{$tipe}].");
            $this->redirect('transactions');
            return;
        }

        if ($keterangan === '') {
            Session::setFlash('danger', 'Keterangan transaksi wajib diisi.');
            $this->redirect('transactions');
            return;
        }

        if (mb_strlen($keterangan) > 500) {
            Session::setFlash('danger', 'Keterangan transaksi maksimal 500 karakter.');
            $this->redirect('transactions');
            return;
        }

        if (empty($tanggal) || !strtotime($tanggal)) {
            $tanggal = $transaction['tanggal'] ?? date('Y-m-d');
        }

        $updateData = [
            'tanggal'     => $tanggal,
            'tipe'        => $tipe,
            'category_id' => $categoryId,
            'nominal'     => $nominal,
            'keterangan'  => $keterangan,
        ];

        $updated = $this->trxModel->update($id, $updateData);

        if ($updated) {
            $this->auditModel->record(
                Auth::id(),
                'UPDATE_TRANSACTION',
                'transactions',
                $id,
                $transaction,
                $updateData
            );
            Session::setFlash('success', "Transaksi [{$transaction['kode_transaksi']}] berhasil diperbarui.");
        } else {
            Session::setFlash('danger', 'Gagal memperbarui transaksi.');
        }

        $this->redirect('transactions');
    }

    /**
     * Delete Transaction
     */
    public function delete(): void {
        if (!$this->isPost()) {
            $this->redirect('transactions');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('transactions');
            return;
        }

        $id = (int)$this->post('id', 0);
        $transaction = $this->trxModel->find($id);

        if (!$transaction) {
            Session::setFlash('danger', 'Transaksi tidak ditemukan.');
            $this->redirect('transactions');
            return;
        }

        $deleted = $this->trxModel->delete($id);

        if ($deleted) {
            $this->auditModel->record(
                Auth::id(),
                'DELETE_TRANSACTION',
                'transactions',
                $id,
                $transaction,
                null
            );
            Session::setFlash('success', "Transaksi [{$transaction['kode_transaksi']}] berhasil dihapus dari buku kas.");
        } else {
            Session::setFlash('danger', 'Gagal menghapus transaksi.');
        }

        $this->redirect('transactions');
    }

    /**
     * Return Transaction Detail JSON for AJAX Modals
     */
    public function getJson(): void {
        $id = (int)$this->get('id', 0);
        $detail = $this->trxModel->getDetail($id);

        if (!$detail) {
            $this->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
            return;
        }

        $this->json(['status' => 'success', 'data' => $detail]);
    }

    /**
     * Return Category Options JSON Filtered by Type
     */
    public function getCategoriesJson(): void {
        $tipe = trim((string)$this->get('tipe', ''));
        if (in_array($tipe, [Category::TYPE_PEMASUKAN, Category::TYPE_PENGELUARAN], true)) {
            $categories = $this->categoryModel->getByType($tipe, true);
        } else {
            $categories = $this->categoryModel->getActive();
        }
        $this->json(['status' => 'success', 'data' => $categories]);
    }
}
