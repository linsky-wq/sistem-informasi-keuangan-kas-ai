<?php
$isLoggedIn = \App\Core\Auth::check();
$isMember = \App\Core\Auth::isMember();

// Generate dynamic breadcrumbs if not provided
if (!isset($breadcrumbs) || !is_array($breadcrumbs)) {
    $breadcrumbs = [];
    if ($isLoggedIn) {
        $homeRoute = $isMember ? 'member/dashboard' : 'dashboard';
        $current = current_route();
        
        if ($current !== 'dashboard' && $current !== 'member/dashboard') {
            $breadcrumbs[] = ['label' => 'Dashboard', 'url' => base_url($homeRoute)];
            if (isset($title) && !empty($title)) {
                $pageLabel = explode('—', $title)[0];
                $pageLabel = trim(str_replace(['Dashboard Super User', 'Dashboard Bendahara', 'Dashboard Pengawas', 'Dashboard Anggota', 'Sistem Keuangan Kas AI'], '', $pageLabel));
                if (!empty($pageLabel) && strcasecmp($pageLabel, 'Dashboard') !== 0) {
                    $breadcrumbs[] = ['label' => $pageLabel, 'url' => ''];
                }
            }
        } else {
            $breadcrumbs[] = ['label' => 'Dashboard', 'url' => ''];
        }
    }
}
?>

<?php if ($isLoggedIn && !empty($breadcrumbs)): ?>
<div class="app-breadcrumb-bar px-3 px-md-4 py-2 border-bottom bg-white">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small d-flex align-items-center">
            <li class="breadcrumb-item">
                <a href="<?= base_url($homeRoute ?? 'dashboard') ?>" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door-fill me-1 text-primary"></i> Utama
                </a>
            </li>
            <?php foreach ($breadcrumbs as $item): ?>
                <?php if (!empty($item['url'])): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= e($item['url']) ?>" class="text-decoration-none"><?= e($item['label']) ?></a>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item active text-secondary fw-semibold" aria-current="page">
                        <?= e($item['label']) ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>
<?php endif; ?>
