<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title & Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1 fw-semibold">
                            <i class="bi bi-file-earmark-bar-graph-fill me-1"></i> Modul Laporan
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Laporan Keuangan & Rekonsiliasi Kas</h3>
                        <p class="text-secondary small mb-0">Pusat pelaporan mutasi buku kas, rekonsiliasi saldo, iuran kas peserta didik, dan audit tunggakan.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= base_url('reports/export?' . http_build_query(array_merge($filters, ['tab' => $activeTab, 'format' => 'pdf']))) ?>" class="btn btn-danger rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
                        </a>
                        <a href="<?= base_url('reports/export?' . http_build_query(array_merge($filters, ['tab' => $activeTab, 'format' => 'excel']))) ?>" class="btn btn-success rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                        </a>
                        <a href="<?= base_url('reports/export?' . http_build_query(array_merge($filters, ['tab' => $activeTab, 'format' => 'csv']))) ?>" class="btn btn-outline-success rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-filetype-csv me-1"></i> Export CSV
                        </a>
                        <a href="<?= base_url('reports/print?' . http_build_query(array_merge($filters, ['tab' => $activeTab]))) ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-printer-fill me-1"></i> Cetak Resmi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Pemasukan -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Pemasukan</span>
                    <h5 class="fw-bold text-success mt-1 mb-0">Rp <?= number_format($executiveSummary['total_income'], 2, ',', '.') ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;"><?= $incomeReport['count'] ?> transaksi masuk</small>
                </div>
            </div>
        </div>

        <!-- Total Pengeluaran -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Pengeluaran</span>
                    <h5 class="fw-bold text-danger mt-1 mb-0">Rp <?= number_format($executiveSummary['total_expense'], 2, ',', '.') ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;"><?= $expenseReport['count'] ?> transaksi keluar</small>
                </div>
            </div>
        </div>

        <!-- Saldo Kas Bersih -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-primary bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Saldo Kas Bersih</span>
                    <h5 class="fw-bold text-primary mt-1 mb-0">Rp <?= number_format($executiveSummary['net_balance'], 2, ',', '.') ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;">Pemasukan - Pengeluaran</small>
                </div>
            </div>
        </div>

        <!-- Total Tagihan Kas -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-info bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Tagihan Iuran</span>
                    <h5 class="fw-bold text-dark mt-1 mb-0">Rp <?= number_format($executiveSummary['total_tagihan'], 2, ',', '.') ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;"><?= $paymentsReport['count'] ?> catatan iuran</small>
                </div>
            </div>
        </div>

        <!-- Total Terbayar -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Iuran Terbayar</span>
                    <h5 class="fw-bold text-success mt-1 mb-0">Rp <?= number_format($executiveSummary['total_paid'], 2, ',', '.') ?></h5>
                    <small class="text-muted" style="font-size: 0.72rem;">Setoran telah diverifikasi</small>
                </div>
            </div>
        </div>

        <!-- Total Tunggakan -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-warning bg-warning-subtle bg-opacity-25">
                <div class="card-body">
                    <span class="text-danger small fw-bold">Total Tunggakan</span>
                    <h5 class="fw-bold text-danger mt-1 mb-0">Rp <?= number_format($executiveSummary['total_tunggakan'], 2, ',', '.') ?></h5>
                    <small class="text-danger" style="font-size: 0.72rem;"><?= $executiveSummary['count_arrears'] ?> tagihan belum lunas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Multi-Kriteria Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-funnel-fill text-primary me-1"></i> Filter Laporan
                    </h6>
                    <?php if (!empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['kelas']) || !empty($filters['category_id']) || !empty($filters['tipe']) || !empty($filters['search'])): ?>
                        <a href="<?= base_url('reports?tab=' . $activeTab) ?>" class="badge bg-secondary-subtle text-secondary border text-decoration-none px-3 py-1 rounded-pill">
                            <i class="bi bi-x-circle me-1"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <form action="<?= base_url('reports') ?>" method="GET" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

                        <!-- Tanggal Mulai -->
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Mulai</label>
                            <input type="date" class="form-control form-control-sm" name="start_date" value="<?= e($filters['start_date']) ?>">
                        </div>

                        <!-- Tanggal Akhir -->
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Akhir</label>
                            <input type="date" class="form-control form-control-sm" name="end_date" value="<?= e($filters['end_date']) ?>">
                        </div>

                        <!-- Kelas -->
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Filter Kelas</label>
                            <select class="form-select form-select-sm" name="kelas">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= e($c) ?>" <?= ($filters['kelas'] === $c) ? 'selected' : '' ?>>Kelas <?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Kategori Kas -->
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Kategori</label>
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">-- Semua Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ((int)$filters['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['nama']) ?> (<?= ucfirst($cat['tipe']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tipe Transaksi -->
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Tipe Mutasi</label>
                            <select class="form-select form-select-sm" name="tipe">
                                <option value="">-- Semua Tipe --</option>
                                <option value="pemasukan" <?= ($filters['tipe'] === 'pemasukan') ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="pengeluaran" <?= ($filters['tipe'] === 'pengeluaran') ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>

                        <!-- Tombol Terapkan -->
                        <div class="col-md-2 col-sm-6 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3">
                                <i class="bi bi-filter me-1"></i> Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav Tabs 5 Jenis Laporan -->
    <div class="row mb-3">
        <div class="col-12">
            <ul class="nav nav-pills bg-white p-2 rounded-4 shadow-sm" id="reportTabs" role="tablist">
                <li class="nav-item flex-fill text-center" role="presentation">
                    <a class="nav-link rounded-pill fw-semibold py-2 <?= $activeTab === 'saldo' ? 'active' : '' ?>" href="<?= base_url('reports?' . http_build_query(array_merge($filters, ['tab' => 'saldo']))) ?>">
                        <i class="bi bi-journal-text me-1"></i> 3. Laporan Saldo
                    </a>
                </li>
                <li class="nav-item flex-fill text-center" role="presentation">
                    <a class="nav-link rounded-pill fw-semibold py-2 <?= $activeTab === 'pemasukan' ? 'active' : '' ?>" href="<?= base_url('reports?' . http_build_query(array_merge($filters, ['tab' => 'pemasukan']))) ?>">
                        <i class="bi bi-arrow-down-left-circle me-1"></i> 1. Laporan Pemasukan
                    </a>
                </li>
                <li class="nav-item flex-fill text-center" role="presentation">
                    <a class="nav-link rounded-pill fw-semibold py-2 <?= $activeTab === 'pengeluaran' ? 'active' : '' ?>" href="<?= base_url('reports?' . http_build_query(array_merge($filters, ['tab' => 'pengeluaran']))) ?>">
                        <i class="bi bi-arrow-up-right-circle me-1"></i> 2. Laporan Pengeluaran
                    </a>
                </li>
                <li class="nav-item flex-fill text-center" role="presentation">
                    <a class="nav-link rounded-pill fw-semibold py-2 <?= $activeTab === 'pembayaran' ? 'active' : '' ?>" href="<?= base_url('reports?' . http_build_query(array_merge($filters, ['tab' => 'pembayaran']))) ?>">
                        <i class="bi bi-receipt me-1"></i> 4. Laporan Pembayaran
                    </a>
                </li>
                <li class="nav-item flex-fill text-center" role="presentation">
                    <a class="nav-link rounded-pill fw-semibold py-2 <?= $activeTab === 'tunggakan' ? 'active' : '' ?>" href="<?= base_url('reports?' . http_build_query(array_merge($filters, ['tab' => 'tunggakan']))) ?>">
                        <i class="bi bi-exclamation-octagon me-1"></i> 5. Laporan Tunggakan
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Report Table Container -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <!-- ======================================================= -->
                <!-- 1. TAB: LAPORAN PEMASUKAN                               -->
                <!-- ======================================================= -->
                <?php if ($activeTab === 'pemasukan'): ?>
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Laporan Pemasukan Kas</h5>
                            <small class="text-muted">Rincian seluruh penerimaan kas, iuran, donasi, dan sumbangan</small>
                        </div>
                        <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 rounded-pill">
                            Total: Rp <?= number_format($incomeReport['total_income'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Tanggal</th>
                                        <th class="py-3">Kode Transaksi</th>
                                        <th class="py-3">Kategori</th>
                                        <th class="py-3">Nominal (Rp)</th>
                                        <th class="pe-4 py-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($incomeReport['data'])): ?>
                                        <?php foreach ($incomeReport['data'] as $idx => $row): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                                <td class="small text-secondary"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($row['kode_transaksi']) ?></span></td>
                                                <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= e($row['category_name']) ?></span></td>
                                                <td class="fw-bold text-success">+ Rp <?= number_format((float)$row['nominal'], 2, ',', '.') ?></td>
                                                <td class="pe-4 small text-dark"><?= e($row['keterangan']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                                Tidak ada data pemasukan kas yang cocok dengan kriteria filter.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($incomeReport['data'])): ?>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="ps-4 text-end">TOTAL PEMASUKAN:</td>
                                        <td class="text-success">Rp <?= number_format($incomeReport['total_income'], 2, ',', '.') ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                <!-- ======================================================= -->
                <!-- 2. TAB: LAPORAN PENGELUARAN                             -->
                <!-- ======================================================= -->
                <?php elseif ($activeTab === 'pengeluaran'): ?>
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Laporan Pengeluaran Kas</h5>
                            <small class="text-muted">Rincian seluruh pembiayaan operasional, belanja perlengkapan, dan kegiatan</small>
                        </div>
                        <span class="badge bg-danger-subtle text-danger fs-6 px-3 py-2 rounded-pill">
                            Total: Rp <?= number_format($expenseReport['total_expense'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Tanggal</th>
                                        <th class="py-3">Kode Transaksi</th>
                                        <th class="py-3">Kategori</th>
                                        <th class="py-3">Nominal (Rp)</th>
                                        <th class="pe-4 py-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($expenseReport['data'])): ?>
                                        <?php foreach ($expenseReport['data'] as $idx => $row): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                                <td class="small text-secondary"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($row['kode_transaksi']) ?></span></td>
                                                <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= e($row['category_name']) ?></span></td>
                                                <td class="fw-bold text-danger">- Rp <?= number_format((float)$row['nominal'], 2, ',', '.') ?></td>
                                                <td class="pe-4 small text-dark"><?= e($row['keterangan']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                                Tidak ada data pengeluaran kas yang cocok dengan kriteria filter.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($expenseReport['data'])): ?>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="ps-4 text-end">TOTAL PENGELUARAN:</td>
                                        <td class="text-danger">Rp <?= number_format($expenseReport['total_expense'], 2, ',', '.') ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                <!-- ======================================================= -->
                <!-- 3. TAB: LAPORAN SALDO (REKONSILIASI KAS UMUM)           -->
                <!-- ======================================================= -->
                <?php elseif ($activeTab === 'saldo'): ?>
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Laporan Saldo & Rekonsiliasi Kas</h5>
                            <small class="text-muted">Buku kas umum kronologis lengkap dengan debit, kredit, dan saldo berjalan</small>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                In: Rp <?= number_format($saldoReport['total_income'], 2, ',', '.') ?>
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">
                                Out: Rp <?= number_format($saldoReport['total_expense'], 2, ',', '.') ?>
                            </span>
                            <span class="badge bg-primary text-white px-3 py-2 rounded-pill">
                                Saldo: Rp <?= number_format($saldoReport['net_balance'], 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">Tanggal</th>
                                        <th class="py-3">Kode Transaksi</th>
                                        <th class="py-3">Kategori</th>
                                        <th class="py-3 text-success">Pemasukan (Debit)</th>
                                        <th class="py-3 text-danger">Pengeluaran (Kredit)</th>
                                        <th class="py-3">Saldo Berjalan</th>
                                        <th class="pe-4 py-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($saldoReport['data'])): ?>
                                        <?php
                                            $runningSaldo = 0.0;
                                        ?>
                                        <?php foreach ($saldoReport['data'] as $row): ?>
                                            <?php
                                                $nom = (float)$row['nominal'];
                                                if ($row['tipe'] === 'pemasukan') {
                                                    $runningSaldo += $nom;
                                                } else {
                                                    $runningSaldo -= $nom;
                                                }
                                            ?>
                                            <tr>
                                                <td class="ps-4 small text-secondary"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($row['kode_transaksi']) ?></span></td>
                                                <td>
                                                    <span class="badge <?= $row['tipe'] === 'pemasukan' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> border">
                                                        <?= e($row['category_name']) ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-success">
                                                    <?= $row['tipe'] === 'pemasukan' ? 'Rp ' . number_format($nom, 2, ',', '.') : '-' ?>
                                                </td>
                                                <td class="fw-semibold text-danger">
                                                    <?= $row['tipe'] === 'pengeluaran' ? 'Rp ' . number_format($nom, 2, ',', '.') : '-' ?>
                                                </td>
                                                <td class="fw-bold text-dark">
                                                    Rp <?= number_format($runningSaldo, 2, ',', '.') ?>
                                                </td>
                                                <td class="pe-4 small text-dark"><?= e($row['keterangan']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                                Belum ada data mutasi saldo kas pada rentang waktu ini.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($saldoReport['data'])): ?>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="ps-4 text-end">TOTAL AKHIR:</td>
                                        <td class="text-success">Rp <?= number_format($saldoReport['total_income'], 2, ',', '.') ?></td>
                                        <td class="text-danger">Rp <?= number_format($saldoReport['total_expense'], 2, ',', '.') ?></td>
                                        <td class="text-primary fs-6">Rp <?= number_format($saldoReport['net_balance'], 2, ',', '.') ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                <!-- ======================================================= -->
                <!-- 4. TAB: LAPORAN PEMBAYARAN                              -->
                <!-- ======================================================= -->
                <?php elseif ($activeTab === 'pembayaran'): ?>
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Laporan Pembayaran Kas Anggota</h5>
                            <small class="text-muted">Rincian status iuran per peserta didik dan periode</small>
                        </div>
                        <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2 rounded-pill">
                            Total Terbayar: Rp <?= number_format($paymentsReport['total_paid'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Anggota</th>
                                        <th class="py-3">Kelas</th>
                                        <th class="py-3">Periode</th>
                                        <th class="py-3">Tagihan</th>
                                        <th class="py-3">Pembayaran</th>
                                        <th class="pe-4 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($paymentsReport['data'])): ?>
                                        <?php foreach ($paymentsReport['data'] as $idx => $p): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($p['member_name']) ?></div>
                                                    <small class="text-muted"><?= e($p['nomor_anggota']) ?> &bull; NIS: <?= e($p['nis'] ?? '-') ?></small>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Kelas <?= e($p['kelas']) ?></span></td>
                                                <td class="small fw-medium text-dark"><?= e($p['periode']) ?></td>
                                                <td class="small text-muted">Rp <?= number_format((float)$p['tagihan'], 2, ',', '.') ?></td>
                                                <td class="fw-bold text-success">Rp <?= number_format((float)$p['nominal'], 2, ',', '.') ?></td>
                                                <td class="pe-4 text-center">
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
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                                Tidak ada data pembayaran kas yang cocok dengan kriteria filter.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($paymentsReport['data'])): ?>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="4" class="ps-4 text-end">TOTAL TAGIHAN & PEMBAYARAN:</td>
                                        <td class="text-muted">Rp <?= number_format($paymentsReport['total_tagihan'], 2, ',', '.') ?></td>
                                        <td class="text-success">Rp <?= number_format($paymentsReport['total_paid'], 2, ',', '.') ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                <!-- ======================================================= -->
                <!-- 5. TAB: LAPORAN TUNGGAKAN                               -->
                <!-- ======================================================= -->
                <?php elseif ($activeTab === 'tunggakan'): ?>
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Laporan Tunggakan Kas</h5>
                            <small class="text-muted">Daftar anggota yang belum membayar atau pembayaran sebagian</small>
                        </div>
                        <span class="badge bg-danger-subtle text-danger fs-6 px-3 py-2 rounded-pill">
                            Total Tunggakan: Rp <?= number_format($arrearsReport['total_tunggakan'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Anggota</th>
                                        <th class="py-3">Kelas</th>
                                        <th class="py-3">Periode Tagihan</th>
                                        <th class="py-3">Nilai Tagihan</th>
                                        <th class="py-3">Sudah Dibayar</th>
                                        <th class="py-3">Sisa Tunggakan</th>
                                        <th class="pe-4 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($arrearsReport['data'])): ?>
                                        <?php foreach ($arrearsReport['data'] as $idx => $arr): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($arr['member_name']) ?></div>
                                                    <small class="text-muted"><?= e($arr['nomor_anggota']) ?> &bull; NIS: <?= e($arr['nis'] ?? '-') ?></small>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Kelas <?= e($arr['kelas']) ?></span></td>
                                                <td class="small fw-medium text-dark"><?= e($arr['periode']) ?></td>
                                                <td class="small text-muted">Rp <?= number_format((float)$arr['tagihan'], 2, ',', '.') ?></td>
                                                <td class="small text-dark">Rp <?= number_format((float)$arr['nominal'], 2, ',', '.') ?></td>
                                                <td class="fw-bold text-danger">
                                                    Rp <?= number_format((float)$arr['sisa'], 2, ',', '.') ?>
                                                </td>
                                                <td class="pe-4 text-center">
                                                    <?php if ($arr['status'] === 'Sebagian'): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                                            <i class="bi bi-clock-fill me-1"></i> SEBAGIAN
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                                            <i class="bi bi-x-circle-fill me-1"></i> BELUM BAYAR
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-5">
                                                <i class="bi bi-check2-circle fs-1 d-block mb-2 text-success"></i>
                                                <h6 class="fw-bold text-success mb-1">Tidak Ada Tunggakan Kas</h6>
                                                <p class="small text-muted mb-0">Seluruh anggota telah melunasi kewajiban iuran kas.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($arrearsReport['data'])): ?>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="6" class="ps-4 text-end">TOTAL SISA TUNGGAKAN:</td>
                                        <td class="text-danger fs-6">Rp <?= number_format($arrearsReport['total_tunggakan'], 2, ',', '.') ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
