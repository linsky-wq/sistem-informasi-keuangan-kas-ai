<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title & Action -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-book-half me-1"></i> Buku Kas Umum
                            </span>
                            <span class="badge bg-light text-dark border rounded-pill">
                                Total <?= $total ?> Transaksi
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Transaksi Keuangan Kas</h3>
                        <p class="text-secondary small mb-0">Pencatatan mutasi arus kas masuk (pemasukan) dan keluar (pengeluaran) organisasi.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahTransaksi">
                            <i class="bi bi-plus-circle-fill"></i> Catat Transaksi Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Real-Time Financial Cards (Database Computed) -->
    <div class="row g-3 mb-4">
        <!-- Saldo Kas Bersih -->
        <div class="col-lg-4 col-md-12">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-primary text-white p-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-white text-primary px-3 py-1 rounded-pill fw-bold">
                            <i class="bi bi-calculator me-1"></i> Saldo Kas Terkini
                        </span>
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-wallet2 fs-5"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1 text-white">Rp <?= number_format($currentBalance, 2, ',', '.') ?></h2>
                    <small class="text-white-50">Total Pemasukan (Rp <?= number_format($totalIncome, 0, ',', '.') ?>) &minus; Total Pengeluaran (Rp <?= number_format($totalExpense, 0, ',', '.') ?>)</small>
                </div>
            </div>
        </div>

        <!-- Total Pemasukan -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Total Pemasukan (Income)</span>
                        <div class="bg-success-subtle text-success p-2 rounded-circle">
                            <i class="bi bi-arrow-down-left fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-1">Rp <?= number_format($totalIncome, 2, ',', '.') ?></h3>
                    <small class="text-muted">Akumulasi seluruh kas masuk & iuran</small>
                </div>
            </div>
        </div>

        <!-- Total Pengeluaran -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Total Pengeluaran (Expense)</span>
                        <div class="bg-danger-subtle text-danger p-2 rounded-circle">
                            <i class="bi bi-arrow-up-right fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-1">Rp <?= number_format($totalExpense, 2, ',', '.') ?></h3>
                    <small class="text-muted">Akumulasi biaya & belanja kas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <form action="<?= base_url('transactions') ?>" method="GET" class="row g-2 align-items-center">
                        <!-- Search Keyword -->
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cari kode, keterangan, kategori...">
                            </div>
                        </div>

                        <!-- Filter Tipe -->
                        <div class="col-md-2">
                            <select class="form-select bg-light" name="tipe">
                                <option value="">-- Semua Tipe --</option>
                                <option value="pemasukan" <?= ($filters['tipe'] ?? '') === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="pengeluaran" <?= ($filters['tipe'] ?? '') === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>

                        <!-- Filter Kategori -->
                        <div class="col-md-2">
                            <select class="form-select bg-light" name="category_id">
                                <option value="">-- Semua Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        [<?= $cat['tipe'] === 'pemasukan' ? 'Masuk' : 'Keluar' ?>] <?= e($cat['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Start -->
                        <div class="col-md-2">
                            <input type="date" class="form-control bg-light" name="start_date" value="<?= e($filters['start_date'] ?? '') ?>" title="Dari Tanggal">
                        </div>

                        <!-- Date End -->
                        <div class="col-md-2">
                            <input type="date" class="form-control bg-light" name="end_date" value="<?= e($filters['end_date'] ?? '') ?>" title="Sampai Tanggal">
                        </div>

                        <!-- Filter & Reset Buttons -->
                        <div class="col-md-1 d-flex gap-1">
                            <button type="submit" class="btn btn-primary w-100 rounded-3" title="Filter Transaksi">
                                <i class="bi bi-funnel-fill"></i>
                            </button>
                            <?php if (!empty($filters['search']) || !empty($filters['tipe']) || !empty($filters['category_id']) || !empty($filters['start_date']) || !empty($filters['end_date'])): ?>
                                <a href="<?= base_url('transactions') ?>" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">No</th>
                                    <th class="py-3">Kode Transaksi</th>
                                    <th class="py-3">Tanggal</th>
                                    <th class="py-3">Tipe</th>
                                    <th class="py-3">Kategori</th>
                                    <th class="py-3">Nominal</th>
                                    <th class="py-3">Keterangan</th>
                                    <th class="py-3">Dibuat Oleh</th>
                                    <th class="pe-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transactions)): ?>
                                    <?php foreach ($transactions as $idx => $t): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= (($page - 1) * $perPage) + $idx + 1 ?></td>
                                            <td>
                                                <span class="badge bg-light text-primary border font-monospace px-2 py-1">
                                                    <?= e($t['kode_transaksi']) ?>
                                                </span>
                                            </td>
                                            <td class="small text-dark">
                                                <?= date('d M Y', strtotime($t['tanggal'])) ?>
                                            </td>
                                            <td>
                                                <?php if ($t['tipe'] === 'pemasukan'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                                    <?= e($t['category_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold <?= $t['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $t['tipe'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format((float)$t['nominal'], 2, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td class="small text-secondary" style="max-width: 250px;">
                                                <div class="text-truncate"><?= e($t['keterangan']) ?></div>
                                            </td>
                                            <td>
                                                <small class="badge bg-light text-muted border">
                                                    <i class="bi bi-person me-1"></i> <?= e($t['creator_name']) ?>
                                                </small>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Detail Button -->
                                                    <button type="button" class="btn btn-outline-info btn-detail-trx" data-id="<?= $t['id'] ?>" title="Detail Transaksi">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <!-- Edit Button -->
                                                    <button type="button" class="btn btn-outline-primary btn-edit-trx" data-id="<?= $t['id'] ?>" title="Edit Transaksi">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <!-- Delete Button -->
                                                    <button type="button" class="btn btn-outline-danger btn-delete-trx" data-id="<?= $t['id'] ?>" data-kode="<?= e($t['kode_transaksi']) ?>" data-nominal="Rp <?= number_format((float)$t['nominal'], 2, ',', '.') ?>" title="Hapus Transaksi">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                            Tidak ada data transaksi yang sesuai dengan kriteria filter.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top gap-2 bg-light">
                        <small class="text-muted">
                            Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $total ?> transaksi)
                        </small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= base_url('transactions?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= base_url('transactions?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= base_url('transactions?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: CATAT TRANSAKSI BARU                             -->
<!-- ======================================================= -->
<div class="modal fade" id="modalTambahTransaksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Catat Transaksi Kas Baru</h5>
                        <small class="text-white-50">Masukkan rincian mutasi pemasukan atau pengeluaran kas</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('transactions/create') ?>" method="POST" id="formTambahTransaksi">
                <?= \App\Core\Security::csrfField() ?>
                <div class="modal-body p-4">
                    <!-- Tipe Transaksi Selection Pills -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary small d-block mb-2">Pilih Tipe Transaksi <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipe" id="tambah_tipe_in" value="pemasukan" checked>
                                <label class="btn btn-outline-success w-100 py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold" for="tambah_tipe_in">
                                    <i class="bi bi-arrow-down-left"></i> Pemasukan (Kas Masuk)
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipe" id="tambah_tipe_out" value="pengeluaran">
                                <label class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold" for="tambah_tipe_out">
                                    <i class="bi bi-arrow-up-right"></i> Pengeluaran (Kas Keluar)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Tanggal Transaksi -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" value="<?= $todayDate ?>" required>
                        </div>

                        <!-- Kategori Kas (Filtered by Type) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Kategori Kas <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="tambah_category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($incomeCategories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nominal (Uang) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nominal Uang (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                                <input type="number" step="0.01" min="1" class="form-control" name="nominal" id="tambah_nominal" placeholder="Contoh: 50000" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Masukkan angka positif tanpa titik/koma.</small>
                        </div>

                        <!-- Kode Transaksi (Otomatis) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Kode Transaksi (Otomatis)</label>
                            <input type="text" class="form-control bg-light font-monospace" name="kode_transaksi" id="tambah_kode_transaksi" value="<?= e($autoIncomeKode) ?>" placeholder="Otomatis digenerate">
                        </div>

                        <!-- Keterangan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Keterangan / Rincian Transaksi <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="keterangan" rows="3" placeholder="Jelaskan sumber pemasukan atau peruntukan pengeluaran secara lengkap..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: EDIT TRANSAKSI                                   -->
<!-- ======================================================= -->
<div class="modal fade" id="modalEditTransaksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Edit Transaksi Kas</h5>
                        <small class="text-white-50">Perbarui rincian transaksi kas yang telah dicatat</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('transactions/update') ?>" method="POST" id="formEditTransaksi">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="edit_trx_id">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Kode Transaksi (Read-only) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Kode Transaksi</label>
                            <input type="text" class="form-control bg-light font-monospace" id="edit_trx_kode" readonly>
                        </div>

                        <!-- Tanggal -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" id="edit_trx_tanggal" required>
                        </div>

                        <!-- Tipe -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Tipe Transaksi <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipe" id="edit_trx_tipe" required>
                                <option value="pemasukan">Pemasukan (Kas Masuk)</option>
                                <option value="pengeluaran">Pengeluaran (Kas Keluar)</option>
                            </select>
                        </div>

                        <!-- Kategori -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Kategori Kas <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="edit_trx_category_id" required>
                                <!-- Populated dynamically by JS -->
                            </select>
                        </div>

                        <!-- Nominal -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-secondary small">Nominal Uang (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                                <input type="number" step="0.01" min="1" class="form-control" name="nominal" id="edit_trx_nominal" required>
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Keterangan / Rincian <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="keterangan" id="edit_trx_keterangan" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: DETAIL TRANSAKSI                                 -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDetailTransaksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-dark p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-receipt fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Detail Bukti Transaksi Kas</h5>
                        <small class="text-dark-50">Informasi lengkap transaksi buku kas umum</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailTrxModalBody">
                <!-- Loaded via AJAX -->
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: KONFIRMASI HAPUS TRANSAKSI                       -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDeleteTransaksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-4">
            <div class="mb-3 text-danger">
                <i class="bi bi-exclamation-triangle-fill fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2 text-danger">Hapus Transaksi</h5>
            <p class="text-secondary small mb-3">
                Apakah Anda yakin ingin menghapus transaksi <strong id="deleteTrxKode" class="text-dark"></strong> sebesar <strong id="deleteTrxNominal" class="text-dark"></strong>?
            </p>
            <div class="alert alert-warning py-2 px-3 small text-start mb-4">
                <i class="bi bi-info-circle me-1"></i>
                Saldo kas bersih akan dihitung ulang secara otomatis setelah transaksi ini dihapus.
            </div>
            <form action="<?= base_url('transactions/delete') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="delete_trx_id">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3 py-2 btn-sm fw-semibold">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dynamic Modal & Type Switch Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dynamic Category Dropdown filter on Add Modal Type Switch
    async function loadCategoriesForType(type, targetSelectEl, selectedId = null) {
        try {
            const res = await fetch(`<?= base_url('transactions/getCategoriesJson') ?>?tipe=${type}`);
            const result = await res.json();
            if (result.status === 'success') {
                targetSelectEl.innerHTML = '<option value="">-- Pilih Kategori --</option>';
                result.data.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.nama;
                    if (selectedId && Number(selectedId) === Number(cat.id)) {
                        opt.selected = true;
                    }
                    targetSelectEl.appendChild(opt);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    const tambahTipeIn = document.getElementById('tambah_tipe_in');
    const tambahTipeOut = document.getElementById('tambah_tipe_out');
    const tambahCatSelect = document.getElementById('tambah_category_id');
    const tambahKodeInput = document.getElementById('tambah_kode_transaksi');

    if (tambahTipeIn && tambahTipeOut) {
        tambahTipeIn.addEventListener('change', () => {
            if (tambahTipeIn.checked) {
                loadCategoriesForType('pemasukan', tambahCatSelect);
                tambahKodeInput.value = '<?= e($autoIncomeKode) ?>';
            }
        });

        tambahTipeOut.addEventListener('change', () => {
            if (tambahTipeOut.checked) {
                loadCategoriesForType('pengeluaran', tambahCatSelect);
                tambahKodeInput.value = '<?= e($autoExpenseKode) ?>';
            }
        });
    }

    // 2. Edit Transaction Modal Handler
    document.querySelectorAll('.btn-edit-trx').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            try {
                const res = await fetch(`<?= base_url('transactions/getJson') ?>?id=${id}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const t = result.data;
                    document.getElementById('edit_trx_id').value = t.id;
                    document.getElementById('edit_trx_kode').value = t.kode_transaksi;
                    document.getElementById('edit_trx_tanggal').value = t.tanggal;
                    document.getElementById('edit_trx_tipe').value = t.tipe;
                    document.getElementById('edit_trx_nominal').value = t.nominal;
                    document.getElementById('edit_trx_keterangan').value = t.keterangan;

                    const editCatSelect = document.getElementById('edit_trx_category_id');
                    await loadCategoriesForType(t.tipe, editCatSelect, t.category_id);

                    const modal = new bootstrap.Modal(document.getElementById('modalEditTransaksi'));
                    modal.show();
                }
            } catch (err) {
                alert('Gagal memuat data transaksi: ' + err.message);
            }
        });
    });

    // Edit Type Change Listener
    const editTipeSelect = document.getElementById('edit_trx_tipe');
    if (editTipeSelect) {
        editTipeSelect.addEventListener('change', function () {
            const editCatSelect = document.getElementById('edit_trx_category_id');
            loadCategoriesForType(this.value, editCatSelect);
        });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 3. Detail Transaction Modal Handler
    document.querySelectorAll('.btn-detail-trx').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const container = document.getElementById('detailTrxModalBody');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

            const modal = new bootstrap.Modal(document.getElementById('modalDetailTransaksi'));
            modal.show();

            try {
                const res = await fetch(`<?= base_url('transactions/getJson') ?>?id=${id}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const t = result.data;
                    const isIncome = t.tipe === 'pemasukan';
                    const safeKode = escapeHtml(t.kode_transaksi);
                    const safeTanggal = escapeHtml(t.tanggal);
                    const safeCatName = escapeHtml(t.category_name);
                    const safeKet = escapeHtml(t.keterangan);
                    const safeCreator = escapeHtml(t.creator_name);
                    const safeCreatedAt = escapeHtml(t.created_at);

                    container.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Kode Transaksi</small>
                                <span class="badge bg-light text-primary border font-monospace fs-6">${safeKode}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Tanggal Transaksi</small>
                                <strong class="text-dark">${safeTanggal}</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Tipe Mutasi</small>
                                <span class="badge ${isIncome ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'} px-3 py-1 rounded-pill">
                                    ${isIncome ? 'Pemasukan (Masuk)' : 'Pengeluaran (Keluar)'}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Kategori Pos</small>
                                <span class="badge bg-secondary-subtle text-secondary fs-6">${safeCatName}</span>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block">Nominal Uang</small>
                                <h3 class="fw-bold ${isIncome ? 'text-success' : 'text-danger'} mb-0">
                                    ${isIncome ? '+' : '-'} Rp ${Number(t.nominal).toLocaleString('id-ID', {minimumFractionDigits: 2})}
                                </h3>
                            </div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12">
                                <small class="text-muted d-block">Keterangan / Uraian</small>
                                <p class="text-dark mb-0">${safeKet}</p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Dicatat Oleh</small>
                                <span class="text-secondary small font-monospace">${safeCreator}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Waktu Pencatatan</small>
                                <span class="text-secondary small">${safeCreatedAt}</span>
                            </div>
                        </div>
                    `;
                }
            } catch (err) {
                container.innerHTML = `<div class="alert alert-danger">Gagal memuat detail: ${escapeHtml(err.message)}</div>`;
            }
        });
    });

    // 4. Delete Transaction Modal Handler
    document.querySelectorAll('.btn-delete-trx').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('delete_trx_id').value = this.dataset.id;
            document.getElementById('deleteTrxKode').textContent = this.dataset.kode;
            document.getElementById('deleteTrxNominal').textContent = this.dataset.nominal;

            const modal = new bootstrap.Modal(document.getElementById('modalDeleteTransaksi'));
            modal.show();
        });
    });
});
</script>
