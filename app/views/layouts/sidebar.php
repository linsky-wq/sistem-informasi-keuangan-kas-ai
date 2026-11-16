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
$roleBadgeBg = match(\App\Core\Auth::role()) {
    \App\Core\Auth::ROLE_SUPER_USER => 'bg-primary',
    \App\Core\Auth::ROLE_BENDAHARA => 'bg-info',
    \App\Core\Auth::ROLE_PENGAWAS => 'bg-warning',
    default => 'bg-success'
};
?>

<?php if ($isLoggedIn): ?>
<!-- Sidebar Backdrop for Mobile/Tablet (< 992px) -->
<div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop" aria-hidden="true"></div>

<!-- Main Application Sidebar -->
<aside class="app-sidebar shadow-lg" id="appSidebar">
    <!-- Brand Logo Area -->
    <a href="<?= $isMember ? base_url('member/dashboard') : base_url('dashboard') ?>" class="sidebar-brand text-decoration-none">
        <div class="sidebar-brand-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm flex-shrink-0" style="width: 38px; height: 38px;">
            <i class="bi bi-wallet2 fs-5"></i>
        </div>
        <div class="overflow-hidden">
            <div class="fw-bold fs-6 lh-1 text-white text-truncate"><?= e(APP_NAME) ?></div>
            <small class="text-secondary d-block mt-1" style="font-size: 0.72rem;">Sistem Keuangan Kas</small>
        </div>
    </a>

    <!-- Sidebar Navigation Menu -->
    <nav class="sidebar-menu" aria-label="Menu Utama Sidebar">
        <?php if ($isSuperUser): ?>
            <!-- SUPER USER MENU -->
            <div class="sidebar-menu-heading">Menu Utama</div>
            
            <a href="<?= base_url('dashboard') ?>" class="sidebar-nav-link <?= is_active_route('dashboard') ?>">
                <i class="bi bi-speedometer2 text-primary"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= base_url('admin/members') ?>" class="sidebar-nav-link <?= is_active_route('admin/members') ?>">
                <i class="bi bi-people-fill text-info"></i>
                <span>Anggota</span>
            </a>

            <a href="<?= base_url('admin/categories') ?>" class="sidebar-nav-link <?= is_active_route('admin/categories') ?>">
                <i class="bi bi-tags-fill text-warning"></i>
                <span>Kategori</span>
            </a>

            <a href="<?= base_url('transactions') ?>" class="sidebar-nav-link <?= is_active_route('transactions') ?>">
                <i class="bi bi-arrow-left-right text-success"></i>
                <span>Transaksi</span>
            </a>

            <a href="<?= base_url('payments') ?>" class="sidebar-nav-link <?= is_active_route('payments') ?>">
                <i class="bi bi-receipt-cutoff text-danger"></i>
                <span>Pembayaran</span>
            </a>

            <a href="<?= base_url('reports') ?>" class="sidebar-nav-link <?= is_active_route('reports') ?>">
                <i class="bi bi-file-earmark-bar-graph-fill text-primary"></i>
                <span>Laporan</span>
            </a>

            <div class="sidebar-menu-heading">Layanan Pendukung</div>
            <a href="<?= base_url('whatsapp') ?>" class="sidebar-nav-link <?= is_active_route('whatsapp') ?>">
                <i class="bi bi-whatsapp text-success"></i>
                <span>WhatsApp (WA)</span>
                <span class="badge bg-success-subtle text-success ms-auto fw-bold" style="font-size: 0.65rem;">WA</span>
            </a>
            <!-- Submenu for WhatsApp logs -->
            <a href="<?= base_url('whatsapp/logs') ?>" class="sidebar-nav-link <?= is_active_route('whatsapp/logs') ?>" style="margin-left: 1.5rem;">
                <i class="bi bi-clock-history text-muted"></i>
                <span>Riwayat Pesan</span>
            </a>

            <div class="sidebar-menu-heading">Akun & Sistem</div>
            <a href="<?= base_url('admin/profile') ?>" class="sidebar-nav-link <?= is_active_route('admin/profile') ?>">
                <i class="bi bi-person-circle text-primary"></i>
                <span>Profil Admin</span>
            </a>
            <a href="<?= base_url('settings') ?>" class="sidebar-nav-link <?= is_active_route('settings') ?>">
                <i class="bi bi-gear-fill text-secondary"></i>
                <span>Pengaturan</span>
            </a>

        <?php elseif ($isBendahara): ?>
            <!-- BENDAHARA MENU -->
            <div class="sidebar-menu-heading">Menu Utama</div>

            <a href="<?= base_url('dashboard') ?>" class="sidebar-nav-link <?= is_active_route('dashboard') ?>">
                <i class="bi bi-speedometer2 text-primary"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= base_url('transactions') ?>" class="sidebar-nav-link <?= is_active_route('transactions') ?>">
                <i class="bi bi-arrow-left-right text-success"></i>
                <span>Transaksi Kas</span>
            </a>

            <a href="<?= base_url('payments') ?>" class="sidebar-nav-link <?= is_active_route('payments') ?>">
                <i class="bi bi-receipt-cutoff text-danger"></i>
                <span>Pembayaran & Verifikasi</span>
            </a>

            <a href="<?= base_url('reports') ?>" class="sidebar-nav-link <?= is_active_route('reports') ?>">
                <i class="bi bi-file-earmark-bar-graph-fill text-primary"></i>
                <span>Laporan Keuangan</span>
            </a>

            <div class="sidebar-menu-heading">Layanan Pendukung</div>
            <a href="<?= base_url('whatsapp') ?>" class="sidebar-nav-link <?= is_active_route('whatsapp') ?>">
                <i class="bi bi-whatsapp text-success"></i>
                <span>WhatsApp (WA)</span>
                <span class="badge bg-success-subtle text-success ms-auto fw-bold" style="font-size: 0.65rem;">WA</span>
            </a>
            <a href="<?= base_url('whatsapp/logs') ?>" class="sidebar-nav-link <?= is_active_route('whatsapp/logs') ?>" style="margin-left: 1.5rem;">
                <i class="bi bi-clock-history text-muted"></i>
                <span>Riwayat Pesan</span>
            </a>

        <?php elseif ($isPengawas): ?>
            <!-- PENGAWAS MENU -->
            <div class="sidebar-menu-heading">Menu Utama</div>

            <a href="<?= base_url('dashboard') ?>" class="sidebar-nav-link <?= is_active_route('dashboard') ?>">
                <i class="bi bi-speedometer2 text-primary"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= base_url('reports') ?>" class="sidebar-nav-link <?= is_active_route('reports') ?>">
                <i class="bi bi-file-earmark-bar-graph-fill text-primary"></i>
                <span>Laporan Keuangan</span>
            </a>

        <?php elseif ($isMember): ?>
            <!-- MEMBER MENU -->
            <div class="sidebar-menu-heading">Menu Anggota</div>

            <a href="<?= base_url('member/dashboard') ?>" class="sidebar-nav-link <?= is_active_route('member/dashboard') ?>">
                <i class="bi bi-speedometer2 text-primary"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= base_url('member/notifications') ?>" class="sidebar-nav-link <?= is_active_route('member/notifications') ?>">
                <i class="bi bi-bell-fill text-warning"></i>
                <span>Notifikasi</span>
                <?php 
                if (!empty($currentMember['id'])) {
                    $unreadCountSidebar = (new \App\Models\WhatsAppLog())->getUnreadCountByMember((int)$currentMember['id']);
                    if ($unreadCountSidebar > 0) {
                        echo '<span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;">' . $unreadCountSidebar . '</span>';
                    }
                }
                ?>
            </a>

            <a href="<?= base_url('member/payments') ?>" class="sidebar-nav-link <?= is_active_route('member/payments') ?>">
                <i class="bi bi-wallet2 text-success"></i>
                <span>Pembayaran Saya</span>
            </a>

            <a href="<?= base_url('member/history') ?>" class="sidebar-nav-link <?= is_active_route('member/history') ?>">
                <i class="bi bi-clock-history text-info"></i>
                <span>Riwayat Pembayaran</span>
            </a>

            <div class="sidebar-menu-heading">Akun Saya</div>
            <a href="<?= base_url('member/profile') ?>" class="sidebar-nav-link <?= is_active_route('member/profile') ?>">
                <i class="bi bi-person-circle text-warning"></i>
                <span>Profil</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar User Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="rounded-circle <?= $roleBadgeBg ?> text-white d-flex align-items-center justify-content-center fw-bold small flex-shrink-0 shadow-sm" style="width: 36px; height: 36px;">
                    <?= strtoupper(substr($isMember ? ($currentMember['nama'] ?? 'M') : ($currentUser['username'] ?? 'A'), 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white text-truncate fw-semibold small">
                        <?= e($isMember ? ($currentMember['nama'] ?? $currentUser['username']) : $currentUser['username']) ?>
                    </div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                        <?= $roleTitle ?>
                    </small>
                </div>
            </div>
            <a href="<?= base_url('auth/logout') ?>" class="btn btn-sm btn-outline-danger border-0 p-1 text-danger" title="Keluar (Logout)" aria-label="Keluar">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </div>
</aside>
<?php endif; ?>
