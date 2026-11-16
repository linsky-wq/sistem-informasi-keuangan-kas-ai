<div class="container py-5 my-auto text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5">
                <div class="text-primary mb-3">
                    <i class="bi bi-exclamation-triangle-fill display-1"></i>
                </div>
                <h1 class="h2 fw-bold text-dark mb-2">404 - Halaman Tidak Ditemukan</h1>
                <p class="text-secondary mb-4">
                    <?= !empty($message) ? e($message) : 'Halaman yang Anda tuju tidak tersedia atau URL salah.' ?>
                </p>
                <div>
                    <a href="<?= base_url() ?>" class="btn btn-primary px-4 py-2 rounded-pill">
                        <i class="bi bi-house-door me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
