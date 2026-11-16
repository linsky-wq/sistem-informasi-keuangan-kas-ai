<?php
/**
 * Admin Controller (Super User Only Module)
 * Handles Member Management, Categories, and Audit Trail
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Member;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Category;

class AdminController extends Controller {
    private Member $memberModel;
    private User $userModel;
    private AuditLog $auditModel;
    private Category $categoryModel;

    public function __construct() {
        // Enforce RBAC: Super User only
        Auth::requireSuperUser();

        $this->memberModel = new Member();
        $this->userModel = new User();
        $this->auditModel = new AuditLog();
        $this->categoryModel = new Category();
    }

    // =========================================================
    // MEMBER MANAGEMENT METHODS
    // =========================================================

    /**
     * Member Management List (Search, Filter, Pagination)
     */
    public function members(): void {
        $search = trim((string)$this->get('search', ''));
        $kelas = trim((string)$this->get('kelas', ''));
        $status = trim((string)$this->get('status', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 10;

        $filters = [
            'search' => $search,
            'kelas'  => $kelas,
            'status' => $status,
        ];

        $paginatedResult = $this->memberModel->getPaginated($filters, $page, $perPage);
        $distinctClasses = $this->memberModel->getDistinctClasses();
        $allMembers = $this->memberModel->fetchAll("SELECT m.id, m.nama, m.nomor_anggota, m.kelas, m.email, u.username, u.status as user_status FROM `members` m LEFT JOIN `users` u ON m.user_id = u.id ORDER BY m.nama ASC");

        $this->view('admin.members', [
            'title'           => 'Manajemen Pengguna/Anggota — ' . APP_NAME,
            'members'         => $paginatedResult['data'],
            'total'           => $paginatedResult['total'],
            'page'            => $paginatedResult['page'],
            'perPage'         => $paginatedResult['perPage'],
            'totalPages'      => $paginatedResult['totalPages'],
            'classes'         => $distinctClasses,
            'filters'         => $filters,
            'autoNomor'       => $this->memberModel->generateNomorAnggota(),
            'allMembers'      => $allMembers,
        ]);
    }

    /**
     * Create New Member & User Account
     */
    public function createMember(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/members');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Token Expired). Silakan coba lagi.');
            $this->redirect('admin/members');
            return;
        }

        $nama = trim((string)$this->post('nama', ''));
        $kelas = trim((string)$this->post('kelas', ''));
        $nomorAnggota = trim((string)$this->post('nomor_anggota', ''));
        $nis = trim((string)$this->post('nis', ''));
        $email = trim((string)$this->post('email', ''));
        $noHp = trim((string)$this->post('no_hp', ''));
        $gender = in_array($this->post('jenis_kelamin'), ['L', 'P'], true) ? $this->post('jenis_kelamin') : 'L';
        $status = in_array($this->post('status'), ['aktif', 'nonaktif'], true) ? $this->post('status') : 'aktif';
        // Read raw password without strip_tags to preserve special chars
        $password = (string)($_POST['password'] ?? 'member123');
        $username = trim((string)$this->post('username', ''));

        // Validation Rules
        if ($nama === '' || mb_strlen($nama) > 100) {
            Session::setFlash('danger', 'Nama lengkap anggota wajib diisi (maksimal 100 karakter).');
            $this->redirect('admin/members');
            return;
        }

        if ($kelas === '' || mb_strlen($kelas) > 50) {
            Session::setFlash('danger', 'Kelas anggota wajib diisi (maksimal 50 karakter).');
            $this->redirect('admin/members');
            return;
        }

        if (strlen($password) < 6) {
            Session::setFlash('danger', 'Password akun anggota minimal 6 karakter.');
            $this->redirect('admin/members');
            return;
        }

        if ($username !== '') {
            if (strlen($username) < 3 || strlen($username) > 50) {
                Session::setFlash('danger', 'Username akun anggota minimal 3 dan maksimal 50 karakter.');
                $this->redirect('admin/members');
                return;
            }
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                Session::setFlash('danger', 'Username hanya boleh terdiri dari huruf, angka, titik, atau garis bawah.');
                $this->redirect('admin/members');
                return;
            }
            if ($this->userModel->findByUsername($username)) {
                Session::setFlash('danger', "Username [{$username}] sudah digunakan oleh akun lain.");
                $this->redirect('admin/members');
                return;
            }
        }

        if ($nomorAnggota !== '' && $this->memberModel->isNomorAnggotaExists($nomorAnggota)) {
            Session::setFlash('danger', "Nomor anggota [{$nomorAnggota}] sudah digunakan oleh anggota lain.");
            $this->redirect('admin/members');
            return;
        }

        if ($nis !== '' && $this->memberModel->isNisExists($nis)) {
            Session::setFlash('danger', "NIS [{$nis}] sudah terdaftar pada anggota lain.");
            $this->redirect('admin/members');
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('danger', 'Format email tidak valid.');
            $this->redirect('admin/members');
            return;
        }

        if ($email !== '' && $this->userModel->findByEmail($email)) {
            Session::setFlash('danger', "Email [{$email}] sudah digunakan oleh akun lain.");
            $this->redirect('admin/members');
            return;
        }

        $createdId = $this->memberModel->createMemberWithUser([
            'nama'          => $nama,
            'kelas'         => $kelas,
            'nomor_anggota' => $nomorAnggota,
            'nis'           => $nis,
            'email'         => $email,
            'no_hp'         => $noHp,
            'jenis_kelamin' => $gender,
            'status'        => $status,
            'password'      => $password,
            'username'      => $username,
        ]);

        if ($createdId) {
            $createdMember = $this->memberModel->getDetail($createdId);
            $finalUsername = $createdMember['username'] ?? $username;

            $this->auditModel->record(
                Auth::id(),
                'CREATE_MEMBER',
                'members',
                $createdId,
                null,
                ['nama' => $nama, 'nomor_anggota' => $nomorAnggota, 'kelas' => $kelas, 'username' => $finalUsername]
            );
            Session::setFlash('success', "Anggota baru [<strong>" . e($nama) . "</strong>] berhasil ditambahkan. Akun Login: <code>" . e($finalUsername) . "</code> (Password: <code>" . e($password) . "</code>).");
        } else {
            Session::setFlash('danger', 'Terjadi kesalahan sistem saat menambahkan anggota.');
        }

        $this->redirect('admin/members');
    }

    /**
     * Update Existing Member
     */
    public function updateMember(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/members');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('admin/members');
            return;
        }

        $id = (int)$this->post('id', 0);
        $member = $this->memberModel->find($id);

        if (!$member) {
            Session::setFlash('danger', 'Data anggota tidak ditemukan.');
            $this->redirect('admin/members');
            return;
        }

        $nama = trim((string)$this->post('nama', ''));
        $kelas = trim((string)$this->post('kelas', ''));
        $nomorAnggota = trim((string)$this->post('nomor_anggota', ''));
        $nis = trim((string)$this->post('nis', ''));
        $email = trim((string)$this->post('email', ''));
        $noHp = trim((string)$this->post('no_hp', ''));
        $gender = in_array($this->post('jenis_kelamin'), ['L', 'P'], true) ? $this->post('jenis_kelamin') : $member['jenis_kelamin'];
        $status = in_array($this->post('status'), ['aktif', 'nonaktif'], true) ? $this->post('status') : $member['status'];
        // Read raw password without strip_tags
        $password = trim((string)($_POST['password'] ?? ''));

        $username = trim((string)$this->post('username', ''));

        if ($nama === '' || mb_strlen($nama) > 100) {
            Session::setFlash('danger', 'Nama lengkap anggota wajib diisi (maksimal 100 karakter).');
            $this->redirect('admin/members');
            return;
        }

        if ($kelas === '' || mb_strlen($kelas) > 50) {
            Session::setFlash('danger', 'Kelas anggota wajib diisi (maksimal 50 karakter).');
            $this->redirect('admin/members');
            return;
        }

        if ($password !== '' && strlen($password) < 6) {
            Session::setFlash('danger', 'Password baru minimal 6 karakter.');
            $this->redirect('admin/members');
            return;
        }

        if ($username !== '') {
            if (strlen($username) < 3 || strlen($username) > 50) {
                Session::setFlash('danger', 'Username minimal 3 dan maksimal 50 karakter.');
                $this->redirect('admin/members');
                return;
            }
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                Session::setFlash('danger', 'Username hanya boleh terdiri dari huruf, angka, titik, atau garis bawah.');
                $this->redirect('admin/members');
                return;
            }
            $existingUserUname = $this->userModel->findByUsername($username);
            if ($existingUserUname && (int)$existingUserUname['id'] !== (int)$member['user_id']) {
                Session::setFlash('danger', "Username [{$username}] sudah digunakan oleh akun lain.");
                $this->redirect('admin/members');
                return;
            }
        }

        if ($nomorAnggota !== '' && $this->memberModel->isNomorAnggotaExists($nomorAnggota, $id)) {
            Session::setFlash('danger', "Nomor anggota [{$nomorAnggota}] sudah digunakan oleh anggota lain.");
            $this->redirect('admin/members');
            return;
        }

        if ($nis !== '' && $this->memberModel->isNisExists($nis, $id)) {
            Session::setFlash('danger', "NIS [{$nis}] sudah terdaftar pada anggota lain.");
            $this->redirect('admin/members');
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('danger', 'Format email tidak valid.');
            $this->redirect('admin/members');
            return;
        }

        $existingUserEmail = $this->userModel->findByEmail($email);
        if ($email !== '' && $existingUserEmail && (int)$existingUserEmail['id'] !== (int)$member['user_id']) {
            Session::setFlash('danger', "Email [{$email}] sudah digunakan oleh akun lain.");
            $this->redirect('admin/members');
            return;
        }

        $updatePayload = [
            'nama'          => $nama,
            'kelas'         => $kelas,
            'nomor_anggota' => $nomorAnggota,
            'nis'           => $nis,
            'email'         => $email,
            'no_hp'         => $noHp,
            'jenis_kelamin' => $gender,
            'status'        => $status,
        ];

        if ($username !== '') {
            $updatePayload['username'] = $username;
        }

        if ($password !== '') {
            $updatePayload['password'] = $password;
        }

        $updated = $this->memberModel->updateMemberWithUser($id, $updatePayload);

        if ($updated) {
            $this->auditModel->record(
                Auth::id(),
                'UPDATE_MEMBER',
                'members',
                $id,
                $member,
                $updatePayload
            );
            Session::setFlash('success', "Data anggota [{$nama}] berhasil diperbarui.");
        } else {
            Session::setFlash('danger', 'Gagal memperbarui data anggota.');
        }

        $this->redirect('admin/members');
    }

    /**
     * Toggle Member Active / Inactive Status
     */
    public function toggleMemberStatus(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/members');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('admin/members');
            return;
        }

        $id = (int)$this->post('id', 0);
        $result = $this->memberModel->toggleStatus($id);

        if ($result) {
            $this->auditModel->record(
                Auth::id(),
                'TOGGLE_MEMBER_STATUS',
                'members',
                $id,
                ['status' => $result['old_status']],
                ['status' => $result['new_status']]
            );
            $badgeText = ($result['new_status'] === 'aktif') ? 'diaktifkan' : 'dinonaktifkan';
            Session::setFlash('success', "Status anggota [{$result['nama']}] berhasil {$badgeText}.");
        } else {
            Session::setFlash('danger', 'Gagal mengubah status anggota.');
        }

        $this->redirect('admin/members');
    }

    /**
     * Create or Link / Manage Member Login Account (Username, Email, Password, Status)
     */
    public function manageMemberAccount(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/members');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid (CSRF Token Expired). Silakan coba lagi.');
            $this->redirect('admin/members');
            return;
        }

        $memberId = (int)$this->post('member_id', 0);
        $member = $this->memberModel->find($memberId);

        if (!$member) {
            Session::setFlash('danger', 'Data anggota tidak ditemukan.');
            $this->redirect('admin/members');
            return;
        }

        $username = trim((string)$this->post('username', ''));
        $email = trim((string)$this->post('email', ''));
        $password = (string)($_POST['password'] ?? '');
        $status = in_array($this->post('status'), ['active', 'inactive'], true) ? $this->post('status') : 'active';

        // 1. Validate Username
        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            Session::setFlash('danger', 'Username wajib diisi dengan panjang antara 3 hingga 50 karakter.');
            $this->redirect('admin/members');
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            Session::setFlash('danger', 'Username hanya boleh terdiri dari huruf, angka, titik, atau garis bawah.');
            $this->redirect('admin/members');
            return;
        }

        $currentUserId = (int)($member['user_id'] ?? 0);

        $existingUserByUname = $this->userModel->findByUsername($username);
        if ($existingUserByUname && (int)$existingUserByUname['id'] !== $currentUserId) {
            Session::setFlash('danger', "Username [{$username}] sudah digunakan oleh akun lain.");
            $this->redirect('admin/members');
            return;
        }

        // 2. Validate Email
        if ($email === '') {
            $email = $username . '@kas.local';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('danger', 'Format email akun tidak valid.');
            $this->redirect('admin/members');
            return;
        }

        $existingUserByEmail = $this->userModel->findByEmail($email);
        if ($existingUserByEmail && (int)$existingUserByEmail['id'] !== $currentUserId) {
            Session::setFlash('danger', "Email [{$email}] sudah digunakan oleh akun lain.");
            $this->redirect('admin/members');
            return;
        }

        // 3. Validate Password
        $currentUser = $currentUserId > 0 ? $this->userModel->find($currentUserId) : null;
        if (!$currentUser && $password === '') {
            $password = 'member123';
        }

        if ($password !== '' && strlen($password) < 6) {
            Session::setFlash('danger', 'Password akun minimal 6 karakter.');
            $this->redirect('admin/members');
            return;
        }

        // 4. Create or Update Account
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $db->beginTransaction();

            if ($currentUser) {
                // Update existing user account
                $sql = "UPDATE `users` SET `username` = :username, `email` = :email, `status` = :status, `updated_at` = NOW()";
                $params = [
                    'username' => $username,
                    'email'    => $email,
                    'status'   => $status,
                    'uid'      => $currentUserId,
                ];

                if ($password !== '') {
                    $sql .= ", `password` = :pwd";
                    $params['pwd'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= " WHERE `id` = :uid";

                $stmt = $db->prepare($sql);
                $stmt->execute($params);

                // Update member's email and status to match
                $memberStatus = ($status === 'active') ? 'aktif' : 'nonaktif';
                $db->prepare("UPDATE `members` SET `email` = :email, `status` = :status, `updated_at` = NOW() WHERE `id` = :mid")
                   ->execute(['email' => $email, 'status' => $memberStatus, 'mid' => $memberId]);

                $finalUserId = $currentUserId;
            } else {
                // Create new user account and link to member
                $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                $uStmt = $db->prepare("INSERT INTO `users` (`username`, `nama`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) 
                                       VALUES (:username, :nama, :email, :password, 'member', :status, NOW(), NOW())");
                $uStmt->execute([
                    'username' => $username,
                    'nama'     => $member['nama'],
                    'email'    => $email,
                    'password' => $hashedPwd,
                    'status'   => $status,
                ]);
                $finalUserId = (int)$db->lastInsertId();

                $memberStatus = ($status === 'active') ? 'aktif' : 'nonaktif';
                $db->prepare("UPDATE `members` SET `user_id` = :uid, `email` = :email, `status` = :status, `updated_at` = NOW() WHERE `id` = :mid")
                   ->execute(['uid' => $finalUserId, 'email' => $email, 'status' => $memberStatus, 'mid' => $memberId]);
            }

            $db->commit();

            // Record audit log
            $this->auditModel->record(
                Auth::id(),
                'MANAGE_MEMBER_ACCOUNT',
                'users',
                $finalUserId,
                $currentUser ? ['username' => $currentUser['username'], 'status' => $currentUser['status']] : null,
                [
                    'member_id' => $memberId,
                    'nama'      => $member['nama'],
                    'username'  => $username,
                    'status'    => $status,
                    'password_changed' => ($password !== ''),
                ]
            );

            $pwdNote = ($password !== '') ? " | Password: <code>" . e($password) . "</code>" : "";
            Session::setFlash('success', "Akun login untuk anggota [<strong>" . e($member['nama']) . "</strong>] berhasil disimpan! Username: <code>" . e($username) . "</code>{$pwdNote}.");

        } catch (\Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in manageMemberAccount: " . $e->getMessage());
            Session::setFlash('danger', 'Terjadi kesalahan sistem saat menyimpan akun login: ' . $e->getMessage());
        }

        $this->redirect('admin/members');
    }

    /**
     * Return Member Detail JSON for Modals
     */
    public function getMemberJson(): void {
        $id = (int)$this->get('id', 0);
        $detail = $this->memberModel->getDetail($id);

        if (!$detail) {
            $this->json(['status' => 'error', 'message' => 'Anggota tidak ditemukan'], 404);
            return;
        }

        $payments = $this->memberModel->payments($id);
        $detail['payments_count'] = count($payments);
        $detail['payments'] = $payments;

        $this->json(['status' => 'success', 'data' => $detail]);
    }

    // =========================================================
    // CATEGORY MANAGEMENT METHODS
    // =========================================================

    /**
     * Categories List View with Filter & Stats
     */
    public function categories(): void {
        $search = trim((string)$this->get('search', ''));
        $tipe = trim((string)$this->get('tipe', ''));
        $status = trim((string)$this->get('status', ''));

        $filters = [
            'search' => $search,
            'tipe'   => $tipe,
            'status' => $status,
        ];

        $categories = $this->categoryModel->getAllWithTransactionCount($filters);

        // Calculate summary counts
        $totalIncomeCats = 0;
        $totalExpenseCats = 0;
        $totalActiveCats = 0;

        foreach ($categories as $c) {
            if ($c['tipe'] === Category::TYPE_PEMASUKAN) $totalIncomeCats++;
            if ($c['tipe'] === Category::TYPE_PENGELUARAN) $totalExpenseCats++;
            if ($c['status'] === Category::STATUS_AKTIF) $totalActiveCats++;
        }

        $this->view('admin.categories', [
            'title'            => 'Manajemen Kategori Kas — ' . APP_NAME,
            'categories'       => $categories,
            'total'            => count($categories),
            'totalIncomeCats'  => $totalIncomeCats,
            'totalExpenseCats' => $totalExpenseCats,
            'totalActiveCats'  => $totalActiveCats,
            'filters'          => $filters,
        ]);
    }

    /**
     * Create New Category
     */
    public function createCategory(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/categories');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid. Silakan coba lagi.');
            $this->redirect('admin/categories');
            return;
        }

        $nama = trim((string)$this->post('nama', ''));
        $tipe = trim((string)$this->post('tipe', ''));
        $deskripsi = trim((string)$this->post('deskripsi', ''));
        $status = in_array($this->post('status'), [Category::STATUS_AKTIF, Category::STATUS_NONAKTIF], true) ? $this->post('status') : Category::STATUS_AKTIF;

        // Validation
        if ($nama === '') {
            Session::setFlash('danger', 'Nama kategori wajib diisi.');
            $this->redirect('admin/categories');
            return;
        }

        if (!in_array($tipe, [Category::TYPE_PEMASUKAN, Category::TYPE_PENGELUARAN], true)) {
            Session::setFlash('danger', 'Tipe kategori tidak valid (pilih Pemasukan atau Pengeluaran).');
            $this->redirect('admin/categories');
            return;
        }

        if ($this->categoryModel->isNameExists($nama, $tipe)) {
            Session::setFlash('danger', "Kategori [{$nama}] untuk tipe [{$tipe}] sudah ada.");
            $this->redirect('admin/categories');
            return;
        }

        $createdId = $this->categoryModel->create([
            'nama'      => $nama,
            'tipe'      => $tipe,
            'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
            'status'    => $status,
        ]);

        if ($createdId) {
            $this->auditModel->record(
                Auth::id(),
                'CREATE_CATEGORY',
                'categories',
                $createdId,
                null,
                ['nama' => $nama, 'tipe' => $tipe, 'status' => $status]
            );
            Session::setFlash('success', "Kategori [{$nama}] ({$tipe}) berhasil ditambahkan.");
        } else {
            Session::setFlash('danger', 'Gagal menambahkan kategori.');
        }

        $this->redirect('admin/categories');
    }

    /**
     * Update Existing Category
     */
    public function updateCategory(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/categories');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('admin/categories');
            return;
        }

        $id = (int)$this->post('id', 0);
        $category = $this->categoryModel->find($id);

        if (!$category) {
            Session::setFlash('danger', 'Kategori tidak ditemukan.');
            $this->redirect('admin/categories');
            return;
        }

        $nama = trim((string)$this->post('nama', ''));
        $tipe = trim((string)$this->post('tipe', ''));
        $deskripsi = trim((string)$this->post('deskripsi', ''));
        $status = in_array($this->post('status'), [Category::STATUS_AKTIF, Category::STATUS_NONAKTIF], true) ? $this->post('status') : $category['status'];

        if ($nama === '') {
            Session::setFlash('danger', 'Nama kategori wajib diisi.');
            $this->redirect('admin/categories');
            return;
        }

        if (!in_array($tipe, [Category::TYPE_PEMASUKAN, Category::TYPE_PENGELUARAN], true)) {
            Session::setFlash('danger', 'Tipe kategori tidak valid.');
            $this->redirect('admin/categories');
            return;
        }

        if ($this->categoryModel->isNameExists($nama, $tipe, $id)) {
            Session::setFlash('danger', "Kategori [{$nama}] untuk tipe [{$tipe}] sudah ada.");
            $this->redirect('admin/categories');
            return;
        }

        $updatePayload = [
            'nama'      => $nama,
            'tipe'      => $tipe,
            'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
            'status'    => $status,
        ];

        $updated = $this->categoryModel->update($id, $updatePayload);

        if ($updated) {
            $this->auditModel->record(
                Auth::id(),
                'UPDATE_CATEGORY',
                'categories',
                $id,
                $category,
                $updatePayload
            );
            Session::setFlash('success', "Kategori [{$nama}] berhasil diperbarui.");
        } else {
            Session::setFlash('danger', 'Gagal memperbarui kategori.');
        }

        $this->redirect('admin/categories');
    }

    /**
     * Toggle Category Active / Nonaktif Status
     */
    public function toggleCategoryStatus(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/categories');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('admin/categories');
            return;
        }

        $id = (int)$this->post('id', 0);
        $result = $this->categoryModel->toggleStatus($id);

        if ($result) {
            $this->auditModel->record(
                Auth::id(),
                'TOGGLE_CATEGORY_STATUS',
                'categories',
                $id,
                ['status' => $result['old_status']],
                ['status' => $result['new_status']]
            );
            $badgeText = ($result['new_status'] === 'aktif') ? 'diaktifkan' : 'dinonaktifkan';
            Session::setFlash('success', "Status kategori [{$result['nama']}] berhasil {$badgeText}.");
        } else {
            Session::setFlash('danger', 'Gagal mengubah status kategori.');
        }

        $this->redirect('admin/categories');
    }

    /**
     * Delete Category (Safe Non-Destructive Check)
     */
    public function deleteCategory(): void {
        if (!$this->isPost()) {
            $this->redirect('admin/categories');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('danger', 'Sesi keamanan tidak valid.');
            $this->redirect('admin/categories');
            return;
        }

        $id = (int)$this->post('id', 0);
        $result = $this->categoryModel->safeDelete($id);

        if ($result['success']) {
            $this->auditModel->record(
                Auth::id(),
                'DELETE_CATEGORY',
                'categories',
                $id,
                $result['category'] ?? null,
                null
            );
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('warning', $result['message']);
        }

        $this->redirect('admin/categories');
    }

    /**
     * Get Category JSON for Edit Modals
     */
    public function getCategoryJson(): void {
        $id = (int)$this->get('id', 0);
        $category = $this->categoryModel->find($id);

        if (!$category) {
            $this->json(['status' => 'error', 'message' => 'Kategori tidak ditemukan'], 404);
            return;
        }

        $category['transaction_count'] = $this->categoryModel->getTransactionCount($id);
        $this->json(['status' => 'success', 'data' => $category]);
    }

    /**
     * View Audit Logs (Super User only)
     */
    public function audit(): void {
        $logs = $this->auditModel->getRecentLogs(50);

        $this->view('admin.audit', [
            'title' => 'Audit Trail & Keamanan — ' . APP_NAME,
            'logs'  => $logs,
        ]);
    }

    /**
     * Admin Personal Profile & Account Name Management
     */
    public function profile(): void {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        if ($this->isPost()) {
            if (!$this->validateCsrf()) {
                Session::setFlash('danger', 'Sesi keamanan tidak valid. Silakan coba lagi.');
                $this->redirect('admin/profile');
                return;
            }

            $nama = trim((string)$this->post('nama', ''));
            // Remove control characters
            $nama = preg_replace('/[\x00-\x1F\x7F]/', '', $nama);

            // Validation Rules
            if ($nama === '') {
                Session::setFlash('danger', 'Nama lengkap Admin tidak boleh kosong.');
                $this->redirect('admin/profile');
                return;
            }

            if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) {
                Session::setFlash('danger', 'Nama lengkap minimal 2 karakter dan maksimal 100 karakter.');
                $this->redirect('admin/profile');
                return;
            }

            $oldData = $this->userModel->find($userId);
            unset($oldData['password']);

            // Update ONLY the 'nama' column in database; preserve username, email, password, role, status, and ID
            $updated = $this->userModel->update($userId, [
                'nama' => $nama
            ]);

            if ($updated) {
                // Instantly update active session from database
                Auth::refreshUserSession();

                // Record audit log
                $this->auditModel->record(
                    $userId,
                    'UPDATE_ADMIN_PROFILE',
                    'users',
                    $userId,
                    ['nama' => $oldData['nama'] ?? null],
                    ['nama' => $nama]
                );

                Session::setFlash('success', 'Nama profil Admin berhasil diperbarui menjadi: ' . e($nama));
            } else {
                Session::setFlash('danger', 'Terjadi kesalahan sistem saat menyimpan nama profil.');
            }

            $this->redirect('admin/profile');
            return;
        }

        // Fetch fresh database row
        $freshUser = $this->userModel->find($userId);
        if ($freshUser) {
            unset($freshUser['password']);
        }

        $this->view('admin.profile', [
            'title' => 'Profil Admin / Super User — ' . APP_NAME,
            'user'  => $freshUser ?: $user,
        ]);
    }
}
