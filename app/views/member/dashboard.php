<div class="container py-4 flex-grow-1">
    <!-- Error / Flash Notification Placeholder -->
    <div id="memberDashboardAlertContainer">
        <!-- Rendered by flash component if any -->
    </div>

    <!-- 1. PROFIL PESERTA DIDIK BANNER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm flex-shrink-0" style="width: 58px; height: 58px;">
                            <?= strtoupper(substr($member['nama'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="badge bg-white text-primary px-3 py-1 rounded-pill mb-1 fw-semibold">
                                <i class="bi bi-person-badge me-1"></i> Profil Pengguna/Anggota
                            </span>
                            <h3 class="fw-bold mb-1"><?= e($member['nama']) ?></h3>
                            <div class="d-flex flex-wrap gap-2 text-white-50 small">
                                <span><i class="bi bi-card-heading me-1"></i> NIS: <strong class="text-white"><?= e($member['nis'] ?? '-') ?></strong></span>
                                <span>&bull;</span>
                                <span><i class="bi bi-mortarboard me-1"></i> Kelas: <strong class="text-white"><?= e($member['kelas']) ?></strong></span>
                                <span>&bull;</span>
                                <span><i class="bi bi-hash me-1"></i> No: <strong class="text-white"><?= e($member['nomor_anggota']) ?></strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= base_url('member/notifications') ?>" class="btn btn-warning rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm text-dark d-flex align-items-center gap-1">
                            <i class="bi bi-bell-fill"></i>
                            <span>Notifikasi</span>
                            <?php if (!empty($unreadNotifCount) && $unreadNotifCount > 0): ?>
                                <span class="badge bg-danger text-white rounded-pill ms-1" style="font-size: 0.7rem;"><?= $unreadNotifCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= base_url('member/profile') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-person-lines-fill me-1"></i> Kelola Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NOTIFIKASI PRIBADI BANNER -->
    <?php if (!empty($unreadNotifCount) && $unreadNotifCount > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning border-0 shadow-sm rounded-4 p-3 mb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center p-2 flex-shrink-0" style="width: 42px; height: 42px;">
                            <i class="bi bi-bell-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Anda memiliki <?= $unreadNotifCount ?> notifikasi / pesan resmi baru</h6>
                            <small class="text-secondary">Informasi pembayaran atau verifikasi kas telah dikirimkan ke nomor WhatsApp Anda.</small>
                        </div>
                    </div>
                    <div>
                        <a href="<?= base_url('member/notifications') ?>" class="btn btn-warning rounded-pill px-3 py-1 btn-sm fw-bold text-dark shadow-xs">
                            Buka Notifikasi <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. STATUS KAS AKTIF / PERIODE BERJALAN -->
    <?php if (!empty($activePayment)): ?>
        <?php
            $actTagihan = (float)($activePayment['tagihan'] ?? 50000.00);
            $actNominal = (float)$activePayment['nominal'];
            $actSisa = (float)($activePayment['sisa'] ?? max(0.0, $actTagihan - $actNominal));
            $actPercent = $actTagihan > 0 ? min(100, round(($actNominal / $actTagihan) * 100)) : 0;
            $actStatus = $activePayment['status'];
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden p-4 border-start border-4 <?= $actStatus === 'Lunas' ? 'border-success' : ($actStatus === 'Sebagian' ? 'border-warning' : 'border-danger') ?>">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-4 col-md-5">
                            <span class="badge bg-light text-primary border px-3 py-1 rounded-pill mb-1 small fw-semibold">
                                <i class="bi bi-calendar-check me-1"></i> Status Kas Periode Terkini
                            </span>
                            <h4 class="fw-bold text-dark mb-1"><?= e($activePayment['periode']) ?></h4>
                            <small class="text-muted">Tanggal Setor: <?= date('d M Y', strtotime($activePayment['tanggal_pembayaran'])) ?></small>
                        </div>
                        
                        <div class="col-lg-5 col-md-7">
                            <div class="row text-center text-md-start g-2">
                                <div class="col-4">
                                    <small class="text-muted d-block">Tagihan</small>
                                    <strong class="text-dark">Rp <?= number_format($actTagihan, 0, ',', '.') ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Sudah Dibayar</small>
                                    <strong class="text-success">Rp <?= number_format($actNominal, 0, ',', '.') ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Sisa</small>
                                    <strong class="<?= $actSisa > 0 ? 'text-danger' : 'text-success' ?>">Rp <?= number_format($actSisa, 0, ',', '.') ?></strong>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar <?= $actPercent >= 100 ? 'bg-success' : ($actPercent > 0 ? 'bg-warning' : 'bg-danger') ?>" role="progressbar" style="width: <?= $actPercent ?>%;" aria-valuenow="<?= $actPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-12 text-lg-end">
                            <?php if ($actStatus === 'Lunas'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                                    <i class="bi bi-check-circle-fill me-1"></i> LUNAS
                                </span>
                            <?php elseif ($actStatus === 'Sebagian'): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill fs-6">
                                    <i class="bi bi-clock-fill me-1"></i> SEBAGIAN
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6">
                                    <i class="bi bi-x-circle-fill me-1"></i> BELUM BAYAR
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. FINANCIAL SUMMARY METRIC CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-primary bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Tagihan Kas</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rp <?= number_format($totalTagihan, 2, ',', '.') ?></h4>
                    <small class="text-muted"><?= count($payments) ?> periode tagihan tercatat</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Iuran Telah Disetor</span>
                    <h4 class="fw-bold text-success mt-1 mb-0">Rp <?= number_format($totalPaid, 2, ',', '.') ?></h4>
                    <small class="text-muted"><?= $lunasCount ?> periode Lunas</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger bg-danger-subtle bg-opacity-25">
                <div class="card-body">
                    <span class="text-danger small fw-bold">Sisa Tunggakan Kas</span>
                    <h4 class="fw-bold text-danger mt-1 mb-0">Rp <?= number_format($totalSisa, 2, ',', '.') ?></h4>
                    <small class="text-danger"><?= $totalSisa > 0 ? ($unpaidCount . ' tagihan belum lunas') : 'Tidak ada tunggakan kas aktif' ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. RIWAYAT PEMBAYARAN TERAKHIR (MOBILE FRIENDLY) -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Riwayat Pembayaran Kas Terbaru</h5>
                        <small class="text-muted">Daftar transaksi kas pribadi Anda</small>
                    </div>
                    <div>
                        <a href="<?= base_url('member/payments') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            Semua Tagihan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Loading State placeholder -->
                <div id="dashboardLoadingState" class="d-none text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat data...</span>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Memuat rincian kas anggota...</p>
                </div>

                <div class="card-body p-0" id="dashboardContentTable">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">Periode</th>
                                    <th class="py-3">Tanggal Setor</th>
                                    <th class="py-3">Tagihan</th>
                                    <th class="py-3">Disetor</th>
                                    <th class="py-3">Sisa</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3">Verifikator</th>
                                    <th class="pe-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentPayments)): ?>
                                    <?php foreach ($recentPayments as $p): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark"><?= e($p['periode']) ?></td>
                                            <td class="small text-secondary"><?= date('d M Y', strtotime($p['tanggal_pembayaran'])) ?></td>
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
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-check-circle-fill me-1"></i> LUNAS
                                                    </span>
                                                <?php elseif ($p['status'] === 'Sebagian'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-clock-fill me-1"></i> SEBAGIAN
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-x-circle-fill me-1"></i> BELUM BAYAR
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <?php if (!empty($p['verified_by'])): ?>
                                                    <span class="text-dark"><i class="bi bi-shield-check text-success me-1"></i> <?= e($p['verifier_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border"><i class="bi bi-hourglass-split me-1"></i> Menunggu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 small text-secondary"><?= e($p['catatan'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- EMPTY STATE -->
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <div class="py-3">
                                                <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                                <h6 class="fw-bold text-secondary mb-1">Belum Ada Riwayat Pembayaran Kas</h6>
                                                <p class="small text-muted mb-0">Anda belum memiliki catatan tagihan atau riwayat setoran iuran kas saat ini.</p>
                                            </div>
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
