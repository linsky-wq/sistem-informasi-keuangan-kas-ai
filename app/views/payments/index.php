<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title & Action -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-receipt me-1"></i> Iuran Anggota & Pembayaran
                            </span>
                            <span class="badge bg-light text-dark border rounded-pill">
                                Total <?= $total ?> Data Tagihan
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Manajemen Pembayaran Kas Anggota</h3>
                        <p class="text-secondary small mb-0">Kelola tagihan, verifikasi bukti transfer, pencatatan uang tunai, dan monitoring kas masuk.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-success rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCatatTunaiDirect">
                            <i class="bi bi-cash-stack"></i> Terima Uang Tunai
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahPembayaran">
                            <i class="bi bi-plus-circle-fill"></i> Tambah Tagihan Kas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary & Tunggakan Stats Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Tagihan -->
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-primary">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Nilai Tagihan</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rp <?= number_format($summary['total_tagihan'], 2, ',', '.') ?></h4>
                    <small class="text-muted"><?= $summary['count_total'] ?> tagihan dibebankan</small>
                </div>
            </div>
        </div>

        <!-- Total Terbayar -->
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Iuran Terbayar</span>
                    <h4 class="fw-bold text-success mt-1 mb-0">Rp <?= number_format($summary['total_dibayar'], 2, ',', '.') ?></h4>
                    <small class="text-muted"><?= $summary['count_lunas'] ?> Lunas &bull; <?= $summary['count_sebagian'] ?> Sebagian</small>
                </div>
            </div>
        </div>

        <!-- Sisa Tunggakan -->
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger bg-danger-subtle bg-opacity-25">
                <div class="card-body">
                    <span class="text-danger small fw-bold">Sisa Tunggakan Kas</span>
                    <h4 class="fw-bold text-danger mt-1 mb-0">Rp <?= number_format($summary['total_tunggakan'], 2, ',', '.') ?></h4>
                    <small class="text-danger"><?= $summary['count_belum_bayar'] ?> belum bayar sama sekali</small>
                </div>
            </div>
        </div>

        <!-- Pengajuan Menunggu Verifikasi -->
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-warning bg-warning-subtle bg-opacity-25">
                <div class="card-body d-flex flex-column justify-content-center">
                    <span class="text-warning small fw-bold">Menunggu Verifikasi</span>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <h4 class="fw-bold text-dark mb-0"><?= $pendingCount ?> Pengajuan</h4>
                        <a href="<?= base_url('payments?tab=verifikasi') ?>" class="btn btn-warning btn-sm rounded-pill px-3 py-1 fw-semibold">
                            Tinjau <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <small class="text-muted mt-1">Bukti transfer & penerimaan tunai</small>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB NAVIGATION -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-2">
                <ul class="nav nav-pills nav-fill gap-2" id="paymentTabs">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill fw-semibold <?= $activeTab === 'tagihan' ? 'active' : '' ?>" href="<?= base_url('payments?tab=tagihan') ?>">
                            <i class="bi bi-receipt me-1"></i> Tagihan Iuran Anggota
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill fw-semibold <?= $activeTab === 'verifikasi' ? 'active' : '' ?> position-relative" href="<?= base_url('payments?tab=verifikasi') ?>">
                            <i class="bi bi-shield-check me-1"></i> Verifikasi Pembayaran Masuk
                            <?php if ($pendingCount > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-2"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill fw-semibold <?= $activeTab === 'transaksi' ? 'active' : '' ?>" href="<?= base_url('payments?tab=transaksi') ?>">
                            <i class="bi bi-clock-history me-1"></i> Riwayat Semua Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill fw-semibold <?= $activeTab === 'pengaturan' ? 'active' : '' ?>" href="<?= base_url('payments?tab=pengaturan') ?>">
                            <i class="bi bi-gear-fill me-1"></i> Rekening Bank & Gateway
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- TAB 1: TAGIHAN IURAN ANGGOTA                            -->
    <!-- ======================================================= -->
    <?php if ($activeTab === 'tagihan'): ?>
        <!-- Search & Multi-Filter Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-3 p-md-4">
                        <form action="<?= base_url('payments') ?>" method="GET" class="row g-2 align-items-center">
                            <input type="hidden" name="tab" value="tagihan">
                            <!-- Search Keyword -->
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cari nama anggota, nomor, NIS...">
                                </div>
                            </div>

                            <!-- Filter Periode -->
                            <div class="col-md-3">
                                <select class="form-select bg-light" name="periode">
                                    <option value="">-- Semua Periode --</option>
                                    <?php foreach ($periods as $p): ?>
                                        <option value="<?= e($p) ?>" <?= ($filters['periode'] ?? '') === $p ? 'selected' : '' ?>>
                                            <?= e($p) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter Kelas -->
                            <div class="col-md-2">
                                <select class="form-select bg-light" name="kelas">
                                    <option value="">-- Semua Kelas --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= e($c) ?>" <?= ($filters['kelas'] ?? '') === $c ? 'selected' : '' ?>>
                                            Kelas <?= e($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter Status -->
                            <div class="col-md-2">
                                <select class="form-select bg-light" name="status">
                                    <option value="">-- Semua Status --</option>
                                    <option value="Lunas" <?= ($filters['status'] ?? '') === 'Lunas' ? 'selected' : '' ?>>LUNAS</option>
                                    <option value="Sebagian" <?= ($filters['status'] ?? '') === 'Sebagian' ? 'selected' : '' ?>>SEBAGIAN</option>
                                    <option value="Belum Bayar" <?= ($filters['status'] ?? '') === 'Belum Bayar' ? 'selected' : '' ?>>BELUM BAYAR</option>
                                </select>
                            </div>

                            <!-- Filter & Reset Buttons -->
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-primary w-100 rounded-3" title="Filter Data">
                                    <i class="bi bi-funnel-fill"></i>
                                </button>
                                <?php if (!empty($filters['search']) || !empty($filters['periode']) || !empty($filters['kelas']) || !empty($filters['status'])): ?>
                                    <a href="<?= base_url('payments?tab=tagihan') ?>" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table Card -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Nama Anggota</th>
                                        <th class="py-3">Periode</th>
                                        <th class="py-3">Tagihan</th>
                                        <th class="py-3">Disetor</th>
                                        <th class="py-3">Sisa Tunggakan</th>
                                        <th class="py-3">Tanggal Bayar</th>
                                        <th class="py-3 text-center">Status</th>
                                        <th class="py-3">Verifikator</th>
                                        <th class="pe-4 py-3 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($payments)): ?>
                                        <?php foreach ($payments as $idx => $p): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= (($page - 1) * $perPage) + $idx + 1 ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($p['member_name']) ?></div>
                                                    <small class="text-muted"><?= e($p['nomor_anggota']) ?> &bull; <?= e($p['kelas']) ?></small>
                                                </td>
                                                <td class="small fw-medium text-dark"><?= e($p['periode']) ?></td>
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
                                                <td class="small text-secondary"><?= date('d M Y', strtotime($p['tanggal_pembayaran'])) ?></td>
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
                                                <td class="small text-muted">
                                                    <?php if (!empty($p['verified_by'])): ?>
                                                        <span class="text-dark"><i class="bi bi-shield-check text-success me-1"></i> <?= e($p['verifier_name']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="bi bi-hourglass me-1"></i> Belum</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- Detail Button -->
                                                        <button type="button" class="btn btn-outline-info btn-detail-payment" data-id="<?= $p['id'] ?>" title="Detail Pembayaran">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <!-- Verify Button -->
                                                        <button type="button" class="btn btn-outline-success btn-verify-payment" data-id="<?= $p['id'] ?>" data-name="<?= e($p['member_name']) ?>" data-nominal="<?= $p['nominal'] ?>" data-tagihan="<?= $p['tagihan'] ?? 50000 ?>" title="Verifikasi Pembayaran">
                                                            <i class="bi bi-check2-square"></i>
                                                        </button>
                                                        <!-- WhatsApp Reminder Button -->
                                                        <button type="button" class="btn btn-outline-success btn-wa-reminder" 
                                                                data-id="<?= $p['id'] ?>" 
                                                                data-name="<?= e($p['member_name']) ?>" 
                                                                data-periode="<?= e($p['periode']) ?>"
                                                                data-sisa="<?= number_format((float)$p['sisa'], 0, ',', '.') ?>"
                                                                title="Kirim Pengingat WhatsApp">
                                                            <i class="bi bi-whatsapp"></i>
                                                        </button>
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-outline-primary btn-edit-payment" data-id="<?= $p['id'] ?>" title="Edit Pembayaran">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <!-- Delete Button -->
                                                        <button type="button" class="btn btn-outline-danger btn-delete-payment" data-id="<?= $p['id'] ?>" data-name="<?= e($p['member_name']) ?>" data-periode="<?= e($p['periode']) ?>" title="Hapus Pembayaran">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-5">
                                                <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 text-secondary"></i>
                                                Tidak ada data pembayaran kas yang cocok dengan kriteria filter.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top gap-2 bg-light">
                            <small class="text-muted">
                                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $total ?> pembayaran)
                            </small>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= base_url('payments?' . http_build_query(array_merge($filters, ['tab' => 'tagihan', 'page' => $page - 1]))) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= base_url('payments?' . http_build_query(array_merge($filters, ['tab' => 'tagihan', 'page' => $p]))) ?>">
                                                <?= $p ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= base_url('payments?' . http_build_query(array_merge($filters, ['tab' => 'tagihan', 'page' => $page + 1]))) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <!-- ======================================================= -->
    <!-- TAB 2: VERIFIKASI PEMBAYARAN MASUK                      -->
    <!-- ======================================================= -->
    <?php elseif ($activeTab === 'verifikasi'): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Antrean Pembayaran Menunggu Verifikasi</h5>
                            <small class="text-muted">Periksa bukti transfer atau konfirmasi setoran tunai anggota sebelum uang dicatat ke Buku Kas Umum</small>
                        </div>
                        <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-2 rounded-pill fw-bold">
                            <i class="bi bi-hourglass-split me-1"></i> <?= $pendingCount ?> Pengajuan Menunggu
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Ref Transaksi</th>
                                        <th class="py-3">Anggota</th>
                                        <th class="py-3">Periode</th>
                                        <th class="py-3">Metode & Channel</th>
                                        <th class="py-3">Nominal Setor</th>
                                        <th class="py-3">Bukti Setor</th>
                                        <th class="py-3">Waktu Pengajuan</th>
                                        <th class="pe-4 py-3 text-end">Aksi Bendahara</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pendingSubmissions['data'])): ?>
                                        <?php foreach ($pendingSubmissions['data'] as $idx => $trx): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                                <td class="fw-semibold text-primary font-monospace small"><?= e($trx['kode_transaksi']) ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($trx['member_name']) ?></div>
                                                    <small class="text-muted"><?= e($trx['nomor_anggota']) ?> &bull; <?= e($trx['kelas']) ?></small>
                                                </td>
                                                <td class="small fw-medium text-dark"><?= e($trx['payment_periode']) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= e(\App\Models\PaymentTransaction::getMethodLabel($trx['metode'])) ?></span>
                                                    <small class="d-block text-muted"><?= e($trx['channel'] ?? '-') ?></small>
                                                </td>
                                                <td class="fw-bold fs-6 text-success">Rp <?= number_format((float)$trx['nominal'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if (!empty($trx['bukti_transfer'])): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill btn-preview-proof" 
                                                                data-file="<?= e($trx['bukti_transfer']) ?>" 
                                                                data-kode="<?= e($trx['kode_transaksi']) ?>"
                                                                data-member="<?= e($trx['member_name']) ?>"
                                                                data-nominal="<?= number_format((float)$trx['nominal'], 0, ',', '.') ?>">
                                                            <i class="bi bi-image me-1"></i> Lihat Bukti
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border">Setor Tunai Fisik</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-secondary"><?= date('d M Y, H:i', strtotime($trx['created_at'])) ?></td>
                                                <td class="pe-4 text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-success btn-action-verify" 
                                                                data-id="<?= $trx['id'] ?>" 
                                                                data-kode="<?= e($trx['kode_transaksi']) ?>" 
                                                                data-name="<?= e($trx['member_name']) ?>" 
                                                                data-nominal="<?= number_format((float)$trx['nominal'], 0, ',', '.') ?>" 
                                                                data-periode="<?= e($trx['payment_periode']) ?>"
                                                                title="Setujui Pembayaran">
                                                            <i class="bi bi-check-lg me-1"></i> Terima
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-action-reject" 
                                                                data-id="<?= $trx['id'] ?>" 
                                                                data-kode="<?= e($trx['kode_transaksi']) ?>" 
                                                                data-name="<?= e($trx['member_name']) ?>"
                                                                title="Tolak Pengajuan">
                                                            <i class="bi bi-x-lg me-1"></i> Tolak
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-5">
                                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                                Tidak ada antrean pembayaran yang menunggu verifikasi saat ini.
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

    <!-- ======================================================= -->
    <!-- TAB 3: RIWAYAT SEMUA TRANSAKSI                          -->
    <!-- ======================================================= -->
    <?php elseif ($activeTab === 'transaksi'): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Riwayat Seluruh Transaksi Pembayaran Kas</h5>
                            <small class="text-muted">Log transaksi iuran kas anggota lintas seluruh metode pembayaran</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <th class="ps-4 py-3">No</th>
                                        <th class="py-3">Kode Transaksi</th>
                                        <th class="py-3">Anggota</th>
                                        <th class="py-3">Periode Tagihan</th>
                                        <th class="py-3">Metode & Channel</th>
                                        <th class="py-3">Nominal</th>
                                        <th class="py-3 text-center">Status Transaksi</th>
                                        <th class="py-3">No. Buku Kas</th>
                                        <th class="py-3">Verifikator</th>
                                        <th class="pe-4 py-3 text-end">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($allTransactions['data'])): ?>
                                        <?php foreach ($allTransactions['data'] as $idx => $t): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= (($allTransactions['page'] - 1) * $allTransactions['perPage']) + $idx + 1 ?></td>
                                                <td class="fw-semibold text-primary font-monospace small"><?= e($t['kode_transaksi']) ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= e($t['member_name']) ?></div>
                                                    <small class="text-muted"><?= e($t['nomor_anggota']) ?> &bull; <?= e($t['kelas']) ?></small>
                                                </td>
                                                <td class="small fw-medium text-dark"><?= e($t['payment_periode']) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= e(\App\Models\PaymentTransaction::getMethodLabel($t['metode'])) ?></span>
                                                    <small class="text-muted d-block"><?= e($t['channel'] ?? '-') ?></small>
                                                </td>
                                                <td class="fw-bold text-dark">Rp <?= number_format((float)$t['nominal'], 2, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= \App\Models\PaymentTransaction::getStatusBadgeClass($t['status']) ?> px-3 py-1 rounded-pill">
                                                        <?= e(\App\Models\PaymentTransaction::getStatusLabel($t['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($t['trx_kas_kode'])): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">
                                                            <i class="bi bi-journal-check me-1"></i> <?= e($t['trx_kas_kode']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= !empty($t['verifier_name']) ? '<i class="bi bi-shield-check text-success me-1"></i>' . e($t['verifier_name']) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td class="pe-4 small text-secondary text-end"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-5">
                                                <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary"></i>
                                                Belum ada transaksi pembayaran yang tercatat.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($allTransactions['totalPages'] > 1): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                            <small class="text-muted">
                                Halaman <?= $allTransactions['page'] ?> dari <?= $allTransactions['totalPages'] ?> (Total <?= $allTransactions['total'] ?> transaksi)
                            </small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= $allTransactions['page'] <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= base_url('payments?tab=transaksi&trx_page=' . ($allTransactions['page'] - 1)) ?>"><i class="bi bi-chevron-left"></i></a>
                                    </li>
                                    <?php for ($p = 1; $p <= $allTransactions['totalPages']; $p++): ?>
                                        <li class="page-item <?= $p === $allTransactions['page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= base_url('payments?tab=transaksi&trx_page=' . $p) ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $allTransactions['page'] >= $allTransactions['totalPages'] ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= base_url('payments?tab=transaksi&trx_page=' . ($allTransactions['page'] + 1)) ?>"><i class="bi bi-chevron-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <!-- ======================================================= -->
    <!-- TAB 4: REKENING BANK & GATEWAY PENGATURAN                -->
    <!-- ======================================================= -->
    <?php elseif ($activeTab === 'pengaturan'): ?>
        <div class="row g-4">
            <!-- 1. Konfigurasi Rekening Bank Tujuan Transfer -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-bank"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Rekening Bank Tujuan Transfer</h5>
                                <small class="text-muted">Tampil pada halaman anggota untuk pilihan transfer bank</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <form action="<?= base_url('payments/saveBankAccounts') ?>" method="POST" id="formBankAccounts">
                            <?= \App\Core\Security::csrfField() ?>
                            
                            <div id="bankAccountsContainer" class="mb-3">
                                <?php if (!empty($bankAccounts)): ?>
                                    <?php foreach ($bankAccounts as $idx => $acc): ?>
                                        <div class="card border p-3 rounded-3 mb-2 bg-light bank-row">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-primary">Rekening #<?= $idx + 1 ?></span>
                                                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 btn-remove-bank" title="Hapus Rekening"><i class="bi bi-trash"></i></button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <input type="text" class="form-control form-control-sm" name="bank_name[]" value="<?= e($acc['bank_name']) ?>" placeholder="Nama Bank (BCA)" required>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control form-control-sm font-monospace" name="account_number[]" value="<?= e($acc['account_number']) ?>" placeholder="Nomor Rekening" required>
                                                </div>
                                                <div class="col-12">
                                                    <input type="text" class="form-control form-control-sm" name="account_holder[]" value="<?= e($acc['account_holder']) ?>" placeholder="Atas Nama (a.n.)" required>
                                                </div>
                                                <div class="col-12">
                                                    <input type="text" class="form-control form-control-sm" name="instructions[]" value="<?= e($acc['instructions'] ?? '') ?>" placeholder="Petunjuk Singkat (Opsional)">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning small mb-2">Belum ada rekening bank yang dikonfigurasi. Klik tombol tambah di bawah.</div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="btnAddBankRow">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Rekening Bank
                                </button>
                                <button type="submit" class="btn btn-primary rounded-pill px-4">
                                    <i class="bi bi-save me-1"></i> Simpan Rekening
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2. Konfigurasi Payment Gateway Midtrans (QRIS & E-Wallet) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-qr-code-scan"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Payment Gateway Resmi (QRIS & E-Wallet)</h5>
                                <small class="text-muted">Integrasi payment gateway resmi (Midtrans Core/Snap)</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <!-- Status Alert -->
                        <?php if ($gatewayConfig['is_qris_active'] || $gatewayConfig['is_ewallet_active']): ?>
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-3">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <div>
                                    <div class="fw-bold">Payment Gateway Aktif</div>
                                    <small>Mode: <?= strtoupper($gatewayConfig['environment']) ?> &bull; Server Key Terkonfigurasi</small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning d-flex align-items-center gap-2 rounded-3 mb-3">
                                <i class="bi bi-shield-lock-fill fs-4 text-warning"></i>
                                <div>
                                    <div class="fw-bold">Metode Digital Belum Aktif</div>
                                    <small>Kredensial Server Key resmi belum diisi. QRIS & E-Wallet tidak ditampilkan aktif ke anggota untuk mencegah transaksi palsu.</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="<?= base_url('payments/saveGatewaySettings') ?>" method="POST">
                            <?= \App\Core\Security::csrfField() ?>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Provider Payment Gateway</label>
                                <select class="form-select bg-light" name="payment_gateway_provider">
                                    <option value="midtrans" selected>Midtrans (Official Payment Gateway)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Environment</label>
                                <select class="form-select bg-light" name="payment_gateway_env">
                                    <option value="sandbox" <?= $gatewayConfig['environment'] === 'sandbox' ? 'selected' : '' ?>>Sandbox (Mode Uji Coba Resmi)</option>
                                    <option value="production" <?= $gatewayConfig['environment'] === 'production' ? 'selected' : '' ?>>Production (Live Transaksi Nyata)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Client Key</label>
                                <input type="text" class="form-control font-monospace" name="midtrans_client_key" value="<?= e($gatewayConfig['client_key']) ?>" placeholder="Contoh: SB-Mid-client-XXXXX">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Server Key (Rahasia)</label>
                                <input type="password" class="form-control font-monospace" name="midtrans_server_key" placeholder="<?= $gatewayConfig['has_server_key'] ? '•••••••••••••••• (Tersimpan aman, isi untuk mengubah)' : 'Contoh: SB-Mid-server-XXXXX' ?>">
                                <small class="text-muted">Kunci rahasia dari Dashboard Midtrans. Disimpan aman pada database.</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="qris_enabled" value="1" id="switchQris" <?= $gatewayConfig['qris_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold small text-dark" for="switchQris">
                                        Aktifkan Metode QRIS Resmi
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ewallet_enabled" value="1" id="switchEwallet" <?= $gatewayConfig['ewallet_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold small text-dark" for="switchEwallet">
                                        Aktifkan Metode E-Wallet (GoPay, ShopeePay, OVO)
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-4">
                                    <i class="bi bi-save me-1"></i> Simpan Pengaturan Gateway
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ======================================================= -->
<!-- MODAL: CATAT PENERIMAAN UANG TUNAI SECARA LANGSUNG      -->
<!-- ======================================================= -->
<div class="modal fade" id="modalCatatTunaiDirect" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-cash-coin fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Pencatatan Uang Tunai Kas</h5>
                        <small class="text-white-50">Catat penerimaan uang tunai fisik langsung ke Buku Kas</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('payments/recordCashReceipt') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Pilih Anggota Penyetor <span class="text-danger">*</span></label>
                        <select class="form-select" name="member_id" id="cash_direct_member" required>
                            <option value="">-- Pilih Anggota --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= e($m['nama']) ?> (<?= e($m['nomor_anggota']) ?> &bull; <?= e($m['kelas']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nominal Uang Tunai Diterima (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold text-success">Rp</span>
                            <input type="number" step="500" min="1000" class="form-control" name="nominal" placeholder="Contoh: 50000" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Catatan Penerimaan</label>
                        <input type="text" class="form-control" name="catatan" placeholder="Contoh: Diterima langsung oleh Bendahara di ruang kelas">
                    </div>

                    <div class="alert alert-info small mb-0 rounded-3">
                        <i class="bi bi-shield-check text-primary me-1"></i> Pembayaran tunai ini akan diverifikasi secara instan dan pemasukan kas umum akan langsung dicatat ke Buku Kas dengan referensi penerimaan tunai.
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Catat & Terima Uang Tunai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: VERIFIKASI TRANSAKSI MASUK                       -->
<!-- ======================================================= -->
<div class="modal fade" id="modalVerifyTrx" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white p-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Konfirmasi Verifikasi Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('payments/verifyTransaction') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="verify_trx_id">
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        Anda akan menyetujui pembayaran kas berikut dan mencatat pemasukannya ke dalam <strong>Buku Kas Umum</strong>:
                    </p>
                    <div class="card bg-light p-3 rounded-3 mb-3 border-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Ref Transaksi:</span>
                            <span class="font-monospace text-primary fw-bold" id="verify_trx_kode"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Anggota:</span>
                            <strong class="text-dark" id="verify_trx_name"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Periode:</span>
                            <span class="text-dark" id="verify_trx_periode"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Nominal:</span>
                            <span class="fw-bold text-success fs-6" id="verify_trx_nominal"></span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small">Catatan Verifikator (Opsional)</label>
                        <input type="text" class="form-control" name="catatan" placeholder="Contoh: Bukti transfer valid sesuai mutasi bank">
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Ya, Setujui & Catat ke Buku Kas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: TOLAK TRANSAKSI PEMBAYARAN                       -->
<!-- ======================================================= -->
<div class="modal fade" id="modalRejectTrx" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white p-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-x-circle-fill me-2"></i> Tolak Pengajuan Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('payments/rejectTransaction') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="reject_trx_id">
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        Tolak pengajuan pembayaran dari <strong id="reject_trx_name"></strong> (<span id="reject_trx_kode" class="font-monospace text-primary"></span>).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_penolakan" rows="3" placeholder="Contoh: Foto bukti transfer buram, mutasi belum masuk ke rekening kas, atau nominal tidak sesuai..." required></textarea>
                        <div class="form-text text-muted small">Alasan ini akan ditampilkan kepada anggota agar dapat mengajukan ulang bukti yang benar.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">
                        <i class="bi bi-x-octagon-fill me-1"></i> Tolak Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: PREVIEW BUKTI TRANSFER                           -->
<!-- ======================================================= -->
<div class="modal fade" id="modalPreviewBukti" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 border-0">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="preview_bukti_title">Bukti Pembayaran Kas</h6>
                    <small class="text-white-50" id="preview_bukti_subtitle"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 text-center bg-light" id="preview_bukti_body">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer bg-white p-3 border-0">
                <a href="#" id="preview_bukti_download" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Berkas Penuh
                </a>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: CATAT TAGIHAN PEMBAYARAN BARU                    -->
<!-- ======================================================= -->
<div class="modal fade" id="modalTambahPembayaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-receipt fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Tambah Tagihan Iuran Kas</h5>
                        <small class="text-white-50">Bebankan tagihan kas ke anggota</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('payments/create') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-secondary small">Pilih Anggota <span class="text-danger">*</span></label>
                            <select class="form-select" name="member_id" required>
                                <option value="">-- Pilih Anggota --</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= e($m['nama']) ?> (<?= e($m['nomor_anggota']) ?> &bull; <?= e($m['kelas']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-secondary small">Periode Tagihan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="periode" placeholder="Contoh: Oktober 2026 (Minggu 2)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nilai Tagihan (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                                <input type="number" step="0.01" min="1" class="form-control" name="tagihan" value="50000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nominal Disetor Saat Ini (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="nominal" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Tanggal Tagihan / Bayar</label>
                            <input type="date" class="form-control" name="tanggal_pembayaran" value="<?= $todayDate ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="2" placeholder="Catatan tagihan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: DETAIL TAGIHAN                                   -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDetailPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-dark p-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-1"></i> Detail Tagihan Iuran Kas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailPaymentModalBody"></div>
            <div class="modal-footer bg-light p-3 border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: VERIFIKASI TAGIHAN                               -->
<!-- ======================================================= -->
<div class="modal fade" id="modalVerifyPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white p-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-1"></i> Verifikasi Tagihan Kas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('payments/verify') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="verify_payment_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <small class="text-muted d-block">Anggota</small>
                        <h6 class="fw-bold text-dark" id="verify_member_name"></h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nominal Terverifikasi (Rp)</label>
                        <input type="number" step="0.01" class="form-control" name="nominal" id="verify_nominal" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small">Catatan Verifikasi</label>
                        <textarea class="form-control" name="catatan" id="verify_catatan" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Verifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: EDIT TAGIHAN                                     -->
<!-- ======================================================= -->
<div class="modal fade" id="modalEditPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4">
                <h5 class="modal-title fw-bold">Edit Tagihan Kas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('payments/update') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="edit_pay_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Anggota</small>
                            <strong class="text-dark" id="edit_pay_member_name"></strong>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Periode Tagihan</label>
                            <input type="text" class="form-control" name="periode" id="edit_pay_periode" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nilai Tagihan (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="tagihan" id="edit_pay_tagihan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Nominal Disetor (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="nominal" id="edit_pay_nominal" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Tanggal Pembayaran</label>
                            <input type="date" class="form-control" name="tanggal_pembayaran" id="edit_pay_tanggal" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Catatan</label>
                            <textarea class="form-control" name="catatan" id="edit_pay_catatan" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: HAPUS TAGIHAN                                    -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDeletePayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-4">
            <div class="mb-3 text-danger"><i class="bi bi-trash3-fill fs-1"></i></div>
            <h5 class="fw-bold mb-2 text-danger">Hapus Tagihan</h5>
            <p class="text-secondary small mb-4">
                Hapus data tagihan untuk <strong id="delete_pay_name" class="text-dark"></strong> periode <strong id="delete_pay_periode" class="text-dark"></strong>?
            </p>
            <form action="<?= base_url('payments/delete') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="delete_pay_id">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3 py-2 btn-sm fw-semibold">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: KIRIM PENGINGAT WHATSAPP                         -->
<!-- ======================================================= -->
<div class="modal fade" id="modalWaReminder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-4">
            <div class="mb-3 text-success"><i class="bi bi-whatsapp fs-1"></i></div>
            <h5 class="fw-bold mb-2 text-dark">Kirim Pengingat WhatsApp</h5>
            <p class="text-secondary small mb-4">
                Kirim pesan tagihan kas untuk <strong id="wa_reminder_name" class="text-dark"></strong> (Sisa: Rp <span id="wa_reminder_sisa"></span>) via WhatsApp?
            </p>
            <form action="<?= base_url('payments/sendWaReminder') ?>" method="POST">
                <?= \App\Core\Security::csrfField() ?>
                <input type="hidden" name="id" id="wa_reminder_id">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-3 py-2 btn-sm fw-semibold">
                        <i class="bi bi-send-fill me-1"></i> Ya, Kirim WA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 1. Action Verify Transaction Button
    document.querySelectorAll('.btn-action-verify').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('verify_trx_id').value = this.dataset.id;
            document.getElementById('verify_trx_kode').textContent = this.dataset.kode;
            document.getElementById('verify_trx_name').textContent = this.dataset.name;
            document.getElementById('verify_trx_periode').textContent = this.dataset.periode;
            document.getElementById('verify_trx_nominal').textContent = 'Rp ' + this.dataset.nominal;

            const modal = new bootstrap.Modal(document.getElementById('modalVerifyTrx'));
            modal.show();
        });
    });

    // 2. Action Reject Transaction Button
    document.querySelectorAll('.btn-action-reject').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('reject_trx_id').value = this.dataset.id;
            document.getElementById('reject_trx_kode').textContent = this.dataset.kode;
            document.getElementById('reject_trx_name').textContent = this.dataset.name;

            const modal = new bootstrap.Modal(document.getElementById('modalRejectTrx'));
            modal.show();
        });
    });

    // 3. Preview Bukti Modal
    document.querySelectorAll('.btn-preview-proof').forEach(btn => {
        btn.addEventListener('click', function () {
            const fileName = this.dataset.file;
            const kode = this.dataset.kode;
            const member = this.dataset.member;
            const nominal = this.dataset.nominal;

            document.getElementById('preview_bukti_title').textContent = `Bukti Transfer: ${kode}`;
            document.getElementById('preview_bukti_subtitle').textContent = `Anggota: ${member} • Nominal: Rp ${nominal}`;
            
            const fileUrl = `<?= base_url('assets/uploads/payments/') ?>${fileName}`;
            document.getElementById('preview_bukti_download').href = fileUrl;

            const bodyEl = document.getElementById('preview_bukti_body');
            const fileExt = fileName.split('.').pop().toLowerCase();

            if (fileExt === 'pdf') {
                bodyEl.innerHTML = `
                    <div class="py-4">
                        <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-2"></i>
                        <h6 class="fw-bold">Dokumen Bukti Berformat PDF</h6>
                        <p class="small text-muted mb-3">Klik tombol di bawah untuk membuka berkas dokumen PDF secara langsung.</p>
                        <a href="${fileUrl}" target="_blank" class="btn btn-danger btn-sm rounded-pill px-4">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka PDF di Tab Baru
                        </a>
                    </div>
                `;
            } else {
                bodyEl.innerHTML = `
                    <img src="${fileUrl}" class="img-fluid rounded-3 shadow-sm" style="max-height: 500px;" alt="Bukti Transfer">
                `;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalPreviewBukti'));
            modal.show();
        });
    });

    // 4. Dynamic Bank Account Rows in Pengaturan Tab
    const btnAddBank = document.getElementById('btnAddBankRow');
    if (btnAddBank) {
        btnAddBank.addEventListener('click', function () {
            const container = document.getElementById('bankAccountsContainer');
            const rowCount = container.querySelectorAll('.bank-row').length + 1;
            const newRow = document.createElement('div');
            newRow.className = 'card border p-3 rounded-3 mb-2 bg-light bank-row';
            newRow.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary">Rekening #${rowCount}</span>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 btn-remove-bank" title="Hapus"><i class="bi bi-trash"></i></button>
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <input type="text" class="form-control form-control-sm" name="bank_name[]" placeholder="Nama Bank (BCA)" required>
                    </div>
                    <div class="col-8">
                        <input type="text" class="form-control form-control-sm font-monospace" name="account_number[]" placeholder="Nomor Rekening" required>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control form-control-sm" name="account_holder[]" placeholder="Atas Nama (a.n.)" required>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control form-control-sm" name="instructions[]" placeholder="Petunjuk Singkat (Opsional)">
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            attachRemoveBank();
        });
    }

    function attachRemoveBank() {
        document.querySelectorAll('.btn-remove-bank').forEach(btn => {
            btn.onclick = function () {
                const row = this.closest('.bank-row');
                if (row) row.remove();
            };
        });
    }
    attachRemoveBank();

    // 5. Existing Tagihan Detail Modal
    document.querySelectorAll('.btn-detail-payment').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const container = document.getElementById('detailPaymentModalBody');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

            const modal = new bootstrap.Modal(document.getElementById('modalDetailPayment'));
            modal.show();

            try {
                const res = await fetch(`<?= base_url('payments/getJson') ?>?id=${id}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const p = result.data;
                    const safeMemberName = escapeHtml(p.member_name);
                    const safeNoAnggota = escapeHtml(p.nomor_anggota);
                    const safeKelas = escapeHtml(p.kelas);
                    const safePeriode = escapeHtml(p.periode);
                    const safeStatus = escapeHtml(p.status);
                    const safeTanggal = escapeHtml(p.tanggal_pembayaran);
                    const safeVerifier = p.verifier_name ? escapeHtml(p.verifier_name) : null;
                    const safeVerifiedAt = escapeHtml(p.verified_at || '-');
                    const safeCatatan = escapeHtml(p.catatan || '-');

                    const statusClass = p.status === 'Lunas' ? 'bg-success-subtle text-success' : (p.status === 'Sebagian' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger');
                    container.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nama Anggota</small>
                                <strong class="text-dark fs-5">${safeMemberName}</strong>
                                <small class="text-muted d-block">${safeNoAnggota} &bull; ${safeKelas}</small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Periode Tagihan</small>
                                <span class="badge bg-light text-primary border fs-6">${safePeriode}</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Total Tagihan</small>
                                <strong class="text-dark">Rp ${Number(p.tagihan || 50000).toLocaleString('id-ID', {minimumFractionDigits: 2})}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nominal Disetor</small>
                                <strong class="text-success">Rp ${Number(p.nominal).toLocaleString('id-ID', {minimumFractionDigits: 2})}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Sisa Tunggakan</small>
                                <strong class="${Number(p.sisa) > 0 ? 'text-danger' : 'text-success'}">Rp ${Number(p.sisa).toLocaleString('id-ID', {minimumFractionDigits: 2})}</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Status Pembayaran</small>
                                <span class="badge ${statusClass} border px-3 py-1 rounded-pill">${safeStatus}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Tanggal Penyetoran</small>
                                <span class="text-dark">${safeTanggal}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Petugas Verifikator</small>
                                <span>${safeVerifier ? '<i class="bi bi-shield-check text-success me-1"></i>' + safeVerifier : '<span class="text-muted">Menunggu Verifikasi</span>'}</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Waktu Diverifikasi</small>
                                <span class="text-muted">${safeVerifiedAt}</span>
                            </div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12">
                                <small class="text-muted d-block">Catatan</small>
                                <p class="text-secondary mb-0">${safeCatatan}</p>
                            </div>
                        </div>
                    `;
                }
            } catch (err) {
                container.innerHTML = `<div class="alert alert-danger">Gagal memuat detail: ${escapeHtml(err.message)}</div>`;
            }
        });
    });

    // 6. Verify Payment Modal
    document.querySelectorAll('.btn-verify-payment').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('verify_payment_id').value = this.dataset.id;
            document.getElementById('verify_member_name').textContent = this.dataset.name;
            document.getElementById('verify_nominal').value = this.dataset.nominal;

            const modal = new bootstrap.Modal(document.getElementById('modalVerifyPayment'));
            modal.show();
        });
    });

    // 7. Edit Payment Modal
    document.querySelectorAll('.btn-edit-payment').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            try {
                const res = await fetch(`<?= base_url('payments/getJson') ?>?id=${id}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const p = result.data;
                    document.getElementById('edit_pay_id').value = p.id;
                    document.getElementById('edit_pay_member_name').textContent = `${p.member_name} (${p.nomor_anggota})`;
                    document.getElementById('edit_pay_periode').value = p.periode;
                    document.getElementById('edit_pay_tagihan').value = p.tagihan || 50000;
                    document.getElementById('edit_pay_nominal').value = p.nominal;
                    document.getElementById('edit_pay_tanggal').value = p.tanggal_pembayaran;
                    document.getElementById('edit_pay_catatan').value = p.catatan || '';

                    const modal = new bootstrap.Modal(document.getElementById('modalEditPayment'));
                    modal.show();
                }
            } catch (err) {
                alert('Gagal memuat pembayaran: ' + err.message);
            }
        });
    });

    // 8. Delete Payment Modal
    document.querySelectorAll('.btn-delete-payment').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('delete_pay_id').value = this.dataset.id;
            document.getElementById('delete_pay_name').textContent = this.dataset.name;
            document.getElementById('delete_pay_periode').textContent = this.dataset.periode;

            const modal = new bootstrap.Modal(document.getElementById('modalDeletePayment'));
            modal.show();
        });
    });

    // 9. WhatsApp Reminder Modal
    document.querySelectorAll('.btn-wa-reminder').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('wa_reminder_id').value = this.dataset.id;
            document.getElementById('wa_reminder_name').textContent = this.dataset.name;
            document.getElementById('wa_reminder_periode').textContent = this.dataset.periode;
            document.getElementById('wa_reminder_sisa').textContent = this.dataset.sisa;

            const modal = new bootstrap.Modal(document.getElementById('modalWaReminder'));
            modal.show();
        });
    });
});
</script>
