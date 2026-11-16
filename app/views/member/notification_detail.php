<div class="container py-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Navigation back -->
            <div class="mb-3">
                <a href="<?= base_url('member/notifications') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm border shadow-xs">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Notifikasi
                </a>
            </div>

            <!-- Detail Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-bell-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="badge bg-white text-primary px-3 py-1 rounded-pill mb-1 fw-semibold small">
                                <?= ucwords(str_replace('_', ' ', $notification['notification_type'])) ?>
                            </span>
                            <h4 class="fw-bold mb-0"><?= e($notification['title']) ?></h4>
                            <small class="text-white-50">Dibuat pada <?= date('d M Y, H:i:s', strtotime($notification['created_at'])) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-light">
                    <!-- Grid Meta Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="bg-white p-3 rounded-3 shadow-xs border">
                                <small class="text-muted d-block">Penerima Pesan</small>
                                <strong class="text-dark d-block text-truncate"><?= e($notification['recipient_name'] ?? $member['nama']) ?></strong>
                                <small class="text-success font-monospace"><?= e($notification['recipient_phone']) ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white p-3 rounded-3 shadow-xs border">
                                <small class="text-muted d-block">Status Pengiriman WA</small>
                                <span class="badge bg-success-subtle text-success border px-2 py-1 rounded-pill mt-1">
                                    <?= strtoupper($notification['status']) ?>
                                </span>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                    <?= !empty($notification['sent_at']) ? 'Terkirim: ' . date('d M Y, H:i:s', strtotime($notification['sent_at'])) : 'Belum tercatat' ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white p-3 rounded-3 shadow-xs border">
                                <small class="text-muted d-block">Status Baca</small>
                                <span class="badge bg-light text-muted border px-2 py-1 rounded-pill mt-1">
                                    <i class="bi bi-check2-all text-primary me-1"></i> Sudah Dibaca
                                </span>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                    <?= !empty($notification['read_at']) ? date('d M Y, H:i', strtotime($notification['read_at'])) : 'Baru saja' ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Timestamps & Provider Info -->
                    <div class="card border-0 shadow-xs rounded-3 p-3 bg-white mb-4">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <span class="text-muted">Provider Message ID:</span>
                                <span class="font-monospace fw-semibold text-dark ms-1">
                                    <?= e($notification['provider_message_id'] ?? '-') ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted">Waktu Delivered:</span>
                                <span class="fw-semibold text-dark ms-1">
                                    <?= !empty($notification['delivered_at']) ? date('d M Y, H:i:s', strtotime($notification['delivered_at'])) : 'Belum dilaporkan provider' ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($notification['error_message'])): ?>
                            <div class="alert alert-danger py-2 px-3 small mt-2 mb-0">
                                <strong>Error Pengiriman:</strong> <?= e($notification['error_message']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Complete WhatsApp Message Body -->
                    <label class="form-label fw-semibold text-secondary small mb-2">
                        <i class="bi bi-chat-left-text-fill me-1 text-success"></i> Isi Pesan Lengkap:
                    </label>
                    <div class="card border-0 shadow-xs rounded-3 p-4 bg-white" style="border-left: 4px solid #25D366 !important;">
                        <div class="font-monospace small text-dark" style="white-space: pre-wrap; font-family: inherit; line-height: 1.7;">
                            <?= e($notification['message']) ?>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Sistem Notifikasi Resmi <?= e(APP_NAME) ?></small>
                    <a href="<?= base_url('member/notifications') ?>" class="btn btn-primary rounded-pill px-4 btn-sm">
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
