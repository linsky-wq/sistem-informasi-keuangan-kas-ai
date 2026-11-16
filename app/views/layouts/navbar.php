<?php
$isLoggedIn = \App\Core\Auth::check();
$isSuperUser = \App\Core\Auth::isSuperUser();
$isBendahara = \App\Core\Auth::isBendahara();
$isPengawas = \App\Core\Auth::isPengawas();
$isMember = \App\Core\Auth::isMember();
$currentUser = \App\Core\Auth::user();
$currentMember = \App\Core\Auth::member();

$roleTitle = match(\App\Core\Auth::role()) {
    \App\Core\Auth::ROLE_SUPER_USER => 'Super User',
    \App\Core\Auth::ROLE_BENDAHARA => 'Bendahara',
    \App\Core\Auth::ROLE_PENGAWAS => 'Pengawas',
    default => 'Pengguna/Anggota'
};
$roleBadgeClass = match(\App\Core\Auth::role()) {
    \App\Core\Auth::ROLE_SUPER_USER => 'bg-primary-subtle text-primary border border-primary-subtle',
    \App\Core\Auth::ROLE_BENDAHARA => 'bg-info-subtle text-info border border-info-subtle',
    \App\Core\Auth::ROLE_PENGAWAS => 'bg-warning-subtle text-warning border border-warning-subtle',
    default => 'bg-success-subtle text-success border border-success-subtle'
};
$avatarBgClass = match(\App\Core\Auth::role()) {
    \App\Core\Auth::ROLE_SUPER_USER => 'bg-primary',
    \App\Core\Auth::ROLE_BENDAHARA => 'bg-info',
    \App\Core\Auth::ROLE_PENGAWAS => 'bg-warning',
    default => 'bg-success'
};
$roleIcon = match(\App\Core\Auth::role()) {
    \App\Core\Auth::ROLE_SUPER_USER => 'bi-shield-check',
    \App\Core\Auth::ROLE_BENDAHARA => 'bi-cash-coin',
    \App\Core\Auth::ROLE_PENGAWAS => 'bi-eye',
    default => 'bi-person-badge'
};
?>

<?php if ($isLoggedIn): ?>
<!-- TOPBAR FOR AUTHENTICATED USERS -->
<header class="app-navbar d-flex align-items-center justify-content-between px-3 px-md-4 sticky-top">
    <div class="d-flex align-items-center gap-3">
        <!-- Sidebar Toggle (Mobile & Tablet) -->
        <button class="btn btn-light btn-sm border d-lg-none shadow-xs d-flex align-items-center justify-content-center" type="button" id="sidebarToggle" aria-label="Buka Navigasi Menu" style="width: 38px; height: 38px; border-radius: 10px;">
            <i class="bi bi-list fs-5 text-dark"></i>
        </button>

        <!-- Brand / Context on Mobile & Desktop -->
        <div class="d-flex align-items-center gap-2">
            <span class="d-lg-none fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-primary"></i>
                <span><?= e(APP_NAME) ?></span>
            </span>
            <div class="d-none d-lg-block">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <span>Sistem Manajemen Kas</span>
                </h6>
            </div>
        </div>
    </div>

    <!-- Right Side Tools & User Profile -->
    <div class="d-flex align-items-center gap-2 gap-md-3">
        <!-- Role Badge -->
        <span class="badge <?= $roleBadgeClass ?> px-3 py-2 rounded-pill d-none d-sm-inline-flex align-items-center gap-1 font-monospace" style="font-size: 0.75rem;">
            <i class="bi <?= $roleIcon ?>"></i>
            <span><?= $roleTitle ?></span>
        </span>

        <!-- User Dropdown Menu -->
        <!-- User Dropdown Menu -->
        <?php
        $navDisplayName = $isMember 
            ? ($currentMember['nama'] ?? ($currentUser['username'] ?? 'User')) 
            : (!empty($currentUser['nama']) ? $currentUser['nama'] : ($currentUser['username'] ?? 'User'));
        ?>
        <div class="dropdown">
            <button class="btn btn-light btn-sm border rounded-pill dropdown-toggle d-flex align-items-center gap-2 px-2 px-md-3 py-1 shadow-xs" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle <?= $avatarBgClass ?> text-white d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.8rem;">
                    <?= strtoupper(substr($navDisplayName, 0, 1)) ?>
                </div>
                <span class="fw-semibold text-dark small d-none d-md-inline text-truncate" style="max-width: 140px;">
                    <?= e($navDisplayName) ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2 py-2" aria-labelledby="userMenuDropdown" style="min-width: 220px;">
                <li class="px-3 py-2 border-bottom bg-light bg-opacity-50">
                    <div class="fw-bold small text-dark text-truncate"><?= e($navDisplayName) ?></div>
                    <small class="text-muted text-truncate d-block" style="font-size: 0.75rem;"><?= e($currentUser['email'] ?? '') ?></small>
                </li>
                <?php if ($isSuperUser): ?>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('dashboard') ?>">
                            <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('admin/profile') ?>">
                            <i class="bi bi-person-circle text-primary"></i> Profil Admin
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('settings') ?>">
                            <i class="bi bi-gear text-secondary"></i> Pengaturan
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                <?php elseif ($isBendahara): ?>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('dashboard') ?>">
                            <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('transactions') ?>">
                            <i class="bi bi-arrow-left-right text-success"></i> Transaksi Kas
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('payments') ?>">
                            <i class="bi bi-receipt text-danger"></i> Pembayaran
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('reports') ?>">
                            <i class="bi bi-file-earmark-bar-graph text-primary"></i> Laporan
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                <?php elseif ($isPengawas): ?>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('dashboard') ?>">
                            <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('reports') ?>">
                            <i class="bi bi-file-earmark-bar-graph text-primary"></i> Laporan Keuangan
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                <?php elseif ($isMember): ?>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('member/notifications') ?>">
                            <i class="bi bi-bell text-warning"></i> Notifikasi Saya
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('member/profile') ?>">
                            <i class="bi bi-person text-warning"></i> Profil Saya
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('member/payments') ?>">
                            <i class="bi bi-wallet2 text-success"></i> Pembayaran Saya
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="<?= base_url('member/history') ?>">
                            <i class="bi bi-clock-history text-info"></i> Riwayat Pembayaran
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item py-2 small text-danger d-flex align-items-center gap-2" href="<?= base_url('auth/logout') ?>">
                        <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<?php else: ?>
<!-- TOPBAR FOR GUEST VISITORS -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-public shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= base_url() ?>">
            <div class="brand-icon bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px;">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <span><?= e(APP_NAME) ?></span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link active d-flex align-items-center gap-1" href="<?= base_url() ?>">
                        <i class="bi bi-house-door"></i> Beranda
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <a href="<?= base_url('auth/login') ?>" class="btn btn-light btn-sm fw-semibold rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-1">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk (Login)
                </a>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

