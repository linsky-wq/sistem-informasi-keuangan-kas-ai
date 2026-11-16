<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title & Add Member Action -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-people-fill me-1"></i> Master Data
                            </span>
                            <span class="badge bg-info text-dark rounded-pill">
                                Total <?= $total ?> Anggota
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Manajemen Pengguna/Anggota</h3>
                        <p class="text-secondary small mb-0">Kelola data seluruh siswa/anggota kas organisasi secara terpusat.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalKelolaAkunMember">
                            <i class="bi bi-key-fill"></i> Atur Akun Login
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahMember">
                            <i class="bi bi-person-plus-fill"></i> Tambah Anggota Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <form action="<?= base_url('admin/members') ?>" method="GET" class="row g-2 align-items-center">
                        <!-- Search Keyword -->
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cari nama, nomor anggota, NIS, email...">
                            </div>
                        </div>

                        <!-- Filter Kelas -->
                        <div class="col-md-3">
                            <select class="form-select bg-light" name="kelas">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= e($c) ?>" <?= ($filters['kelas'] ?? '') === $c ? 'selected' : '' ?>>
                                        Kelas <?= e($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter Status -->
                        <div class="col-md-2">
                            <select class="form-select bg-light" name="status">
                                <option value="">-- Semua Status --</option>
                                <option value="aktif" <?= ($filters['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($filters['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>

                        <!-- Submit & Reset Buttons -->
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 rounded-3">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>
                            <?php if (!empty($filters['search']) || !empty($filters['kelas']) || !empty($filters['status'])): ?>
                                <a href="<?= base_url('admin/members') ?>" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">No</th>
                                    <th class="py-3">No. Anggota</th>
                                    <th class="py-3">NIS</th>
                                    <th class="py-3">Nama Lengkap</th>
                                    <th class="py-3">Kelas</th>
                                    <th class="py-3">Email & No. HP</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="pe-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $idx => $m): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= (($page - 1) * $perPage) + $idx + 1 ?></td>
                                            <td>
                                                <span class="badge bg-light text-primary border font-monospace px-2 py-1">
                                                    <?= e($m['nomor_anggota']) ?>
                                                </span>
                                            </td>
                                            <td class="small font-monospace text-secondary">
                                                <?= !empty($m['nis']) ? e($m['nis']) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle <?= $m['jenis_kelamin'] === 'L' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' ?> d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width: 32px; height: 32px;">
                                                        <?= strtoupper(substr($m['nama'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-dark"><?= e($m['nama']) ?></div>
                                                        <small class="text-muted" style="font-size: 0.75rem;">Username: <?= e($m['username']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                                    <?= e($m['kelas']) ?>
                                                </span>
                                            </td>
                                            <td class="small">
                                                <div class="text-dark"><?= e($m['email']) ?></div>
                                                <small class="text-muted"><?= !empty($m['no_hp']) ? e($m['no_hp']) : '-' ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($m['status'] === 'aktif'): ?>
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
                                                    <!-- Detail Button -->
                                                    <button type="button" class="btn btn-outline-info btn-detail-member" data-id="<?= $m['id'] ?>" title="Lihat Detail Anggota">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <!-- Edit Button -->
                                                    <button type="button" class="btn btn-outline-primary btn-edit-member" data-id="<?= $m['id'] ?>" title="Edit Data Anggota">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <!-- Kelola Akun Login Button -->
                                                    <button type="button" class="btn btn-outline-dark btn-account-member" data-id="<?= $m['id'] ?>" title="Kelola Akun Login">
                                                        <i class="bi bi-key-fill"></i>
                                                    </button>
                                                    <!-- Toggle Status Button -->
                                                    <button type="button" class="btn <?= $m['status'] === 'aktif' ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-toggle-status" data-id="<?= $m['id'] ?>" data-name="<?= e($m['nama']) ?>" data-status="<?= $m['status'] ?>" title="<?= $m['status'] === 'aktif' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>">
                                                        <i class="bi <?= $m['status'] === 'aktif' ? 'bi-person-x' : 'bi-person-check' ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                            Tidak ada data anggota yang cocok dengan kriteria pencarian.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Navigation -->
                    <?php if ($totalPages > 1): ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top gap-2 bg-light">
                        <small class="text-muted">
                            Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $total ?> anggota)
                        </small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <!-- Previous -->
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= base_url('admin/members?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <!-- Page Numbers -->
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= base_url('admin/members?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <!-- Next -->
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= base_url('admin/members?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>">
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
<!-- MODAL: TAMBAH ANGGOTA BARU                              -->
<!-- ======================================================= -->
<div class="modal fade" id="modalTambahMember" tabindex="-1" aria-labelledby="modalTambahMemberLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-plus-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="modalTambahMemberLabel">Tambah Pengguna/Anggota Baru</h5>
                        <small class="text-white-50">Lengkapi data anggota untuk pembuatan akun kas</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/createMember') ?>" method="POST" id="formTambahMember">
                <?= \App\Core\Security::csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Nama Lengkap -->
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-secondary small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama" placeholder="Contoh: Muhammad Rizky" required>
                        </div>

                        <!-- Kelas -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-secondary small">Kelas / Jurusan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kelas" placeholder="Contoh: XII RPL 1" required>
                        </div>

                        <!-- Nomor Anggota -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nomor Anggota (Unik)</label>
                            <input type="text" class="form-control" name="nomor_anggota" value="<?= e($autoNomor) ?>" placeholder="ANG-xxx">
                            <small class="text-muted" style="font-size: 0.75rem;">Biarkan sesuai saran atau masukkan kode khusus.</small>
                        </div>

                        <!-- NIS -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">NIS (Nomor Induk Siswa)</label>
                            <input type="text" class="form-control" name="nis" placeholder="Contoh: 20261010">
                        </div>

                        <!-- Jenis Kelamin -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_kelamin" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <!-- Status Awal -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Status Akun</label>
                            <select class="form-select" name="status">
                                <option value="aktif" selected>Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Alamat Email</label>
                            <input type="email" class="form-control" name="email" placeholder="nama@kas.local / email aktif">
                        </div>

                        <!-- No HP / WhatsApp -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">No. HP / WhatsApp</label>
                            <input type="text" class="form-control" name="no_hp" placeholder="08xxxxxxxxxx">
                        </div>

                        <!-- Username Akun Login (Opsional) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Username Akun Login (Opsional)</label>
                            <input type="text" class="form-control font-monospace" name="username" placeholder="Otomatis jika kosong">
                            <small class="text-muted" style="font-size: 0.75rem;">Contoh: <code>budi_santoso</code> (huruf, angka, _)</small>
                        </div>

                        <!-- Password Awal Akun -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Password Awal Akun Login</label>
                            <input type="text" class="form-control bg-light font-monospace" name="password" value="member123">
                            <small class="text-muted" style="font-size: 0.75rem;">Default: <code>member123</code> (dapat diubah nanti).</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Data Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: EDIT DATA ANGGOTA                                -->
<!-- ======================================================= -->
<div class="modal fade" id="modalEditMember" tabindex="-1" aria-labelledby="modalEditMemberLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="modalEditMemberLabel">Edit Data Pengguna/Anggota</h5>
                        <small class="text-white-50">Perbarui profil dan informasi akun anggota</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/updateMember') ?>" method="POST" id="formEditMember">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="edit_id">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Nama Lengkap -->
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-secondary small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama" id="edit_nama" required>
                        </div>

                        <!-- Kelas -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-secondary small">Kelas / Jurusan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kelas" id="edit_kelas" required>
                        </div>

                        <!-- Nomor Anggota -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nomor Anggota (Unik)</label>
                            <input type="text" class="form-control" name="nomor_anggota" id="edit_nomor_anggota" required>
                        </div>

                        <!-- NIS -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">NIS</label>
                            <input type="text" class="form-control" name="nis" id="edit_nis">
                        </div>

                        <!-- Jenis Kelamin -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Jenis Kelamin</label>
                            <select class="form-select" name="jenis_kelamin" id="edit_jenis_kelamin">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <!-- Status Akun -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Status Akun</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Alamat Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>

                        <!-- No HP -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">No. HP / WhatsApp</label>
                            <input type="text" class="form-control" name="no_hp" id="edit_no_hp">
                        </div>

                        <!-- Username Akun Login -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Username Akun Login</label>
                            <input type="text" class="form-control font-monospace" name="username" id="edit_username" placeholder="Username akun">
                            <small class="text-muted" style="font-size: 0.75rem;">Username anggota untuk login kas.</small>
                        </div>

                        <!-- Reset Password (Opsional) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Ganti Password (Opsional)</label>
                            <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak diubah">
                            <small class="text-muted" style="font-size: 0.75rem;">Minimal 6 karakter jika diisi.</small>
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
<!-- MODAL: DETAIL ANGGOTA                                   -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDetailMember" tabindex="-1" aria-labelledby="modalDetailMemberLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-dark p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="modalDetailMemberLabel">Detail Profil Peserta Didik</h5>
                        <small class="text-dark-50">Informasi akun dan histori pembayaran kas anggota</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: KONFIRMASI TOGGLE STATUS                         -->
<!-- ======================================================= -->
<div class="modal fade" id="modalToggleStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-4">
            <div class="mb-3 text-warning">
                <i class="bi bi-exclamation-circle fs-1" id="toggleStatusIcon"></i>
            </div>
            <h5 class="fw-bold mb-2">Konfirmasi Status</h5>
            <p class="text-secondary small mb-4">
                Apakah Anda yakin ingin <span id="toggleStatusActionText" class="fw-bold"></span> status anggota <strong id="toggleStatusMemberName" class="text-dark"></strong>?
            </p>
            <form action="<?= base_url('admin/toggleMemberStatus') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="toggle_status_id">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-3 py-2 btn-sm fw-semibold" id="toggleStatusSubmitBtn">Ya, Lanjutkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: KELOLA / HUBUNGKAN AKUN LOGIN ANGGOTA            -->
<!-- ======================================================= -->
<div class="modal fade" id="modalKelolaAkunMember" tabindex="-1" aria-labelledby="modalKelolaAkunMemberLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-key-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="modalKelolaAkunMemberLabel">Atur & Hubungkan Akun Login Anggota</h5>
                        <small class="text-white-50">Kelola kredensial login (username & password) agar anggota dapat login mandiri</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/manageMemberAccount') ?>" method="POST" id="formKelolaAkunMember">
                <?= \App\Core\Security::csrfField() ?>
                <div class="modal-body p-4">
                    <!-- Pilih Anggota Terdaftar -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Pilih Anggota Terdaftar <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" name="member_id" id="account_member_id" required>
                            <option value="">-- Pilih Siswa / Anggota --</option>
                            <?php if (!empty($allMembers)): ?>
                                <?php foreach ($allMembers as $am): ?>
                                    <option value="<?= $am['id'] ?>">
                                        <?= e($am['nomor_anggota']) ?> — <?= e($am['nama']) ?> (Kelas <?= e($am['kelas']) ?>) <?= !empty($am['username']) ? '[Akun: ' . e($am['username']) . ']' : '[Belum Ada Akun]' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Pilih anggota yang ingin dibuatkan atau diperbarui akun loginnya.</small>
                    </div>

                    <div id="account_info_box" class="alert alert-light border py-2 px-3 small d-flex align-items-center gap-2 mb-3 d-none">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span id="account_info_text">Pilih anggota untuk memuat detail akun.</span>
                    </div>

                    <div class="row g-3">
                        <!-- Username Akun Login -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Username Akun Login <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control font-monospace" name="username" id="account_username" placeholder="Contoh: budi_santoso" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Digunakan anggota untuk masuk ke sistem kas (huruf, angka, _, titik).</small>
                        </div>

                        <!-- Email Akun Login -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Email Akun Login <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="account_email" placeholder="budi@kas.local" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Email akun (juga dapat digunakan untuk login).</small>
                        </div>

                        <!-- Password Akun -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-secondary small">Password Akun Login</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control font-monospace" name="password" id="account_password" placeholder="Minimal 6 karakter (cth: member123)">
                                <button class="btn btn-outline-secondary" type="button" id="toggleAccountPassBtn">
                                    <i class="bi bi-eye" id="toggleAccountPassIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="account_password_help" style="font-size: 0.75rem;">Kosongkan jika tidak ingin mengubah password akun yang sudah ada. Minimal 6 karakter.</small>
                        </div>

                        <!-- Status Akun Login -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small">Status Akun</label>
                            <select class="form-select" name="status" id="account_status">
                                <option value="active" selected>Active (Aktif)</option>
                                <option value="inactive">Inactive (Nonaktif)</option>
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">Status izin akses login.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Simpan & Hubungkan Akun Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Member Management Interactive Scripts -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Handle Edit Member Click & Fetch JSON Data
    document.querySelectorAll('.btn-edit-member').forEach(btn => {
        btn.addEventListener('click', async function () {
            const memberId = this.dataset.id;
            try {
                const res = await fetch(`<?= base_url('admin/getMemberJson') ?>?id=${memberId}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const m = result.data;
                    document.getElementById('edit_id').value = m.id;
                    document.getElementById('edit_nama').value = m.nama;
                    document.getElementById('edit_kelas').value = m.kelas;
                    document.getElementById('edit_nomor_anggota').value = m.nomor_anggota;
                    document.getElementById('edit_nis').value = m.nis || '';
                    document.getElementById('edit_jenis_kelamin').value = m.jenis_kelamin;
                    document.getElementById('edit_status').value = m.status;
                    document.getElementById('edit_email').value = m.email || '';
                    document.getElementById('edit_no_hp').value = m.no_hp || '';
                    document.getElementById('edit_username').value = m.username || '';

                    const modal = new bootstrap.Modal(document.getElementById('modalEditMember'));
                    modal.show();
                }
            } catch (err) {
                alert('Gagal mengambil data anggota: ' + err.message);
            }
        });
    });

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 2. Handle Detail Member Click & Render Modal Content
    document.querySelectorAll('.btn-detail-member').forEach(btn => {
        btn.addEventListener('click', async function () {
            const memberId = this.dataset.id;
            const detailContainer = document.getElementById('detailModalBody');
            detailContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

            const modal = new bootstrap.Modal(document.getElementById('modalDetailMember'));
            modal.show();

            try {
                const res = await fetch(`<?= base_url('admin/getMemberJson') ?>?id=${memberId}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const m = result.data;
                    const safeNama = escapeHtml(m.nama);
                    const safeNoAnggota = escapeHtml(m.nomor_anggota);
                    const safeNis = escapeHtml(m.nis || '-');
                    const safeKelas = escapeHtml(m.kelas);
                    const safeStatus = escapeHtml(m.status);
                    const safeEmail = escapeHtml(m.email || '-');
                    const safeNoHp = escapeHtml(m.no_hp || '-');

                    detailContainer.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nama Lengkap</small>
                                <strong class="text-dark fs-5">${safeNama}</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nomor Anggota / NIS</small>
                                <span class="badge bg-light text-primary border font-monospace me-1">${safeNoAnggota}</span>
                                <span class="text-secondary small font-monospace">${safeNis}</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Kelas</small>
                                <span class="badge bg-secondary-subtle text-secondary">${safeKelas}</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Jenis Kelamin</small>
                                <span>${m.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Status Akun</small>
                                <span class="badge ${m.status === 'aktif' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} border px-2 py-1">${safeStatus}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Email Akun</small>
                                <span>${safeEmail}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">No. HP / WhatsApp</small>
                                <span>${safeNoHp}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Username Akun Login</small>
                                <span class="badge bg-light text-primary border font-monospace px-2 py-1">${escapeHtml(m.username || 'Belum Ada Akun')}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Status Akun Login</small>
                                <span class="badge ${m.user_status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} border px-2 py-1">${escapeHtml(m.user_status || '-')}</span>
                            </div>
                            <div class="col-12"><hr class="my-2"></div>
                            <div class="col-12">
                                <h6 class="fw-bold mb-2">Riwayat Setoran Kas (${Number(m.payments_count || 0)} Transaksi)</h6>
                                ${m.payments && m.payments.length > 0 ? `
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered small mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Periode</th>
                                                    <th>Tanggal</th>
                                                    <th>Nominal</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${m.payments.map(p => `
                                                    <tr>
                                                        <td>${escapeHtml(p.periode)}</td>
                                                        <td>${escapeHtml(p.tanggal_pembayaran)}</td>
                                                        <td class="fw-bold">Rp ${Number(p.nominal).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                                                        <td><span class="badge ${p.status === 'Lunas' ? 'bg-success' : (p.status === 'Sebagian' ? 'bg-warning text-dark' : 'bg-danger')}">${escapeHtml(p.status)}</span></td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : '<p class="text-muted small mb-0">Belum ada riwayat pembayaran yang tercatat.</p>'}
                            </div>
                        </div>
                    `;
                }
            } catch (err) {
                detailContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat detail: ${escapeHtml(err.message)}</div>`;
            }
        });
    });

    // 3. Handle Toggle Status Confirmation
    document.querySelectorAll('.btn-toggle-status').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const currentStatus = this.dataset.status;
            const nextAction = currentStatus === 'aktif' ? 'menonaktifkan' : 'mengaktifkan';

            document.getElementById('toggle_status_id').value = id;
            document.getElementById('toggleStatusMemberName').textContent = name;
            document.getElementById('toggleStatusActionText').textContent = nextAction;
            
            const submitBtn = document.getElementById('toggleStatusSubmitBtn');
            submitBtn.className = currentStatus === 'aktif' ? 'btn btn-danger rounded-pill px-3 py-2 btn-sm fw-semibold' : 'btn btn-success rounded-pill px-3 py-2 btn-sm fw-semibold';

            const modal = new bootstrap.Modal(document.getElementById('modalToggleStatus'));
            modal.show();
        });
    });

    // 4. Handle Manage Member Account Click (Row Action)
    document.querySelectorAll('.btn-account-member').forEach(btn => {
        btn.addEventListener('click', function () {
            const memberId = this.dataset.id;
            const selectEl = document.getElementById('account_member_id');
            if (selectEl) {
                selectEl.value = memberId;
                selectEl.dispatchEvent(new Event('change'));
            }
            const modal = new bootstrap.Modal(document.getElementById('modalKelolaAkunMember'));
            modal.show();
        });
    });

    // 5. Handle Member Selection Change in modalKelolaAkunMember
    const accountMemberSelect = document.getElementById('account_member_id');
    if (accountMemberSelect) {
        accountMemberSelect.addEventListener('change', async function () {
            const memberId = this.value;
            const infoBox = document.getElementById('account_info_box');
            const infoText = document.getElementById('account_info_text');
            const usernameInput = document.getElementById('account_username');
            const emailInput = document.getElementById('account_email');
            const statusSelect = document.getElementById('account_status');
            const passwordInput = document.getElementById('account_password');
            const passwordHelp = document.getElementById('account_password_help');

            if (!memberId) {
                if (infoBox) infoBox.classList.add('d-none');
                if (usernameInput) usernameInput.value = '';
                if (emailInput) emailInput.value = '';
                if (passwordInput) {
                    passwordInput.value = '';
                    passwordInput.placeholder = 'Minimal 6 karakter';
                }
                return;
            }

            try {
                const res = await fetch(`<?= base_url('admin/getMemberJson') ?>?id=${memberId}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const m = result.data;
                    if (infoBox) infoBox.classList.remove('d-none');
                    if (m.username) {
                        if (infoText) {
                            infoText.innerHTML = `Anggota: <strong>${escapeHtml(m.nama)}</strong> | Akun Login Saat Ini: <code class="text-primary">${escapeHtml(m.username)}</code> (Status: <span class="badge bg-light text-dark border">${escapeHtml(m.user_status || 'active')}</span>)`;
                        }
                        if (usernameInput) usernameInput.value = m.username;
                        if (passwordInput) {
                            passwordInput.value = '';
                            passwordInput.placeholder = 'Kosongkan jika tidak ingin mengubah password';
                        }
                        if (passwordHelp) {
                            passwordHelp.textContent = 'Kosongkan jika tidak ingin mengubah password akun yang sudah ada. Minimal 6 karakter jika diisi.';
                        }
                    } else {
                        if (infoText) {
                            infoText.innerHTML = `Anggota: <strong>${escapeHtml(m.nama)}</strong> | <span class="text-warning fw-semibold">Belum memiliki akun login.</span> Silakan buat kredensial login baru di bawah.`;
                        }
                        if (usernameInput) {
                            usernameInput.value = (m.nama || '').toLowerCase().replace(/[^a-z0-9]/g, '_');
                        }
                        if (passwordInput) {
                            passwordInput.value = 'member123';
                            passwordInput.placeholder = 'Password akun baru (minimal 6 karakter)';
                        }
                        if (passwordHelp) {
                            passwordHelp.textContent = 'Wajib diisi untuk akun baru. Minimal 6 karakter (default: member123).';
                        }
                    }
                    if (emailInput) emailInput.value = m.email || (usernameInput.value + '@kas.local');
                    if (statusSelect) statusSelect.value = (m.user_status === 'inactive') ? 'inactive' : 'active';
                }
            } catch (err) {
                console.error('Error fetching member account info:', err);
            }
        });
    }

    // 6. Toggle Password Visibility in modalKelolaAkunMember
    document.getElementById('toggleAccountPassBtn')?.addEventListener('click', function () {
        const passInput = document.getElementById('account_password');
        const icon = document.getElementById('toggleAccountPassIcon');
        if (passInput) {
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon?.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passInput.type = 'password';
                icon?.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    });
});
</script>
