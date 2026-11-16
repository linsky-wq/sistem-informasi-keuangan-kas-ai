<div class="container py-5 my-auto text-center">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5">
                <div class="text-danger mb-3">
                    <i class="bi bi-shield-x display-1"></i>
                </div>
                <h1 class="h2 fw-bold text-dark mb-2">403 - Akses Ditolak</h1>
                <p class="text-secondary mb-4">
                    <?= !empty($message) ? e($message) : 'Anda tidak memiliki hak akses yang cukup untuk membuka halaman atau modul ini.' ?>
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <?php if (\App\Core\Auth::isMember()): ?>
                        <a href="<?= base_url('member/dashboard') ?>" class="btn btn-primary px-4 py-2 rounded-pill">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard Anggota
                        </a>
                    <?php elseif (\App\Core\Auth::check()): ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary px-4 py-2 rounded-pill">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('auth/login') ?>" class="btn btn-primary px-4 py-2 rounded-pill">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Halaman Login
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url() ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        <i class="bi bi-house me-1"></i> Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
