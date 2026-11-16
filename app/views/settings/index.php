<div class="container-fluid py-3 flex-grow-1">
    <!-- Header Title Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                <i class="bi bi-gear-fill me-1"></i> Pusat Konfigurasi Terpadu
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                <i class="bi bi-shield-check me-1"></i> Mode Aman & RBAC Admin
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">Pengaturan Sistem & Integrasi</h3>
                        <p class="text-secondary small mb-0">Kelola AI Financial Assistant, WhatsApp Gateway (FONNTE), dan spesifikasi lingkungan runtime dalam satu pusat kendali.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= base_url('admin/profile') ?>" class="btn btn-primary rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-person-circle"></i> Profil Administrator
                        </a>
                        <a href="<?= base_url('admin/audit') ?>" class="btn btn-outline-primary rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-shield-shaded"></i> Log Audit Keamanan
                        </a>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm fw-semibold border shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Status Cards -->
    <div class="row g-3 mb-4">
        <!-- App Status -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Status Aplikasi</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill"><?= e($systemInfo['app_env']) ?></span>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= e($systemInfo['app_name']) ?></h5>
                <small class="text-muted" style="font-size: 0.75rem;">Versi <?= e($systemInfo['app_version']) ?></small>
            </div>
        </div>

        <!-- Database Status -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Basis Data MySQL</span>
                    <span class="badge bg-success-subtle text-success rounded-pill">Online</span>
                </div>
                <h5 class="fw-bold text-success mb-1"><?= e($systemInfo['db_name']) ?></h5>
                <small class="text-muted" style="font-size: 0.75rem;"><?= number_format($systemInfo['total_trx'], 0, ',', '.') ?> transaksi tercatat</small>
            </div>
        </div>

        <!-- AI Engine Status -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">AI Assistant Aktif</span>
                    <span class="badge <?= $activeAiIsAvailable ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> rounded-pill">
                        <?= $activeAiIsAvailable ? 'Siap Digunakan' : 'Fallback Mode' ?>
                    </span>
                </div>
                <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($activeAiProviderName) ?>">
                    <?= e($activeAiProviderName) ?>
                </h5>
                <small class="text-muted" style="font-size: 0.75rem;">Provider: <?= e($aiSettings['ai_provider'] ?? 'auto') ?></small>
            </div>
        </div>

        <!-- WhatsApp Gateway Status -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">WhatsApp Gateway</span>
                    <span class="badge <?= !empty($waSettings['wa_enabled']) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill">
                        <?= !empty($waSettings['wa_enabled']) ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= strtoupper(e($waSettings['wa_provider'] ?? 'FONNTE')) ?></h5>
                <small class="text-muted" style="font-size: 0.75rem;">Pengirim: <?= e($waSettings['wa_sender_number'] ?? '-') ?></small>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-header bg-white border-0 p-3 pt-3 pb-0">
            <ul class="nav nav-tabs nav-fill border-bottom-0" id="settingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'ai' ? 'active fw-bold text-primary border-primary border-bottom-0' : 'text-secondary' ?> py-3 rounded-top-3 d-flex align-items-center justify-content-center gap-2" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai-tab-pane" type="button" role="tab" aria-controls="ai-tab-pane" aria-selected="<?= $activeTab === 'ai' ? 'true' : 'false' ?>">
                        <i class="bi bi-stars text-warning fs-5"></i>
                        <span>AI Financial Assistant</span>
                        <span class="badge bg-warning-subtle text-dark rounded-pill ms-1 font-monospace" style="font-size: 0.7rem;">Gemini / Groq / NVIDIA</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'whatsapp' ? 'active fw-bold text-success border-success border-bottom-0' : 'text-secondary' ?> py-3 rounded-top-3 d-flex align-items-center justify-content-center gap-2" id="wa-tab" data-bs-toggle="tab" data-bs-target="#wa-tab-pane" type="button" role="tab" aria-controls="wa-tab-pane" aria-selected="<?= $activeTab === 'whatsapp' ? 'true' : 'false' ?>">
                        <i class="bi bi-whatsapp text-success fs-5"></i>
                        <span>WhatsApp Gateway</span>
                        <span class="badge bg-success-subtle text-success rounded-pill ms-1 font-monospace" style="font-size: 0.7rem;">FONNTE</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'system' ? 'active fw-bold text-primary border-primary border-bottom-0' : 'text-secondary' ?> py-3 rounded-top-3 d-flex align-items-center justify-content-center gap-2" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-tab-pane" type="button" role="tab" aria-controls="system-tab-pane" aria-selected="<?= $activeTab === 'system' ? 'true' : 'false' ?>">
                        <i class="bi bi-hdd-stack text-primary fs-5"></i>
                        <span>Sistem & Keamanan</span>
                        <span class="badge bg-light text-muted rounded-pill ms-1 font-monospace" style="font-size: 0.7rem;">Server Runtime</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content" id="settingsTabContent">
        <!-- ========================================== -->
        <!-- TAB 1: AI FINANCIAL ASSISTANT CONFIGURATION -->
        <!-- ========================================== -->
        <div class="tab-pane fade <?= $activeTab === 'ai' ? 'show active' : '' ?>" id="ai-tab-pane" role="tabpanel" aria-labelledby="ai-tab" tabindex="0">
            <div class="row g-4 mb-4">
                <!-- Left Column: AI Providers & Credentials Form -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-circle">
                                    <i class="bi bi-cpu-fill fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Konfigurasi Mesin AI Assistant</h5>
                                    <small class="text-muted">Pilih provider aktif dan kelola kredensial API key untuk analisis keuangan kas</small>
                                </div>
                            </div>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill small">
                                <i class="bi bi-shield-check me-1"></i> Proteksi Kredensial
                            </span>
                        </div>

                        <form action="<?= base_url('settings/updateAi') ?>" method="POST">
                            <?= \App\Core\Security::csrfField() ?>

                            <!-- AI Master Switch -->
                            <div class="mb-4 p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                <div>
                                    <label class="form-check-label fw-bold text-dark d-block" for="ai_enabled">
                                        Status Layanan AI Financial Assistant
                                    </label>
                                    <small class="text-muted">Aktifkan untuk mengizinkan sistem memberikan rekomendasi cerdas dan menjawab pertanyaan kas.</small>
                                </div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="ai_enabled" name="ai_enabled" value="1" <?= !empty($aiSettings['ai_enabled']) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <!-- Provider Selector -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small">Pilihan Provider AI Aktif <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg fw-semibold" name="ai_provider" id="ai_provider" required>
                                    <option value="auto" <?= ($aiSettings['ai_provider'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>
                                        ⚙ Otomatis (Prioritas: NVIDIA NIM &rarr; Google Gemini &rarr; Groq &rarr; Local Mock)
                                    </option>
                                    <option value="gemini" <?= ($aiSettings['ai_provider'] ?? '') === 'gemini' ? 'selected' : '' ?>>
                                        ✨ Google Gemini AI (gemini-1.5-flash)
                                    </option>
                                    <option value="groq" <?= ($aiSettings['ai_provider'] ?? '') === 'groq' ? 'selected' : '' ?>>
                                        ⚡ Groq Cloud (Ultra-Fast LLaMA 3.3 70B Versatile)
                                    </option>
                                    <option value="nvidia" <?= ($aiSettings['ai_provider'] ?? '') === 'nvidia' ? 'selected' : '' ?>>
                                        🟢 NVIDIA NIM Microservice (meta/llama-3.1-70b-instruct)
                                    </option>
                                    <option value="mock" <?= ($aiSettings['ai_provider'] ?? '') === 'mock' ? 'selected' : '' ?>>
                                        💻 Local Analytical AI (Mode Offline / Pengembangan Tanpa Kuota)
                                    </option>
                                </select>
                                <small class="text-muted" style="font-size: 0.75rem;">Sistem otomatis menggunakan mode fallback jika provider eksternal mengalami kendala kuota atau koneksi.</small>
                            </div>

                            <!-- Accordion: Detailed Provider Configurations -->
                            <div class="accordion mb-4" id="aiProvidersAccordion">
                                <!-- Provider 1: Google Gemini -->
                                <div class="accordion-item border rounded-3 mb-3 overflow-hidden shadow-xs">
                                    <h2 class="accordion-header" id="headingGemini">
                                        <button class="accordion-button fw-bold py-3 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGemini" aria-expanded="true" aria-controls="collapseGemini">
                                            <i class="bi bi-google text-primary me-2 fs-5"></i> Google Gemini Generative AI
                                        </button>
                                    </h2>
                                    <div id="collapseGemini" class="accordion-collapse collapse show" aria-labelledby="headingGemini" data-bs-parent="#aiProvidersAccordion">
                                        <div class="accordion-body p-3">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small">
                                                    Gemini API Key
                                                    <?php if (!empty($aiMaskedKeys['gemini_api_key'])): ?>
                                                        <span class="badge bg-success-subtle text-success ms-2 font-monospace">
                                                            <i class="bi bi-key-fill me-1"></i><?= e($aiMaskedKeys['gemini_api_key']) ?>
                                                            <?= !empty($aiEnvStatus['gemini_api_key']) ? ' (dari .env)' : ' (dari basis data)' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="password" class="form-control font-monospace" name="gemini_api_key" placeholder="<?= !empty($aiMaskedKeys['gemini_api_key']) ? 'Biarkan kosong untuk mempertahankan key saat ini' : 'Masukkan GEMINI_API_KEY Anda' ?>">
                                                <small class="text-muted" style="font-size: 0.75rem;">Kunci API Google AI Studio untuk model Gemini.</small>
                                            </div>
                                            <div>
                                                <label class="form-label fw-semibold text-secondary small">Gemini Model</label>
                                                <input type="text" class="form-control font-monospace" name="gemini_model" value="<?= e($aiSettings['gemini_model'] ?? 'gemini-1.5-flash') ?>" placeholder="gemini-1.5-flash">
                                                <small class="text-muted" style="font-size: 0.75rem;">Default: <code>gemini-1.5-flash</code></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Provider 2: Groq Cloud -->
                                <div class="accordion-item border rounded-3 mb-3 overflow-hidden shadow-xs">
                                    <h2 class="accordion-header" id="headingGroq">
                                        <button class="accordion-button fw-bold py-3 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGroq" aria-expanded="true" aria-controls="collapseGroq">
                                            <i class="bi bi-lightning-charge-fill text-warning me-2 fs-5"></i> Groq Cloud (Ultra-Fast Inference)
                                        </button>
                                    </h2>
                                    <div id="collapseGroq" class="accordion-collapse collapse show" aria-labelledby="headingGroq" data-bs-parent="#aiProvidersAccordion">
                                        <div class="accordion-body p-3">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small">
                                                    Groq API Key
                                                    <?php if (!empty($aiMaskedKeys['groq_api_key'])): ?>
                                                        <span class="badge bg-success-subtle text-success ms-2 font-monospace">
                                                            <i class="bi bi-key-fill me-1"></i><?= e($aiMaskedKeys['groq_api_key']) ?>
                                                            <?= !empty($aiEnvStatus['groq_api_key']) ? ' (dari .env)' : ' (dari basis data)' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="password" class="form-control font-monospace" name="groq_api_key" placeholder="<?= !empty($aiMaskedKeys['groq_api_key']) ? 'Biarkan kosong untuk mempertahankan key saat ini' : 'Masukkan gsk_... Groq API Key' ?>">
                                                <small class="text-muted" style="font-size: 0.75rem;">Kunci API dari Groq Console (https://console.groq.com).</small>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label fw-semibold text-secondary small">Groq Model</label>
                                                    <input type="text" class="form-control font-monospace" name="groq_model" value="<?= e($aiSettings['groq_model'] ?? 'llama-3.3-70b-versatile') ?>" placeholder="llama-3.3-70b-versatile">
                                                    <small class="text-muted" style="font-size: 0.75rem;">Contoh: <code>llama-3.3-70b-versatile</code> atau <code>llama-3.1-8b-instant</code></small>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label fw-semibold text-secondary small">Groq Endpoint URL</label>
                                                    <input type="url" class="form-control font-monospace" name="groq_api_url" value="<?= e($aiSettings['groq_api_url'] ?? 'https://api.groq.com/openai/v1/chat/completions') ?>" placeholder="https://api.groq.com/openai/v1/chat/completions">
                                                    <small class="text-muted" style="font-size: 0.75rem;">Endpoint standar OpenAI-compatible.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Provider 3: NVIDIA NIM -->
                                <div class="accordion-item border rounded-3 mb-3 overflow-hidden shadow-xs">
                                    <h2 class="accordion-header" id="headingNvidia">
                                        <button class="accordion-button fw-bold py-3 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNvidia" aria-expanded="true" aria-controls="collapseNvidia">
                                            <i class="bi bi-gpu-card text-success me-2 fs-5"></i> NVIDIA NIM Microservice
                                        </button>
                                    </h2>
                                    <div id="collapseNvidia" class="accordion-collapse collapse show" aria-labelledby="headingNvidia" data-bs-parent="#aiProvidersAccordion">
                                        <div class="accordion-body p-3">
                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="nvidia_enabled" name="nvidia_enabled" value="1" <?= !empty($aiSettings['nvidia_enabled']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-semibold text-dark small" for="nvidia_enabled">
                                                    Aktifkan NVIDIA NIM Provider
                                                </label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small">
                                                    NVIDIA API Key
                                                    <?php if (!empty($aiMaskedKeys['nvidia_api_key'])): ?>
                                                        <span class="badge bg-success-subtle text-success ms-2 font-monospace">
                                                            <i class="bi bi-key-fill me-1"></i><?= e($aiMaskedKeys['nvidia_api_key']) ?>
                                                            <?= !empty($aiEnvStatus['nvidia_api_key']) ? ' (dari .env)' : ' (dari basis data)' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="password" class="form-control font-monospace" name="nvidia_api_key" placeholder="<?= !empty($aiMaskedKeys['nvidia_api_key']) ? 'Biarkan kosong untuk mempertahankan key saat ini' : 'Masukkan nvapi-... NVIDIA Key' ?>">
                                                <small class="text-muted" style="font-size: 0.75rem;">Kunci API dari build.nvidia.com.</small>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label fw-semibold text-secondary small">NVIDIA Model</label>
                                                    <input type="text" class="form-control font-monospace" name="nvidia_model" value="<?= e($aiSettings['nvidia_model'] ?? 'meta/llama-3.1-70b-instruct') ?>" placeholder="meta/llama-3.1-70b-instruct">
                                                    <small class="text-muted" style="font-size: 0.75rem;">Default: <code>meta/llama-3.1-70b-instruct</code></small>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label fw-semibold text-secondary small">NVIDIA Endpoint URL</label>
                                                    <input type="url" class="form-control font-monospace" name="nvidia_api_url" value="<?= e($aiSettings['nvidia_api_url'] ?? 'https://integrate.api.nvidia.com/v1/chat/completions') ?>" placeholder="https://integrate.api.nvidia.com/v1/chat/completions">
                                                    <small class="text-muted" style="font-size: 0.75rem;">Endpoint NIM API.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2">
                                    <i class="bi bi-save-fill"></i> Simpan Seluruh Pengaturan AI
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Live Testing & Guidelines -->
                <div class="col-lg-4">
                    <!-- Live Connection Test Widget -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-activity text-primary"></i> Pengujian Koneksi AI
                        </h6>
                        <p class="text-secondary small mb-3">Uji coba langsung ke provider yang sedang aktif saat ini tanpa mengubah data kas.</p>

                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <small class="text-muted d-block" style="font-size: 0.72rem;">Provider Aktif Saat Ini:</small>
                            <span class="fw-bold text-dark font-monospace"><?= e($activeAiProviderName) ?></span>
                            <div class="mt-1">
                                <span class="badge <?= $activeAiIsAvailable ? 'bg-success text-white' : 'bg-warning text-dark' ?> rounded-pill" style="font-size: 0.7rem;">
                                    <?= $activeAiIsAvailable ? 'Terkoneksi & Siap' : 'Perlu API Key / Mock Fallback' ?>
                                </span>
                            </div>
                        </div>

                        <form action="<?= base_url('settings/testAi') ?>" method="POST">
                            <?= \App\Core\Security::csrfField() ?>
                            <button type="submit" class="btn btn-outline-primary w-100 rounded-pill py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-plug-fill"></i> Uji Koneksi Provider AI Aktif
                            </button>
                        </form>
                    </div>

                    <!-- Guidelines Card -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle text-info"></i> Petunjuk Integrasi AI
                        </h6>
                        <ul class="text-secondary small ps-3 mb-0" style="font-size: 0.8rem; line-height: 1.6;">
                            <li>Semua API key disimpan aman dan disensor pada antarmuka.</li>
                            <li>Jika database kosong, sistem otomatis membaca konfigurasi dari file <code>.env</code>.</li>
                            <li><strong>Local Analytical AI (Mock)</strong> selalu siaga sebagai cadangan otomatis apabila terjadi error kuota atau jaringan.</li>
                            <li>Untuk mencoba percakapan langsung dengan AI, silakan buka <a href="<?= base_url('ai') ?>" class="text-primary fw-semibold">Halaman AI Assistant</a>.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: WHATSAPP GATEWAY (FONNTE) CONFIG     -->
        <!-- ========================================== -->
        <div class="tab-pane fade <?= $activeTab === 'whatsapp' ? 'show active' : '' ?>" id="wa-tab-pane" role="tabpanel" aria-labelledby="wa-tab" tabindex="0">
            <div class="row g-4 mb-4">
                <!-- Left Column: WhatsApp Gateway Settings Form -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success p-2 rounded-circle">
                                    <i class="bi bi-whatsapp fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Konfigurasi WhatsApp Gateway (FONNTE)</h5>
                                    <small class="text-muted">Kelola gateway pesan, nomor pengirim, dan aturan otomatisasi notifikasi kas</small>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small">
                                <i class="bi bi-patch-check-fill me-1"></i> Fonnte Compatible
                            </span>
                        </div>

                        <form action="<?= base_url('settings/updateWa') ?>" method="POST">
                            <?= \App\Core\Security::csrfField() ?>

                            <!-- Enable Switch -->
                            <div class="mb-4 p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                <div>
                                    <label class="form-check-label fw-bold text-dark d-block" for="wa_enabled">
                                        Status Integrasi WhatsApp
                                    </label>
                                    <small class="text-muted">Aktifkan untuk mengizinkan pengiriman notifikasi kas ke nomor WhatsApp anggota.</small>
                                </div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="wa_enabled" name="wa_enabled" value="1" <?= !empty($waSettings['wa_enabled']) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <!-- Provider Selection -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Gateway Provider <span class="text-danger">*</span></label>
                                <select class="form-select" name="wa_provider" id="wa_provider" required>
                                    <option value="fonnte" <?= ($waSettings['wa_provider'] ?? '') === 'fonnte' ? 'selected' : '' ?>>
                                        FONNTE (api.fonnte.com) — Rekomendasi
                                    </option>
                                    <option value="mock" <?= ($waSettings['wa_provider'] ?? '') === 'mock' ? 'selected' : '' ?>>
                                        Mock Simulator (Lokal / Sandbox / Pengujian Tanpa Kuota)
                                    </option>
                                    <option value="wablas" <?= ($waSettings['wa_provider'] ?? '') === 'wablas' ? 'selected' : '' ?>>
                                        Wablas (wablas.com)
                                    </option>
                                    <option value="generic" <?= ($waSettings['wa_provider'] ?? '') === 'generic' ? 'selected' : '' ?>>
                                        Generic REST HTTP Gateway / Webhook
                                    </option>
                                </select>
                            </div>

                            <!-- API Endpoint URL -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">API Endpoint URL</label>
                                <input type="url" class="form-control font-monospace" name="wa_api_url" value="<?= e($waSettings['wa_api_url'] ?? 'https://api.fonnte.com/send') ?>" placeholder="https://api.fonnte.com/send">
                                <small class="text-muted" style="font-size: 0.75rem;">Alamat endpoint REST API penyedia gateway WhatsApp.</small>
                            </div>

                            <!-- Live Device Info if Connected -->
                            <?php if (!empty($waDeviceInfo) && !empty($waDeviceInfo['success'])): ?>
                                <div class="alert alert-success border-0 rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-patch-check-fill fs-5 text-success"></i>
                                        <div>
                                            <strong>Device Terhubung:</strong> <?= e($waDeviceInfo['name'] ?? 'WhatsApp Gateway') ?> (Paket: <?= e($waDeviceInfo['package'] ?? 'Fonnte') ?>)
                                            <div class="text-muted" style="font-size: 0.72rem;">Sisa Kuota: <strong><?= e($waDeviceInfo['quota'] ?? '-') ?> pesan</strong> &bull; Expired: <?= e($waDeviceInfo['expired'] ?? '-') ?></div>
                                        </div>
                                    </div>
                                    <span class="badge bg-success text-white rounded-pill px-2 py-1">Connect</span>
                                </div>
                            <?php endif; ?>

                            <!-- API Token -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">
                                    API Token / Secret Key 
                                    <?php if (!empty($waMaskedToken)): ?>
                                        <span class="badge bg-success-subtle text-success ms-2 font-monospace">
                                            <i class="bi bi-key-fill me-1"></i><?= e($waMaskedToken) ?>
                                            <?= !empty($isWaEnvToken) ? ' (dari .env)' : ' (dari basis data)' ?>
                                        </span>
                                    <?php endif; ?>
                                </label>
                                <input type="password" class="form-control font-monospace" name="wa_api_token" placeholder="<?= !empty($waMaskedToken) ? 'Biarkan kosong untuk mempertahankan token saat ini' : 'Masukkan API Token gateway WhatsApp' ?>">
                                <small class="text-muted" style="font-size: 0.75rem;">Token rahasia tidak akan ditampilkan ulang secara penuh demi keamanan.</small>
                            </div>

                            <!-- Sender Number -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary small">Nomor WhatsApp Pengirim (Sender Number)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                    <input type="text" class="form-control font-monospace" name="wa_sender_number" value="<?= e($waSettings['wa_sender_number'] ?? '081234567890') ?>" placeholder="081234567890">
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">Nomor WhatsApp resmi kas sekolah/organisasi.</small>
                            </div>

                            <!-- Automation Policies -->
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-robot text-success"></i> Otomatisasi Notifikasi Kas
                            </h6>
                            <div class="form-check mb-3 p-3 rounded-3 bg-light border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" id="wa_auto_notify_payment" name="wa_auto_notify_payment" value="1" <?= !empty($waSettings['wa_auto_notify_payment']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-dark" for="wa_auto_notify_payment">
                                    Notifikasi Setoran Baru Dibuat
                                    <small class="text-muted d-block fw-normal mt-1">Kirimkan konfirmasi penerimaan setoran kas secara otomatis sesaat setelah pembayaran dicatat.</small>
                                </label>
                            </div>
                            <div class="form-check mb-4 p-3 rounded-3 bg-light border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" id="wa_auto_notify_verification" name="wa_auto_notify_verification" value="1" <?= !empty($waSettings['wa_auto_notify_verification']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-dark" for="wa_auto_notify_verification">
                                    Notifikasi Verifikasi Pembayaran
                                    <small class="text-muted d-block fw-normal mt-1">Kirimkan bukti pengesahan status pembayaran ketika diverifikasi oleh petugas berwenang.</small>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2">
                                    <i class="bi bi-save-fill"></i> Simpan Pengaturan WhatsApp
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Test Send WhatsApp & Links -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-send-fill text-success"></i> Uji Coba Pengiriman WhatsApp
                        </h6>
                        <p class="text-secondary small mb-3">Kirimkan pesan uji coba ke nomor WhatsApp Anda untuk memvalidasi token dan koneksi gateway.</p>

                        <form action="<?= base_url('settings/testWa') ?>" method="POST">
                            <?= \App\Core\Security::csrfField() ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Nomor Tujuan Uji Coba <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                                    <input type="text" class="form-control font-monospace" name="test_phone" placeholder="081234567890 atau 628..." required>
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Gunakan nomor WhatsApp aktif.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Pesan Kustom (Opsional)</label>
                                <textarea class="form-control font-monospace" name="test_message" rows="3" placeholder="Tes koneksi gateway WhatsApp Sistem Kas..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-outline-success w-100 rounded-pill py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-send-check"></i> Kirim Pesan Uji Coba
                            </button>
                        </form>
                    </div>

                    <!-- WhatsApp Quick Actions -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-link-45deg text-primary"></i> Navigasi Layanan WhatsApp
                        </h6>
                        <div class="d-flex flex-column gap-2">
                            <a href="<?= base_url('whatsapp') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm fw-semibold border shadow-xs d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-speedometer2 text-success me-2"></i> Dashboard WhatsApp</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="<?= base_url('whatsapp/logs') ?>" class="btn btn-light rounded-pill px-3 py-2 btn-sm fw-semibold border shadow-xs d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-clock-history text-primary me-2"></i> Riwayat Pengiriman & Log</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: SPESIFIKASI SISTEM & KEAMANAN        -->
        <!-- ========================================== -->
        <div class="tab-pane fade <?= $activeTab === 'system' ? 'show active' : '' ?>" id="system-tab-pane" role="tabpanel" aria-labelledby="system-tab" tabindex="0">
            <!-- Main Configuration Details -->
            <div class="row g-4 mb-4">
                <!-- System & Runtime Environment -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-header bg-transparent border-0 p-4 pb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary p-2 rounded-circle">
                                    <i class="bi bi-hdd-stack fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Spesifikasi Lingkungan & Server</h5>
                                    <small class="text-muted">Parameter teknis PHP runtime dan web server</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4 pt-2">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2" style="width: 40%;">PHP Version</td>
                                            <td class="text-dark font-monospace fw-bold py-2"><?= e($systemInfo['php_version']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Web Server</td>
                                            <td class="text-dark small py-2"><?= e($systemInfo['server_software']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Base URL</td>
                                            <td class="text-primary font-monospace small py-2"><?= e($systemInfo['base_url']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Zona Waktu (Timezone)</td>
                                            <td class="text-dark py-2">
                                                <span class="badge bg-light text-dark border"><?= e($systemInfo['timezone']) ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Waktu Server Saat Ini</td>
                                            <td class="text-dark py-2"><?= e($systemInfo['current_time']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Total Pengguna/Anggota</td>
                                            <td class="text-dark fw-bold py-2"><?= number_format($systemInfo['total_members'], 0, ',', '.') ?> Siswa</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security & Session Policies -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-header bg-transparent border-0 p-4 pb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success p-2 rounded-circle">
                                    <i class="bi bi-shield-lock-fill fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Kebijakan Keamanan & Sesi</h5>
                                    <small class="text-muted">Proteksi autentikasi, anti-tampering, dan enkripsi</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4 pt-2">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2" style="width: 45%;">Session Idle Timeout</td>
                                            <td class="py-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                    <i class="bi bi-clock-history me-1"></i> <?= e($systemInfo['session_timeout']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">CSRF Token Guard</td>
                                            <td class="py-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                    <i class="bi bi-check-circle me-1"></i> Aktif (64-Hex SHA-256)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">User-Agent Fingerprinting</td>
                                            <td class="py-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                    <i class="bi bi-fingerprint me-1"></i> Aktif (Anti Hijack)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Password Hashing</td>
                                            <td class="py-2">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill">
                                                    BCRYPT (Cost Factor 10)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Kalkulasi Saldo Dinamis</td>
                                            <td class="py-2">
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill">
                                                    Backend Auto-Computed
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">AI Credential Masking</td>
                                            <td class="py-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                    <i class="bi bi-eye-slash me-1"></i> Otomatis Sensor Log
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Administrator Account & Profile Section -->
            <?php $adminUser = \App\Core\Auth::user(); ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 border-start border-4 border-primary">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 flex-shrink-0 shadow-sm" style="width: 52px; height: 52px;">
                                    <?= strtoupper(substr(!empty($adminUser['nama']) ? $adminUser['nama'] : ($adminUser['username'] ?? 'A'), 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace" style="font-size: 0.7rem;">
                                            <i class="bi bi-shield-check me-1"></i> Super User
                                        </span>
                                        <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.7rem;">
                                            @<?= e($adminUser['username'] ?? 'admin') ?>
                                        </span>
                                    </div>
                                    <h5 class="fw-bold mb-0 text-dark">
                                        <?= e(!empty($adminUser['nama']) ? $adminUser['nama'] : 'Administrator (Belum Mengatur Nama Lengkap)') ?>
                                    </h5>
                                    <small class="text-secondary">Email: <?= e($adminUser['email'] ?? 'admin@kas.local') ?> &bull; Status: <span class="text-success fw-bold">Aktif</span></small>
                                </div>
                            </div>
                            <div>
                                <a href="<?= base_url('admin/profile') ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2">
                                    <i class="bi bi-pencil-square"></i> Ubah Nama Profil Admin
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Integration & Status Banner -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning-subtle text-warning p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                    <i class="bi bi-stars fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark">Integrasi Kecerdasan Buatan (AI Financial Assistant)</h5>
                                    <p class="text-secondary small mb-0">
                                        Status: <strong><?= e($aiStatus['provider']) ?></strong> &bull; API Key: <span class="text-success"><?= e($aiStatus['api_key_status']) ?></span>.
                                        Sistem otomatis menggunakan *Graceful Fallback Mode* tanpa mengganggu kestabilan operasional kas.
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= base_url('ai') ?>" class="btn btn-warning rounded-pill px-4 py-2 text-dark fw-semibold shadow-sm d-flex align-items-center gap-2">
                                    <i class="bi bi-chat-dots-fill"></i> Buka AI Assistant
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
