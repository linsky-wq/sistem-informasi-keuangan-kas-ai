<div class="container py-4 flex-grow-1">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill mb-2">
                            <i class="bi bi-clock-history me-1"></i> Log Histori
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Histori Pembayaran Kas Pribadi</h3>
                        <p class="text-secondary small mb-0">Arsip kronologis seluruh penyetoran dan verifikasi iuran kas atas nama <strong><?= e($member['nama']) ?></strong>.</p>
                    </div>
                    <div>
                        <a href="<?= base_url('member/payments') ?>" class="btn btn-primary rounded-pill px-3 py-2 btn-sm">
                            <i class="bi bi-receipt me-1"></i> Lihat Status Tagihan & Tunggakan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Paid Summary -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-success">
                <span class="text-muted small fw-semibold">Total Dana Kas Disetorkan</span>
                <h4 class="fw-bold text-success mt-1 mb-0">Rp <?= number_format($totalPaid ?? 0, 2, ',', '.') ?></h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold text-dark mb-0">Riwayat Penyetoran Iuran Kas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">Tanggal Setor</th>
                                    <th class="py-3">Periode Kas</th>
                                    <th class="py-3">Nilai Tagihan</th>
                                    <th class="py-3">Nominal Disetor</th>
                                    <th class="py-3">Sisa Tunggakan</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3">Waktu Diverifikasi</th>
                                    <th class="py-3">Verifikator</th>
                                    <th class="pe-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($payments)): ?>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td class="ps-4 small text-muted"><?= date('d M Y', strtotime($p['tanggal_pembayaran'])) ?></td>
                                            <td class="fw-semibold text-dark"><?= e($p['periode']) ?></td>
                                            <td class="small text-muted">Rp <?= number_format((float)($p['tagihan'] ?? 50000), 2, ',', '.') ?></td>
                                            <td class="fw-bold text-dark">Rp <?= number_format((float)$p['nominal'], 2, ',', '.') ?></td>
                                            <td>
                                                <?php if ((float)$p['sisa'] > 0): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                        Rp <?= number_format((float)$p['sisa'], 2, ',', '.') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success">Lunas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($p['status'] === 'Lunas'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">Lunas</span>
                                                <?php elseif ($p['status'] === 'Sebagian'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">Sebagian</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">Belum Bayar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= !empty($p['verified_at']) ? date('d M Y H:i', strtotime($p['verified_at'])) : '-' ?></td>
                                            <td class="small">
                                                <?php if (!empty($p['verifier_name'])): ?>
                                                    <span class="text-dark"><i class="bi bi-shield-check text-success me-1"></i> <?= e($p['verifier_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">Belum Diverifikasi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 small text-secondary"><?= e($p['catatan'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                            Belum ada riwayat pembayaran yang tercatat.
                                        </td>
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
