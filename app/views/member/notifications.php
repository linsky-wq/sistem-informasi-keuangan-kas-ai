<?php
$csrfToken = \App\Core\Security::generateCsrfToken();
?>
<div class="container py-4 flex-grow-1">
    <!-- Header Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-bell-fill me-1"></i> Notifikasi Anggota
                            </span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1" id="badgeUnreadTotal">
                                    <?= $unreadCount ?> Belum Dibaca
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" id="badgeUnreadTotal">
                                    Semua Sudah Dibaca
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Pesan & Notifikasi Saya</h3>
                        <p class="text-secondary small mb-0">Daftar notifikasi resmi sistem dan informasi WhatsApp yang dikirimkan khusus untuk <strong><?= e($member['nama']) ?></strong>.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($unreadCount > 0): ?>
                            <form action="<?= base_url('member/markAllNotificationsRead') ?>" method="POST" id="formMarkAllRead">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <button type="submit" class="btn btn-outline-primary rounded-pill px-3 py-2 btn-sm fw-semibold shadow-xs">
                                    <i class="bi bi-check2-all me-1"></i> Tandai Semua Sudah Dibaca
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="<?= base_url('member/dashboard') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm border shadow-xs">
                            <i class="bi bi-arrow-left me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="<?= base_url('member/notifications') ?>" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Cari Pesan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Kata kunci pesan...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Status Dibaca</label>
                        <select class="form-select" name="read_status">
                            <option value="">-- Semua Status --</option>
                            <option value="unread" <?= ($filters['read_status'] ?? '') === 'unread' ? 'selected' : '' ?>>Belum Dibaca</option>
                            <option value="read" <?= ($filters['read_status'] ?? '') === 'read' ? 'selected' : '' ?>>Sudah Dibaca</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Jenis Notifikasi</label>
                        <select class="form-select" name="notification_type">
                            <option value="">-- Semua Jenis --</option>
                            <option value="pengingat_pembayaran" <?= ($filters['notification_type'] ?? '') === 'pengingat_pembayaran' ? 'selected' : '' ?>>Pengingat Pembayaran</option>
                            <option value="konfirmasi_pembayaran" <?= ($filters['notification_type'] ?? '') === 'konfirmasi_pembayaran' ? 'selected' : '' ?>>Konfirmasi Penerimaan</option>
                            <option value="status_verifikasi" <?= ($filters['notification_type'] ?? '') === 'status_verifikasi' ? 'selected' : '' ?>>Status Verifikasi</option>
                            <option value="informasi_tagihan" <?= ($filters['notification_type'] ?? '') === 'informasi_tagihan' ? 'selected' : '' ?>>Informasi Tagihan Baru</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Status WhatsApp</label>
                        <select class="form-select" name="status">
                            <option value="">-- Semua --</option>
                            <option value="sent" <?= ($filters['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                            <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-3 py-2 w-100 fw-semibold">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                        <a href="<?= base_url('member/notifications') ?>" class="btn btn-light rounded-pill px-3 py-2 border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        <th class="ps-4 py-3" style="width: 50px;">#</th>
                        <th class="py-3" style="min-width: 140px;">Waktu</th>
                        <th class="py-3" style="min-width: 180px;">Jenis & Judul</th>
                        <th class="py-3" style="min-width: 250px;">Ringkasan Pesan</th>
                        <th class="py-3 text-center">Status Baca</th>
                        <th class="py-3 text-center">Status WA</th>
                        <th class="pe-4 py-3 text-end" style="min-width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $idx => $row): ?>
                            <?php 
                                $isUnread = empty($row['is_read']);
                                $typeBadge = match ($row['notification_type']) {
                                    'pengingat_pembayaran'  => 'bg-warning-subtle text-warning border-warning-subtle',
                                    'konfirmasi_pembayaran' => 'bg-info-subtle text-info border-info-subtle',
                                    'status_verifikasi'     => 'bg-primary-subtle text-primary border-primary-subtle',
                                    'informasi_tagihan'     => 'bg-success-subtle text-success border-success-subtle',
                                    default                 => 'bg-light text-dark border',
                                };
                                $waBadge = match ($row['status']) {
                                    'sent'      => 'bg-success-subtle text-success border-success-subtle',
                                    'delivered' => 'bg-primary-subtle text-primary border-primary-subtle',
                                    'failed'    => 'bg-danger-subtle text-danger border-danger-subtle',
                                    default     => 'bg-warning-subtle text-warning border-warning-subtle',
                                };
                            ?>
                            <tr id="notif-row-<?= (int)$row['id'] ?>" class="<?= $isUnread ? 'table-warning bg-opacity-25' : '' ?>">
                                <td class="ps-4 text-muted small">
                                    <?php if ($isUnread): ?>
                                        <i class="bi bi-circle-fill text-danger" style="font-size: 0.6rem;" title="Belum Dibaca"></i>
                                    <?php else: ?>
                                        <i class="bi bi-check2 text-muted" title="Sudah Dibaca"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">
                                        <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <?= !empty($row['sent_at']) ? 'Kirim: ' . date('H:i:s', strtotime($row['sent_at'])) : '-' ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small mb-1"><?= e($row['title']) ?></div>
                                    <span class="badge <?= $typeBadge ?> border px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                        <?= ucwords(str_replace('_', ' ', $row['notification_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small text-truncate" style="max-width: 320px;" title="<?= e($row['message']) ?>">
                                        <?= e(mb_strimwidth(strip_tags($row['message']), 0, 95, '...')) ?>
                                    </div>
                                    <?php if (!empty($row['provider_message_id'])): ?>
                                        <small class="text-secondary font-monospace d-block" style="font-size: 0.68rem;">
                                            ID: <?= e($row['provider_message_id']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" id="read-badge-container-<?= (int)$row['id'] ?>">
                                    <?php if ($isUnread): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small">
                                            <i class="bi bi-envelope-fill me-1"></i> Belum Dibaca
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small">
                                            <i class="bi bi-envelope-open me-1"></i> Sudah Dibaca
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $waBadge ?> border px-2 py-1 rounded-pill small text-uppercase">
                                        <?= e($row['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm rounded-pill px-3 btn-detail-notif shadow-xs"
                                                data-id="<?= (int)$row['id'] ?>"
                                                data-title="<?= e($row['title']) ?>"
                                                data-type="<?= ucwords(str_replace('_', ' ', $row['notification_type'])) ?>"
                                                data-time="<?= date('d M Y, H:i:s', strtotime($row['created_at'])) ?>"
                                                data-recipient-name="<?= e($row['recipient_name'] ?? $member['nama']) ?>"
                                                data-recipient-phone="<?= e($row['recipient_phone']) ?>"
                                                data-message="<?= e($row['message']) ?>"
                                                data-status="<?= e($row['status']) ?>"
                                                data-provider-id="<?= e($row['provider_message_id'] ?? '-') ?>"
                                                data-sent-at="<?= !empty($row['sent_at']) ? date('d M Y, H:i:s', strtotime($row['sent_at'])) : '-' ?>"
                                                data-delivered-at="<?= !empty($row['delivered_at']) ? date('d M Y, H:i:s', strtotime($row['delivered_at'])) : '-' ?>"
                                                data-error="<?= e($row['error_message'] ?? '') ?>"
                                                data-read="<?= $isUnread ? '0' : '1' ?>"
                                                data-read-at="<?= !empty($row['read_at']) ? date('d M Y, H:i', strtotime($row['read_at'])) : '-' ?>">
                                            <i class="bi bi-eye me-1"></i> Lihat Detail
                                        </button>

                                        <?php if ($isUnread): ?>
                                            <form action="<?= base_url('member/markNotificationRead/' . (int)$row['id']) ?>" method="POST" class="d-inline form-mark-read" data-id="<?= (int)$row['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Tandai Sudah Dibaca">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-bell-slash fs-2 d-block mb-2 text-secondary"></i>
                                Tidak ada notifikasi yang sesuai dengan kriteria filter.
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
                    Menampilkan halaman <strong><?= $page ?></strong> dari total <strong><?= $totalPages ?></strong> (Total <?= number_format($total, 0, ',', '.') ?> pesan)
                </small>
                <nav aria-label="Navigasi Notifikasi">
                    <ul class="pagination pagination-sm mb-0">
                        <?php $queryParams = $filters; ?>
                        <?php if ($page > 1): ?>
                            <?php $queryParams['page'] = $page - 1; ?>
                            <li class="page-item"><a class="page-link" href="<?= base_url('member/notifications?' . http_build_query($queryParams)) ?>">&laquo; Prev</a></li>
                        <?php endif; ?>

                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                            <?php $queryParams['page'] = $p; ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('member/notifications?' . http_build_query($queryParams)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <?php $queryParams['page'] = $page + 1; ?>
                            <li class="page-item"><a class="page-link" href="<?= base_url('member/notifications?' . http_build_query($queryParams)) ?>">Next &raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Detail Notifikasi & Pesan WhatsApp -->
<div class="modal fade" id="modalNotificationDetail" tabindex="-1" aria-labelledby="modalNotificationDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-bell-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalNotifTitle">Detail Notifikasi</h6>
                        <small class="text-white-50" id="modalNotifMeta">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <!-- Meta Info Cards Grid -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-md-4">
                        <div class="bg-white p-3 rounded-3 shadow-xs border">
                            <small class="text-muted d-block">Penerima Pesan</small>
                            <strong class="text-dark d-block text-truncate" id="modalRecipientName">-</strong>
                            <small class="text-success font-monospace" id="modalRecipientPhone">-</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="bg-white p-3 rounded-3 shadow-xs border">
                            <small class="text-muted d-block">Status Pengiriman WA</small>
                            <span class="badge bg-success-subtle text-success border px-2 py-1 rounded-pill mt-1" id="modalWaStatus">-</span>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" id="modalSentAt">-</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="bg-white p-3 rounded-3 shadow-xs border">
                            <small class="text-muted d-block">Status Baca</small>
                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill mt-1" id="modalReadStatus">-</span>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" id="modalReadAt">-</small>
                        </div>
                    </div>
                </div>

                <!-- Timestamps & Delivery Details -->
                <div class="card border-0 shadow-xs rounded-3 p-3 bg-white mb-3">
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <span class="text-muted">Provider Message ID:</span>
                            <span class="font-monospace fw-semibold text-dark ms-1" id="modalProviderId">-</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Waktu Delivered:</span>
                            <span class="fw-semibold text-dark ms-1" id="modalDeliveredAt">-</span>
                        </div>
                    </div>
                    <div id="modalErrorContainer" class="d-none mt-2 pt-2 border-top">
                        <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
                            <div>
                                <strong>Error Pengiriman:</strong>
                                <span id="modalErrorText">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Chat Bubble Simulation -->
                <label class="form-label fw-semibold text-secondary small mb-2">
                    <i class="bi bi-chat-left-text-fill me-1 text-success"></i> Isi Pesan Lengkap (Sistem / WhatsApp):
                </label>
                <div class="card border-0 shadow-xs rounded-3 p-3 bg-white" style="border-left: 4px solid #25D366 !important;">
                    <div class="small text-dark font-monospace" id="modalMessageBody" style="white-space: pre-wrap; font-family: inherit; line-height: 1.6;">
                        -
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white p-3 d-flex justify-content-between">
                <small class="text-muted">Pesan resmi dari <?= e(APP_NAME) ?></small>
                <button type="button" class="btn btn-secondary rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('modalNotificationDetail');
    const modal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.btn-detail-notif').forEach(btn => {
        btn.addEventListener('click', function () {
            const notifId = this.dataset.id;
            const title = this.dataset.title;
            const type = this.dataset.type;
            const time = this.dataset.time;
            const name = this.dataset.recipientName;
            const phone = this.dataset.recipientPhone;
            const message = this.dataset.message;
            const status = this.dataset.status;
            const providerId = this.dataset.providerId;
            const sentAt = this.dataset.sentAt;
            const deliveredAt = this.dataset.deliveredAt;
            const error = this.dataset.error;
            const isRead = this.dataset.read === '1';
            const readAt = this.dataset.readAt;

            // Populate modal fields
            document.getElementById('modalNotifTitle').textContent = title;
            document.getElementById('modalNotifMeta').textContent = `${type} • ${time}`;
            document.getElementById('modalRecipientName').textContent = name;
            document.getElementById('modalRecipientPhone').textContent = phone;
            document.getElementById('modalWaStatus').textContent = status.toUpperCase();
            document.getElementById('modalSentAt').textContent = sentAt !== '-' ? `Terkirim: ${sentAt}` : 'Waktu kirim belum tercatat';
            document.getElementById('modalReadStatus').textContent = isRead ? 'Sudah Dibaca' : 'Baru Dibaca Sekarang';
            document.getElementById('modalReadAt').textContent = isRead ? (readAt !== '-' ? `Dibaca: ${readAt}` : '') : 'Baru saja';
            document.getElementById('modalProviderId').textContent = providerId && providerId !== '' ? providerId : '-';
            document.getElementById('modalDeliveredAt').textContent = deliveredAt && deliveredAt !== '' ? deliveredAt : 'Belum dilaporkan provider';
            document.getElementById('modalMessageBody').textContent = message;

            const errorContainer = document.getElementById('modalErrorContainer');
            if (error && error.trim() !== '') {
                document.getElementById('modalErrorText').textContent = error;
                errorContainer.classList.remove('d-none');
            } else {
                errorContainer.classList.add('d-none');
            }

            modal.show();

            // Trigger asynchronous mark as read if currently unread
            if (!isRead) {
                fetch(`<?= base_url('member/notificationDetail/') ?>${notifId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.dataset.read = '1';
                        const row = document.getElementById(`notif-row-${notifId}`);
                        if (row) {
                            row.classList.remove('table-warning', 'bg-opacity-25');
                            const indicator = row.querySelector('.bi-circle-fill');
                            if (indicator) {
                                indicator.className = 'bi bi-check2 text-muted';
                            }
                        }
                        const badgeContainer = document.getElementById(`read-badge-container-${notifId}`);
                        if (badgeContainer) {
                            badgeContainer.innerHTML = '<span class="badge bg-light text-muted border px-2 py-1 rounded-pill small"><i class="bi bi-envelope-open me-1"></i> Sudah Dibaca</span>';
                        }
                        const markReadForm = document.querySelector(`.form-mark-read[data-id="${notifId}"]`);
                        if (markReadForm) markReadForm.remove();
                    }
                })
                .catch(err => console.error('Error marking as read:', err));
            }
        });
    });
});
</script>
