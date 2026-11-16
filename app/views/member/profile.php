<div class="container py-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width: 56px; height: 56px;">
                            <?= strtoupper(substr($member['nama'], 0, 1)) ?>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Profil Pengguna/Anggota</h4>
                            <p class="text-white-50 small mb-0">Informasi akun dan data diri anggota kas</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="<?= base_url('member/profile') ?>" method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="row g-3">
                            <!-- Nama Lengkap (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Nama Lengkap</label>
                                <input type="text" class="form-control bg-light" value="<?= e($member['nama']) ?>" readonly>
                            </div>

                            <!-- Nomor Anggota (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Nomor Anggota</label>
                                <input type="text" class="form-control bg-light" value="<?= e($member['nomor_anggota']) ?>" readonly>
                            </div>

                            <!-- NIS (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">NIS (Nomor Induk Siswa)</label>
                                <input type="text" class="form-control bg-light" value="<?= e($member['nis'] ?? '-') ?>" readonly>
                            </div>

                            <!-- Kelas (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Kelas</label>
                                <input type="text" class="form-control bg-light" value="<?= e($member['kelas']) ?>" readonly>
                            </div>

                            <!-- Jenis Kelamin (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Jenis Kelamin</label>
                                <input type="text" class="form-control bg-light" value="<?= $member['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>" readonly>
                            </div>

                            <!-- Username Akun (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Username Akun</label>
                                <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" readonly>
                            </div>

                            <!-- Email (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Email</label>
                                <input type="email" class="form-control bg-light" value="<?= e($member['email']) ?>" readonly>
                            </div>

                            <!-- Nomor HP / WhatsApp (Editable) -->
                            <div class="col-md-6">
                                <label for="no_hp" class="form-label fw-semibold text-secondary small">Nomor HP / WhatsApp</label>
                                <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= e($member['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                                <small class="text-muted">Nomor kontak aktif yang dapat dihubungi pengurus.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('member/dashboard') ?>" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
