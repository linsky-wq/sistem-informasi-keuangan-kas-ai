<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($title) ? e($title) : e(APP_NAME) ?></title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <!-- Brand Header -->
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1"><?= e(APP_NAME) ?></h3>
                    <p class="text-secondary small mb-0">Silakan masukkan akun Anda untuk melanjutkan</p>
                </div>

                <!-- Flash Messages -->
                <?php $flashes = \App\Core\Session::getFlashes(); ?>
                <?php if (!empty($flashes)): ?>
                    <?php foreach ($flashes as $type => $messages): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm rounded-3 py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                                <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                                <div><?= e($msg) ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Login Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="card-body p-4 p-sm-5">
                        <form action="<?= base_url('auth/login') ?>" method="POST" id="loginForm">
                            <!-- CSRF Field -->
                            <?= \App\Core\Security::csrfField() ?>

                            <!-- Username / Email -->
                            <div class="mb-3">
                                <label for="identifier" class="form-label fw-semibold text-secondary small">Username atau Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 ps-0" id="identifier" name="identifier" placeholder="admin atau ahmad_fauzi" required autofocus>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label fw-semibold text-secondary small mb-0">Password</label>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="••••••••" required>
                                    <button class="btn btn-outline-light text-muted border border-start-0" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
                            </button>
                        </form>
                    </div>
                    
                    <!-- Quick Demo Credentials Box -->
                    <div class="card-footer bg-light border-top p-3 text-center">
                        <p class="text-muted small fw-semibold mb-2">Akun Uji Coba Cepat (Klik untuk Mengisi):</p>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill demo-login-btn px-3" data-user="admin" data-pass="admin123">
                                <i class="bi bi-shield-check me-1"></i> Admin / Super User
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill demo-login-btn px-3" data-user="bendahara" data-pass="bendahara123">
                                <i class="bi bi-cash-stack me-1"></i> Bendahara
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill demo-login-btn px-3" data-user="ahmad_fauzi" data-pass="member123">
                                <i class="bi bi-person me-1"></i> Anggota (Ahmad)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Back to Home Link -->
                <div class="text-center">
                    <a href="<?= base_url() ?>" class="text-decoration-none text-secondary small">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Depan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle visibility
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        // Quick Demo button autofill
        document.querySelectorAll('.demo-login-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('identifier').value = this.dataset.user;
                document.getElementById('password').value = this.dataset.pass;
            });
        });
    </script>
</body>
</html>
