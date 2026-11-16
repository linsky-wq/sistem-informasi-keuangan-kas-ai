<footer class="app-footer mt-auto">
    <div class="container-fluid px-3 px-md-4 text-center text-muted small">
        <div class="row align-items-center">
            <div class="col-md-6 text-md-start mb-2 mb-md-0">
                &copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong>. Lingkungan Sekolah & Organisasi.
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-secondary me-2">Bootstrap 5.3 UI & MVC Architecture</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Online</span>
            </div>
        </div>
    </div>
</footer>

<?php
// Floating AI Assistant Widget for authenticated users
if (\App\Core\Auth::check()) {
    require APP_ROOT . '/views/layouts/ai_floating_widget.php';
}
?>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Chart.js 4.4.4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<!-- Custom App JS -->
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
