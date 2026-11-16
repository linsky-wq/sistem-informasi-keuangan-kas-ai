<?php
$flashes = \App\Core\Session::getFlashes();
?>

<?php if (!empty($flashes)): ?>
<div class="px-3 px-md-4 pt-3">
    <?php foreach ($flashes as $type => $messages): ?>
        <?php foreach ($messages as $msg): ?>
            <?php
            $icon = match($type) {
                'success' => 'bi-check-circle-fill',
                'danger', 'error' => 'bi-exclamation-triangle-fill',
                'warning' => 'bi-exclamation-circle-fill',
                default => 'bi-info-circle-fill',
            };
            $alertClass = ($type === 'error') ? 'danger' : $type;
            ?>
            <div class="alert alert-<?= e($alertClass) ?> alert-dismissible fade show shadow-sm rounded-3 py-3 px-3 d-flex align-items-center mb-3" role="alert">
                <i class="bi <?= $icon ?> me-2 fs-5"></i>
                <div class="fw-medium small"><?= e($msg) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
