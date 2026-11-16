<div class="container-fluid py-3 flex-grow-1">
    <?php
    $roleLabel = match(\App\Core\Auth::role()) {
        \App\Core\Auth::ROLE_SUPER_USER => 'Mode Super User',
        \App\Core\Auth::ROLE_BENDAHARA => 'Mode Bendahara',
        \App\Core\Auth::ROLE_PENGAWAS => 'Mode Pengawas',
        default => 'Mode Pengguna'
    };
    ?>
    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 1;">
                    <div>
                        <span class="badge bg-white text-primary px-3 py-1 rounded-pill mb-2 fw-bold">
                            <i class="bi bi-shield-check me-1"></i> <?= $roleLabel ?>
                        </span>
                        <h2 class="fw-bold mb-1">Selamat Datang, <?= e(!empty($user['nama']) ? $user['nama'] : $user['username']) ?>!</h2>
                        <p class="text-white-50 mb-0">Dashboard terpadu pemantauan mutasi kas, trend saldo, status pembayaran, dan peringatan tunggakan anggota.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (\App\Core\Auth::isSuperUser() || \App\Core\Auth::isBendahara()): ?>
                        <a href="<?= base_url('transactions') ?>" class="btn btn-light rounded-pill px-3 py-2 fw-semibold text-primary shadow-sm btn-sm">
                            <i class="bi bi-plus-circle-fill me-1"></i> Catat Transaksi
                        </a>
                        <a href="<?= base_url('payments') ?>" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold btn-sm">
                            <i class="bi bi-receipt me-1"></i> Iuran Anggota
                        </a>
                        <?php else: ?>
                        <a href="<?= base_url('reports') ?>" class="btn btn-light rounded-pill px-3 py-2 fw-semibold text-primary shadow-sm btn-sm">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> Lihat Laporan
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Alert (Jumlah Pengguna/Anggota Belum Membayar) -->
    <?php if ($unpaidMembersCount > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning border-0 shadow-sm rounded-4 p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning text-dark p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Peringatan Pembayaran Kas (Payment Alert)</h6>
                        <span class="small text-dark-50">
                            Terdapat <strong><?= $unpaidMembersCount ?> peserta didik</strong> yang belum melunasi iuran kas dengan akumulasi tunggakan sebesar 
                            <strong class="text-danger">Rp <?= number_format($totalTunggakan ?? 0, 2, ',', '.') ?></strong>.
                        </span>
                    </div>
                </div>
                <div>
                    <a href="<?= base_url('payments?status=Belum+Bayar') ?>" class="btn btn-warning text-dark rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm">
                        <i class="bi bi-bell-fill me-1"></i> Lihat Data Tunggakan
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 d-flex align-items-center gap-3">
                <div class="bg-success text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-success">Status Iuran Kas Terpenuhi</h6>
                    <small class="text-success-emphasis">Seluruh peserta didik telah melunasi kewajiban iuran kas periode aktif.</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards (6 Cards) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Pemasukan -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success bg-white">
                <div class="card-body d-flex flex-column justify-content-between p-3">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Pemasukan</span>
                            <div class="bg-success-subtle text-success p-2 rounded-circle">
                                <i class="bi bi-arrow-down-left fs-5"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-success mb-2">Rp <?= number_format($totalIncome, 0, ',', '.') ?></h4>
                    </div>
                    <div class="border-top pt-2 mt-1" style="font-size: 0.75rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary"><i class="bi bi-dot text-success me-0"></i>Iuran</span>
                            <span class="fw-semibold text-dark font-monospace">Rp <?= number_format($incomeBreakdown['iuran'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary"><i class="bi bi-dot text-success me-0"></i>Donasi</span>
                            <span class="fw-semibold text-dark font-monospace">Rp <?= number_format($incomeBreakdown['donasi'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary"><i class="bi bi-dot text-success me-0"></i>Sumbangan</span>
                            <span class="fw-semibold text-dark font-monospace">Rp <?= number_format($incomeBreakdown['sumbangan'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Pengeluaran -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Pengeluaran</span>
                        <div class="bg-danger-subtle text-danger p-2 rounded-circle">
                            <i class="bi bi-arrow-up-right fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-danger mb-1">Rp <?= number_format($totalExpense, 0, ',', '.') ?></h4>
                    <small class="text-muted" style="font-size: 0.75rem;">Operasional & kegiatan</small>
                </div>
            </div>
        </div>

        <!-- 3. Saldo Kas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-primary bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Saldo Kas Bersih</span>
                        <div class="bg-primary-subtle text-primary p-2 rounded-circle">
                            <i class="bi bi-wallet2 fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-primary mb-1">Rp <?= number_format($currentBalance, 0, ',', '.') ?></h4>
                    <small class="text-muted" style="font-size: 0.75rem;">Pemasukan - Pengeluaran</small>
                </div>
            </div>
        </div>

        <!-- 4. Jumlah Anggota -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-info bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Jumlah Anggota</span>
                        <div class="bg-info-subtle text-info p-2 rounded-circle">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-1"><?= $totalMembers ?> <small class="fs-6 text-muted fw-normal">Siswa</small></h4>
                    <small class="text-muted" style="font-size: 0.75rem;">Peserta didik terdaftar</small>
                </div>
            </div>
        </div>

        <!-- 5. Sudah Bayar -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Sudah Bayar</span>
                        <div class="bg-success-subtle text-success p-2 rounded-circle">
                            <i class="bi bi-check-circle fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-success mb-1"><?= $countPaid ?> <small class="fs-6 text-muted fw-normal">Lunas</small></h4>
                    <small class="text-muted" style="font-size: 0.75rem;">Iuran status lunas</small>
                </div>
            </div>
        </div>

        <!-- 6. Belum Bayar -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-warning bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Belum Bayar</span>
                        <div class="bg-warning-subtle text-warning p-2 rounded-circle">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-warning mb-1"><?= $countUnpaid ?> <small class="fs-6 text-muted fw-normal">Pending</small></h4>
                    <small class="text-muted" style="font-size: 0.75rem;">Belum bayar / sebagian</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row (3 Grafik) -->
    <div class="row g-4 mb-4">
        <!-- Grafik 1: Pemasukan vs Pengeluaran -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white pt-4 px-4 pb-0 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Pemasukan vs Pengeluaran</h6>
                        <small class="text-muted">Perbandingan mutasi per bulan</small>
                    </div>
                    <span class="badge bg-light text-muted border">Bulan</span>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <?php if ($monthlyComparison['hasData']): ?>
                        <div style="position: relative; height: 260px;">
                            <canvas id="chartIncomeExpense"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bar-chart fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            <p class="small mb-0">Belum ada data transaksi bulanan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grafik 2: Trend Saldo Kas -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white pt-4 px-4 pb-0 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Trend Saldo Kas</h6>
                        <small class="text-muted">Pergerakan saldo akumulatif</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Kumulatif</span>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <?php if ($balanceTrend['hasData']): ?>
                        <div style="position: relative; height: 260px;">
                            <canvas id="chartBalanceTrend"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-graph-up fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            <p class="small mb-0">Belum ada data trend saldo.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grafik 3: Status Pembayaran -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white pt-4 px-4 pb-0 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Komposisi Status Iuran</h6>
                        <small class="text-muted">Proporsi pembayaran anggota</small>
                    </div>
                    <span class="badge bg-light text-muted border">Iuran</span>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <?php if ($statusDistribution['hasData']): ?>
                        <div style="position: relative; height: 260px;">
                            <canvas id="chartPaymentStatus"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-pie-chart fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            <p class="small mb-0">Belum ada data status pembayaran kas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaksi Kas Terbaru (5 Transaksi) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Transaksi Kas Terbaru</h5>
                        <small class="text-muted">5 mutasi pemasukan dan pengeluaran kas terakhir</small>
                    </div>
                    <div>
                        <a href="<?= base_url('transactions') ?>" class="btn btn-outline-primary rounded-pill px-3 py-1 btn-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i> Seluruh Transaksi
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">Tanggal</th>
                                    <th class="py-3">Kode Transaksi</th>
                                    <th class="py-3">Tipe</th>
                                    <th class="py-3">Nominal</th>
                                    <th class="pe-4 py-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentTransactions)): ?>
                                    <?php foreach ($recentTransactions as $t): ?>
                                        <tr>
                                            <td class="ps-4 small text-secondary">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?= date('d M Y', strtotime($t['tanggal'])) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace">
                                                    <?= e($t['kode_transaksi']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($t['tipe'] === 'pemasukan'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="<?= $t['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $t['tipe'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format((float)$t['nominal'], 2, ',', '.') ?>
                                                </strong>
                                            </td>
                                            <td class="pe-4 small text-dark">
                                                <div><?= e($t['keterangan']) ?></div>
                                                <small class="text-muted"><?= e($t['category_name'] ?? 'Kategori Kas') ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                            <h6 class="fw-bold text-secondary mb-1">Belum Ada Transaksi Kas</h6>
                                            <p class="small text-muted mb-3">Database saat ini belum memiliki catatan pemasukan maupun pengeluaran kas.</p>
                                            <a href="<?= base_url('transactions') ?>" class="btn btn-primary btn-sm rounded-pill px-4">
                                                <i class="bi bi-plus-circle me-1"></i> Catat Transaksi Pertama
                                            </a>
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

    <!-- Pembayaran Iuran & Status Verifikasi -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Pembayaran Iuran Kas & Verifikasi</h5>
                        <small class="text-muted">Catatan iuran anggota dan status verifikasi bendahara</small>
                    </div>
                    <div>
                        <a href="<?= base_url('payments') ?>" class="btn btn-outline-primary rounded-pill px-3 py-1 btn-sm">
                            <i class="bi bi-receipt me-1"></i> Kelola Pembayaran
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">Anggota</th>
                                    <th class="py-3">Periode</th>
                                    <th class="py-3">Tagihan & Dibayar</th>
                                    <th class="py-3">Status</th>
                                    <th class="pe-4 py-3">Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentPayments)): ?>
                                    <?php foreach ($recentPayments as $p): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark small"><?= e($p['member_name'] ?? 'Anggota') ?></div>
                                                <small class="text-muted font-monospace"><?= e($p['nomor_anggota'] ?? '-') ?> &bull; <?= e($p['kelas'] ?? '-') ?></small>
                                            </td>
                                            <td class="small text-secondary font-monospace">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                <?= e($p['periode']) ?>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold text-dark">Dibayar: Rp <?= number_format((float)$p['nominal'], 0, ',', '.') ?></div>
                                                <small class="text-muted">Tagihan: Rp <?= number_format((float)($p['tagihan'] ?? 50000), 0, ',', '.') ?></small>
                                            </td>
                                            <td>
                                                <?php if ($p['status'] === 'Lunas'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Lunas
                                                    </span>
                                                <?php elseif ($p['status'] === 'Sebagian'): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-pie-chart-fill me-1"></i> Sebagian
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                                        <i class="bi bi-clock-history me-1"></i> Belum Bayar
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 small text-secondary">
                                                <?php if (!empty($p['verified_by'])): ?>
                                                    <span class="text-success small d-flex align-items-center gap-1">
                                                        <i class="bi bi-patch-check-fill text-success"></i> Terverifikasi
                                                    </span>
                                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Oleh: <?= e($p['verifier_name'] ?? 'Bendahara') ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted small">
                                                        <i class="bi bi-hourglass-split me-1"></i> Belum Diverifikasi
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                            <h6 class="fw-bold text-secondary mb-1">Belum Ada Data Pembayaran</h6>
                                            <p class="small text-muted mb-0">Catatan pembayaran iuran kas akan muncul di sini setelah dicatat.</p>
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

    <!-- Riwayat Notifikasi WhatsApp & AI Assistant Banner -->
    <div class="row g-4 mb-4">
        <!-- Riwayat Notifikasi WhatsApp (Jika tersedia) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success-subtle text-success p-2 rounded-circle">
                            <i class="bi bi-whatsapp fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Riwayat Notifikasi WhatsApp</h6>
                            <small class="text-muted">Log pesan & pengingat iuran anggota</small>
                        </div>
                    </div>
                    <?php if (\App\Core\Auth::canSendWhatsApp()): ?>
                    <a href="<?= base_url('whatsapp/logs') ?>" class="btn btn-outline-success rounded-pill px-3 py-1 btn-sm">
                        <i class="bi bi-clock-history me-1"></i> Semua Log
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recentWaLogs)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentWaLogs as $w): ?>
                                <div class="list-group-item px-4 py-3 border-light d-flex justify-content-between align-items-center">
                                    <div class="overflow-hidden me-3">
                                        <div class="fw-semibold text-dark small text-truncate">
                                            <?= e($w['title'] ?? 'Notifikasi WhatsApp') ?>
                                        </div>
                                        <small class="text-muted text-truncate d-block">
                                            Penerima: <?= e($w['recipient_name'] ?? $w['member_nama'] ?? $w['recipient_phone']) ?> (<?= e($w['recipient_phone']) ?>)
                                        </small>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <?php if ($w['status'] === 'sent' || $w['status'] === 'delivered'): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill font-monospace" style="font-size: 0.7rem;">
                                                <i class="bi bi-check2-all me-1"></i> Terkirim
                                            </span>
                                        <?php elseif ($w['status'] === 'failed'): ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill font-monospace" style="font-size: 0.7rem;">
                                                <i class="bi bi-x-circle me-1"></i> Gagal
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill font-monospace" style="font-size: 0.7rem;">
                                                <i class="bi bi-hourglass-split me-1"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;"><?= date('d/m H:i', strtotime($w['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted px-4">
                            <i class="bi bi-chat-left-dots fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            <h6 class="fw-bold text-secondary mb-1">Belum Ada Riwayat Notifikasi</h6>
                            <p class="small text-muted mb-0">Pesan notifikasi tagihan atau konfirmasi iuran otomatis akan tercatat pada modul ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- AI Assistant Financial Insight Card -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-warning-subtle text-warning p-2 rounded-circle">
                            <i class="bi bi-stars fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">AI Financial Assistant</h6>
                            <small class="text-muted">Kecerdasan buatan analisis kondisi kas</small>
                        </div>
                    </div>
                    <a href="<?= base_url('ai') ?>" class="btn btn-warning text-dark rounded-pill px-3 py-1 btn-sm fw-semibold shadow-sm">
                        <i class="bi bi-chat-dots-fill me-1"></i> Buka Chat AI
                    </a>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="p-3 bg-light rounded-4 mb-3 border">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark px-2 py-1 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    <i class="bi bi-robot me-1"></i> Asisten Kas
                                </span>
                                <small class="text-muted">Siap membantu Bendahara & Pengurus</small>
                            </div>
                            <p class="small text-secondary mb-0">
                                Konsultasikan evaluasi saldo kas, rasio pengeluaran terbesar, analisis tunggakan iuran, serta peroleh saran perencanaan anggaran berdasarkan data keuangan aktual.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= base_url('ai') ?>" class="badge bg-light text-primary border text-decoration-none px-3 py-2 rounded-pill small">
                                <i class="bi bi-question-circle me-1"></i> Berapa saldo kas saat ini?
                            </a>
                            <a href="<?= base_url('ai') ?>" class="badge bg-light text-primary border text-decoration-none px-3 py-2 rounded-pill small">
                                <i class="bi bi-graph-up-arrow me-1"></i> Bagaimana kondisi keuangan bulan ini?
                            </a>
                            <a href="<?= base_url('ai') ?>" class="badge bg-light text-primary border text-decoration-none px-3 py-2 rounded-pill small">
                                <i class="bi bi-exclamation-circle me-1"></i> Analisis tunggakan iuran anggota
                            </a>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="bi bi-shield-lock-fill text-success me-1"></i> Data keuangan read-only & aman
                        </div>
                        <a href="<?= base_url('reports') ?>" class="small text-decoration-none fw-semibold text-primary">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> Buka Laporan Keuangan &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- CHART INITIALIZATION SCRIPTS                            -->
<!-- ======================================================= -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Chart: Pemasukan vs Pengeluaran
    const ctxIncomeExpense = document.getElementById('chartIncomeExpense');
    if (ctxIncomeExpense) {
        const incomeExpenseLabels = <?= json_encode($monthlyComparison['labels'] ?? []) ?>;
        const incomeData = <?= json_encode($monthlyComparison['income'] ?? []) ?>;
        const expenseData = <?= json_encode($monthlyComparison['expense'] ?? []) ?>;

        new Chart(ctxIncomeExpense, {
            type: 'bar',
            data: {
                labels: incomeExpenseLabels.length > 0 ? incomeExpenseLabels : ['Bulan Ini'],
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: incomeData.length > 0 ? incomeData : [0],
                        backgroundColor: 'rgba(25, 135, 84, 0.85)',
                        borderColor: '#198754',
                        borderRadius: 6,
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseData.length > 0 ? expenseData : [0],
                        backgroundColor: 'rgba(220, 53, 69, 0.85)',
                        borderColor: '#dc3545',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + Number(context.raw).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart: Trend Saldo Kas
    const ctxBalanceTrend = document.getElementById('chartBalanceTrend');
    if (ctxBalanceTrend) {
        const trendLabels = <?= json_encode($balanceTrend['labels'] ?? []) ?>;
        const trendData = <?= json_encode($balanceTrend['data'] ?? []) ?>;

        new Chart(ctxBalanceTrend, {
            type: 'line',
            data: {
                labels: trendLabels.length > 0 ? trendLabels : ['Hari Ini'],
                datasets: [{
                    label: 'Saldo Kas',
                    data: trendData.length > 0 ? trendData : [<?= (float)$currentBalance ?>],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Saldo: Rp ' + Number(context.raw).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Chart: Status Pembayaran Kas
    const ctxPaymentStatus = document.getElementById('chartPaymentStatus');
    if (ctxPaymentStatus) {
        const statusLabels = <?= json_encode($statusDistribution['labels'] ?? ['Lunas', 'Sebagian', 'Belum Bayar']) ?>;
        const statusData = <?= json_encode($statusDistribution['data'] ?? [0, 0, 0]) ?>;

        new Chart(ctxPaymentStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: [
                        '#198754', // Lunas (Success)
                        '#ffc107', // Sebagian (Warning)
                        '#dc3545', // Belum Bayar (Danger)
                    ],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
