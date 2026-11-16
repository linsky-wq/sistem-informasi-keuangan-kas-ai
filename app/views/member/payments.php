<div class="container py-4 flex-grow-1">
    <!-- Header Title & Action -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                            <i class="bi bi-wallet2 me-1"></i> Iuran Anggota
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Tagihan & Pembayaran Kas Saya</h3>
                        <p class="text-secondary small mb-0">Kelola kewajiban iuran kas atas nama <strong><?= e($member['nama']) ?></strong> (<?= e($member['nomor_anggota']) ?> &bull; Kelas <?= e($member['kelas']) ?>)</p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (!empty($activeBills)): ?>
                            <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalBayarIuran">
                                <i class="bi bi-credit-card-fill"></i> Bayar Iuran Sekarang
                            </button>
                        <?php endif; ?>
                        <a href="<?= base_url('member/history') ?>" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm d-flex align-items-center gap-1">
                            <i class="bi bi-clock-history"></i> Riwayat Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Summary Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Tagihan -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-primary bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Nilai Tagihan</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rp <?= number_format($totalTagihan ?? 0, 2, ',', '.') ?></h4>
                    <small class="text-muted"><?= count($payments) ?> periode tagihan tercatat</small>
                </div>
            </div>
        </div>

        <!-- Total Terbayar -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-success bg-white">
                <div class="card-body">
                    <span class="text-muted small fw-semibold">Total Iuran Terverifikasi</span>
                    <h4 class="fw-bold text-success mt-1 mb-0">Rp <?= number_format($totalPaid ?? 0, 2, ',', '.') ?></h4>
                    <small class="text-muted">Telah disetujui & masuk kas umum</small>
                </div>
            </div>
        </div>

        <!-- Sisa Tunggakan -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-4 border-danger <?= ($totalTunggakan ?? 0) > 0 ? 'bg-danger-subtle bg-opacity-25' : 'bg-white' ?>">
                <div class="card-body">
                    <span class="text-danger small fw-bold">Sisa Tunggakan Aktif</span>
                    <h4 class="fw-bold text-danger mt-1 mb-0">Rp <?= number_format($totalTunggakan ?? 0, 2, ',', '.') ?></h4>
                    <small class="<?= ($totalTunggakan ?? 0) > 0 ? 'text-danger fw-medium' : 'text-success' ?>">
                        <?= ($totalTunggakan ?? 0) > 0 ? 'Harap diselesaikan melalui transfer atau tunai' : 'Alhamdulillah, tidak ada tunggakan aktif!' ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 1: DAFTAR TAGIHAN KAS SAYA -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Daftar Tagihan Iuran Kas</h5>
                        <small class="text-muted">Kewajiban pembayaran kas per periode</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">No</th>
                                    <th class="py-3">Periode Tagihan</th>
                                    <th class="py-3">Total Tagihan</th>
                                    <th class="py-3">Sudah Dibayar</th>
                                    <th class="py-3">Sisa Tunggakan</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3">Terakhir Update</th>
                                    <th class="pe-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($payments)): ?>
                                    <?php foreach ($payments as $idx => $p): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                            <td class="fw-semibold text-dark"><?= e($p['periode']) ?></td>
                                            <td class="small text-muted">Rp <?= number_format((float)($p['tagihan'] ?? 50000), 2, ',', '.') ?></td>
                                            <td class="fw-bold text-dark">Rp <?= number_format((float)$p['nominal'], 2, ',', '.') ?></td>
                                            <td>
                                                <?php if ((float)$p['sisa'] > 0): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
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
                                                        <i class="bi bi-pie-chart-fill me-1"></i> SEBAGIAN
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                                        <i class="bi bi-exclamation-circle-fill me-1"></i> BELUM BAYAR
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-secondary"><?= date('d M Y', strtotime($p['tanggal_pembayaran'])) ?></td>
                                            <td class="pe-4 text-end">
                                                <?php if ((float)$p['sisa'] > 0): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-pay-specific" 
                                                            data-payment-id="<?= $p['id'] ?>" 
                                                            data-periode="<?= e($p['periode']) ?>" 
                                                            data-sisa="<?= (float)$p['sisa'] ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalBayarIuran">
                                                        <i class="bi bi-credit-card me-1"></i> Bayar
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border px-2 py-1"><i class="bi bi-check2-all text-success me-1"></i> Lunas</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                            Belum ada tagihan kas yang tercatat untuk Anda.
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

    <!-- SECTION 2: RIWAYAT PENGAJUAN PEMBAYARAN SAYA -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Riwayat Pengajuan Pembayaran Saya</h5>
                        <small class="text-muted">Status verifikasi pembayaran via Transfer Bank, Tunai, QRIS, & E-Wallet</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <th class="ps-4 py-3">No</th>
                                    <th class="py-3">Kode Ref</th>
                                    <th class="py-3">Periode</th>
                                    <th class="py-3">Metode & Channel</th>
                                    <th class="py-3">Nominal Setor</th>
                                    <th class="py-3 text-center">Status Pembayaran</th>
                                    <th class="py-3">Tanggal Pengajuan</th>
                                    <th class="pe-4 py-3 text-end">Aksi & Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($submissions)): ?>
                                    <?php foreach ($submissions as $sIdx => $sub): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= $sIdx + 1 ?></td>
                                            <td class="fw-semibold text-primary font-monospace small"><?= e($sub['kode_transaksi']) ?></td>
                                            <td class="small text-dark fw-medium"><?= e($sub['payment_periode'] ?? '-') ?></td>
                                            <td>
                                                <div class="small fw-semibold text-dark">
                                                    <?= e(\App\Models\PaymentTransaction::getMethodLabel($sub['metode'])) ?>
                                                </div>
                                                <small class="text-muted"><?= e($sub['channel'] ?? '-') ?></small>
                                            </td>
                                            <td class="fw-bold text-dark">Rp <?= number_format((float)$sub['nominal'], 2, ',', '.') ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= \App\Models\PaymentTransaction::getStatusBadgeClass($sub['status']) ?> px-3 py-1 rounded-pill">
                                                    <?php if ($sub['status'] === 'berhasil'): ?>
                                                        <i class="bi bi-check-circle-fill me-1"></i>
                                                    <?php elseif ($sub['status'] === 'menunggu_verifikasi'): ?>
                                                        <i class="bi bi-hourglass-split me-1"></i>
                                                    <?php elseif ($sub['status'] === 'ditolak'): ?>
                                                        <i class="bi bi-x-circle-fill me-1"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-info-circle me-1"></i>
                                                    <?php endif; ?>
                                                    <?= e(\App\Models\PaymentTransaction::getStatusLabel($sub['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="small text-secondary"><?= date('d M Y, H:i', strtotime($sub['created_at'])) ?></td>
                                            <td class="pe-4 text-end">
                                                <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-3 btn-view-submission" data-id="<?= $sub['id'] ?>">
                                                    <i class="bi bi-eye me-1"></i> Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-clock-history fs-3 d-block mb-1 text-secondary"></i>
                                            Belum ada pengajuan pembayaran yang dilakukan.
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

<!-- ======================================================= -->
<!-- MODAL: AJUKAN PEMBAYARAN IURAN KAS                      -->
<!-- ======================================================= -->
<div class="modal fade" id="modalBayarIuran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4 border-0">
                <div>
                    <h5 class="modal-title fw-bold"><i class="bi bi-credit-card-fill me-2"></i> Form Pembayaran Iuran Kas</h5>
                    <p class="small text-white-50 mb-0">Pilih periode tagihan, tentukan nominal, dan pilih metode pembayaran resmi</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= base_url('member/payBill') ?>" method="POST" enctype="multipart/form-data" id="formPayBill">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::getCsrfToken() ?>">
                
                <div class="modal-body p-4">
                    <!-- 1. Pilih Periode Tagihan -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Pilih Periode Tagihan <span class="text-danger">*</span></label>
                        <select class="form-select bg-light form-select-lg" name="payment_id" id="selectPaymentBill" required>
                            <option value="">-- Pilih Periode Tagihan Kas --</option>
                            <?php foreach ($activeBills as $ab): ?>
                                <option value="<?= $ab['id'] ?>" data-sisa="<?= (float)$ab['sisa'] ?>" data-tagihan="<?= (float)$ab['tagihan'] ?>">
                                    <?= e($ab['periode']) ?> — Sisa Tunggakan: Rp <?= number_format((float)$ab['sisa'], 0, ',', '.') ?> (Tagihan: Rp <?= number_format((float)$ab['tagihan'], 0, ',', '.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Nominal Pembayaran -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">Nominal Pembayaran <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="btnFillFullSisa">
                                <i class="bi bi-check2-circle"></i> Bayar Penuh Sisa
                            </button>
                        </div>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                            <input type="number" class="form-control" name="nominal" id="inputNominalBayar" min="1000" step="500" placeholder="Contoh: 50000" required>
                        </div>
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i> Mendukung pembayaran sebagian (cicilan) atau pelunasan penuh sesuai aturan kas.
                        </div>
                    </div>

                    <!-- 3. Pilihan Metode Pembayaran -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark mb-2">Metode Pembayaran <span class="text-danger">*</span></label>
                        
                        <div class="row g-3">
                            <!-- Metode 1: Transfer Bank -->
                            <div class="col-md-6">
                                <label class="card h-100 border p-3 rounded-4 cursor-pointer payment-method-card active" for="methodTransfer">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0 fs-5" type="radio" name="metode" id="methodTransfer" value="transfer_bank" checked>
                                        <div>
                                            <div class="fw-bold text-dark"><i class="bi bi-bank text-primary me-1"></i> Transfer Bank</div>
                                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> Aktif &bull; Verifikasi Bendahara</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Metode 2: Pembayaran Tunai -->
                            <div class="col-md-6">
                                <label class="card h-100 border p-3 rounded-4 cursor-pointer payment-method-card" for="methodTunai">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0 fs-5" type="radio" name="metode" id="methodTunai" value="tunai">
                                        <div>
                                            <div class="fw-bold text-dark"><i class="bi bi-cash-stack text-success me-1"></i> Pembayaran Tunai</div>
                                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> Aktif &bull; Setor Langsung Fisik</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Metode 3: QRIS -->
                            <div class="col-md-6">
                                <label class="card h-100 border p-3 rounded-4 payment-method-card <?= $gatewayConfig['is_qris_active'] ? 'cursor-pointer' : 'opacity-75 bg-light' ?>" for="methodQris">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0 fs-5" type="radio" name="metode" id="methodQris" value="qris" <?= $gatewayConfig['is_qris_active'] ? '' : 'disabled' ?>>
                                        <div>
                                            <div class="fw-bold text-dark"><i class="bi bi-qr-code-scan text-danger me-1"></i> QRIS Resmi</div>
                                            <?php if ($gatewayConfig['is_qris_active']): ?>
                                                <small class="text-success"><i class="bi bi-check-circle-fill"></i> Aktif Resmi (Gateway)</small>
                                            <?php else: ?>
                                                <small class="text-muted"><i class="bi bi-lock-fill"></i> Belum Aktif (Perlu Kredensial)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Metode 4: E-Wallet -->
                            <div class="col-md-6">
                                <label class="card h-100 border p-3 rounded-4 payment-method-card <?= $gatewayConfig['is_ewallet_active'] ? 'cursor-pointer' : 'opacity-75 bg-light' ?>" for="methodEwallet">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0 fs-5" type="radio" name="metode" id="methodEwallet" value="ewallet" <?= $gatewayConfig['is_ewallet_active'] ? '' : 'disabled' ?>>
                                        <div>
                                            <div class="fw-bold text-dark"><i class="bi bi-phone-fill text-info me-1"></i> E-Wallet (GoPay/OVO)</div>
                                            <?php if ($gatewayConfig['is_ewallet_active']): ?>
                                                <small class="text-success"><i class="bi bi-check-circle-fill"></i> Aktif Resmi (Gateway)</small>
                                            <?php else: ?>
                                                <small class="text-muted"><i class="bi bi-lock-fill"></i> Belum Aktif (Perlu Kredensial)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- PENGATURAN SUB-METODE: TRANSFER BANK -->
                    <div id="sectionTransferBank" class="p-3 bg-light rounded-4 mb-4 border">
                        <label class="form-label fw-bold text-dark mb-2">Pilih Rekening Tujuan Transfer Kas <span class="text-danger">*</span></label>
                        
                        <?php if (!empty($bankAccounts)): ?>
                            <div class="row g-2 mb-3">
                                <?php foreach ($bankAccounts as $bIdx => $acc): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 shadow-sm p-3 rounded-3 bg-white">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="channel" id="bank_<?= $bIdx ?>" value="<?= e($acc['bank_name']) ?>" <?= $bIdx === 0 ? 'checked' : '' ?>>
                                                <label class="form-check-label w-100" for="bank_<?= $bIdx ?>">
                                                    <span class="badge bg-primary px-2 py-1 mb-1"><?= e($acc['bank_name']) ?></span>
                                                    <div class="fw-bold text-dark fs-5 font-monospace"><?= e($acc['account_number']) ?></div>
                                                    <div class="small text-muted">a.n. <?= e($acc['account_holder']) ?></div>
                                                    <?php if (!empty($acc['instructions'])): ?>
                                                        <small class="text-secondary d-block mt-1 fst-italic"><?= e($acc['instructions']) ?></small>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning small mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Belum ada nomor rekening yang dikonfigurasi oleh Bendahara. Silakan hubungi pengurus.
                            </div>
                        <?php endif; ?>

                        <!-- Upload Bukti Transfer -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark">Unggah Bukti Transfer <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="bukti_transfer" id="inputBuktiTransfer" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            <div class="form-text text-muted small mt-1">
                                Format didukung: <strong>JPG, JPEG, PNG, WEBP, PDF</strong> (Maksimal 2 MB). Pastikan nominal, tanggal, dan nomor referensi terbaca jelas.
                            </div>
                        </div>
                    </div>

                    <!-- PENGATURAN SUB-METODE: TUNAI -->
                    <div id="sectionTunai" class="p-3 bg-light rounded-4 mb-4 border d-none">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                <i class="bi bi-cash-coin fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Ketentuan Pembayaran Tunai</h6>
                                <p class="small text-secondary mb-0">
                                    Pengajuan ini mencatat janji setor uang kas secara tunai. Status akan berada pada <strong>Menunggu Verifikasi</strong> sampai Bendahara menerima uang fisik dan mengonfirmasinya pada sistem kas.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- PENGATURAN SUB-METODE: QRIS / E-WALLET INFO -->
                    <div id="sectionDigitalGateway" class="p-3 bg-light rounded-4 mb-4 border d-none">
                        <div class="alert alert-warning mb-0 rounded-3">
                            <div class="d-flex gap-2">
                                <i class="bi bi-shield-exclamation fs-4 text-warning"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Integrasi Pembayaran Digital Belum Aktif</h6>
                                    <p class="small mb-0">
                                        Untuk menjaga integritas keuangan dan keamanan kas, sistem <strong>tidak menggunakan QR palsu</strong>. Metode QRIS dan E-Wallet resmi memerlukan konfigurasi kredensial Payment Gateway (Midtrans Server Key) oleh Administrator. Silakan gunakan metode <strong>Transfer Bank</strong> atau <strong>Pembayaran Tunai</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Catatan Pembayaran (Opsional)</label>
                        <input type="text" class="form-control" name="catatan" placeholder="Contoh: Titip iuran lewat Ahmad, atau transfer dari rek BCA atas nama Orang Tua">
                    </div>
                </div>

                <div class="modal-footer bg-light p-3 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSubmitPayment">
                        <i class="bi bi-send-fill me-1"></i> Kirim Pengajuan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: DETAIL PENGAJUAN PEMBAYARAN                      -->
<!-- ======================================================= -->
<div class="modal fade" id="modalDetailSubmission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-info-circle me-1"></i> Rincian Pengajuan Pembayaran</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailSubmissionBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="small text-muted mt-2">Memuat data pengajuan...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectBill = document.getElementById('selectPaymentBill');
    const inputNominal = document.getElementById('inputNominalBayar');
    const btnFillFullSisa = document.getElementById('btnFillFullSisa');
    const methodRadios = document.querySelectorAll('input[name="metode"]');
    const sectionTransfer = document.getElementById('sectionTransferBank');
    const sectionTunai = document.getElementById('sectionTunai');
    const sectionDigital = document.getElementById('sectionDigitalGateway');
    const btnSubmit = document.getElementById('btnSubmitPayment');
    const inputBukti = document.getElementById('inputBuktiTransfer');

    // Handle payment bill selection
    if (selectBill) {
        selectBill.addEventListener('change', function () {
            const selectedOpt = selectBill.options[selectBill.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.sisa) {
                const sisaVal = parseFloat(selectedOpt.dataset.sisa);
                inputNominal.value = sisaVal;
                inputNominal.max = sisaVal;
            }
        });
    }

    if (btnFillFullSisa) {
        btnFillFullSisa.addEventListener('click', function () {
            const selectedOpt = selectBill ? selectBill.options[selectBill.selectedIndex] : null;
            if (selectedOpt && selectedOpt.dataset.sisa) {
                inputNominal.value = parseFloat(selectedOpt.dataset.sisa);
            }
        });
    }

    // Handle click from specific bill "Bayar" button in table
    document.querySelectorAll('.btn-pay-specific').forEach(btn => {
        btn.addEventListener('click', function () {
            const pid = this.dataset.paymentId;
            const sisa = this.dataset.sisa;
            if (selectBill) {
                selectBill.value = pid;
                inputNominal.value = parseFloat(sisa);
                inputNominal.max = parseFloat(sisa);
            }
        });
    });

    // Handle payment method toggling
    methodRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('active'));
            const parentLabel = this.closest('.payment-method-card');
            if (parentLabel) parentLabel.classList.add('active');

            if (this.value === 'transfer_bank') {
                sectionTransfer.classList.remove('d-none');
                sectionTunai.classList.add('d-none');
                sectionDigital.classList.add('d-none');
                btnSubmit.disabled = false;
                if (inputBukti) inputBukti.required = true;
            } else if (this.value === 'tunai') {
                sectionTransfer.classList.add('d-none');
                sectionTunai.classList.remove('d-none');
                sectionDigital.classList.add('d-none');
                btnSubmit.disabled = false;
                if (inputBukti) inputBukti.required = false;
            } else if (this.value === 'qris' || this.value === 'ewallet') {
                sectionTransfer.classList.add('d-none');
                sectionTunai.classList.add('d-none');
                sectionDigital.classList.remove('d-none');
                btnSubmit.disabled = true; // Digital gateway not yet active
                if (inputBukti) inputBukti.required = false;
            }
        });
    });

    // Handle View Submission Detail Modal
    document.querySelectorAll('.btn-view-submission').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const modalEl = new bootstrap.Modal(document.getElementById('modalDetailSubmission'));
            modalEl.show();

            const bodyEl = document.getElementById('detailSubmissionBody');
            bodyEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="small text-muted mt-2">Memuat rincian...</p></div>';

            try {
                const res = await fetch(`<?= base_url('member/submissionDetail') ?>?id=${id}`);
                const json = await res.json();

                if (json.status === 'success') {
                    const data = json.data;
                    let proofHtml = '';
                    if (data.bukti_transfer) {
                        const fileExt = data.bukti_transfer.split('.').pop().toLowerCase();
                        if (fileExt === 'pdf') {
                            proofHtml = `
                                <div class="mt-3">
                                    <label class="small text-muted fw-bold d-block mb-1">Bukti Transfer (Dokumen PDF)</label>
                                    <a href="<?= base_url('assets/uploads/payments/') ?>${data.bukti_transfer}" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Buka Berkas PDF
                                    </a>
                                </div>
                            `;
                        } else {
                            proofHtml = `
                                <div class="mt-3">
                                    <label class="small text-muted fw-bold d-block mb-1">Bukti Transfer</label>
                                    <div class="border rounded-3 overflow-hidden text-center bg-light p-1">
                                        <img src="<?= base_url('assets/uploads/payments/') ?>${data.bukti_transfer}" class="img-fluid rounded" style="max-height: 250px;" alt="Bukti Transfer">
                                    </div>
                                    <a href="<?= base_url('assets/uploads/payments/') ?>${data.bukti_transfer}" target="_blank" class="btn btn-link btn-sm text-decoration-none mt-1">
                                        <i class="bi bi-box-arrow-up-right"></i> Buka Gambar Ukuran Penuh
                                    </a>
                                </div>
                            `;
                        }
                    }

                    let rejectionHtml = '';
                    if (data.status === 'ditolak') {
                        rejectionHtml = `
                            <div class="alert alert-danger rounded-3 mt-3 mb-0">
                                <h6 class="fw-bold mb-1"><i class="bi bi-x-octagon-fill me-1"></i> Alasan Penolakan dari Bendahara:</h6>
                                <p class="small mb-0">${data.alasan_penolakan || 'Bukti pembayaran tidak sesuai atau tidak valid.'}</p>
                            </div>
                        `;
                    }

                    let statusBadge = '';
                    if (data.status === 'berhasil') {
                        statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Berhasil / Terverifikasi</span>';
                    } else if (data.status === 'menunggu_verifikasi') {
                        statusBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill"><i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi Bendahara</span>';
                    } else if (data.status === 'ditolak') {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> Ditolak</span>';
                    } else {
                        statusBadge = `<span class="badge bg-secondary px-3 py-1 rounded-pill">${data.status}</span>`;
                    }

                    bodyEl.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="font-monospace text-primary fw-bold">${data.kode_transaksi}</span>
                            ${statusBadge}
                        </div>
                        <div class="list-group list-group-flush small">
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Periode Tagihan</span>
                                <span class="fw-semibold text-dark">${data.payment_periode || '-'}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Metode Pembayaran</span>
                                <span class="fw-semibold text-dark">${data.metode.toUpperCase()} ${data.channel ? '(' + data.channel + ')' : ''}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Nominal Disetor</span>
                                <span class="fw-bold fs-6 text-success">Rp ${new Intl.NumberFormat('id-ID').format(data.nominal)}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Tanggal Pengajuan</span>
                                <span class="text-dark">${data.created_at}</span>
                            </div>
                            ${data.verifier_name ? `
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Verifikator</span>
                                <span class="text-dark fw-medium"><i class="bi bi-shield-check text-success"></i> ${data.verifier_name}</span>
                            </div>` : ''}
                            ${data.trx_kas_kode ? `
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">No. Buku Kas</span>
                                <span class="font-monospace text-success fw-bold">${data.trx_kas_kode}</span>
                            </div>` : ''}
                            ${data.catatan ? `
                            <div class="list-group-item px-0">
                                <span class="text-muted d-block mb-1">Catatan:</span>
                                <span class="text-secondary fst-italic">${data.catatan}</span>
                            </div>` : ''}
                        </div>
                        ${rejectionHtml}
                        ${proofHtml}
                    `;
                } else {
                    bodyEl.innerHTML = `<div class="alert alert-danger mb-0">${json.message || 'Gagal memuat rincian pengajuan.'}</div>`;
                }
            } catch (err) {
                bodyEl.innerHTML = '<div class="alert alert-danger mb-0">Terjadi kesalahan koneksi saat memuat rincian.</div>';
            }
        });
    });
});
</script>
