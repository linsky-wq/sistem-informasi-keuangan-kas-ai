<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-shield-lock-fill me-1"></i> Area Administrator
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                <i class="bi bi-whatsapp me-1"></i> WhatsApp Gateway
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Pengaturan WhatsApp Gateway</h3>
                        <p class="text-secondary small mb-0">Kelola koneksi provider WhatsApp, token autentikasi rahasia, nomor pengirim, dan aturan otomatisasi notifikasi kas.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('settings?tab=whatsapp') ?>" class="btn btn-outline-primary rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-sliders"></i> Pusat Pengaturan Terpadu
                        </a>
                        <a href="<?= base_url('whatsapp') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm fw-semibold border shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard WA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Alert Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 rounded-4 shadow-xs p-3 d-flex align-items-start gap-3 mb-0 bg-info-subtle text-dark">
                <i class="bi bi-shield-check fs-4 text-info flex-shrink-0 mt-1"></i>
                <div class="small">
                    <strong>Pemberitahuan Keamanan (Security Notice):</strong>
                    Semua kredensial API secret/token dienkripsi dan diproteksi di tingkat server. Token tidak pernah diekspos dalam repositori kode sumber secara plaintext. Anda dapat memperbarui token sewaktu-waktu di bawah ini.
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Form -->
    <form action="<?= base_url('whatsapp/updateSettings') ?>" method="POST">
        <?= \App\Core\Security::csrfField() ?>
        
        <div class="row g-4">
            <!-- Left Column: Gateway & Provider -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-primary"></i> Konfigurasi Gateway
                    </h5>

                    <!-- Enable Switch -->
                    <div class="mb-4 p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                        <div>
                            <label class="form-check-label fw-bold text-dark d-block" for="wa_enabled">
                                Status Integrasi WhatsApp
                            </label>
                            <small class="text-muted">Aktifkan untuk mengizinkan sistem mengirimkan notifikasi WA ke anggota.</small>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="wa_enabled" name="wa_enabled" value="1" <?= !empty($settings['wa_enabled']) ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- Provider Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Gateway Provider <span class="text-danger">*</span></label>
                        <select class="form-select" name="wa_provider" id="wa_provider" required>
                            <option value="mock" <?= ($settings['wa_provider'] ?? '') === 'mock' ? 'selected' : '' ?>>
                                Mock Simulator (Lokal / Sandbox / Testing Tanpa Kuota)
                            </option>
                            <option value="fonnte" <?= ($settings['wa_provider'] ?? '') === 'fonnte' ? 'selected' : '' ?>>
                                Fonnte (api.fonnte.com)
                            </option>
                            <option value="wablas" <?= ($settings['wa_provider'] ?? '') === 'wablas' ? 'selected' : '' ?>>
                                Wablas (wablas.com)
                            </option>
                            <option value="generic" <?= ($settings['wa_provider'] ?? '') === 'generic' ? 'selected' : '' ?>>
                                Generic REST HTTP Gateway / Webhook
                            </option>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Gunakan Mock Simulator saat tahap pengembangan lokal atau offline.</small>
                    </div>

                    <!-- API URL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">API Endpoint URL</label>
                        <input type="url" class="form-control font-monospace" name="wa_api_url" value="<?= e($settings['wa_api_url'] ?? 'https://api.fonnte.com/send') ?>" placeholder="https://api.fonnte.com/send">
                        <small class="text-muted" style="font-size: 0.75rem;">Alamat endpoint REST API penyedia gateway WhatsApp.</small>
                    </div>

                    <?php if (!empty($deviceInfo) && !empty($deviceInfo['success'])): ?>
                        <div class="alert alert-success border-0 rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 small">
                                <i class="bi bi-patch-check-fill fs-5 text-success"></i>
                                <div>
                                    <strong>Device Terhubung:</strong> <?= e($deviceInfo['name'] ?? 'WhatsApp Gateway') ?> (Paket: <?= e($deviceInfo['package'] ?? 'Fonnte') ?>)
                                    <div class="text-muted" style="font-size: 0.72rem;">Sisa Kuota: <strong><?= e($deviceInfo['quota'] ?? '-') ?> pesan</strong> &bull; Expired: <?= e($deviceInfo['expired'] ?? '-') ?></div>
                                </div>
                            </div>
                            <span class="badge bg-success text-white rounded-pill px-2 py-1">Connect</span>
                        </div>
                    <?php endif; ?>

                    <!-- API Token -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">
                            API Token / Secret Key 
                            <?php if (!empty($maskedToken)): ?>
                                <span class="badge bg-success-subtle text-success ms-2 font-monospace">
                                    <i class="bi bi-key-fill me-1"></i><?= e($maskedToken) ?>
                                    <?= !empty($isEnvToken) ? ' (dari .env)' : ' (dari database)' ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <input type="password" class="form-control font-monospace" name="wa_api_token" placeholder="<?= !empty($maskedToken) ? 'Biarkan kosong jika tidak ingin mengubah token' : 'Masukkan API Token gateway WhatsApp' ?>">
                        <small class="text-muted" style="font-size: 0.75rem;">Token rahasia tidak akan ditampilkan ulang secara penuh demi keamanan.</small>
                    </div>

                    <!-- Sender Number -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nomor WhatsApp Pengirim (Sender Number)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                            <input type="text" class="form-control font-monospace" name="wa_sender_number" value="<?= e($settings['wa_sender_number'] ?? '081234567890') ?>" placeholder="081234567890">
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Nomor WhatsApp resmi kas sekolah/organisasi.</small>
                    </div>
                </div>
            </div>

            <!-- Right Column: Automation & Policy -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-robot text-success"></i> Otomatisasi Notifikasi
                        </h5>
                        <p class="text-secondary small mb-4">Tentukan tindakan keuangan apa saja yang otomatis memicu pengiriman notifikasi ke WhatsApp anggota.</p>

                        <!-- Trigger 1: Payment Created -->
                        <div class="form-check mb-3 p-3 rounded-3 bg-light border">
                            <input class="form-check-input ms-0 me-3" type="checkbox" id="wa_auto_notify_payment" name="wa_auto_notify_payment" value="1" <?= !empty($settings['wa_auto_notify_payment']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark" for="wa_auto_notify_payment">
                                Notifikasi Setoran Baru Dibuat
                                <small class="text-muted d-block fw-normal mt-1">Kirimkan konfirmasi penerimaan setoran kas secara otomatis sesaat setelah pembayaran dicatat oleh Bendahara/Admin.</small>
                            </label>
                        </div>

                        <!-- Trigger 2: Payment Verified -->
                        <div class="form-check mb-3 p-3 rounded-3 bg-light border">
                            <input class="form-check-input ms-0 me-3" type="checkbox" id="wa_auto_notify_verification" name="wa_auto_notify_verification" value="1" <?= !empty($settings['wa_auto_notify_verification']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark" for="wa_auto_notify_verification">
                                Notifikasi Verifikasi Pembayaran
                                <small class="text-muted d-block fw-normal mt-1">Kirimkan bukti pengesahan dan status pembayaran (Lunas/Sebagian) ketika diverifikasi oleh petugas berwenang.</small>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Area -->
                    <div class="pt-4 border-top">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-save-fill"></i> Simpan Seluruh Pengaturan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
