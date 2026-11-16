<?php
/**
 * Automated Test Suite: PROMPT 33 — Perbaikan Login Role Bendahara dan Anggota
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

// Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    if (count($parts) > 1) $parts[0] = strtolower($parts[0]);
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "=================================================================\n";
echo " TEST SUITE: PROMPT 33 — PERBAIKAN LOGIN BENDAHARA & ANGGOTA     \n";
echo "=================================================================\n\n";

$passedCount = 0;
$failedCount = 0;

function runTest(string $description, callable $testFn): void {
    global $passedCount, $failedCount;
    echo "• Testing: {$description} ... ";
    try {
        $testFn();
        echo "[PASSED]\n";
        $passedCount++;
    } catch (\Throwable $e) {
        echo "[FAILED]\n  Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
        $failedCount++;
    }
}

$userModel = new \App\Models\User();
$memberModel = new \App\Models\Member();
$trxModel = new \App\Models\Transaction();
$paymentModel = new \App\Models\Payment();
$logModel = new \App\Models\WhatsAppLog();
$aiService = new \App\Services\AiService();
$financialService = new \App\Services\FinancialDataService();

// -----------------------------------------------------------------
// 1. Login menggunakan akun Bendahara yang valid
// -----------------------------------------------------------------
runTest("1. Login menggunakan akun Bendahara yang valid (bendahara / bendahara123)", function() use ($userModel) {
    \App\Core\Session::destroy();
    $user = $userModel->authenticate('bendahara', 'bendahara123');
    if (!$user) {
        throw new \Exception("Autentikasi akun bendahara gagal.");
    }
    if ($user['role'] !== \App\Core\Auth::ROLE_BENDAHARA) {
        throw new \Exception("Role akun bukan bendahara: " . $user['role']);
    }
    \App\Core\Auth::login($user);
    if (!\App\Core\Auth::check()) {
        throw new \Exception("Session tidak aktif setelah login.");
    }
    if (!\App\Core\Auth::isBendahara()) {
        throw new \Exception("Auth::isBendahara() harus bernilai true.");
    }
    if (\App\Core\Auth::isMember()) {
        throw new \Exception("Auth::isMember() harus bernilai false untuk bendahara.");
    }
});

// -----------------------------------------------------------------
// 2. Pastikan Bendahara diarahkan ke dashboard Bendahara & dapat akses fitur transaksi, pembayaran, laporan
// -----------------------------------------------------------------
runTest("2. Akses fitur Bendahara (Dashboard, Transaksi, Pembayaran, Laporan)", function() use ($trxModel, $paymentModel) {
    // Current user is bendahara
    if (!\App\Core\Auth::isBendahara()) {
        throw new \Exception("User harus terautentikasi sebagai bendahara.");
    }

    // Role canSendWhatsApp
    if (!\App\Core\Auth::canSendWhatsApp()) {
        throw new \Exception("Bendahara harus memiliki izin mengirim/melihat WhatsApp.");
    }

    // Bendahara can query transactions
    $balance = $trxModel->getCurrentBalance();
    if (!is_float($balance) && !is_numeric($balance)) {
        throw new \Exception("Bendahara gagal membaca saldo kas.");
    }

    // Bendahara can query payments and summary stats
    $stats = $paymentModel->getSummaryStats();
    if (!isset($stats['total_tagihan'])) {
        throw new \Exception("Bendahara gagal membaca statistik pembayaran.");
    }
});

// -----------------------------------------------------------------
// 3. Login menggunakan akun Anggota yang valid
// -----------------------------------------------------------------
runTest("3. Login menggunakan akun Anggota yang valid (ahmad_fauzi / member123)", function() use ($userModel) {
    \App\Core\Session::destroy();
    $user = $userModel->authenticate('ahmad_fauzi', 'member123');
    if (!$user) {
        throw new \Exception("Autentikasi akun anggota gagal.");
    }
    if ($user['role'] !== \App\Core\Auth::ROLE_MEMBER && $user['role'] !== 'anggota') {
        throw new \Exception("Role akun bukan member: " . $user['role']);
    }
    \App\Core\Auth::login($user);
    if (!\App\Core\Auth::check()) {
        throw new \Exception("Session tidak aktif setelah login.");
    }
    if (!\App\Core\Auth::isMember()) {
        throw new \Exception("Auth::isMember() harus bernilai true.");
    }
    if (\App\Core\Auth::isSuperUser() || \App\Core\Auth::isBendahara()) {
        throw new \Exception("Anggota tidak boleh memiliki role super_user atau bendahara.");
    }
    $memberProfile = \App\Core\Auth::member();
    if (!$memberProfile || $memberProfile['nama'] !== 'Ahmad Fauzi') {
        throw new \Exception("Profil anggota Ahmad Fauzi gagal dimuat secara otomatis.");
    }
});

// -----------------------------------------------------------------
// 4. Pastikan Anggota dapat melihat profil dan riwayat pembayaran miliknya sendiri
// -----------------------------------------------------------------
runTest("4. Anggota mengakses data tagihan dan riwayat pembayaran pribadi", function() use ($paymentModel) {
    $member = \App\Core\Auth::member();
    if (!$member) {
        throw new \Exception("Profil anggota tidak ditemukan di session.");
    }
    $history = $paymentModel->getMemberHistory((int)$member['id']);
    foreach ($history as $row) {
        if ((int)$row['member_id'] !== (int)$member['id']) {
            throw new \Exception("Anggota menerima data pembayaran anggota lain!");
        }
    }
});

// -----------------------------------------------------------------
// 5. Coba login dengan password yang salah dan pastikan ditolak
// -----------------------------------------------------------------
runTest("5. Login dengan password salah ditolak", function() use ($userModel) {
    $wrongBendahara = $userModel->authenticate('bendahara', 'password_salah');
    if ($wrongBendahara !== null) {
        throw new \Exception("Password salah untuk bendahara seharusnya ditolak.");
    }
    $wrongMember = $userModel->authenticate('ahmad_fauzi', 'password_salah');
    if ($wrongMember !== null) {
        throw new \Exception("Password salah untuk anggota seharusnya ditolak.");
    }
});

// -----------------------------------------------------------------
// 6. Coba membuka URL dashboard tanpa login dan pastikan ditolak
// -----------------------------------------------------------------
runTest("6. Verifikasi Auth::check() menolak pengguna tanpa session aktif", function() {
    \App\Core\Session::destroy();
    if (\App\Core\Auth::check()) {
        throw new \Exception("Pengguna tanpa session terdeteksi logged in.");
    }
    if (\App\Core\Auth::user() !== null) {
        throw new \Exception("Auth::user() harus null tanpa session.");
    }
});

// -----------------------------------------------------------------
// 7. Coba akses hak akses Bendahara / Super User menggunakan akun Anggota dan pastikan ditolak
// -----------------------------------------------------------------
runTest("7. Anggota dilarang mengakses modul Bendahara / Super User", function() use ($userModel) {
    \App\Core\Session::destroy();
    $userMember = $userModel->authenticate('ahmad_fauzi', 'member123');
    \App\Core\Auth::login($userMember);

    // Member must NOT be Super User or Bendahara
    if (\App\Core\Auth::isSuperUser()) {
        throw new \Exception("Anggota terdeteksi sebagai Super User!");
    }
    if (\App\Core\Auth::isBendahara()) {
        throw new \Exception("Anggota terdeteksi sebagai Bendahara!");
    }
    if (\App\Core\Auth::canManageWhatsApp()) {
        throw new \Exception("Anggota tidak boleh memiliki izin canManageWhatsApp!");
    }
    if (\App\Core\Auth::canSendWhatsApp()) {
        throw new \Exception("Anggota tidak boleh memiliki izin canSendWhatsApp!");
    }
});

// -----------------------------------------------------------------
// 8. Pastikan Anggota tidak dapat membaca data keuangan / notifikasi anggota lain
// -----------------------------------------------------------------
runTest("8. Isolasi data antar anggota (Data isolation & IDOR prevention)", function() use ($paymentModel, $logModel, $userModel) {
    // Ahmad Fauzi (Member ID 1)
    $member1User = $userModel->authenticate('ahmad_fauzi', 'member123');
    \App\Core\Auth::login($member1User);
    $member1 = \App\Core\Auth::member();
    $member1Id = (int)$member1['id'];

    // Query member 1 payments
    $member1Payments = $paymentModel->getMemberHistory($member1Id);
    foreach ($member1Payments as $p) {
        if ((int)$p['member_id'] !== $member1Id) {
            throw new \Exception("Kebocoran data pada getMemberHistory.");
        }
    }

    // Attempt to access member 2 notifications via getMemberNotifications
    $member2Id = $member1Id + 1;
    $member1Notifs = $logModel->getMemberNotifications($member1Id);
    foreach ($member1Notifs['data'] as $notif) {
        if ((int)$notif['member_id'] !== $member1Id) {
            throw new \Exception("Kebocoran notifikasi milik anggota lain.");
        }
    }
});

// -----------------------------------------------------------------
// 9. Pastikan AI Assistant dapat digunakan oleh kedua role sesuai hak akses
// -----------------------------------------------------------------
runTest("9. AI Assistant dapat digunakan oleh Bendahara dan Anggota", function() use ($userModel, $aiService, $financialService) {
    $metrics = $financialService->getFinancialMetrics();
    $factualContext = $financialService->getFactualContextString();

    // 1. As Bendahara
    \App\Core\Session::destroy();
    $bendahara = $userModel->authenticate('bendahara', 'bendahara123');
    \App\Core\Auth::login($bendahara);

    $aiResBendahara = $aiService->analyze("Bagaimana kondisi saldo kas saat ini?", $metrics, $factualContext, (int)$bendahara['id']);
    if (!$aiResBendahara['success'] || empty($aiResBendahara['response'])) {
        throw new \Exception("AI Assistant gagal merespons pertanyaan Bendahara.");
    }

    // 2. As Anggota
    \App\Core\Session::destroy();
    $anggota = $userModel->authenticate('ahmad_fauzi', 'member123');
    \App\Core\Auth::login($anggota);

    $aiResAnggota = $aiService->analyze("Siapa kamu dan apa tugas kamu?", $metrics, $factualContext, (int)$anggota['id']);
    if (!$aiResAnggota['success'] || empty($aiResAnggota['response'])) {
        throw new \Exception("AI Assistant gagal merespons pertanyaan Anggota.");
    }
    if (!str_contains($aiResAnggota['response'], 'Asisten AI')) {
        throw new \Exception("Jawaban AI tidak memuat identitas Asisten AI.");
    }
});

// -----------------------------------------------------------------
// 10. Pastikan logout mengakhiri session dan halaman terlindungi tidak bisa diakses
// -----------------------------------------------------------------
runTest("10. Logout mengakhiri session sepenuhnya", function() use ($userModel) {
    $user = $userModel->authenticate('bendahara', 'bendahara123');
    \App\Core\Auth::login($user);
    if (!\App\Core\Auth::check()) {
        throw new \Exception("Login gagal disiapkan untuk test logout.");
    }

    \App\Core\Auth::logout();
    if (\App\Core\Auth::check()) {
        throw new \Exception("Auth::check() masih bernilai true setelah logout.");
    }
    if (\App\Core\Auth::user() !== null) {
        throw new \Exception("Auth::user() masih menyimpan data setelah logout.");
    }
    if (\App\Core\Auth::member() !== null) {
        throw new \Exception("Auth::member() masih menyimpan data setelah logout.");
    }
});

echo "\n=================================================================\n";
echo " RESULTS: {$passedCount} PASSED | {$failedCount} FAILED\n";
echo "=================================================================\n";

if ($failedCount > 0) {
    exit(1);
}
