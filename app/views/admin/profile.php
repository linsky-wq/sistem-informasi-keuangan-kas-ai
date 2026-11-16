<div class="container py-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('settings') ?>" class="text-decoration-none">Pengaturan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profil Admin</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <!-- Card Header -->
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm flex-shrink-0" style="width: 56px; height: 56px;">
                            <?= strtoupper(substr(!empty($user['nama']) ? $user['nama'] : $user['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-white text-primary rounded-pill px-2 py-1 font-monospace" style="font-size: 0.7rem;">
                                    <i class="bi bi-shield-check me-1"></i> Super User
                                </span>
                                <span class="badge bg-success-subtle text-white rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-check-circle me-1"></i> Akun Terverifikasi
                                </span>
                            </div>
                            <h4 class="fw-bold mb-1"><?= e(!empty($user['nama']) ? $user['nama'] : 'Profil Administrator') ?></h4>
                            <p class="text-white-50 small mb-0">Kelola nama tampilan profil dan pantau informasi akun administrator.</p>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body p-4 p-md-5">
                    <form action="<?= base_url('admin/profile') ?>" method="POST" id="adminProfileForm">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="alert alert-info border-0 rounded-3 small d-flex align-items-center mb-4">
                            <i class="bi bi-info-circle-fill me-2 fs-5 flex-shrink-0 text-info"></i>
                            <div>
                                Anda dapat memperbarui <strong>Nama Lengkap / Nama Tampilan</strong> akun Admin. Nama ini akan disimpan ke database dan ditampilkan secara konsisten pada dashboard, navbar, serta AI Asisten. Username login, password, dan email tetap tidak berubah.
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Nama Lengkap / Display Name (Editable) -->
                            <div class="col-12">
                                <label for="nama" class="form-label fw-bold text-dark">
                                    Nama Lengkap / Nama Tampilan <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control form-control-lg fs-6" id="nama" name="nama" 
                                           value="<?= e($user['nama'] ?? '') ?>" 
                                           placeholder="Masukkan nama lengkap Anda (misal: Muhammad Pigih)" 
                                           required minlength="2" maxlength="100" autofocus>
                                </div>
                                <div class="form-text small text-muted">
                                    Nama resmi atau nama panggilan yang akan ditampilkan oleh sistem dan AI Asisten saat Anda bertanya “Siapa saya?”.
                                </div>
                            </div>

                            <!-- Username (Read-Only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Username Akun</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-at"></i></span>
                                    <input type="text" class="form-control bg-light text-dark" value="<?= e($user['username']) ?>" readonly>
                                </div>
                                <div class="form-text small text-muted">Username digunakan untuk masuk (login) ke sistem.</div>
                            </div>

                            <!-- Email (Read-Only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Alamat Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control bg-light text-dark" value="<?= e($user['email']) ?>" readonly>
                                </div>
                                <div class="form-text small text-muted">Email resmi yang terhubung dengan akun Admin.</div>
                            </div>

                            <!-- Role / Peran (Read-Only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Role / Hak Akses</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-shield-lock"></i></span>
                                    <input type="text" class="form-control bg-light text-dark fw-semibold" value="<?= e(strtoupper(str_replace('_', ' ', $user['role']))) ?> (Akses Penuh)" readonly>
                                </div>
                            </div>

                            <!-- Status Akun (Read-Only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Status Akun</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-check2-circle"></i></span>
                                    <input type="text" class="form-control bg-light text-success fw-bold" value="Aktif (Active)" readonly>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Action Buttons -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary rounded-pill px-4 py-2 order-2 order-sm-1">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm order-1 order-sm-2 d-flex align-items-center gap-2">
                                <i class="bi bi-check2-circle"></i> Simpan Nama Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
