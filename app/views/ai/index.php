<?php
/**
 * AI Financial Insight - Chat Interface
 * Architecture: Controller -> Financial Data Service -> AI Service -> AI Provider
 */
?>

<style>
.ai-chat-shell {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 148px);
    min-height: 520px;
    background: #f0f4ff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 32px rgba(79,70,229,.10);
    border: 1px solid #e0e7ff;
}
.ai-chat-header {
    background: linear-gradient(120deg,#1e1b4b 0%,#312e81 60%,#4338ca 100%);
    padding: 16px 22px 14px;
    color: white;
    flex-shrink: 0;
    position: relative;
}
.ai-avatar-ring {
    width:44px; height:44px; border-radius:50%;
    background: linear-gradient(135deg,#818cf8,#c084fc);
    display:flex; align-items:center; justify-content:center;
    font-size:1.25rem;
    box-shadow: 0 0 0 3px rgba(255,255,255,.20);
    flex-shrink:0; position:relative;
}
.ai-status-dot {
    width:11px; height:11px; border-radius:50%;
    background:#4ade80; border:2px solid #1e1b4b;
    position:absolute; bottom:1px; right:1px;
    animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
    0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.6)}
    50%{box-shadow:0 0 0 5px rgba(74,222,128,0)}
}
.ai-provider-badge {
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.20);
    color:#c7d2fe; border-radius:20px;
    padding:3px 10px; font-size:.72rem;
    display:inline-flex; align-items:center; gap:5px;
}
.ai-metrics-strip {
    background:#fff; border-bottom:1px solid #e0e7ff;
    padding:9px 18px; flex-shrink:0;
    display:flex; gap:10px; overflow-x:auto; scrollbar-width:none;
}
.ai-metrics-strip::-webkit-scrollbar{display:none}
.ai-metric-item {
    display:flex; align-items:center; gap:8px;
    white-space:nowrap; padding:4px 12px; border-radius:30px;
    background:#f5f3ff; border:1px solid #e0e7ff;
    font-size:.78rem; flex-shrink:0;
}
.ai-metric-icon {
    width:26px; height:26px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.78rem; flex-shrink:0;
}
.ai-disclaimer-banner {
    background:linear-gradient(90deg,#fef9c3,#fefce8);
    border-bottom:1px solid #fde68a;
    padding:7px 18px; font-size:.76rem; color:#854d0e;
    display:flex; align-items:center; gap:8px; flex-shrink:0;
}
.ai-chat-messages {
    flex:1; overflow-y:auto;
    padding:18px 18px 6px;
    display:flex; flex-direction:column; gap:14px;
    scroll-behavior:smooth;
}
.ai-chat-messages::-webkit-scrollbar{width:5px}
.ai-chat-messages::-webkit-scrollbar-thumb{background:#c7d2fe;border-radius:10px}
.ai-welcome {
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    text-align:center; padding:24px 16px; gap:12px; color:#6b7280;
}
.ai-welcome-icon {
    width:68px; height:68px; border-radius:50%;
    background:linear-gradient(135deg,#818cf8,#c084fc);
    display:flex; align-items:center; justify-content:center;
    font-size:1.9rem;
    box-shadow:0 8px 24px rgba(129,140,248,.35);
    animation:float-ai 3s ease-in-out infinite;
}
@keyframes float-ai {
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-8px)}
}
.chat-row {display:flex; gap:9px; max-width:100%;}
.chat-row.user-row {flex-direction:row-reverse}
.bubble-avatar {
    width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; flex-shrink:0; align-self:flex-end;
}
.bubble-avatar.ai-av {
    background:linear-gradient(135deg,#818cf8,#c084fc);
    color:white; box-shadow:0 2px 8px rgba(129,140,248,.4);
}
.bubble-avatar.user-av {
    background:linear-gradient(135deg,#4338ca,#6366f1);
    color:white; box-shadow:0 2px 8px rgba(99,102,241,.4); font-weight:700;
}
.bubble-content {display:flex; flex-direction:column; gap:3px; max-width:calc(100% - 50px)}
.chat-row.user-row .bubble-content{align-items:flex-end}
.bubble {
    padding:11px 15px; border-radius:16px;
    font-size:.87rem; line-height:1.55;
    box-shadow:0 1px 4px rgba(0,0,0,.07); word-break:break-word;
}
.bubble.ai-bubble {
    background:white; border-bottom-left-radius:4px;
    color:#1f2937; border:1px solid #e0e7ff; max-width:720px;
}
.bubble.user-bubble {
    background:linear-gradient(135deg,#4338ca,#6366f1);
    color:white; border-bottom-right-radius:4px; max-width:480px;
}
.bubble.error-bubble {
    background:#fff1f2; border:1px solid #fecdd3; color:#be123c;
}
.bubble-ts{font-size:.68rem;color:#9ca3af;padding:0 4px}
.typing-dots {
    display:flex; gap:4px; background:white;
    border:1px solid #e0e7ff; padding:12px 16px;
    border-radius:16px; border-bottom-left-radius:4px;
    box-shadow:0 1px 4px rgba(0,0,0,.07);
}
.typing-dots span {
    width:8px; height:8px; background:#818cf8;
    border-radius:50%; animation:bounce-dot 1.2s infinite; display:inline-block;
}
.typing-dots span:nth-child(2){animation-delay:.2s}
.typing-dots span:nth-child(3){animation-delay:.4s}
@keyframes bounce-dot{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-7px);opacity:1}}
.ai-response-body ul{margin:4px 0 8px 0;padding-left:18px}
.ai-response-body li{margin-bottom:3px;font-size:.845rem}
.ai-response-body p{margin-bottom:5px;font-size:.845rem}
.ai-response-body strong{font-weight:600}
.ai-section-pill {
    display:inline-flex; align-items:center; gap:4px;
    font-size:.70rem; font-weight:700; padding:2px 9px;
    border-radius:20px; margin-bottom:5px;
    text-transform:uppercase; letter-spacing:.04em;
}
.pill-ringkasan{background:#dbeafe;color:#1d4ed8}
.pill-temuan{background:#dcfce7;color:#166534}
.pill-peringatan{background:#fef9c3;color:#854d0e}
.pill-rekomendasi{background:#f3e8ff;color:#6b21a8}
.ai-suggestions-row {
    display:flex; gap:8px; overflow-x:auto;
    padding:6px 18px 12px; flex-shrink:0; scrollbar-width:none;
}
.ai-suggestions-row::-webkit-scrollbar{display:none}
.suggestion-chip {
    white-space:nowrap; background:white;
    border:1.5px solid #c7d2fe; color:#4338ca;
    border-radius:30px; padding:5px 13px;
    font-size:.76rem; font-weight:500; cursor:pointer;
    transition:all .18s; flex-shrink:0; user-select:none;
}
.suggestion-chip:hover {
    background:#4338ca; color:white; border-color:#4338ca;
    transform:translateY(-1px); box-shadow:0 3px 10px rgba(67,56,202,.25);
}
.ai-chat-input-area {
    background:#fff; border-top:1px solid #e0e7ff;
    padding:12px 16px 14px; flex-shrink:0;
}
.ai-input-wrapper {
    display:flex; gap:10px; align-items:flex-end;
    background:#f5f3ff; border:1.5px solid #c7d2fe;
    border-radius:14px; padding:8px 8px 8px 14px;
    transition:border-color .2s,box-shadow .2s;
}
.ai-input-wrapper:focus-within{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.ai-textarea {
    flex:1; background:transparent; border:none; outline:none;
    resize:none; font-size:.875rem; color:#1f2937;
    font-family:inherit; max-height:120px; min-height:22px; line-height:1.5;
}
.ai-textarea::placeholder{color:#9ca3af}
.ai-send-btn {
    width:38px; height:38px; border-radius:10px;
    background:linear-gradient(135deg,#4338ca,#6366f1);
    border:none; color:white;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; cursor:pointer; transition:all .18s;
    box-shadow:0 2px 8px rgba(99,102,241,.35);
}
.ai-send-btn:hover:not(:disabled){background:linear-gradient(135deg,#3730a3,#4f46e5);transform:scale(1.07);box-shadow:0 4px 14px rgba(99,102,241,.5)}
.ai-send-btn:disabled{opacity:.55;cursor:not-allowed;transform:none}
.ai-history-panel {
    background:white; border-left:1px solid #e0e7ff;
    width:0; overflow:hidden; transition:width .3s ease;
    flex-shrink:0; display:flex; flex-direction:column;
    border-radius:16px; box-shadow:0 4px 24px rgba(79,70,229,.08);
    height:calc(100vh - 148px); min-height:520px;
}
.ai-history-panel.open{width:290px}
.ai-history-item {
    padding:10px 14px; border-bottom:1px solid #f3f4f6;
    cursor:pointer; transition:background .15s; font-size:.78rem;
}
.ai-history-item:hover{background:#f5f3ff}
.ai-history-item .hist-q{color:#374151;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ai-history-item .hist-ts{color:#9ca3af;font-size:.68rem;margin-top:2px}
.btn-clear-hist {
    background:transparent; border:1px solid #fca5a5; color:#ef4444;
    border-radius:20px; padding:2px 9px; font-size:.70rem;
    cursor:pointer; transition:all .18s;
}
.btn-clear-hist:hover{background:#fee2e2}
.ai-page-wrapper{display:flex;gap:14px;align-items:flex-start}
@media(max-width:768px){
    .ai-chat-shell{height:calc(100vh - 200px)}
    .ai-history-panel{height:auto}
    .ai-history-panel.open{width:100%}
}
</style>

<div class="container-fluid px-3 px-md-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:34px;height:34px;background:linear-gradient(135deg,#818cf8,#c084fc);color:white;box-shadow:0 2px 10px rgba(129,140,248,.4)">
                    <i class="bi bi-stars" style="font-size:.95rem"></i>
                </span>
                AI Assistant
            </h4>
            <small class="text-muted">Asisten analitik keuangan berbasis data riil dari database</small>
        </div>
        <?php if (!empty($history)): ?>
        <button class="btn btn-sm btn-outline-secondary rounded-pill d-none d-lg-inline-flex align-items-center gap-1" id="btnToggleHistory">
            <i class="bi bi-clock-history"></i> Riwayat
            <span class="badge bg-secondary rounded-pill ms-1"><?= count($history) ?></span>
        </button>
        <?php endif; ?>
    </div>

    <div class="ai-page-wrapper" id="aiPageWrapper">

        <!-- CHAT SHELL -->
        <div class="ai-chat-shell flex-grow-1" id="aiChatShell">

            <!-- Header -->
            <div class="ai-chat-header">
                <div class="d-flex align-items-center position-relative" style="gap:12px">
                    <div class="ai-avatar-ring">
                        <i class="bi bi-robot"></i>
                        <div class="ai-status-dot"></div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold lh-1" style="font-size:1rem">Asisten AI Sistem Keuangan Kas</div>
                        <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                            <span class="ai-provider-badge"><i class="bi bi-cpu-fill"></i> <?= e($providerName) ?></span>
                            <span class="ai-provider-badge"><i class="bi bi-shield-check-fill text-success"></i> Read-Only</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small mb-1" style="color:rgba(255,255,255,.55)">Kondisi Kas</div>
                        <?php
                            $cond = $metrics['cash_condition'] ?? 'Stabil';
                            $condColor = str_contains($cond,'Sehat') ? '#4ade80' : (str_contains($cond,'Waspada') ? '#fbbf24' : '#f87171');
                        ?>
                        <span class="badge rounded-pill fw-semibold px-3 py-1"
                            style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:<?= $condColor ?>">
                            <i class="bi bi-heart-pulse-fill me-1"></i><?= e($cond) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Metrics Strip -->
            <div class="ai-metrics-strip">
                <div class="ai-metric-item">
                    <div class="ai-metric-icon" style="background:#dbeafe;color:#2563eb"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <div style="font-size:.65rem;color:#6b7280">Saldo Kas</div>
                        <div class="fw-bold" style="color:#1d4ed8;font-size:.80rem">Rp <?= number_format($metrics['current_balance']??0,0,',','.') ?></div>
                    </div>
                </div>
                <div class="ai-metric-item">
                    <div class="ai-metric-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-arrow-down-left-circle"></i></div>
                    <div>
                        <div style="font-size:.65rem;color:#6b7280">Pemasukan</div>
                        <div class="fw-bold" style="color:#166534;font-size:.80rem">Rp <?= number_format($metrics['total_income']??0,0,',','.') ?></div>
                    </div>
                </div>
                <div class="ai-metric-item">
                    <div class="ai-metric-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-arrow-up-right-circle"></i></div>
                    <div>
                        <div style="font-size:.65rem;color:#6b7280">Pengeluaran</div>
                        <div class="fw-bold" style="color:#b91c1c;font-size:.80rem">Rp <?= number_format($metrics['total_expense']??0,0,',','.') ?></div>
                    </div>
                </div>
                <div class="ai-metric-item">
                    <div class="ai-metric-icon" style="background:#fef9c3;color:#ca8a04"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div style="font-size:.65rem;color:#6b7280">Tunggakan</div>
                        <div class="fw-bold" style="color:#a16207;font-size:.80rem">Rp <?= number_format($metrics['total_tunggakan']??0,0,',','.') ?></div>
                    </div>
                </div>
                <div class="ai-metric-item">
                    <div class="ai-metric-icon" style="background:#f3e8ff;color:#9333ea"><i class="bi bi-people"></i></div>
                    <div>
                        <div style="font-size:.65rem;color:#6b7280">Belum Bayar</div>
                        <div class="fw-bold" style="color:#7e22ce;font-size:.80rem"><?= (int)($metrics['unpaid_members_count']??0) ?> siswa</div>
                    </div>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="ai-disclaimer-banner">
                <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                <span><strong>Disclaimer:</strong> AI merupakan alat bantu analisis dan informasi yang bersifat read-only. Selalu verifikasi ke data transaksi resmi.</span>
            </div>

            <!-- Messages -->
            <div class="ai-chat-messages" id="chatMessages">

                <div class="ai-welcome" id="welcomeState">
                    <div class="ai-welcome-icon">&#x2728;</div>
                    <div>
                        <div class="fw-bold text-dark mb-1" style="font-size:1.05rem">Halo! Saya Asisten AI pada Sistem Keuangan Kas</div>
                        <div class="text-muted" style="font-size:.84rem;max-width:380px">
                            Asisten virtual yang siap membantu Anda memahami informasi keuangan, panduan fitur, dan pengelolaan kas.
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-1">
                        <span class="suggestion-chip" data-query="Siapa saya?">&#128100; Siapa saya?</span>
                        <span class="suggestion-chip" data-query="Berapa tagihan saya?">&#128179; Tagihan saya?</span>
                        <span class="suggestion-chip" data-query="Berapa saldo kas saat ini?">&#127974; Saldo kas?</span>
                        <span class="suggestion-chip" data-query="Berapa pemasukan bulan ini?">&#128176; Pemasukan?</span>
                        <span class="suggestion-chip" data-query="Berapa pengeluaran bulan ini?">&#128228; Pengeluaran?</span>
                        <span class="suggestion-chip" data-query="Apakah kamu bendahara?">&#129302; Apakah bendahara?</span>
                        <span class="suggestion-chip" data-query="Bagaimana kondisi kas saat ini?">&#10084; Kondisi kas?</span>
                    </div>
                </div>

                <div id="chatHistoryContainer"></div>

                <div id="typingRow" class="chat-row" style="display:none">
                    <div class="bubble-avatar ai-av"><i class="bi bi-robot"></i></div>
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>

            </div>

            <!-- Suggestion Chips -->
            <div class="ai-suggestions-row">
                <span class="suggestion-chip" data-query="Siapa saya?">&#128100; Siapa saya?</span>
                <span class="suggestion-chip" data-query="Berapa tagihan saya?">&#128179; Tagihan saya?</span>
                <span class="suggestion-chip" data-query="Berapa saldo kas saat ini?">&#127974; Saldo kas?</span>
                <span class="suggestion-chip" data-query="Berapa pemasukan bulan ini?">&#128176; Pemasukan?</span>
                <span class="suggestion-chip" data-query="Berapa pengeluaran bulan ini?">&#128228; Pengeluaran?</span>
                <span class="suggestion-chip" data-query="Apakah kamu bendahara?">&#129302; Apakah bendahara?</span>
                <span class="suggestion-chip" data-query="Bagaimana kondisi kas saat ini?">&#10084; Kondisi kas?</span>
            </div>

            <!-- Input -->
            <div class="ai-chat-input-area">
                <form id="aiChatForm" action="<?= base_url('ai/ask') ?>" method="POST">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="<?= \App\Core\Security::generateCsrfToken() ?>">
                    <div class="ai-input-wrapper">
                        <textarea class="ai-textarea" id="aiQuestion" name="question" rows="1"
                            placeholder="Ketik pertanyaan keuangan... (Enter untuk kirim, Shift+Enter baris baru)"
                            autocomplete="off"></textarea>
                        <button type="submit" class="ai-send-btn" id="aiSendBtn" title="Kirim">
                            <span id="sendIcon"><i class="bi bi-send-fill" style="font-size:.88rem"></i></span>
                            <span id="sendSpinner" style="display:none">
                                <span class="spinner-border spinner-border-sm" style="width:14px;height:14px;border-width:2px"></span>
                            </span>
                        </button>
                    </div>
                    <div class="text-center mt-2" style="font-size:.69rem;color:#9ca3af">
                        <i class="bi bi-shield-lock me-1"></i>AI tidak dapat menulis atau mengubah data &mdash; hanya membaca fakta dari database
                    </div>
                </form>
            </div>

        </div><!-- /.ai-chat-shell -->


        <!-- HISTORY PANEL -->
        <div class="ai-history-panel" id="historyPanel">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-shrink-0">
                <span class="fw-bold text-dark" style="font-size:.84rem">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Chat
                </span>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (!empty($history)): ?>
                    <form action="<?= base_url('ai/clearHistory') ?>" method="POST" onsubmit="return confirm('Hapus semua riwayat chat AI?')">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrfToken() ?>">
                        <button type="submit" class="btn-clear-hist"><i class="bi bi-trash me-1"></i>Hapus</button>
                    </form>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-link text-secondary p-0" id="btnCloseHistory"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div style="overflow-y:auto;flex:1">
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $h): ?>
                    <div class="ai-history-item hist-clickable" data-q="<?= htmlspecialchars($h['question'],ENT_QUOTES) ?>">
                        <div class="hist-q"><?= e($h['question']) ?></div>
                        <div class="hist-ts"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i',strtotime($h['created_at'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted" style="font-size:.80rem">
                        <i class="bi bi-chat-dots d-block mb-2 fs-4"></i>Belum ada riwayat chat.
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-3 border-top flex-shrink-0 text-center" style="font-size:.70rem;color:#9ca3af">
                <?= count($history) ?> interaksi terakhir
            </div>
        </div>

    </div><!-- /.ai-page-wrapper -->
</div>

<script id="phpHistoryData" type="application/json">
<?= json_encode(array_map(function($h){return['question'=>$h['question'],'response'=>$h['response'],'created_at'=>date('d M Y, H:i',strtotime($h['created_at']))];},array_reverse($history??[])),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) ?>
</script>

<script>
document.addEventListener('DOMContentLoaded',function(){
    function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
    function nowStr(){var d=new Date();return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'})+', '+d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});}

    function parseAiResponse(raw){
        if(!raw||!raw.trim())return'<p class="text-muted mb-0">Tidak ada respons.</p>';
        var sec={ringkasan:'',temuan:'',peringatan:'',rekomendasi:'',other:''};
        var cur='other';
        raw.split('\n').forEach(function(line){
            var t=line.trim();
            if(/^#{1,4}\s*ringkasan/i.test(t)){cur='ringkasan';return;}
            if(/^#{1,4}\s*temuan/i.test(t)){cur='temuan';return;}
            if(/^#{1,4}\s*peringatan/i.test(t)){cur='peringatan';return;}
            if(/^#{1,4}\s*rekomendasi/i.test(t)){cur='rekomendasi';return;}
            sec[cur]+=line+'\n';
        });
        function fmtBody(txt){
            if(!txt||!txt.trim())return'<p class="text-muted mb-0 small">Tidak ada informasi spesifik.</p>';
            var safe = escHtml(txt.trim());
            var h=safe.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/^[\s]*[-*]\s+(.*)$/gm,'<li>$1</li>');
            h=h.replace(/(<li>[\s\S]*?<\/li>(\n|))+/g,'<ul class="mb-1 ps-3 small">$&</ul>');
            return h.split(/\n{2,}/).map(function(p){if(/^<ul/.test(p.trim()))return p;return'<p class="mb-1 small">'+p.replace(/\n/g,'<br>')+'</p>';}).join('');
        }
        var hasStruct=sec.ringkasan.trim()||sec.temuan.trim()||sec.peringatan.trim()||sec.rekomendasi.trim();
        if(!hasStruct)return'<div class="ai-response-body">'+fmtBody(sec.other||raw)+'</div>';
        var defs=[
            {key:'ringkasan',label:'Ringkasan',icon:'&#128203;',pill:'pill-ringkasan'},
            {key:'temuan',label:'Temuan Penting',icon:'&#128269;',pill:'pill-temuan'},
            {key:'peringatan',label:'Peringatan & Risiko',icon:'&#9888;&#65039;',pill:'pill-peringatan'},
            {key:'rekomendasi',label:'Rekomendasi',icon:'&#128161;',pill:'pill-rekomendasi'},
        ];
        var out='<div class="ai-response-body">';
        defs.forEach(function(d){out+='<div class="mb-3"><span class="ai-section-pill '+d.pill+'">'+d.icon+' '+d.label+'</span><div>'+fmtBody(sec[d.key])+'</div></div>';});
        return out+'</div>';
    }

    var chatMessages=document.getElementById('chatMessages');
    var histContainer=document.getElementById('chatHistoryContainer');
    var welcomeState=document.getElementById('welcomeState');
    var typingRow=document.getElementById('typingRow');
    var aiQuestion=document.getElementById('aiQuestion');
    var aiSendBtn=document.getElementById('aiSendBtn');
    var sendIcon=document.getElementById('sendIcon');
    var sendSpinner=document.getElementById('sendSpinner');
    var form=document.getElementById('aiChatForm');
    var historyPanel=document.getElementById('historyPanel');

    function mkUserBubble(text,ts){
        var row=document.createElement('div');
        row.className='chat-row user-row';
        row.innerHTML='<div class="bubble-avatar user-av"><i class="bi bi-person-fill"></i></div><div class="bubble-content"><div class="bubble user-bubble">'+escHtml(text)+'</div><span class="bubble-ts">'+(ts||nowStr())+'</span></div>';
        return row;
    }
    function mkAiBubble(resp,ts,isErr){
        var row=document.createElement('div');
        row.className='chat-row';
        var cls=isErr?'bubble error-bubble':'bubble ai-bubble';
        var body=isErr?'<i class="bi bi-exclamation-triangle-fill me-2"></i>'+escHtml(resp):parseAiResponse(resp);
        row.innerHTML='<div class="bubble-avatar ai-av"><i class="bi bi-robot"></i></div><div class="bubble-content"><div class="'+cls+'">'+body+'</div><span class="bubble-ts">'+(ts||nowStr())+'</span></div>';
        return row;
    }
    function scrollBot(){chatMessages.scrollTop=chatMessages.scrollHeight;}

    var histDataEl=document.getElementById('phpHistoryData');
    var histData=[];
    try{histData=JSON.parse(histDataEl.textContent||'[]');}catch(e){}
    if(histData.length>0){
        welcomeState.style.display='none';
        histData.forEach(function(item){
            histContainer.appendChild(mkUserBubble(item.question,item.created_at));
            histContainer.appendChild(mkAiBubble(item.response,item.created_at,false));
        });
        scrollBot();
    }

    aiQuestion.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px';});
    aiQuestion.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();form.dispatchEvent(new Event('submit'));}});

    function sendQ(q){aiQuestion.value=q;aiQuestion.style.height='auto';form.dispatchEvent(new Event('submit'));}
    document.querySelectorAll('.suggestion-chip').forEach(function(c){c.addEventListener('click',function(){sendQ(this.getAttribute('data-query'));});});

    form.addEventListener('submit',function(e){
        e.preventDefault();
        var question=aiQuestion.value.trim();
        if(!question)return;
        welcomeState.style.display='none';
        histContainer.appendChild(mkUserBubble(question,nowStr()));
        typingRow.style.display='flex';
        scrollBot();
        aiSendBtn.disabled=true; aiQuestion.disabled=true;
        sendIcon.style.display='none'; sendSpinner.style.display='inline-flex';
        aiQuestion.value=''; aiQuestion.style.height='auto';
        var fd=new FormData(form);
        fd.set('question',question); fd.append('ajax','1');
        fetch(form.action,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
        .then(function(data){
            typingRow.style.display='none';
            if(data.success){histContainer.appendChild(mkAiBubble(data.response,data.created_at,false));}
            else{histContainer.appendChild(mkAiBubble(data.message||'Terjadi kesalahan.',nowStr(),true));}
        })
        .catch(function(err){
            typingRow.style.display='none';
            histContainer.appendChild(mkAiBubble('Gagal menghubungi server. ('+err.message+')',nowStr(),true));
        })
        .finally(function(){
            aiSendBtn.disabled=false; aiQuestion.disabled=false;
            sendIcon.style.display='inline'; sendSpinner.style.display='none';
            aiQuestion.focus(); scrollBot();
        });
    });

    var btnToggle=document.getElementById('btnToggleHistory');
    var btnClose=document.getElementById('btnCloseHistory');
    if(btnToggle)btnToggle.addEventListener('click',function(){historyPanel.classList.toggle('open');});
    if(btnClose)btnClose.addEventListener('click',function(){historyPanel.classList.remove('open');});

    document.querySelectorAll('.hist-clickable').forEach(function(item){
        item.addEventListener('click',function(){
            aiQuestion.value=this.getAttribute('data-q');
            aiQuestion.style.height='auto';
            aiQuestion.style.height=Math.min(aiQuestion.scrollHeight,120)+'px';
            aiQuestion.focus();
        });
    });

    scrollBot();
});
</script>
