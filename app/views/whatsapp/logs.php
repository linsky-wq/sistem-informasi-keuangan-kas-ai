<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-clock-history me-1"></i> Audit Pengiriman
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">
                                Total <?= number_format($total, 0, ',', '.') ?> Notifikasi
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Riwayat & Status Pengiriman WhatsApp</h3>
                        <p class="text-secondary small mb-0">Catatan lengkap status transmisi pesan, nomor tujuan anggota, waktu pengiriman, dan respons gateway.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('whatsapp') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm fw-semibold border shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-arrow-left"></i> Dashboard WA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-4">
            <form action="<?= base_url('whatsapp/logs') ?>" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Nama, No HP, atau pesan...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Status Pengiriman</label>
                        <select class="form-select" name="status">
                            <option value="">-- Semua Status --</option>
                            <option value="sent" <?= ($filters['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Berhasil (Sent)</option>
                            <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Gagal (Failed)</option>
                            <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Menunggu (Pending)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Tipe Notifikasi</label>
                        <select class="form-select" name="notification_type">
                            <option value="">-- Semua Tipe --</option>
                            <option value="pengingat_pembayaran" <?= ($filters['notification_type'] ?? '') === 'pengingat_pembayaran' ? 'selected' : '' ?>>Pengingat Pembayaran</option>
                            <option value="konfirmasi_pembayaran" <?= ($filters['notification_type'] ?? '') === 'konfirmasi_pembayaran' ? 'selected' : '' ?>>Konfirmasi Pembayaran</option>
                            <option value="status_verifikasi" <?= ($filters['notification_type'] ?? '') === 'status_verifikasi' ? 'selected' : '' ?>>Status Verifikasi</option>
                            <option value="informasi_tagihan" <?= ($filters['notification_type'] ?? '') === 'informasi_tagihan' ? 'selected' : '' ?>>Informasi Tagihan</option>
                            <option value="test" <?= ($filters['notification_type'] ?? '') === 'test' ? 'selected' : '' ?>>Uji Coba Koneksi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Mulai Tanggal</label>
                        <input type="date" class="form-control" name="date_start" value="<?= e($filters['date_start'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-3 py-2 w-100 fw-semibold">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                        <a href="<?= base_url('whatsapp/logs') ?>" class="btn btn-light rounded-pill px-3 py-2 border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4 py-3" style="width: 60px;">#</th>
                        <th class="py-3">Waktu</th>
                        <th class="py-3">Penerima</th>
                        <th class="py-3">Tipe Pesan</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Dipicu Oleh</th>
                        <th class="pe-4 py-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $idx => $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= (($page - 1) * $perPage) + $idx + 1 ?></td>
                                <td>
                                    <div class="fw-semibold text-dark small"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></div>
                                    <small class="text-muted font-monospace" style="font-size: 0.7rem;"><?= date('s', strtotime($row['created_at'])) ?> detik</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($row['recipient_name'] ?? '-') ?></div>
                                    <span class="font-monospace text-secondary small">
                                        <i class="bi bi-whatsapp text-success me-1"></i><?= e($row['recipient_phone']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $typeBadge = match ($row['notification_type']) {
                                        'pengingat_pembayaran'  => 'bg-warning-subtle text-warning border-warning-subtle',
                                        'konfirmasi_pembayaran' => 'bg-info-subtle text-info border-info-subtle',
                                        'status_verifikasi'     => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'informasi_tagihan'     => 'bg-success-subtle text-success border-success-subtle',
                                        'test'                  => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                        default                 => 'bg-light text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $typeBadge ?> border px-2 py-1 rounded-pill small">
                                        <?= ucwords(str_replace('_', ' ', $row['notification_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'sent' || $row['status'] === 'delivered'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-check2-circle me-1"></i> Berhasil
                                        </span>
                                    <?php elseif ($row['status'] === 'failed'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Gagal
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-hourglass-split me-1"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['sender_username'])): ?>
                                        <span class="small text-dark fw-medium">
                                            <i class="bi bi-person-fill text-muted me-1"></i><?= e($row['sender_username']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">
                                            <i class="bi bi-cpu me-1"></i> Otomatis Sistem
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 btn-view-message" 
                                            data-phone="<?= e($row['recipient_phone']) ?>"
                                            data-name="<?= e($row['recipient_name'] ?? '-') ?>"
                                            data-type="<?= ucwords(str_replace('_', ' ', $row['notification_type'])) ?>"
                                            data-time="<?= date('d M Y, H:i:s', strtotime($row['created_at'])) ?>"
                                            data-status="<?= e($row['status']) ?>"
                                            data-message="<?= e($row['message']) ?>"
                                            data-payload="<?= e($row['response_payload'] ?? '') ?>">
                                        <i class="bi bi-chat-text me-1"></i> Lihat Pesan
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox-fill fs-2 d-block mb-2 text-secondary"></i>
                                Tidak ada catatan riwayat pengiriman notifikasi WhatsApp yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan halaman <strong><?= $page ?></strong> dari total <strong><?= $totalPages ?></strong> (Total <?= number_format($total, 0, ',', '.') ?> log)
                </small>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $queryParams = $filters;
                        ?>
                        <?php if ($page > 1): ?>
                            <?php $queryParams['page'] = $page - 1; ?>
                            <li class="page-item"><a class="page-link" href="<?= base_url('whatsapp/logs?' . http_build_query($queryParams)) ?>">&laquo; Prev</a></li>
                        <?php endif; ?>

                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                            <?php $queryParams['page'] = $p; ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('whatsapp/logs?' . http_build_query($queryParams)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <?php $queryParams['page'] = $page + 1; ?>
                            <li class="page-item"><a class="page-link" href="<?= base_url('whatsapp/logs?' . http_build_query($queryParams)) ?>">Next &raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Preview Pesan WhatsApp -->
<div class="modal fade" id="modalMessagePreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white p-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp fs-4"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Pratinjau Pesan WhatsApp</h6>
                        <small class="text-white-50" id="modalMsgMeta">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- WhatsApp Chat Bubble Simulation -->
                <div class="card border-0 shadow-xs rounded-3 p-3 bg-white mb-3" style="border-left: 4px solid #25D366 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                        <span class="fw-bold text-dark small" id="modalMsgRecipient">-</span>
                        <span class="badge bg-success-subtle text-success small rounded-pill" id="modalMsgStatus">-</span>
                    </div>
                    <div class="font-monospace small text-dark" id="modalMsgBody" style="white-space: pre-wrap; font-family: inherit; line-height: 1.6;">
                    </div>
                </div>

                <!-- Provider Payload Details -->
                <div class="accordion" id="accordionPayload">
                    <div class="accordion-item border-0 rounded-3 shadow-xs">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2 px-3 small text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePayload">
                                <i class="bi bi-code-square me-2"></i> Respons Gateway API (Payload)
                            </button>
                        </h2>
                        <div id="collapsePayload" class="accordion-collapse collapse">
                            <div class="accordion-body p-3 bg-dark text-white rounded-bottom-3 font-monospace small" id="modalMsgPayload" style="white-space: pre-wrap; word-break: break-all; font-size: 0.75rem;">
                                -
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('modalMessagePreview');
    const modal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.btn-view-message').forEach(btn => {
        btn.addEventListener('click', function () {
            const phone = this.dataset.phone;
            const name = this.dataset.name;
            const type = this.dataset.type;
            const time = this.dataset.time;
            const status = this.dataset.status;
            const msg = this.dataset.message;
            const payload = this.dataset.payload;

            document.getElementById('modalMsgMeta').textContent = `${type} • ${time}`;
            document.getElementById('modalMsgRecipient').textContent = `${name} (${phone})`;
            document.getElementById('modalMsgStatus').textContent = status.toUpperCase();
            document.getElementById('modalMsgBody').textContent = msg;
            document.getElementById('modalMsgPayload').textContent = payload && payload !== '' ? payload : '(Tidak ada data payload respons)';

            modal.show();
        });
    });
});
</script>
