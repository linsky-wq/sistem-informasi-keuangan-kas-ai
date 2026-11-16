<div class="container py-4 flex-grow-1">
    <!-- Header Hero Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                                <i class="bi bi-cpu me-1"></i> Sistem Keuangan Kas Terintegrasi AI
                            </span>
                            <h1 class="display-6 fw-bold text-dark mb-2">
                                <?= e(APP_NAME) ?>
                            </h1>
                            <p class="text-secondary lead fs-6 mb-3">
                                Platform pengelolaan kas sekolah dan organisasi dengan arsitektur MVC yang bersih, aman, dan siap diperluas.
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-3">
                                    <i class="bi bi-check-circle me-1"></i> MVC Framework Ready
                                </span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-3">
                                    <i class="bi bi-shield-check me-1"></i> Security & CSRF Layer Active
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-3">
                                    <i class="bi bi-bootstrap me-1"></i> Bootstrap 5.3 UI
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center mt-4 mt-lg-0">
                            <div class="status-box p-4 rounded-4 bg-light border text-center">
                                <div class="fs-1 text-success mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Status Setup Task 01</h6>
                                <p class="text-muted small mb-0">Arsitektur dasar berhasil dikonfigurasi & siap dijalankan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostics & Healthcheck Section -->
    <div class="row g-4 mb-4">
        <!-- PHP & Server Environment -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                            <i class="bi bi-filetype-php fs-4"></i>
                        </span>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <h6 class="card-subtitle text-muted mb-1 small">Bahasa Pemrograman</h6>
                    <h5 class="card-title fw-bold text-dark mb-0">PHP <?= PHP_VERSION ?></h5>
                    <p class="text-secondary small mt-2 mb-0">CLI & Web Runtime siap</p>
                </div>
            </div>
        </div>

        <!-- Database Status -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-info-subtle text-info p-2 rounded-3">
                            <i class="bi bi-database fs-4"></i>
                        </span>
                        <?php if ($sysInfo['db_status']['connected']): ?>
                            <span class="badge bg-success">Terhubung</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Siap Migrasi</span>
                        <?php endif; ?>
                    </div>
                    <h6 class="card-subtitle text-muted mb-1 small">Database Relational</h6>
                    <h5 class="card-title fw-bold text-dark mb-0">MySQL / MariaDB</h5>
                    <p class="text-secondary small mt-2 mb-0">
                        Host: <code><?= e($sysInfo['db_status']['host']) ?></code>
                    </p>
                </div>
            </div>
        </div>

        <!-- Architecture & Router -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-warning-subtle text-warning p-2 rounded-3">
                            <i class="bi bi-diagram-3 fs-4"></i>
                        </span>
                        <span class="badge bg-success">Aktif</span>
                    </div>
                    <h6 class="card-subtitle text-muted mb-1 small">Pola Arsitektur</h6>
                    <h5 class="card-title fw-bold text-dark mb-0">MVC Clean Pattern</h5>
                    <p class="text-secondary small mt-2 mb-0">Front Controller & PSR-4</p>
                </div>
            </div>
        </div>

        <!-- Security Baseline -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-danger-subtle text-danger p-2 rounded-3">
                            <i class="bi bi-shield-lock fs-4"></i>
                        </span>
                        <span class="badge bg-success">Proteksi Siap</span>
                    </div>
                    <h6 class="card-subtitle text-muted mb-1 small">Proteksi Keamanan</h6>
                    <h5 class="card-title fw-bold text-dark mb-0">CSRF, XSS, PDO</h5>
                    <p class="text-secondary small mt-2 mb-0">Prepared Statements</p>
                </div>
            </div>
        </div>
    </div>

    <!-- User Roles & Access Overview -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary p-2 rounded-circle">
                            <i class="bi bi-person-badge fs-6"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-0">Role 1: Super User</h5>
                            <small class="text-muted">Akses Penuh Pengelolaan Sistem</small>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 mt-2">
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-primary fw-bold"></i> Dashboard & Laporan Keuangan Lengkap
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-primary fw-bold"></i> Manajemen Pengguna/Anggota / Anggota & Kategori Kas
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-primary fw-bold"></i> Pencatatan Pemasukan, Pengeluaran & Tagihan
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-primary fw-bold"></i> Verifikasi Pembayaran & Histori Transaksi
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-primary fw-bold"></i> AI Financial Insight & Asisten Analitik
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary p-2 rounded-circle">
                            <i class="bi bi-people fs-6"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-0">Role 2: Pengguna/Anggota</h5>
                            <small class="text-muted">Akses Terbatas & Personal</small>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 mt-2">
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-success fw-bold"></i> Dashboard Pribadi & Ringkasan Akun
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-success fw-bold"></i> Informasi Profil & Pengaturan Akun
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-success fw-bold"></i> Melihat Tagihan Kas yang Dibebankan
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-success fw-bold"></i> Status Pembayaran (Belum Bayar, Sebagian, Lunas)
                        </li>
                        <li class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="bi bi-check2 text-success fw-bold"></i> Histori Pembayaran Kas Pribadi
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
