<?php
/**
 * Automated Test Suite: PROMPT 34 — Perbaiki Dashboard Bendahara dan Akses Login Anggota
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

$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

echo "=================================================================\n";
echo " TEST SUITE: PROMPT 34 — DASHBOARD BENDAHARA & LOGIN ANGGOTA     \n";
echo "=================================================================\n\n";

class TestHttpClient {
    private string $cookieJar = '';
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function clearCookies(): void {
        $this->cookieJar = '';
    }

    public function request(string $path, string $method = 'GET', array $data = [], bool $followRedirect = false): array {
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

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr((string)$rawResponse, 0, $headerSize);
        $body = substr((string)$rawResponse, $headerSize);

        // Parse Cookies
        if (preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headers, $matches)) {
            $cookies = [];
            foreach ($matches[1] as $c) {
                $parts = explode('=', $c, 2);
                if (count($parts) === 2) {
                    $cookies[$parts[0]] = $parts[1];
                }
            }
            $existing = [];
            if (!empty($this->cookieJar)) {
                foreach (explode('; ', $this->cookieJar) as $pair) {
                    $p = explode('=', $pair, 2);
                    if (count($p) === 2) $existing[$p[0]] = $p[1];
                }
            }
            $merged = array_merge($existing, $cookies);
            $cookieParts = [];
            foreach ($merged as $k => $v) {
                $cookieParts[] = "{$k}={$v}";
            }
            $this->cookieJar = implode('; ', $cookieParts);
        }

        // Parse Location Header
        $location = null;
        if (preg_match('/^Location:\s*(.+)$/mi', $headers, $locMatch)) {
            $location = trim($locMatch[1]);
        }

        return [
            'code'     => $httpCode,
            'headers'  => $headers,
            'body'     => $body,
            'location' => $location,
        ];
    }

    public function extractCsrfToken(string $html): ?string {
        if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}

$client = new TestHttpClient($baseUrl);
$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $msg = ''): void {
    global $passed, $failed;
    echo "• {$name} ... ";
    if ($condition) {
        echo "[PASSED]\n";
        $passed++;
    } else {
        echo "[FAILED] {$msg}\n";
        $failed++;
    }
}

// -------------------------------------------------------------
// 1. Login Bendahara & Operasional Dashboard Check
// -------------------------------------------------------------
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);

$loginBendahara = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'bendahara',
    'password'   => 'bendahara123',
]);

check("1.1 Login Bendahara redirect ke dashboard", 
    $loginBendahara['code'] === 302 && str_contains($loginBendahara['location'] ?? '', 'dashboard')
);

$bendaharaDash = $client->request('dashboard');
check("1.2 Dashboard Bendahara menampilkan Mode Bendahara (bukan setup)", 
    $bendaharaDash['code'] === 200 && str_contains($bendaharaDash['body'], 'Mode Bendahara') && !str_contains($bendaharaDash['body'], 'Status Setup Task 01')
);

check("1.3 Dashboard Bendahara memuat Ringkasan Saldo Kas & Mutasi", 
    str_contains($bendaharaDash['body'], 'Total Pemasukan') && 
    str_contains($bendaharaDash['body'], 'Total Pengeluaran') && 
    str_contains($bendaharaDash['body'], 'Saldo Kas Bersih')
);

check("1.4 Dashboard Bendahara memuat Tabel Transaksi Kas & Pembayaran Verifikasi", 
    str_contains($bendaharaDash['body'], 'Transaksi Kas Terbaru') && 
    str_contains($bendaharaDash['body'], 'Pembayaran Iuran Kas & Verifikasi')
);

check("1.5 Dashboard Bendahara memuat Widget WhatsApp Logs & AI Assistant", 
    str_contains($bendaharaDash['body'], 'Riwayat Notifikasi WhatsApp') && 
    str_contains($bendaharaDash['body'], 'AI Financial Assistant')
);

// -------------------------------------------------------------
// 2. Akses Root URL (/) saat Bendahara Login diarahkan ke Dashboard
// -------------------------------------------------------------
$homeReq = $client->request('');
check("2.1 Akses URL / saat Bendahara login dialihkan ke operational dashboard", 
    $homeReq['code'] === 302 && str_contains($homeReq['location'] ?? '', 'dashboard')
);

// -------------------------------------------------------------
// 3. Verifikasi Route Menu Bendahara Tidak 404
// -------------------------------------------------------------
$trxReq = $client->request('transactions');
check("3.1 Route /transactions HTTP 200 OK untuk Bendahara", $trxReq['code'] === 200);

$payReq = $client->request('payments');
check("3.2 Route /payments HTTP 200 OK untuk Bendahara", $payReq['code'] === 200);

$repReq = $client->request('reports');
check("3.3 Route /reports HTTP 200 OK untuk Bendahara", $repReq['code'] === 200);

$aiReq = $client->request('ai');
check("3.4 Route /ai HTTP 200 OK untuk Bendahara", $aiReq['code'] === 200);

$waReq = $client->request('whatsapp');
check("3.5 Route /whatsapp HTTP 200 OK untuk Bendahara", $waReq['code'] === 200);

$waLogsReq = $client->request('whatsapp/logs');
check("3.6 Route /whatsapp/logs HTTP 200 OK untuk Bendahara", $waLogsReq['code'] === 200);

// Logout Bendahara
$client->request('auth/logout');

// -------------------------------------------------------------
// 4. Login Anggota & Operasional Member Dashboard Check
// -------------------------------------------------------------
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);

$loginAnggota = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'ahmad_fauzi',
    'password'   => 'member123',
]);

check("4.1 Login Anggota redirect ke member/dashboard", 
    $loginAnggota['code'] === 302 && str_contains($loginAnggota['location'] ?? '', 'member/dashboard')
);

$memberDash = $client->request('member/dashboard');
check("4.2 Dashboard Anggota menampilkan data pribadi Ahmad Fauzi (bukan setup/bendahara)", 
    $memberDash['code'] === 200 && str_contains($memberDash['body'], 'Ahmad Fauzi') && !str_contains($memberDash['body'], 'Mode Bendahara')
);

$homeMemberReq = $client->request('');
check("4.3 Akses URL / saat Anggota login dialihkan ke member/dashboard", 
    $homeMemberReq['code'] === 302 && str_contains($homeMemberReq['location'] ?? '', 'member/dashboard')
);

// -------------------------------------------------------------
// 5. Pembatasan Hak Akses (Anggota dilarang akses fitur bendahara/admin)
// -------------------------------------------------------------
$memberBlockedTrx = $client->request('transactions');
check("5.1 Anggota dilarang akses /transactions (403 Forbidden)", $memberBlockedTrx['code'] === 403);

$memberBlockedRep = $client->request('reports');
check("5.2 Anggota dilarang akses /reports (403 Forbidden)", $memberBlockedRep['code'] === 403);

$memberBlockedAdmin = $client->request('admin/members');
check("5.3 Anggota dilarang akses /admin/members (403 Forbidden)", $memberBlockedAdmin['code'] === 403);

$memberBlockedSettings = $client->request('settings');
check("5.4 Anggota dilarang akses /settings (403 Forbidden)", $memberBlockedSettings['code'] === 403);

// Logout Anggota
$client->request('auth/logout');

// -------------------------------------------------------------
// 6. Super User & Pengawas Login Check
// -------------------------------------------------------------
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);

$loginAdmin = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'admin',
    'password'   => 'admin123',
]);
check("6.1 Super User login berhasil", $loginAdmin['code'] === 302 && str_contains($loginAdmin['location'] ?? '', 'dashboard'));

$client->request('auth/logout');
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);

$loginPengawas = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'pengawas',
    'password'   => 'pengawas123',
]);
check("6.2 Pengawas login berhasil", $loginPengawas['code'] === 302 && str_contains($loginPengawas['location'] ?? '', 'dashboard'));

$pengawasDash = $client->request('dashboard');
check("6.3 Dashboard Pengawas menampilkan Mode Pengawas", $pengawasDash['code'] === 200 && str_contains($pengawasDash['body'], 'Mode Pengawas'));

$client->request('auth/logout');

echo "\n=================================================================\n";
echo " RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed > 0) {
    exit(1);
}
