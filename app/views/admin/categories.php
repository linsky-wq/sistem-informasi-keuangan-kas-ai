<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title & Add Action -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-tags-fill me-1"></i> Master Data
                            </span>
                            <span class="badge bg-light text-dark border rounded-pill">
                                Total <?= $total ?> Kategori
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Manajemen Kategori Kas</h3>
                        <p class="text-secondary small mb-0">Kelola pos-pos kategori pemasukan dan pengeluaran kas sekolah/organisasi.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                            <i class="bi bi-plus-circle-fill"></i> Tambah Kategori Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Kategori Pemasukan</span>
                        <div class="bg-success-subtle text-success p-2 rounded-circle">
                            <i class="bi bi-arrow-down-left fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0"><?= $totalIncomeCats ?></h3>
                    <small class="text-muted">Pos penerimaan kas & iuran</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Kategori Pengeluaran</span>
                        <div class="bg-danger-subtle text-danger p-2 rounded-circle">
                            <i class="bi bi-arrow-up-right fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-0"><?= $totalExpenseCats ?></h3>
                    <small class="text-muted">Pos pengeluaran operasional</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Status Aktif</span>
                        <div class="bg-primary-subtle text-primary p-2 rounded-circle">
                            <i class="bi bi-check-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= $totalActiveCats ?> <span class="text-muted fs-6 fw-normal">/ <?= $total ?></span></h3>
                    <small class="text-muted">Kategori aktif dapat digunakan</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <form action="<?= base_url('admin/categories') ?>" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cari nama kategori atau deskripsi...">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <select class="form-select bg-light" name="tipe">
                                <option value="">-- Semua Tipe --</option>
                                <option value="pemasukan" <?= ($filters['tipe'] ?? '') === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="pengeluaran" <?= ($filters['tipe'] ?? '') === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select class="form-select bg-light" name="status">
                                <option value="">-- Semua Status --</option>
                                <option value="aktif" <?= ($filters['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($filters['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 rounded-3">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>
                            <?php if (!empty($filters['search']) || !empty($filters['tipe']) || !empty($filters['status'])): ?>
                                <a href="<?= base_url('admin/categories') ?>" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">No</th>
                                    <th class="py-3">Nama Kategori</th>
                                    <th class="py-3">Tipe</th>
                                    <th class="py-3">Deskripsi</th>
                                    <th class="py-3 text-center">Penggunaan Kas</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="pe-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $idx => $c): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= e($c['nama']) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($c['tipe'] === 'pemasukan'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-secondary">
                                                <?= !empty($c['deskripsi']) ? e($c['deskripsi']) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <?= $c['transaction_count'] ?> Transaksi
                                                </span>
                                                <?php if ($c['transaction_count'] > 0): ?>
                                                    <div class="small fw-semibold text-muted mt-1" style="font-size: 0.75rem;">
                                                        Rp <?= number_format((float)$c['total_nominal'], 2, ',', '.') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($c['status'] === 'aktif'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-slash-circle-fill me-1"></i> Nonaktif
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Edit Button -->
                                                    <button type="button" class="btn btn-outline-primary btn-edit-cat" data-id="<?= $c['id'] ?>" title="Edit Kategori">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <!-- Toggle Status Button -->
                                                    <button type="button" class="btn <?= $c['status'] === 'aktif' ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-toggle-cat-status" data-id="<?= $c['id'] ?>" data-name="<?= e($c['nama']) ?>" data-status="<?= $c['status'] ?>" title="<?= $c['status'] === 'aktif' ? 'Nonaktifkan Kategori' : 'Aktifkan Kategori' ?>">
                                                        <i class="bi <?= $c['status'] === 'aktif' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                    </button>
                                                    <!-- Delete / Safe Delete Button -->
                                                    <button type="button" class="btn btn-outline-danger btn-delete-cat" data-id="<?= $c['id'] ?>" data-name="<?= e($c['nama']) ?>" data-count="<?= $c['transaction_count'] ?>" title="Hapus Kategori">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
                                            Tidak ada kategori yang cocok dengan filter.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: TAMBAH KATEGORI                                  -->
<!-- ======================================================= -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-plus-circle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Tambah Kategori Kas</h5>
                        <small class="text-white-50">Definisikan pos baru transaksi kas</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/createCategory') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" placeholder="Contoh: Pembayaran Kas Mingguan / Donasi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tipe Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" name="tipe" required>
                            <option value="pemasukan" selected>Pemasukan (Income)</option>
                            <option value="pengeluaran">Pengeluaran (Expense)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Status</label>
                        <select class="form-select" name="status">
                            <option value="aktif" selected>Aktif (Dapat digunakan)</option>
                            <option value="nonaktif">Nonaktif (Diarsipkan)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small">Deskripsi (Opsional)</label>
                        <textarea class="form-control" name="deskripsi" rows="3" placeholder="Keterangan singkat peruntukan kategori..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: EDIT KATEGORI                                    -->
<!-- ======================================================= -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Edit Kategori Kas</h5>
                        <small class="text-white-50">Perbarui nama, tipe, dan deskripsi kategori</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/updateCategory') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="edit_cat_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="edit_cat_nama" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tipe Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" name="tipe" id="edit_cat_tipe" required>
                            <option value="pemasukan">Pemasukan (Income)</option>
                            <option value="pengeluaran">Pengeluaran (Expense)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Status</label>
                        <select class="form-select" name="status" id="edit_cat_status">
                            <option value="aktif">Aktif (Dapat digunakan)</option>
                            <option value="nonaktif">Nonaktif (Diarsipkan)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small">Deskripsi (Opsional)</label>
                        <textarea class="form-control" name="deskripsi" id="edit_cat_deskripsi" rows="3"></textarea>
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
<!-- MODAL: TOGGLE STATUS KATEGORI                           -->
<!-- ======================================================= -->
<div class="modal fade" id="modalToggleCatStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-4">
            <div class="mb-3 text-warning">
                <i class="bi bi-exclamation-circle fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2">Konfirmasi Status</h5>
            <p class="text-secondary small mb-4">
                Apakah Anda yakin ingin <span id="toggleCatActionText" class="fw-bold"></span> kategori <strong id="toggleCatName" class="text-dark"></strong>?
            </p>
            <form action="<?= base_url('admin/toggleCategoryStatus') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="toggle_cat_id">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-3 py-2 btn-sm fw-semibold" id="toggleCatSubmitBtn">Ya, Lanjutkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: HAPUS KATEGORI (SAFE CHECK)                      -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDeleteCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-4">
            <div class="mb-3 text-danger">
                <i class="bi bi-trash3-fill fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2 text-danger">Hapus Kategori</h5>
            <div id="deleteWarningArea">
                <p class="text-secondary small mb-4">
                    Apakah Anda yakin ingin menghapus kategori <strong id="deleteCatName" class="text-dark"></strong>?
                </p>
            </div>
            <form action="<?= base_url('admin/deleteCategory') ?>" method="POST" id="formDeleteCat">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="delete_cat_id">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3 py-2 btn-sm fw-semibold" id="btnConfirmDelete">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Edit Category AJAX
    document.querySelectorAll('.btn-edit-cat').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            try {
                const res = await fetch(`<?= base_url('admin/getCategoryJson') ?>?id=${id}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const c = result.data;
                    document.getElementById('edit_cat_id').value = c.id;
                    document.getElementById('edit_cat_nama').value = c.nama;
                    document.getElementById('edit_cat_tipe').value = c.tipe;
                    document.getElementById('edit_cat_status').value = c.status;
                    document.getElementById('edit_cat_deskripsi').value = c.deskripsi || '';

                    const modal = new bootstrap.Modal(document.getElementById('modalEditKategori'));
                    modal.show();
                }
            } catch (err) {
                alert('Gagal memuat kategori: ' + err.message);
            }
        });
    });

    // 2. Toggle Status Category Modal
    document.querySelectorAll('.btn-toggle-cat-status').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const status = this.dataset.status;
            const nextAction = status === 'aktif' ? 'menonaktifkan' : 'mengaktifkan';

            document.getElementById('toggle_cat_id').value = id;
            document.getElementById('toggleCatName').textContent = name;
            document.getElementById('toggleCatActionText').textContent = nextAction;

            const submitBtn = document.getElementById('toggleCatSubmitBtn');
            submitBtn.className = status === 'aktif' ? 'btn btn-danger rounded-pill px-3 py-2 btn-sm fw-semibold' : 'btn btn-success rounded-pill px-3 py-2 btn-sm fw-semibold';

            const modal = new bootstrap.Modal(document.getElementById('modalToggleCatStatus'));
            modal.show();
        });
    });

    // 3. Delete Category Modal with Transaction In-Use Warning
    document.querySelectorAll('.btn-delete-cat').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const count = parseInt(this.dataset.count || '0', 10);

            document.getElementById('delete_cat_id').value = id;
            document.getElementById('deleteCatName').textContent = name;

            const warningArea = document.getElementById('deleteWarningArea');
            const submitBtn = document.getElementById('btnConfirmDelete');

            if (count > 0) {
                warningArea.innerHTML = `
                    <div class="alert alert-warning py-2 px-3 small text-start mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Kategori ini telah digunakan pada <strong>${count} transaksi</strong> kas. Sistem akan menolak penghapusan destruktif untuk menjaga integritas data keuangan.
                    </div>
                    <p class="text-muted small mb-3">Sebaiknya gunakan fitur <strong>Nonaktifkan Kategori</strong> agar tidak dapat dipilih pada transaksi baru.</p>
                `;
            } else {
                warningArea.innerHTML = `
                    <p class="text-secondary small mb-4">
                        Apakah Anda yakin ingin menghapus kategori <strong class="text-dark">${name}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </p>
                `;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalDeleteCategory'));
            modal.show();
        });
    });
});
</script>
