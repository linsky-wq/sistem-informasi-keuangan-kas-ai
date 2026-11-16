<?php
/**
 * Test Suite: PROMPT 40 — ATUR BATASAN AI ASISTEN UNTUK ADMIN, BENDAHARA, DAN ANGGOTA
 * 
 * Verifikasi 5 Pertanyaan Wajib untuk 3 Role (Admin, Bendahara, Anggota):
 * 1. "Buatkan coding Python untuk menuliskan nama Anda." -> Harus menolak diawali "Mohon maaf karena..."
 * 2. "Buatkan kode Python untuk menghitung total iuran anggota." -> Boleh dijawab karena terkait kas/iuran
 * 3. "Bagaimana cara melihat status pembayaran iuran?" -> Dijawab sesuai fitur dan hak akses masing-masing role
 * 4. "Buatkan puisi tentang persahabatan." -> Harus menolak dengan sopan karena di luar ruang lingkup
 * 5. "Mengapa pembayaran saya belum diverifikasi?" -> Panduan sesuai role dan data aktual
 * 
 * Pengujian Tambahan:
 * - Pertanyaan teknis kas pendukung (error pembayaran, laporan pemasukan tidak muncul, koneksi database)
 * - Restriksi data pribadi: Anggota tidak boleh melihat data pembayaran anggota lain
 * - Pengujian Live HTTP /ai/ask untuk Admin, Bendahara, dan Anggota
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

use App\Services\FinancialDataService;
use App\Services\AiService;
use App\Services\Ai\MockAiProvider;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\Member;

echo "=================================================================\n";
echo " TEST SUITE: PROMPT 40 — BATASAN AI ASISTEN ROLE RBAC           \n";
echo "=================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function runTest(string $description, callable $fn): void {
    global $testsPassed, $testsFailed;
    echo "• Testing: {$description} ... ";
    try {
        $fn();
        echo "[\033[32mPASSED\033[0m]\n";
        $testsPassed++;
    } catch (\Throwable $e) {
        echo "[\033[31mFAILED\033[0m]\n";
        echo "  Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
        $testsFailed++;
    }
}

$financialService = new FinancialDataService();
$mockProvider = new MockAiProvider();
$userModel = new User();
$memberModel = new Member();

// Load actual accounts for testing
$adminUser = $userModel->findByUsernameOrEmail('admin');
$bendaharaUser = $userModel->findByUsernameOrEmail('bendahara');
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
$memberProfile = $memberUser ? $memberModel->findByUserId((int)$memberUser['id']) : null;

$rolesToTest = [
    'admin'     => [
        'name'    => 'Admin / Super User',
        'role'    => 'super_user',
        'user'    => $adminUser,
        'profile' => null,
    ],
    'bendahara' => [
        'name'    => 'Bendahara',
        'role'    => 'bendahara',
        'user'    => $bendaharaUser,
        'profile' => null,
    ],
    'member'    => [
        'name'    => 'Anggota',
        'role'    => 'member',
        'user'    => $memberUser,
        'profile' => $memberProfile,
    ],
];

// =============================================================================
// BAGIAN 1: PENGUJIAN 5 PERTANYAAN WAJIB PADA SETIAP ROLE
// =============================================================================

foreach ($rolesToTest as $roleKey => $roleData) {
    $roleName = $roleData['name'];
    $roleVal = $roleData['role'];
    $userId = $roleData['user'] ? (int)$roleData['user']['id'] : 1;
    $mProfile = $roleData['profile'];

    $metrics = $financialService->getFinancialMetrics($roleVal, $userId, $mProfile);
    $context = $financialService->getFactualContextString($roleVal, $userId, $mProfile);
    $aiService = new AiService($financialService);

    echo "\n--- PENGUJIAN ROLE: {$roleName} ---\n";

    // 1. "Buatkan coding Python untuk menuliskan nama Anda."
    runTest("[{$roleName}] 1. Tolak coding Python nama (Diawali 'Mohon maaf karena...')", function() use ($mockProvider, $context, $metrics) {
        $q = "Buatkan coding Python untuk menuliskan nama Anda.";
        $resp = $mockProvider->generateInsight($q, $context, $metrics);

        // Harus diawali dengan "Mohon maaf karena..."
        if (!str_starts_with($resp, "Mohon maaf karena")) {
            throw new \Exception("Jawaban HARUS diawali dengan 'Mohon maaf karena...'. Diterima: " . substr($resp, 0, 50));
        }

        // Harus menjelaskan batasan AI
        if (!str_contains($resp, 'AI Asisten khusus Sistem Keuangan Kas') && !str_contains($resp, 'Asisten AI Sistem Keuangan Kas')) {
            throw new \Exception("Jawaban harus menegaskan peran AI Asisten Sistem Keuangan Kas.");
        }

        if (!str_contains($resp, 'tidak dapat membantu membuat coding Python umum')) {
            throw new \Exception("Jawaban harus menjelaskan tidak dapat membantu coding Python umum.");
        }

        // Dilarang menghasilkan blok kode python
        if (str_contains($resp, '```python')) {
            throw new \Exception("AI tidak boleh menghasilkan kode program non-kas.");
        }
    });

    // 2. "Buatkan kode Python untuk menghitung total iuran anggota."
    runTest("[{$roleName}] 2. Jawab kode Python hitung iuran anggota (Terkait kas)", function() use ($mockProvider, $context, $metrics) {
        $q = "Buatkan kode Python untuk menghitung total iuran anggota.";
        $resp = $mockProvider->generateInsight($q, $context, $metrics);

        // Tidak boleh menolak
        if (str_starts_with($resp, "Mohon maaf karena")) {
            throw new \Exception("Pertanyaan terkait kas/iuran tidak boleh ditolak.");
        }

        // Harus menyajikan contoh program python
        if (!str_contains($resp, '```python')) {
            throw new \Exception("Harus menyertakan blok kode Python.");
        }

        // Harus menghitung iuran anggota
        if (!str_contains($resp, 'total_iuran') && !str_contains($resp, 'iuran')) {
            throw new \Exception("Kode harus mencakup perhitungan iuran anggota.");
        }

        // Menjelaskan kaitan dengan sistem aplikasi
        if (!str_contains($resp, 'Sistem Keuangan Kas') && !str_contains($resp, 'Pembayaran')) {
            throw new \Exception("Harus menjelaskan bahwa sistem kas telah mengotomatisasi perhitungan ini.");
        }
    });

    // 3. "Bagaimana cara melihat status pembayaran iuran?"
    runTest("[{$roleName}] 3. Cara melihat status pembayaran iuran sesuai fitur role", function() use ($mockProvider, $context, $metrics, $roleKey) {
        $q = "Bagaimana cara melihat status pembayaran iuran?";
        $resp = $mockProvider->generateInsight($q, $context, $metrics);

        // Tidak boleh menolak
        if (str_starts_with($resp, "Mohon maaf karena")) {
            throw new \Exception("Panduan fitur pembayaran tidak boleh ditolak.");
        }

        if ($roleKey === 'member') {
            // Role Anggota: melihat pada menu Pembayaran Saya / Riwayat Pembayaran
            if (!str_contains($resp, 'Pembayaran Saya') && !str_contains($resp, 'Riwayat Pembayaran')) {
                throw new \Exception("Anggota harus diarahkan ke menu 'Pembayaran Saya' atau 'Riwayat Pembayaran'. Got: " . $resp);
            }
            if (!str_contains($resp, 'Dashboard Anggota')) {
                throw new \Exception("Harus menyebutkan Dashboard Anggota.");
            }
            if (!str_contains($resp, 'pribadi') && !str_contains($resp, 'sendiri')) {
                throw new \Exception("Harus menegaskan akses data pribadi.");
            }
        } elseif ($roleKey === 'bendahara') {
            // Role Bendahara: menu Pembayaran, verifikasi, Laporan
            if (!str_contains($resp, 'Pembayaran')) {
                throw new \Exception("Bendahara harus diarahkan ke menu Pembayaran.");
            }
            if (!str_contains($resp, 'verifikasi') && !str_contains($resp, 'Verifikasi')) {
                throw new \Exception("Bendahara harus mendapatkan panduan verifikasi pembayaran.");
            }
            if (!str_contains($resp, 'Laporan') && !str_contains($resp, 'Rekap Iuran')) {
                throw new \Exception("Bendahara harus diarahkan ke Laporan Rekap Iuran Anggota.");
            }
        } else {
            // Role Admin: wewenang penuh pengawasan dan verifikasi
            if (!str_contains($resp, 'Pembayaran')) {
                throw new \Exception("Admin harus diarahkan ke menu Pembayaran.");
            }
            if (!str_contains($resp, 'Laporan') && !str_contains($resp, 'Rekap Iuran')) {
                throw new \Exception("Admin harus diarahkan ke Laporan Rekap Iuran Anggota.");
            }
        }
    });

    // 4. "Buatkan puisi tentang persahabatan."
    runTest("[{$roleName}] 4. Tolak puisi tentang persahabatan (Diawali 'Mohon maaf karena...')", function() use ($mockProvider, $context, $metrics) {
        $q = "Buatkan puisi tentang persahabatan.";
        $resp = $mockProvider->generateInsight($q, $context, $metrics);

        // Harus diawali dengan "Mohon maaf karena..."
        if (!str_starts_with($resp, "Mohon maaf karena")) {
            throw new \Exception("Jawaban HARUS diawali dengan 'Mohon maaf karena...'. Diterima: " . substr($resp, 0, 50));
        }

        // Harus menyebutkan puisi di luar tugas utama
        if (!str_contains($resp, 'puisi')) {
            throw new \Exception("Penolakan harus menyebutkan puisi secara kontekstual.");
        }

        if (!str_contains($resp, 'di luar tugas utama') && !str_contains($resp, 'tidak dapat membantu membuat puisi')) {
            throw new \Exception("Harus menjelaskan bahwa permintaan di luar tugas utama.");
        }
    });

    // 5. "Mengapa pembayaran saya belum diverifikasi?"
    runTest("[{$roleName}] 5. Panduan pembayaran belum diverifikasi sesuai role & data", function() use ($mockProvider, $context, $metrics, $roleKey) {
        $q = "Mengapa pembayaran saya belum diverifikasi?";
        $resp = $mockProvider->generateInsight($q, $context, $metrics);

        // Tidak boleh menolak
        if (str_starts_with($resp, "Mohon maaf karena")) {
            throw new \Exception("Pertanyaan status verifikasi pembayaran tidak boleh ditolak.");
        }

        if ($roleKey === 'member') {
            // Panduan bagi anggota
            if (!str_contains($resp, 'Bendahara') && !str_contains($resp, 'Super User')) {
                throw new \Exception("Harus menjelaskan verifikasi dilakukan oleh Bendahara atau Super User.");
            }
            if (!str_contains($resp, 'Menunggu Verifikasi')) {
                throw new \Exception("Harus menyebutkan status antrean 'Menunggu Verifikasi'.");
            }
            if (!str_contains($resp, 'bukti') && !str_contains($resp, 'Bukti')) {
                throw new \Exception("Harus memberikan panduan mengenai kelayakan bukti transfer.");
            }
        } elseif ($roleKey === 'bendahara') {
            // Panduan bagi bendahara
            if (!str_contains($resp, 'Bendahara')) {
                throw new \Exception("Harus menjelaskan posisi Bendahara.");
            }
            if (!str_contains($resp, 'Verifikasi') && !str_contains($resp, 'verifikasi')) {
                throw new \Exception("Harus memandu tindakan tombol Verifikasi.");
            }
            if (!str_contains($resp, 'Lihat Bukti') && !str_contains($resp, 'bukti')) {
                throw new \Exception("Harus menginstruksikan pemeriksaan bukti transfer.");
            }
        } else {
            // Panduan bagi Admin
            if (!str_contains($resp, 'Super User') && !str_contains($resp, 'Admin')) {
                throw new \Exception("Harus menjelaskan sudut pandang Admin / Super User.");
            }
            if (!str_contains($resp, 'Pembayaran')) {
                throw new \Exception("Harus merujuk ke menu Pembayaran.");
            }
        }
    });
}

// =============================================================================
// BAGIAN 2: PENGUJIAN PERTANYAAN TEKNIS KAS (BAGIAN 3 PROMPT 40)
// =============================================================================

echo "\n--- PENGUJIAN BANTUAN TEKNIS KAS ---\n";

$adminMetrics = $financialService->getFinancialMetrics('super_user');
$adminContext = $financialService->getFactualContextString('super_user');

runTest("Teknis 1: 'Bagaimana memperbaiki error pembayaran di aplikasi ini?'", function() use ($mockProvider, $adminContext, $adminMetrics) {
    $q = "Bagaimana memperbaiki error pembayaran di aplikasi ini?";
    $resp = $mockProvider->generateInsight($q, $adminContext, $adminMetrics);

    if (str_starts_with($resp, "Mohon maaf karena")) {
        throw new \Exception("Pertanyaan error pembayaran tidak boleh ditolak.");
    }
    if (!str_contains($resp, 'JPG') || !str_contains($resp, '2 MB')) {
        throw new \Exception("Harus menyertakan pengecekan format dan ukuran bukti file.");
    }
    if (!str_contains($resp, 'CSRF') && !str_contains($resp, 'login')) {
        throw new \Exception("Harus menyertakan pengecekan sesi login dan CSRF.");
    }
});

runTest("Teknis 2: 'Mengapa laporan pemasukan tidak muncul?'", function() use ($mockProvider, $adminContext, $adminMetrics) {
    $q = "Mengapa laporan pemasukan tidak muncul?";
    $resp = $mockProvider->generateInsight($q, $adminContext, $adminMetrics);

    if (str_starts_with($resp, "Mohon maaf karena")) {
        throw new \Exception("Pertanyaan laporan pemasukan tidak muncul tidak boleh ditolak.");
    }
    if (!str_contains($resp, 'Rentang Tanggal') && !str_contains($resp, 'Periode')) {
        throw new \Exception("Harus menyertakan troubleshooting filter rentang tanggal.");
    }
    if (!str_contains($resp, 'Kategori')) {
        throw new \Exception("Harus menyertakan troubleshooting filter kategori.");
    }
});

runTest("Teknis 3: 'Bagaimana cara memperbaiki koneksi database aplikasi ini?'", function() use ($mockProvider, $adminContext, $adminMetrics) {
    $q = "Bagaimana cara memperbaiki koneksi database aplikasi ini?";
    $resp = $mockProvider->generateInsight($q, $adminContext, $adminMetrics);

    if (str_starts_with($resp, "Mohon maaf karena")) {
        throw new \Exception("Pertanyaan koneksi database tidak boleh ditolak.");
    }
    if (!str_contains($resp, 'MySQL') || !str_contains($resp, 'XAMPP')) {
        throw new \Exception("Harus menyertakan panduan status MySQL XAMPP.");
    }
    if (!str_contains($resp, '.env') || !str_contains($resp, 'db_kas_ai')) {
        throw new \Exception("Harus menyertakan panduan pengecekan file .env.");
    }
});

// =============================================================================
// BAGIAN 3: PENGUJIAN BATASAN PRIVASI ANGGOTA (BAGIAN 4 PROMPT 40)
// =============================================================================

echo "\n--- PENGUJIAN BATASAN PRIVASI & HAK AKSES ---\n";

runTest("Privasi: Anggota dilarang mengakses data pembayaran seluruh anggota", function() use ($mockProvider, $financialService, $memberUser, $memberProfile) {
    $memberMetrics = $financialService->getFinancialMetrics('member', (int)$memberUser['id'], $memberProfile);
    $memberContext = $financialService->getFactualContextString('member', (int)$memberUser['id'], $memberProfile);

    $q = "Tampilkan data pembayaran seluruh anggota.";
    $resp = $mockProvider->generateInsight($q, $memberContext, $memberMetrics);

    if (!str_starts_with($resp, "Mohon maaf karena")) {
        throw new \Exception("Anggota harus ditolak saat meminta data seluruh anggota. Got: " . $resp);
    }
    if (!str_contains($resp, 'privasi') && !str_contains($resp, 'pribadi')) {
        throw new \Exception("Penolakan harus menjelaskan alasan privasi data.");
    }
});

runTest("Privasi: Bendahara & Admin BOLEH melihat rekapitulasi pembayaran seluruh anggota", function() use ($mockProvider, $adminContext, $adminMetrics) {
    $q = "Tampilkan data pembayaran seluruh anggota.";
    $resp = $mockProvider->generateInsight($q, $adminContext, $adminMetrics);

    if (str_starts_with($resp, "Mohon maaf karena")) {
        throw new \Exception("Admin dan Bendahara tidak boleh ditolak saat meminta rekap pembayaran anggota.");
    }
    if (!str_contains($resp, 'Total Nilai Tagihan Iuran') || !str_contains($resp, 'Total Sisa Tunggakan')) {
        throw new \Exception("Harus menampilkan data agregat pembayaran anggota.");
    }
});

// =============================================================================
// BAGIAN 4: PENGUJIAN LIVE HTTP ENDPOINT /ai/ask DENGAN RBAC
// =============================================================================

echo "\n--- PENGUJIAN LIVE HTTP /ai/ask UNTUK ADMIN, BENDAHARA, ANGGOTA ---\n";

class Prompt40HttpClient {
    private string $cookieJar = '';
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function clearCookies(): void {
        $this->cookieJar = '';
    }

    public function request(string $path, string $method = 'GET', array $data = [], array $headers = []): array {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
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
        curl_close($ch);

        $headerText = substr((string)$rawResponse, 0, (int)$headerSize);
        $body = substr((string)$rawResponse, (int)$headerSize);

        // Update cookie jar
        preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headerText, $matches);
        if (!empty($matches[1])) {
            $cookies = [];
            if (!empty($this->cookieJar)) {
                foreach (explode('; ', $this->cookieJar) as $c) {
                    $p = explode('=', $c, 2);
                    if (count($p) === 2) $cookies[$p[0]] = $p[1];
                }
            }
            foreach ($matches[1] as $cookieLine) {
                $p = explode('=', $cookieLine, 2);
                if (count($p) === 2) $cookies[$p[0]] = $p[1];
            }
            $pairs = [];
            foreach ($cookies as $k => $v) $pairs[] = "$k=$v";
            $this->cookieJar = implode('; ', $pairs);
        }

        return ['code' => $httpCode, 'body' => $body];
    }

    public function login(string $username, string $password): bool {
        $loginPage = $this->request('auth/login');
        preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $m);
        $csrfToken = $m[1] ?? '';

        $res = $this->request('auth/login', 'POST', [
            'identifier' => $username,
            'password'   => $password,
            'csrf_token' => $csrfToken,
        ]);

        return $res['code'] === 302;
    }

    public function askAi(string $question): array {
        // Fetch dashboard or member/dashboard to obtain valid CSRF token
        $dash = $this->request('dashboard');
        if ($dash['code'] !== 200) {
            $dash = $this->request('member/dashboard');
        }
        $csrfToken = '';
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $dash['body'], $m)) {
            $csrfToken = $m[1];
        } elseif (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/i', $dash['body'], $m)) {
            $csrfToken = $m[1];
        }

        $res = $this->request('ai/ask', 'POST', [
            'question'   => $question,
            'csrf_token' => $csrfToken,
            'ajax'       => '1',
        ], [
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ]);

        $json = json_decode($res['body'], true);
        return [
            'http_code' => $res['code'],
            'body'      => $res['body'],
            'json'      => $json,
        ];
    }
}

$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';
$client = new Prompt40HttpClient($baseUrl);

runTest("Live HTTP: Role Admin menolak coding Python nama", function() use ($client) {
    $client->clearCookies();
    $ok = $client->login('admin', 'admin123');
    if (!$ok) throw new \Exception("Login admin gagal.");

    $res = $client->askAi("Buatkan coding Python untuk menuliskan nama Anda.");
    if ($res['http_code'] !== 200 || !($res['json']['success'] ?? false)) {
        throw new \Exception("AI ask failed: HTTP " . $res['http_code']);
    }
    $answer = $res['json']['response'];
    if (!str_starts_with($answer, "Mohon maaf karena")) {
        throw new \Exception("Jawaban live admin harus diawali 'Mohon maaf karena...'. Got: " . $answer);
    }
});

runTest("Live HTTP: Role Bendahara menolak puisi", function() use ($client) {
    $client->clearCookies();
    $ok = $client->login('bendahara', 'bendahara123');
    if (!$ok) throw new \Exception("Login bendahara gagal.");

    $res = $client->askAi("Buatkan puisi tentang persahabatan.");
    if ($res['http_code'] !== 200 || !($res['json']['success'] ?? false)) {
        throw new \Exception("AI ask failed: HTTP " . $res['http_code']);
    }
    $answer = $res['json']['response'];
    if (!str_starts_with($answer, "Mohon maaf karena")) {
        throw new \Exception("Jawaban live bendahara harus diawali 'Mohon maaf karena...'. Got: " . $answer);
    }
});

runTest("Live HTTP: Role Anggota melihat status pembayaran iuran pribadi", function() use ($client) {
    $client->clearCookies();
    $ok = $client->login('ahmad_fauzi', 'member123');
    if (!$ok) throw new \Exception("Login anggota gagal.");

    $res = $client->askAi("Bagaimana cara melihat status pembayaran iuran?");
    if ($res['http_code'] !== 200 || !($res['json']['success'] ?? false)) {
        throw new \Exception("AI ask failed: HTTP " . $res['http_code']);
    }
    $answer = $res['json']['response'];
    if (!str_contains($answer, 'Pembayaran Saya') && !str_contains($answer, 'Riwayat Pembayaran')) {
        throw new \Exception("Jawaban live anggota harus mengarahkan ke Pembayaran Saya/Riwayat Pembayaran. Got: " . $answer);
    }
});

runTest("Live HTTP: Role Anggota menolak permintaan data seluruh anggota", function() use ($client) {
    $client->clearCookies();
    $ok = $client->login('ahmad_fauzi', 'member123');
    if (!$ok) throw new \Exception("Login anggota gagal.");

    $res = $client->askAi("Tampilkan data pembayaran seluruh anggota.");
    if ($res['http_code'] !== 200 || !($res['json']['success'] ?? false)) {
        throw new \Exception("AI ask failed: HTTP " . $res['http_code']);
    }
    $answer = $res['json']['response'];
    if (!str_starts_with($answer, "Mohon maaf karena")) {
        throw new \Exception("Anggota harus ditolak saat meminta data seluruh anggota. Got: " . $answer);
    }
});

echo "\n=================================================================\n";
echo " RESULTS: {$testsPassed} PASSED | {$testsFailed} FAILED\n";
echo "=================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
