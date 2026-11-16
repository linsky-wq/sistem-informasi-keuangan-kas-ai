<?php
/**
 * Test Suite: Prompt 35 — Floating Chat Icon & Widget for AI Assistant
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
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\User;
use App\Core\Auth;
use App\Core\Session;
use App\Controllers\DashboardController;
use App\Controllers\MemberController;
use App\Controllers\HomeController;
use App\Controllers\AuthController;

$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

echo "=================================================================\n";
echo " TEST SUITE: PROMPT 35 — FLOATING CHAT ICON ASISTEN AI           \n";
echo "=================================================================\n\n";

$userModel = new User();
$passed = 0;
$failed = 0;

function runTest(string $description, callable $testFn): void {
    global $passed, $failed;
    echo "• {$description} ... ";
    try {
        $testFn();
        echo "[PASSED]\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "[FAILED]\n";
        echo "  → Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
        $failed++;
    }
}

// HTTP Client for Live Endpoints
class TestHttpClient {
    private string $cookieJar = '';
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function clearCookies(): void {
        $this->cookieJar = '';
    }

    public function request(string $path, string $method = 'GET', array $data = [], array $headers = [], bool $followRedirect = false): array {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TestAgent/1.0');

        if (!empty($this->cookieJar)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookieJar);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $rawResponse = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        $headerText = substr((string)$rawResponse, 0, (int)$headerSize);
        $body = substr((string)$rawResponse, (int)$headerSize);

        // Extract and retain set-cookie
        preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headerText, $matches);
        if (!empty($matches[1])) {
            $cookies = [];
            if (!empty($this->cookieJar)) {
                foreach (explode('; ', $this->cookieJar) as $c) {
                    $parts = explode('=', $c, 2);
                    if (count($parts) === 2) $cookies[$parts[0]] = $parts[1];
                }
            }
            foreach ($matches[1] as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) === 2) {
                    $cookies[$parts[0]] = $parts[1];
                }
            }
            $cookiePairs = [];
            foreach ($cookies as $k => $v) {
                $cookiePairs[] = "$k=$v";
            }
            $this->cookieJar = implode('; ', $cookiePairs);
        }

        return [
            'code' => $httpCode,
            'body' => $body,
            'header' => $headerText,
            'redirect' => $redirectUrl
        ];
    }

    public function extractCsrf(string $html): ?string {
        if (preg_match('/<input[^>]+name="csrf_token"[^>]+value="([^"]+)"/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/i', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}

$client = new TestHttpClient($baseUrl);

// -------------------------------------------------------------
// SECTION 1: Audit Sidebar across all 4 Roles
// -------------------------------------------------------------
runTest("1.1 Sidebar Super User: Menu AI Assistant tidak ada di sidebar", function() use ($userModel) {
    Session::destroy();
    $admin = $userModel->findByUsernameOrEmail('admin');
    Auth::login($admin);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    preg_match('/<aside class="app-sidebar.*?<\/aside>/s', $html, $matches);
    $sidebar = $matches[0] ?? '';

    if (empty($sidebar)) throw new \Exception("Sidebar Super User tidak ditemukan");
    if (str_contains($sidebar, 'AI Assistant') || str_contains($sidebar, 'Kecerdasan Buatan')) {
        throw new \Exception("Menu AI Assistant masih muncul di sidebar Super User");
    }
    if (!str_contains($sidebar, 'Transaksi') || !str_contains($sidebar, 'Laporan') || !str_contains($sidebar, 'Anggota')) {
        throw new \Exception("Menu sidebar Super User lainnya hilang secara tidak sengaja");
    }
});

runTest("1.2 Sidebar Bendahara: Menu AI Assistant tidak ada di sidebar", function() use ($userModel) {
    Session::destroy();
    $bendahara = $userModel->findByUsernameOrEmail('bendahara');
    Auth::login($bendahara);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    preg_match('/<aside class="app-sidebar.*?<\/aside>/s', $html, $matches);
    $sidebar = $matches[0] ?? '';

    if (str_contains($sidebar, 'AI Assistant') || str_contains($sidebar, 'Kecerdasan Buatan')) {
        throw new \Exception("Menu AI Assistant masih muncul di sidebar Bendahara");
    }
    if (!str_contains($sidebar, 'Transaksi Kas') || !str_contains($sidebar, 'Pembayaran & Verifikasi')) {
        throw new \Exception("Menu operasional Bendahara hilang dari sidebar");
    }
});

runTest("1.3 Sidebar Pengawas: Menu AI Assistant tidak ada di sidebar", function() use ($userModel) {
    Session::destroy();
    $pengawas = $userModel->findByUsernameOrEmail('pengawas');
    Auth::login($pengawas);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    preg_match('/<aside class="app-sidebar.*?<\/aside>/s', $html, $matches);
    $sidebar = $matches[0] ?? '';

    if (str_contains($sidebar, 'AI Assistant') || str_contains($sidebar, 'Kecerdasan Buatan')) {
        throw new \Exception("Menu AI Assistant masih muncul di sidebar Pengawas");
    }
    if (!str_contains($sidebar, 'Laporan Keuangan')) {
        throw new \Exception("Menu laporan Pengawas hilang dari sidebar");
    }
});

runTest("1.4 Sidebar Anggota: Menu AI Assistant tidak ada di sidebar", function() use ($userModel) {
    Session::destroy();
    $member = $userModel->findByUsernameOrEmail('ahmad_fauzi');
    Auth::login($member);

    ob_start();
    (new MemberController())->dashboard();
    $html = ob_get_clean();

    preg_match('/<aside class="app-sidebar.*?<\/aside>/s', $html, $matches);
    $sidebar = $matches[0] ?? '';

    if (str_contains($sidebar, 'AI Assistant') || str_contains($sidebar, 'Kecerdasan Buatan')) {
        throw new \Exception("Menu AI Assistant masih muncul di sidebar Anggota");
    }
    if (!str_contains($sidebar, 'Pembayaran Saya') || !str_contains($sidebar, 'Riwayat Pembayaran')) {
        throw new \Exception("Menu anggota hilang dari sidebar");
    }
});

// -------------------------------------------------------------
// SECTION 2: Floating AI Chat Button & Panel UI Rendering
// -------------------------------------------------------------
runTest("2.1 Floating AI Widget tersedia di halaman Super User", function() use ($userModel) {
    Session::destroy();
    $admin = $userModel->findByUsernameOrEmail('admin');
    Auth::login($admin);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    if (!str_contains($html, 'id="aiFloatingTrigger"')) throw new \Exception("Tombol #aiFloatingTrigger tidak ditemukan");
    if (!str_contains($html, 'id="aiFloatingWindow"')) throw new \Exception("Panel chat #aiFloatingWindow tidak ditemukan");
    if (!str_contains($html, 'id="aiFloatingForm"')) throw new \Exception("Form input #aiFloatingForm tidak ditemukan");
    if (!str_contains($html, 'id="aiFloatingInput"')) throw new \Exception("Input field #aiFloatingInput tidak ditemukan");
    if (!str_contains($html, 'id="aiCloseWindowBtn"')) throw new \Exception("Tombol tutup #aiCloseWindowBtn tidak ditemukan");
});

runTest("2.2 Floating AI Widget tersedia di halaman Bendahara", function() use ($userModel) {
    Session::destroy();
    $bendahara = $userModel->findByUsernameOrEmail('bendahara');
    Auth::login($bendahara);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    if (!str_contains($html, 'id="aiFloatingTrigger"') || !str_contains($html, 'id="aiFloatingWindow"')) {
        throw new \Exception("Floating AI widget tidak dirender di dashboard Bendahara");
    }
});

runTest("2.3 Floating AI Widget tersedia di halaman Pengawas", function() use ($userModel) {
    Session::destroy();
    $pengawas = $userModel->findByUsernameOrEmail('pengawas');
    Auth::login($pengawas);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    if (!str_contains($html, 'id="aiFloatingTrigger"') || !str_contains($html, 'id="aiFloatingWindow"')) {
        throw new \Exception("Floating AI widget tidak dirender di dashboard Pengawas");
    }
});

runTest("2.4 Floating AI Widget tersedia di halaman Anggota", function() use ($userModel) {
    Session::destroy();
    $member = $userModel->findByUsernameOrEmail('ahmad_fauzi');
    Auth::login($member);

    ob_start();
    (new MemberController())->dashboard();
    $html = ob_get_clean();

    if (!str_contains($html, 'id="aiFloatingTrigger"') || !str_contains($html, 'id="aiFloatingWindow"')) {
        throw new \Exception("Floating AI widget tidak dirender di dashboard Anggota");
    }
});

runTest("2.5 Floating AI Widget TIDAK muncul pada Tamu / Halaman Publik", function() {
    Session::destroy();
    Auth::logout();

    ob_start();
    (new HomeController())->index();
    $htmlHome = ob_get_clean();

    if (str_contains($htmlHome, 'id="aiFloatingTrigger"') || str_contains($htmlHome, 'id="aiFloatingWindow"')) {
        throw new \Exception("Floating AI widget tidak boleh muncul di landing page publik");
    }

    ob_start();
    (new AuthController())->login();
    $htmlLogin = ob_get_clean();

    if (str_contains($htmlLogin, 'id="aiFloatingTrigger"') || str_contains($htmlLogin, 'id="aiFloatingWindow"')) {
        throw new \Exception("Floating AI widget tidak boleh muncul di halaman login publik");
    }
});

// -------------------------------------------------------------
// SECTION 3: CSS Specifications, Tokens, Accessibility & Responsiveness
// -------------------------------------------------------------
runTest("3.1 Verifikasi spesifikasi CSS, Posisi Fixed, Z-Index, dan Responsivitas", function() use ($userModel) {
    Session::destroy();
    $admin = $userModel->findByUsernameOrEmail('admin');
    Auth::login($admin);

    ob_start();
    (new DashboardController())->index();
    $html = ob_get_clean();

    if (!str_contains($html, 'position: fixed')) {
        throw new \Exception("CSS 'position: fixed' tidak ditemukan untuk widget floating");
    }
    if (!str_contains($html, 'bottom: 24px') || !str_contains($html, 'right: 24px')) {
        throw new \Exception("Offset posisi (24px) tidak sesuai spesifikasi");
    }
    if (!str_contains($html, 'width: 60px') || !str_contains($html, 'height: 60px')) {
        throw new \Exception("Ukuran trigger (60px) tidak sesuai spesifikasi");
    }
    if (!str_contains($html, 'z-index: 1050')) {
        throw new \Exception("Z-Index 1050 tidak ditemukan untuk menjaga posisi di atas konten");
    }
    if (!str_contains($html, 'aria-label="Buka Asisten AI"')) {
        throw new \Exception("Atribut aksesibilitas aria-label trigger tidak lengkap");
    }
    if (!str_contains($html, 'role="dialog"')) {
        throw new \Exception("Atribut aksesibilitas role='dialog' tidak ditemukan pada window");
    }
    if (!str_contains($html, '@media (max-width: 576px)')) {
        throw new \Exception("Media query responsive mobile tidak ditemukan di widget floating");
    }
    if (!str_contains($html, 'ai-typing-indicator')) {
        throw new \Exception("Indikator animasi typing AI tidak ditemukan");
    }
    if (!str_contains($html, 'ai-suggestion-chip')) {
        throw new \Exception("Quick suggestion chips tidak ditemukan");
    }
});

// -------------------------------------------------------------
// SECTION 4: Live HTTP AJAX Endpoint Integration
// -------------------------------------------------------------
runTest("4.1 Live HTTP: Login Super User dan verifikasi ketiadaan menu sidebar AI & kehadiran floating widget", function() use ($client) {
    $client->clearCookies();

    // 1. Dapatkan login page & CSRF token
    $resLogin = $client->request('auth/login');
    $csrf = $client->extractCsrf($resLogin['body']);
    if (!$csrf) throw new \Exception("Gagal mengekstrak CSRF token dari halaman login");

    // 2. Kirim POST login
    $resPost = $client->request('auth/login', 'POST', [
        'csrf_token' => $csrf,
        'identifier' => 'admin',
        'password' => 'admin123'
    ]);

    // 3. Akses /dashboard
    $resDash = $client->request('dashboard');
    if ($resDash['code'] !== 200) throw new \Exception("HTTP status /dashboard bukan 200: " . $resDash['code']);

    // Verifikasi ketiadaan link sidebar AI dan keberadaan floating trigger
    preg_match('/<aside class="app-sidebar.*?<\/aside>/s', $resDash['body'], $matches);
    $sidebar = $matches[0] ?? '';
    if (str_contains($sidebar, 'AI Assistant') || str_contains($sidebar, 'Kecerdasan Buatan')) {
        throw new \Exception("Menu AI Assistant masih ditemukan di sidebar live HTTP");
    }
    if (!str_contains($resDash['body'], 'id="aiFloatingTrigger"')) {
        throw new \Exception("Tombol floating AI tidak ditemukan pada live HTTP /dashboard");
    }
    if (!str_contains($resDash['body'], 'id="aiFloatingWindow"')) {
        throw new \Exception("Panel floating chat AI tidak ditemukan pada live HTTP /dashboard");
    }
});

runTest("4.2 Live HTTP: AJAX POST /ai/ask untuk pertanyaan identitas ('Siapa kamu?')", function() use ($client) {
    // Ambil CSRF token dari dashboard
    $resDash = $client->request('dashboard');
    $csrf = $client->extractCsrf($resDash['body']);
    if (!$csrf) throw new \Exception("Gagal mengambil CSRF token dari dashboard");

    $resAi = $client->request('ai/ask', 'POST', [
        'csrf_token' => $csrf,
        'question' => 'Siapa kamu dan apa tugas kamu?',
        'ajax' => '1'
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json'
    ]);

    if ($resAi['code'] !== 200) {
        throw new \Exception("HTTP status /ai/ask bukan 200: " . $resAi['code'] . " - " . substr($resAi['body'], 0, 100));
    }

    $json = json_decode($resAi['body'], true);
    if (!is_array($json)) {
        throw new \Exception("Response /ai/ask bukan JSON valid: " . substr($resAi['body'], 0, 120));
    }
    if (!isset($json['success']) || $json['success'] !== true) {
        throw new \Exception("Response success is not true: " . json_encode($json));
    }
    if (!str_contains($json['response'], 'Asisten AI pada Sistem Keuangan Kas')) {
        throw new \Exception("Jawaban identitas AI tidak sesuai: " . $json['response']);
    }
});

runTest("4.3 Live HTTP: AJAX POST /ai/ask untuk pertanyaan saldo kas aktual", function() use ($client) {
    $resDash = $client->request('dashboard');
    $csrf = $client->extractCsrf($resDash['body']);

    $resAi = $client->request('ai/ask', 'POST', [
        'csrf_token' => $csrf,
        'question' => 'Berapa saldo kas saat ini?',
        'ajax' => '1'
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json'
    ]);

    if ($resAi['code'] !== 200) throw new \Exception("HTTP status bukan 200");
    $json = json_decode($resAi['body'], true);
    if (!isset($json['success']) || $json['success'] !== true) {
        throw new \Exception("AI response gagal untuk saldo kas");
    }
    if (!str_contains($json['response'], 'Rp') && !str_contains($json['response'], 'Saldo')) {
        throw new \Exception("AI tidak menyebutkan nominal saldo kas: " . $json['response']);
    }
});

runTest("4.4 Live HTTP: AJAX POST /ai/ask menolak permintaan di luar job desk", function() use ($client) {
    $resDash = $client->request('dashboard');
    $csrf = $client->extractCsrf($resDash['body']);

    $resAi = $client->request('ai/ask', 'POST', [
        'csrf_token' => $csrf,
        'question' => 'Buatkan resep masakan rendang Padang yang lezat.',
        'ajax' => '1'
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json'
    ]);

    if ($resAi['code'] !== 200) throw new \Exception("HTTP status bukan 200");
    $json = json_decode($resAi['body'], true);
    if (!isset($json['success']) || $json['success'] !== true) {
        throw new \Exception("AI response gagal");
    }
    $resp = strtolower($json['response']);
    if (!str_contains($resp, 'maaf karena') && !str_contains($resp, 'maaf')) {
        throw new \Exception("AI tidak menolak permintaan out-of-scope dengan sopan: " . $json['response']);
    }
});

runTest("4.5 Live HTTP: Login Bendahara dan verifikasi floating widget & respons AI", function() use ($client) {
    $client->clearCookies();

    // Login sebagai bendahara
    $resLogin = $client->request('auth/login');
    $csrf = $client->extractCsrf($resLogin['body']);
    $client->request('auth/login', 'POST', [
        'csrf_token' => $csrf,
        'identifier' => 'bendahara',
        'password' => 'bendahara123'
    ]);

    $resDash = $client->request('dashboard');
    if (!str_contains($resDash['body'], 'id="aiFloatingTrigger"')) {
        throw new \Exception("Floating AI widget tidak ada pada dashboard Bendahara via HTTP");
    }

    $csrfDash = $client->extractCsrf($resDash['body']);
    $resAi = $client->request('ai/ask', 'POST', [
        'csrf_token' => $csrfDash,
        'question' => 'Bagaimana kondisi keuangan bulan ini?',
        'ajax' => '1'
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json'
    ]);

    $json = json_decode($resAi['body'], true);
    if (!isset($json['success']) || $json['success'] !== true) {
        throw new \Exception("Bendahara gagal menanyakan kondisi keuangan via AI widget");
    }
});

runTest("4.6 Live HTTP: Login Anggota dan verifikasi floating widget & isolasi data", function() use ($client) {
    $client->clearCookies();

    // Login sebagai anggota (ahmad_fauzi)
    $resLogin = $client->request('auth/login');
    $csrf = $client->extractCsrf($resLogin['body']);
    $client->request('auth/login', 'POST', [
        'csrf_token' => $csrf,
        'identifier' => 'ahmad_fauzi',
        'password' => 'member123'
    ]);

    $resDash = $client->request('member/dashboard');
    if (!str_contains($resDash['body'], 'id="aiFloatingTrigger"')) {
        throw new \Exception("Floating AI widget tidak ada pada dashboard Anggota via HTTP");
    }

    $csrfDash = $client->extractCsrf($resDash['body']);
    $resAi = $client->request('ai/ask', 'POST', [
        'csrf_token' => $csrfDash,
        'question' => 'Bagaimana cara melihat riwayat pembayaran saya?',
        'ajax' => '1'
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json'
    ]);

    $json = json_decode($resAi['body'], true);
    if (!isset($json['success']) || $json['success'] !== true) {
        throw new \Exception("Anggota gagal bertanya panduan via AI widget");
    }
});

runTest("4.7 Live HTTP: Tamu / Unauthenticated user dialihkan saat mengakses /ai/ask", function() use ($client) {
    $client->clearCookies();

    $resAi = $client->request('ai/ask', 'POST', [
        'question' => 'Halo AI',
        'ajax' => '1'
    ]);

    // Should redirect to auth/login (code 302) or reject
    if ($resAi['code'] !== 302 && $resAi['code'] !== 401 && $resAi['code'] !== 403) {
        throw new \Exception("Tamu tidak dialihkan dari /ai/ask (status code: {$resAi['code']})");
    }
});

// -------------------------------------------------------------
// SECTION 5: Existing AI Page & Clear History preserved
// -------------------------------------------------------------
runTest("5.1 Halaman full /ai tetap ada dan dapat diakses", function() use ($client) {
    $client->clearCookies();

    // Login sebagai super user
    $resLogin = $client->request('auth/login');
    $csrf = $client->extractCsrf($resLogin['body']);
    $client->request('auth/login', 'POST', [
        'csrf_token' => $csrf,
        'identifier' => 'admin',
        'password' => 'admin123'
    ]);

    $resAi = $client->request('ai');
    if ($resAi['code'] !== 200) {
        throw new \Exception("Halaman /ai mengembalikan status code {$resAi['code']}");
    }
    if (!str_contains($resAi['body'], 'Asisten AI Keuangan') && !str_contains($resAi['body'], 'AI Assistant')) {
        throw new \Exception("Halaman /ai tidak memuat konten utama AI Assistant");
    }
});

echo "\n=================================================================\n";
echo " RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed > 0) {
    exit(1);
}
