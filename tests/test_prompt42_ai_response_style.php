<?php
/**
 * TEST SUITE — PROMPT 42: PERBAIKI GAYA JAWABAN AI ASISTEN
 * Memvalidasi AI Asisten menjawab secara langsung, singkat, jelas, dan sesuai pertanyaan pengguna.
 * Menguji Admin, Bendahara, dan Anggota.
 *
 * Jalankan: php tests/test_prompt42_ai_response_style.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

// Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\Ai\MockAiProvider;
use App\Services\FinancialDataService;

$provider = new MockAiProvider();
$passed = 0;
$failed = 0;
$results = [];

function runTest(string $id, string $desc, callable $fn): void {
    global $passed, $failed, $results;
    try {
        $ok = $fn();
        if ($ok === true) {
            $passed++;
            $results[] = ['id' => $id, 'desc' => $desc, 'status' => 'PASS', 'detail' => ''];
        } else {
            $failed++;
            $results[] = ['id' => $id, 'desc' => $desc, 'status' => 'FAIL', 'detail' => is_string($ok) ? $ok : 'Assertion failed'];
        }
    } catch (\Throwable $e) {
        $failed++;
        $results[] = ['id' => $id, 'desc' => $desc, 'status' => 'ERROR', 'detail' => $e->getMessage()];
    }
}

function countSentences(string $text): int {
    // Strip code blocks and markdown headings first
    $clean = preg_replace('/```[\s\S]*?```/', '', $text);
    $clean = preg_replace('/^#+\s+.*$/m', '', $clean);
    $clean = preg_replace('/^\d+\.\s+/m', '', $clean);
    $clean = trim($clean);
    if ($clean === '') return 0;
    // Split on sentence-ending punctuation: period, exclamation, question mark
    $parts = preg_split('/[.!?]+(\s+|$)/', $clean, -1, PREG_SPLIT_NO_EMPTY);
    return count($parts);
}

// ── Metrics Fixtures ──────────────────────────────────────────────────────────

$adminMetrics = [
    'user_role'                  => 'super_user',
    'user_name'                  => 'Ahmad Budi Santoso',
    'user_username'              => 'admin_budi',
    'user_role_label'            => 'Admin / Super User',
    'total_income'               => 15000000.0,
    'total_expense'              => 8500000.0,
    'current_balance'            => 6500000.0,
    'cash_condition'             => 'Sehat (Kas stabil dengan cadangan dana mencukupi)',
    'total_members'              => 35,
    'unpaid_members_count'       => 5,
    'partial_members_count'      => 3,
    'paid_members_count'         => 27,
    'total_tagihan'              => 3500000.0,
    'total_dibayar'              => 2900000.0,
    'total_tunggakan'            => 600000.0,
    'pending_verification_count' => 2,
    'top_expense_categories'     => [['name' => 'Konsumsi Rapat', 'nominal' => 2500000, 'percentage' => 29.4]],
];

$bendaharaMetrics = [
    'user_role'                  => 'bendahara',
    'user_name'                  => 'Siti Rahayu',
    'user_username'              => 'bendahara_siti',
    'user_role_label'            => 'Bendahara',
    'total_income'               => 15000000.0,
    'total_expense'              => 8500000.0,
    'current_balance'            => 6500000.0,
    'cash_condition'             => 'Sehat (Kas stabil dengan cadangan dana mencukupi)',
    'total_members'              => 35,
    'unpaid_members_count'       => 5,
    'partial_members_count'      => 3,
    'paid_members_count'         => 27,
    'total_tagihan'              => 3500000.0,
    'total_dibayar'              => 2900000.0,
    'total_tunggakan'            => 600000.0,
    'pending_verification_count' => 2,
    'top_expense_categories'     => [['name' => 'Konsumsi Rapat', 'nominal' => 2500000, 'percentage' => 29.4]],
];

$memberWithBillMetrics = [
    'user_role'                  => 'member',
    'user_name'                  => 'Dewi Lestari',
    'user_username'              => 'dewi_lestari',
    'user_role_label'            => 'Anggota',
    'member_name'                => 'Dewi Lestari',
    'member_nis'                 => '2024001',
    'member_tagihan'             => 100000.0,
    'member_dibayar'             => 50000.0,
    'member_tunggakan'           => 50000.0,
    'member_status'              => 'Sebagian',
    'member_last_payment'        => [
        'tanggal'      => '2026-10-10 10:00:00',
        'nominal'      => 50000.0,
        'metode'       => 'Transfer Bank',
        'status'       => 'menunggu_verifikasi',
        'status_label' => 'Menunggu Verifikasi',
    ],
    'total_income'               => 15000000.0,
    'total_expense'              => 8500000.0,
    'current_balance'            => 6500000.0,
    'cash_condition'             => 'Sehat',
    'total_tagihan'              => 3500000.0,
    'total_tunggakan'            => 600000.0,
];

$memberNoBillMetrics = [
    'user_role'                  => 'member',
    'user_name'                  => 'Rian Pratama',
    'user_username'              => 'rian_pratama',
    'user_role_label'            => 'Anggota',
    'member_name'                => 'Rian Pratama',
    'member_nis'                 => '2024002',
    'member_tagihan'             => 0.0,
    'member_dibayar'             => 0.0,
    'member_tunggakan'           => 0.0,
    'member_status'              => 'Belum Ada Tagihan',
    'member_last_payment'        => null,
    'total_income'               => 15000000.0,
    'total_expense'              => 8500000.0,
    'current_balance'            => 6500000.0,
    'cash_condition'             => 'Sehat',
    'total_tagihan'              => 3500000.0,
    'total_tunggakan'            => 600000.0,
];

// Forbidden boilerplate strings that indicate unnecessary offers of help or echo questions
$forbiddenPhrases = [
    'apakah ada hal lain yang bisa saya bantu',
    'ada yang bisa saya bantu lagi',
    'semoga bermanfaat',
    'semoga informasi ini membantu',
    'silakan tanyakan hal yang berkaitan',
    'jangan ragu untuk bertanya',
    'mengenai pertanyaan anda',
    'terima kasih atas pertanyaan',
];

// =============================================================================
// GRUP 1: KETENTUAN 4 — "SIAPA SAYA?"
// Format: “Anda adalah [nama pengguna], dengan role [role aktif].”
// =============================================================================

runTest('P42-01', 'Admin: "Siapa saya?" menjawab langsung nama dan role', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Siapa saya?', '', $adminMetrics);
    if (!str_starts_with($r, 'Anda adalah')) return "Tidak dimulai dengan 'Anda adalah': $r";
    if (!str_contains($r, 'Ahmad Budi Santoso')) return "Nama tidak ditemukan: $r";
    if (!str_contains($r, 'Admin / Super User')) return "Role tidak ditemukan: $r";
    return true;
});

runTest('P42-02', 'Bendahara: "Siapa saya?" menjawab langsung nama dan role', function() use ($provider, $bendaharaMetrics) {
    $r = $provider->generateInsight('Siapa saya?', '', $bendaharaMetrics);
    if (!str_starts_with($r, 'Anda adalah')) return "Tidak dimulai dengan 'Anda adalah': $r";
    if (!str_contains($r, 'Siti Rahayu')) return "Nama bendahara tidak ditemukan: $r";
    if (!str_contains($r, 'Bendahara')) return "Role Bendahara tidak ditemukan: $r";
    return true;
});

runTest('P42-03', 'Anggota: "Siapa saya?" menjawab langsung nama dan role', function() use ($provider, $memberWithBillMetrics) {
    $r = $provider->generateInsight('Siapa saya?', '', $memberWithBillMetrics);
    if (!str_starts_with($r, 'Anda adalah')) return "Tidak dimulai dengan 'Anda adalah': $r";
    if (!str_contains($r, 'Dewi Lestari')) return "Nama anggota tidak ditemukan: $r";
    if (!str_contains($r, 'Anggota')) return "Role Anggota tidak ditemukan: $r";
    return true;
});

runTest('P42-04', 'Ketentuan 3: "Siapa saya?" cukup 1 kalimat sederhana', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Siapa saya?', '', $adminMetrics);
    $sc = countSentences($r);
    return $sc <= 3 ? true : "Terlalu panjang ($sc kalimat): $r";
});

// =============================================================================
// GRUP 2: KETENTUAN 5 — TAGIHAN, PEMBAYARAN, SALDO, TRANSAKSI
// Pertanyaan: “Berapa tagihan saya?”
// =============================================================================

runTest('P42-05', 'Anggota: "Berapa tagihan saya?" dengan data tagihan riil', function() use ($provider, $memberWithBillMetrics) {
    $r = $provider->generateInsight('Berapa tagihan saya?', '', $memberWithBillMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Pertanyaan tagihan tidak boleh ditolak: $r";
    if (!str_contains($r, '100.000,00') && !str_contains($r, '100000')) return "Nominal tagihan Rp 100.000 tidak tercantum: $r";
    if (!str_contains($r, '50.000,00') && !str_contains($r, '50000')) return "Sisa tunggakan Rp 50.000 tidak tercantum: $r";
    return true;
});

runTest('P42-06', 'Anggota: "Berapa tagihan saya?" jika data belum tersedia', function() use ($provider, $memberNoBillMetrics) {
    $r = $provider->generateInsight('Berapa tagihan saya?', '', $memberNoBillMetrics);
    if (!str_contains($r, 'belum tersedia')) return "Harus menyatakan bahwa data tagihan belum tersedia: $r";
    return true;
});

runTest('P42-07', 'Admin: "Berapa tagihan saya?" dijawab sesuai konteks akun admin dan data sistem', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Berapa tagihan saya?', '', $adminMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Tidak boleh ditolak: $r";
    if (!str_contains($r, 'Admin / Super User') && !str_contains($r, 'Admin')) return "Harus menjelaskan role admin: $r";
    if (!str_contains($r, '3.500.000,00') && !str_contains($r, '3500000')) return "Harus menyajikan total tagihan sistem: $r";
    return true;
});

runTest('P42-08', 'Pertanyaan saldo kas: "Berapa saldo kas saat ini?" singkat dan langsung', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Berapa saldo kas saat ini?', '', $adminMetrics);
    if (!str_starts_with($r, 'Saldo kas')) return "Harus langsung menyebut saldo kas: $r";
    if (!str_contains($r, '6.500.000,00')) return "Nominal saldo Rp 6.500.000,00 tidak cocok: $r";
    $sc = countSentences($r);
    return $sc <= 3 ? true : "Terlalu panjang ($sc kalimat): $r";
});

// =============================================================================
// GRUP 3: KETENTUAN 7 — PERTANYAAN DI LUAR SISTEM (OUT-OF-SCOPE)
// Contoh: "Apa itu AI?" -> “Mohon maaf karena saya berfokus membantu penggunaan Sistem Keuangan Kas, bukan menjawab pertanyaan umum di luar sistem.”
// =============================================================================

runTest('P42-09', 'Out-of-scope: "Apa itu AI?" dijawab singkat dengan awalan "Mohon maaf karena..."', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Apa itu AI?', '', $adminMetrics);
    if (!str_starts_with($r, 'Mohon maaf karena')) return "Harus diawali 'Mohon maaf karena...': $r";
    if (!str_contains($r, 'Sistem Keuangan Kas')) return "Harus menyebutkan fokus pada Sistem Keuangan Kas: $r";
    $sc = countSentences($r);
    return $sc <= 2 ? true : "Terlalu panjang ($sc kalimat): $r";
});

runTest('P42-10', 'Out-of-scope: Puisi tentang persahabatan ditolak singkat', function() use ($provider, $bendaharaMetrics) {
    $r = $provider->generateInsight('Buatkan puisi tentang persahabatan.', '', $bendaharaMetrics);
    if (!str_starts_with($r, 'Mohon maaf karena')) return "Harus diawali 'Mohon maaf karena...': $r";
    if (!str_contains($r, 'Sistem Keuangan Kas')) return "Harus menyebutkan fokus pada Sistem Keuangan Kas: $r";
    $sc = countSentences($r);
    return $sc <= 2 ? true : "Terlalu panjang ($sc kalimat): $r";
});

runTest('P42-11', 'Out-of-scope: Coding Python umum tanpa konteks kas ditolak singkat', function() use ($provider, $memberWithBillMetrics) {
    $r = $provider->generateInsight('Buatkan coding Python untuk menuliskan nama Anda.', '', $memberWithBillMetrics);
    if (!str_starts_with($r, 'Mohon maaf karena')) return "Harus diawali 'Mohon maaf karena...': $r";
    if (!str_contains($r, 'Sistem Keuangan Kas')) return "Harus menyebutkan fokus pada Sistem Keuangan Kas: $r";
    $sc = countSentences($r);
    return $sc <= 2 ? true : "Terlalu panjang ($sc kalimat): $r";
});

// =============================================================================
// GRUP 4: KETENTUAN 6 — LANGKAH PENGGUNAAN FITUR
// "Jika pengguna meminta langkah penggunaan fitur, berikan langkah singkat dan langsung."
// =============================================================================

runTest('P42-12', 'Fitur: "Bagaimana cara melihat laporan keuangan?" langkah singkat dan langsung', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Bagaimana cara melihat laporan keuangan?', '', $adminMetrics);
    if (!str_starts_with($r, 'Berikut langkah')) return "Harus langsung ke langkah: $r";
    if (!str_contains($r, '1.') || !str_contains($r, '2.')) return "Harus menggunakan format langkah bernomor: $r";
    if (str_contains($r, 'Halo!') || str_contains($r, 'Tentu, saya')) return "Ada pengantar berlebihan: $r";
    return true;
});

runTest('P42-13', 'Fitur: "Bagaimana cara melihat status pembayaran iuran?" untuk Anggota', function() use ($provider, $memberWithBillMetrics) {
    $r = $provider->generateInsight('Bagaimana cara melihat status pembayaran iuran?', '', $memberWithBillMetrics);
    if (!str_starts_with($r, 'Berikut langkah')) return "Harus langsung ke langkah: $r";
    if (!str_contains($r, 'Pembayaran Saya')) return "Harus mengarahkan ke Pembayaran Saya: $r";
    return true;
});

// =============================================================================
// GRUP 5: KETENTUAN 8 — PERTANYAAN TEKNIS RELEVAN DENGAN KAS TIDAK DITOLAK
// =============================================================================

runTest('P42-14', 'Teknis in-scope: Error pembayaran dijawab dengan langkah perbaikan teknis', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Bagaimana memperbaiki error pembayaran di aplikasi ini?', '', $adminMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Tidak boleh ditolak: $r";
    if (!str_contains($r, 'JPG') || !str_contains($r, '2 MB')) return "Langkah teknis ukuran file tidak ada: $r";
    if (!str_contains($r, 'CSRF')) return "Langkah teknis CSRF tidak ada: $r";
    return true;
});

runTest('P42-15', 'Teknis in-scope: Script Python iuran anggota kas dijawab dengan kode', function() use ($provider, $bendaharaMetrics) {
    $r = $provider->generateInsight('Buatkan kode Python untuk menghitung total iuran anggota.', '', $bendaharaMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Tidak boleh ditolak: $r";
    if (!str_contains($r, '```python')) return "Harus berisi blok kode python: $r";
    if (!str_contains($r, 'total_tagihan')) return "Harus menghitung tagihan/tunggakan: $r";
    return true;
});

runTest('P42-16', 'Teknis in-scope: Koneksi database aplikasi dijawab dengan panduan teknis', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Bagaimana cara memperbaiki koneksi database aplikasi ini?', '', $adminMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Tidak boleh ditolak: $r";
    if (!str_contains($r, '3306') || !str_contains($r, '.env')) return "Harus menyebutkan port 3306 dan .env: $r";
    return true;
});

// =============================================================================
// GRUP 6: KETENTUAN 9 — TIDAK MENAWARKAN BANTUAN TAMBAHAN & TIDAK MENGULANG PERTANYAAN
// =============================================================================

runTest('P42-17', 'Ketentuan 9: Respons tidak mengandung kalimat penutup basa-basi atau tawaran bantuan berlebih', function() use ($provider, $adminMetrics, $bendaharaMetrics, $memberWithBillMetrics, $forbiddenPhrases) {
    $testQueries = [
        ['Siapa saya?', $adminMetrics],
        ['Berapa saldo kas saat ini?', $adminMetrics],
        ['Berapa tagihan saya?', $memberWithBillMetrics],
        ['Apakah kamu bendahara?', $bendaharaMetrics],
        ['Apa itu AI?', $adminMetrics],
    ];

    foreach ($testQueries as [$q, $m]) {
        $r = mb_strtolower($provider->generateInsight($q, '', $m));
        foreach ($forbiddenPhrases as $fp) {
            if (str_contains($r, $fp)) {
                return "Ditemukan frasa terlarang '$fp' pada respons pertanyaan '$q': $r";
            }
        }
    }
    return true;
});

// =============================================================================
// GRUP 7: KETENTUAN 10 & 11 — INTEGRITAS DATA & RBAC (ADMIN, BENDAHARA, ANGGOTA)
// =============================================================================

runTest('P42-18', 'RBAC: Anggota dilarang melihat data pembayaran seluruh anggota (privasi)', function() use ($provider, $memberWithBillMetrics) {
    $r = $provider->generateInsight('Tampilkan data pembayaran seluruh anggota.', '', $memberWithBillMetrics);
    if (!str_starts_with($r, 'Mohon maaf karena')) return "Harus ditolak diawali 'Mohon maaf karena...': $r";
    if (!str_contains($r, 'privasi') && !str_contains($r, 'pribadi')) return "Harus menyebutkan alasan privasi: $r";
    return true;
});

runTest('P42-19', 'RBAC: Bendahara BOLEH melihat rekapitulasi pembayaran seluruh anggota', function() use ($provider, $bendaharaMetrics) {
    $r = $provider->generateInsight('Tampilkan data pembayaran seluruh anggota.', '', $bendaharaMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Bendahara tidak boleh ditolak: $r";
    if (!str_contains($r, 'Total Nilai Tagihan Iuran') || !str_contains($r, 'Total Sisa Tunggakan')) return "Data agregat tidak lengkap: $r";
    return true;
});

runTest('P42-20', 'RBAC: Admin BOLEH melihat rekapitulasi pembayaran seluruh anggota', function() use ($provider, $adminMetrics) {
    $r = $provider->generateInsight('Tampilkan data pembayaran seluruh anggota.', '', $adminMetrics);
    if (str_starts_with($r, 'Mohon maaf')) return "Admin tidak boleh ditolak: $r";
    if (!str_contains($r, 'Total Nilai Tagihan Iuran') || !str_contains($r, 'Total Sisa Tunggakan')) return "Data agregat tidak lengkap: $r";
    return true;
});

runTest('P42-21', 'Ketentuan 3: Pertanyaan sederhana (1-3 kalimat) pada seluruh peran', function() use ($provider, $adminMetrics, $bendaharaMetrics, $memberWithBillMetrics) {
    $queries = [
        ['Berapa pemasukan bulan ini?', $adminMetrics],
        ['Berapa pengeluaran bulan ini?', $bendaharaMetrics],
        ['Berapa saldo kas saat ini?', $memberWithBillMetrics],
        ['Apakah kamu bendahara?', $adminMetrics],
    ];

    foreach ($queries as [$q, $m]) {
        $r = $provider->generateInsight($q, '', $m);
        $sc = countSentences($r);
        if ($sc > 3) {
            return "Respons '$q' melebihi 3 kalimat ($sc kalimat): $r";
        }
    }
    return true;
});

// ── Hasil Akhir ───────────────────────────────────────────────────────────────

echo "\n";
echo str_repeat("=", 75) . "\n";
echo "  TEST SUITE — Prompt 42: Perbaiki Gaya Jawaban AI Asisten\n";
echo str_repeat("=", 75) . "\n\n";

foreach ($results as $r) {
    $icon = $r['status'] === 'PASS' ? "\033[32m[OK] \033[0m" : "\033[31m[FAIL]\033[0m";
    echo "  {$icon} [{$r['id']}] {$r['desc']}\n";
    if ($r['detail']) {
        echo "       -> \033[33m" . substr($r['detail'], 0, 150) . "\033[0m\n";
    }
}

$total = $passed + $failed;
echo "\n" . str_repeat("-", 75) . "\n";
echo "  Total: $total | Passed: $passed | Failed: $failed\n";
echo str_repeat("-", 75) . "\n\n";

exit($failed > 0 ? 1 : 0);
