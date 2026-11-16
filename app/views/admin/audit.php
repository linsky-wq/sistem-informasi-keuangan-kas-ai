<div class="container py-4 flex-grow-1">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1 rounded-pill mb-2">
                            <i class="bi bi-shield-lock me-1"></i> Keamanan & Log
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Audit Trail Aktivitas Sistem</h3>
                        <p class="text-secondary small mb-0">Catatan kronologis aktivitas autentikasi, mutasi data, dan keamanan pengguna.</p>
                    </div>
                    <div>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted">
                                    <th>Waktu</th>
                                    <th>Pengguna</th>
                                    <th>Aksi</th>
                                    <th>Entitas Target</th>
                                    <th>IP Address</th>
                                    <th>Perubahan Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td class="small text-muted"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= e($log['username'] ?? 'System / Anonymous') ?></div>
                                                <small class="badge bg-light text-secondary border"><?= e($log['role'] ?? 'guest') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                    <?= e($log['action']) ?>
                                                </span>
                                            </td>
                                            <td class="small font-monospace text-secondary"><?= e($log['entity']) ?> <?= !empty($log['entity_id']) ? '#' . $log['entity_id'] : '' ?></td>
                                            <td class="small font-monospace"><?= e($log['ip_address']) ?></td>
                                            <td class="small">
                                                <?php if (!empty($log['new_data'])): ?>
                                                    <code class="text-dark bg-light p-1 rounded"><?= e($log['new_data']) ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada catatan aktivitas di audit log.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
