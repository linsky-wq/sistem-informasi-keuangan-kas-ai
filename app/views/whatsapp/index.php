<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-whatsapp me-1"></i> WhatsApp Gateway
                            </span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">
                                <i class="bi bi-diagram-2 me-1"></i> External Supporting Service
                            </span>
                            <?php if ($statusInfo['is_enabled']): ?>
                                <span class="badge bg-success text-white rounded-pill px-2 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1">
                                    <i class="bi bi-dash-circle-fill me-1"></i> Nonaktif
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Integrasi Layanan WhatsApp (WA)</h3>
                        <p class="text-secondary small mb-0">Otomatisasi dan pengiriman notifikasi pengingat pembayaran, konfirmasi setoran kas, dan informasi tagihan kepada anggota.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= base_url('whatsapp/logs') ?>" class="btn btn-outline-success rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-clock-history"></i> Riwayat Pengiriman
                        </a>
                        <?php if ($canManage): ?>
                            <a href="<?= base_url('whatsapp/settings') ?>" class="btn btn-primary rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm d-flex align-items-center gap-1">
                                <i class="bi bi-gear-fill"></i> Kelola Pengaturan WA
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Status & Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Status Layanan -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Status Gateway</span>
                    <i class="bi bi-whatsapp text-success fs-5"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">
                    <?= $statusInfo['is_enabled'] ? (!empty($statusInfo['device_info']['success']) ? 'Online & Terhubung' : 'Online & Aktif') : 'Dinonaktifkan' ?>
                </h5>
                <small class="text-muted text-truncate d-block" style="font-size: 0.75rem;" title="<?= e($statusInfo['provider_name']) ?>">
                    <?= e($statusInfo['provider_name']) ?>
                    <?= !empty($statusInfo['device_info']['quota']) ? ' &bull; Kuota: ' . e((string)$statusInfo['device_info']['quota']) : '' ?>
                </small>
            </div>
        </div>

        <!-- Total Terkirim -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Notifikasi</span>
                    <i class="bi bi-send-check text-primary fs-5"></i>
                </div>
                <h5 class="fw-bold text-primary mb-1"><?= number_format($stats['total'], 0, ',', '.') ?></h5>
                <small class="text-muted" style="font-size: 0.75rem;">
                    <span class="text-success fw-bold"><?= $stats['success_count'] ?> Berhasil</span> &bull; 
                    <span class="text-danger fw-bold"><?= $stats['failed_count'] ?> Gagal</span>
                </small>
            </div>
        </div>

        <!-- Pengingat Pembayaran -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pengingat Iuran</span>
                    <i class="bi bi-bell-fill text-warning fs-5"></i>
                </div>
                <h5 class="fw-bold text-warning mb-1"><?= number_format($stats['reminder_count'], 0, ',', '.') ?></h5>
                <small class="text-muted" style="font-size: 0.75rem;">Notifikasi tunggakan kas dikirim</small>
            </div>
        </div>

        <!-- Verifikasi & Konfirmasi -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Bukti & Verifikasi</span>
                    <i class="bi bi-shield-check text-info fs-5"></i>
                </div>
                <h5 class="fw-bold text-info mb-1"><?= number_format($stats['confirmation_count'] + $stats['verification_count'], 0, ',', '.') ?></h5>
                <small class="text-muted" style="font-size: 0.75rem;">Konfirmasi & status sah terkirim</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Left Column: Quick Dispatch Tools -->
        <div class="col-lg-6">
            <!-- Form 1: Kirim Informasi Tagihan Kas Baru -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-chat-left-dots-fill fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Kirim Informasi Tagihan Kas ke Anggota</h6>
                            <small class="text-muted">Kirim rincian kewajiban iuran kas langsung ke nomor WhatsApp anggota</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('whatsapp/sendBilling') ?>" method="POST">
                        <?= \App\Core\Security::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Pilih Anggota Penerima <span class="text-danger">*</span></label>
                            <select class="form-select" name="member_id" required>
                                <option value="">-- Pilih Anggota --</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= e($m['nama']) ?> (<?= e($m['nomor_anggota']) ?> &bull; <?= e($m['kelas']) ?>) &mdash; WA: <?= !empty($m['no_hp']) ? e($m['no_hp']) : 'Belum Ada No HP' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold text-secondary small">Periode Tagihan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="periode" placeholder="Contoh: September 2026 (Minggu 2)" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold text-secondary small">Nominal Iuran (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary">Rp</span>
                                    <input type="number" step="1000" min="1000" class="form-control" name="tagihan" value="50000" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-whatsapp"></i> Kirim Notifikasi Tagihan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Form 2: Uji Coba Koneksi Gateway (Super User Only) -->
            <?php if ($canManage): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-broadcast fs-6"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Uji Coba Koneksi Gateway WhatsApp</h6>
                                <small class="text-muted">Kirim pesan uji coba ke nomor tujuan untuk memvalidasi sambungan API</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?= base_url('whatsapp/testSend') ?>" method="POST">
                            <?= \App\Core\Security::csrfField() ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Nomor WhatsApp Tujuan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary"><i class="bi bi-telephone-fill"></i></span>
                                    <input type="text" class="form-control" name="test_phone" placeholder="Contoh: 081234567890 atau 6281234567890" required>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">Mendukung format awalan 08 maupun 62.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Pesan Kustom (Opsional)</label>
                                <textarea class="form-control" name="test_message" rows="2" placeholder="Kosongkan untuk menggunakan pesan template uji koneksi standar..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-outline-success rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2">
                                    <i class="bi bi-send-fill"></i> Jalankan Tes Pengiriman
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: System Spec & Recent Logs -->
        <div class="col-lg-6">
            <!-- Gateway Specification Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h6 class="fw-bold text-dark mb-0">Spesifikasi Gateway & Kebijakan</h6>
                    <small class="text-muted">Konfigurasi arsitektur pendukung WhatsApp</small>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2" style="width: 40%;">Aktor Sistem</td>
                                    <td class="text-dark py-2">
                                        <span class="badge bg-secondary-subtle text-secondary border">External Supporting Service</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Provider Gateway</td>
                                    <td class="text-dark fw-bold py-2"><?= e($statusInfo['provider_name']) ?></td>
                                </tr>
                                <?php if (!empty($statusInfo['device_info']) && !empty($statusInfo['device_info']['success'])): ?>
                                    <?php $dev = $statusInfo['device_info']; ?>
                                    <tr>
                                        <td class="text-secondary fw-semibold py-2">Device Terhubung</td>
                                        <td class="text-dark py-2">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1">
                                                <i class="bi bi-phone-fill me-1"></i><?= e($dev['name'] ?? 'WhatsApp') ?>
                                            </span>
                                            <span class="badge bg-light text-secondary border">Sisa Kuota: <?= e((string)$dev['quota']) ?></span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Nomor Pengirim / Sender</td>
                                    <td class="text-dark font-monospace py-2"><?= e($statusInfo['sender_number']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Endpoint URL</td>
                                    <td class="text-dark font-monospace small py-2"><?= e($statusInfo['api_url']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Keamanan Kredensial</td>
                                    <td class="text-dark py-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-shield-lock-fill me-1"></i> Masked <?= !empty($statusInfo['is_env_token']) ? '(.env)' : '(Database)' ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Delivery Logs -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Riwayat Pengiriman Terkini</h6>
                        <small class="text-muted">6 aktivitas notifikasi WhatsApp terbaru</small>
                    </div>
                    <a href="<?= base_url('whatsapp/logs') ?>" class="btn btn-sm btn-link text-decoration-none fw-semibold text-success p-0">
                        Lihat Semua <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (!empty($recentLogs)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="list-group-item px-0 py-3 border-bottom d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3 overflow-hidden me-2">
                                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0 <?= $log['status'] === 'sent' || $log['status'] === 'delivered' ? 'bg-success-subtle text-success' : ($log['status'] === 'failed' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') ?>" style="width: 36px; height: 36px;">
                                            <i class="bi <?= $log['status'] === 'sent' || $log['status'] === 'delivered' ? 'bi-check2' : ($log['status'] === 'failed' ? 'bi-x-lg' : 'bi-hourglass') ?>"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-semibold text-dark text-truncate small">
                                                <?= e($log['recipient_name'] ?? $log['recipient_phone']) ?>
                                            </div>
                                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                                <?= e($log['recipient_phone']) ?> &bull; 
                                                <?= ucwords(str_replace('_', ' ', $log['notification_type'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <?php if ($log['status'] === 'sent' || $log['status'] === 'delivered'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">Berhasil</span>
                                        <?php elseif ($log['status'] === 'failed'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">Gagal</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small">Pending</span>
                                        <?php endif; ?>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                            <?= date('H:i, d M', strtotime($log['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                            Belum ada riwayat pengiriman notifikasi WhatsApp.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
