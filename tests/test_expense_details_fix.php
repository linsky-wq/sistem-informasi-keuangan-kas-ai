<?php
/**
 * Test Suite: VERIFIKASI PERBAIKAN CHATBOT++ RINCIAN PENGELUARAN KAS
 * 
 * Menguji 5 Skenario Wajib + Variasi Bahasa Alami:
 * 1. "Berapa total pengeluaran kas saat ini?"
 * 2. "Pengeluarannya digunakan untuk apa aja?"
 * 3. "Sebutkan tanggal, keterangan, dan nominal setiap pengeluaran."
 * 4. "Pengeluaran bulan ini untuk apa saja?"
 * 5. Satu pertanyaan yang benar-benar di luar konteks ("Siapa presiden Amerika Serikat?")
 * Plus variasi:
 * - "Pengeluaran itu untuk apa?"
 * - "Uang kas dipakai untuk apa saja?"
 * - "Rincian pengeluaran bulan ini apa saja?"
 * - "Sebutkan keterangan setiap pengeluaran."
 * - "Pengeluaran terbesar digunakan untuk apa?"
 * Plus Live HTTP test ke endpoint /ai/ask (Validasi JSON dan response).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

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
use App\Core\Database;

echo "=================================================================\n";
echo " TEST SUITE: CHATBOT++ PERBAIKAN RINCIAN PENGELUARAN KAS         \n";
echo "=================================================================\n\n";

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT t.id, t.kode_transaksi, t.tanggal, t.nominal, t.keterangan, c.nama as kategori FROM transactions t LEFT JOIN categories c ON t.category_id = c.id WHERE t.tipe = 'pengeluaran' ORDER BY t.tanggal DESC, t.id DESC");
$dbExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalExpenseDb = (float)$db->query("SELECT SUM(nominal) FROM transactions WHERE tipe = 'pengeluaran'")->fetchColumn();
$formattedTotalExpense = number_format($totalExpenseDb, 2, ',', '.');

echo "Database Info:\n";
echo "- Total Pengeluaran DB: Rp {$formattedTotalExpense}\n";
echo "- Jumlah Transaksi Pengeluaran: " . count($dbExpenses) . "\n";
if (!empty($dbExpenses)) {
    echo "- Contoh Keterangan: \"{$dbExpenses[0]['keterangan']}\"\n";
    echo "- Kategori: {$dbExpenses[0]['kategori']}\n";
}
echo "-----------------------------------------------------------------\n\n";

$financialService = new FinancialDataService();
$aiService = new AiService($financialService);

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
        echo "  Error: " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// -----------------------------------------------------------------------------
// PENGUJIAN 1: "Berapa total pengeluaran kas saat ini?"
// -----------------------------------------------------------------------------
runTest("1. 'Berapa total pengeluaran kas saat ini?' — Menjawab nominal total pengeluaran akurat", function() use ($aiService, $formattedTotalExpense) {
    $res = $aiService->ask("Berapa total pengeluaran kas saat ini?", 1, 'super_user');
    if (!str_contains($res['response'], $formattedTotalExpense)) {
        throw new \Exception("Jawaban tidak memuat total pengeluaran Rp {$formattedTotalExpense}. Got: " . $res['response']);
    }
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak sebagai di luar tugas utama.");
    }
});

// -----------------------------------------------------------------------------
// PENGUJIAN 2: "Pengeluarannya digunakan untuk apa aja?"
// -----------------------------------------------------------------------------
runTest("2. 'Pengeluarannya digunakan untuk apa aja?' — TIDAK ditolak & memuat rincian keterangan", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Pengeluarannya digunakan untuk apa aja?", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan 'Pengeluarannya digunakan untuk apa aja?' salah ditolak sebagai di luar tugas utama!");
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan aktual transaksi '{$dbExpenses[0]['keterangan']}'. Got: " . $res['response']);
    }
    if (!str_contains($res['response'], '85.000')) {
        throw new \Exception("Jawaban tidak memuat nominal transaksi. Got: " . $res['response']);
    }
});

// -----------------------------------------------------------------------------
// PENGUJIAN 3: "Sebutkan tanggal, keterangan, dan nominal setiap pengeluaran."
// -----------------------------------------------------------------------------
runTest("3. 'Sebutkan tanggal, keterangan, dan nominal setiap pengeluaran.' — Memuat tanggal, keterangan, nominal", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Sebutkan tanggal, keterangan, dan nominal setiap pengeluaran.", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], '03 Sep 2026')) {
        throw new \Exception("Jawaban tidak memuat tanggal transaksi. Got: " . $res['response']);
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan transaksi. Got: " . $res['response']);
    }
    if (!str_contains($res['response'], '85.000')) {
        throw new \Exception("Jawaban tidak memuat nominal transaksi. Got: " . $res['response']);
    }
});

// -----------------------------------------------------------------------------
// PENGUJIAN 4: "Pengeluaran bulan ini untuk apa saja?"
// -----------------------------------------------------------------------------
runTest("4. 'Pengeluaran bulan ini untuk apa saja?' — Filter periode benar & jujur", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Pengeluaran bulan ini untuk apa saja?", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan transaksi database. Got: " . $res['response']);
    }
});

// -----------------------------------------------------------------------------
// PENGUJIAN 5: Satu pertanyaan yang benar-benar di luar konteks
// -----------------------------------------------------------------------------
runTest("5. Pertanyaan off-topic ('Siapa presiden Amerika Serikat?') — Tetap ditolak sesuai batasan konteks", function() use ($aiService) {
    $res = $aiService->ask("Siapa presiden Amerika Serikat?", 1, 'super_user');
    if (!str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan di luar sistem harus ditolak dengan penjelasan tugas utama. Got: " . $res['response']);
    }
});

// -----------------------------------------------------------------------------
// PENGUJIAN VARIASI BAHASA ALAMI
// -----------------------------------------------------------------------------
runTest("6a. Variasi: 'Pengeluaran itu untuk apa?'", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Pengeluaran itu untuk apa?", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan transaksi. Got: " . $res['response']);
    }
});

runTest("6b. Variasi: 'Uang kas dipakai untuk apa saja?'", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Uang kas dipakai untuk apa saja?", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan transaksi. Got: " . $res['response']);
    }
});

runTest("6c. Variasi: 'Rincian pengeluaran bulan ini apa saja?'", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Rincian pengeluaran bulan ini apa saja?", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan transaksi. Got: " . $res['response']);
    }
});

runTest("6d. Variasi: 'Sebutkan keterangan setiap pengeluaran.'", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Sebutkan keterangan setiap pengeluaran.", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan transaksi. Got: " . $res['response']);
    }
});

runTest("6e. Variasi: 'Pengeluaran terbesar digunakan untuk apa?'", function() use ($aiService, $dbExpenses) {
    $res = $aiService->ask("Pengeluaran terbesar digunakan untuk apa?", 1, 'super_user');
    if (str_contains($res['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan salah ditolak!");
    }
    if (!str_contains($res['response'], 'Pengeluaran terbesar') && !str_contains($res['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban tidak memuat keterangan pengeluaran terbesar. Got: " . $res['response']);
    }
});

// -----------------------------------------------------------------------------
// PENGUJIAN LIVE HTTP ENDPOINT /ai/ask (AJAX JSON RESPONSE)
// -----------------------------------------------------------------------------
class TestHttpClient {
    private string $baseUrl;
    private string $cookieJar = '';

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function request(string $path, string $method = 'GET', array $data = [], array $headers = []): array {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TestAgent/ChatbotFix');

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

        return ['code' => $httpCode, 'body' => $body, 'header' => $headerText];
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

    public function getCsrfToken(): string {
        $dash = $this->request('dashboard');
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $dash['body'], $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/i', $dash['body'], $m)) {
            return $m[1];
        }
        return '';
    }
}

$baseUrl = defined('APP_URL') ? APP_URL : 'http://localhost/Project%20RPL/Project%20Kas';
$client = new TestHttpClient($baseUrl);

runTest("7. Live HTTP: Login Admin & AJAX POST /ai/ask 'Pengeluarannya digunakan untuk apa aja?'", function() use ($client, $dbExpenses) {
    if (!$client->login('admin', 'admin123')) {
        throw new \Exception("Gagal login sebagai admin.");
    }

    $csrf = $client->getCsrfToken();
    if (empty($csrf)) {
        throw new \Exception("Gagal mendapatkan token CSRF.");
    }

    $res = $client->request('ai/ask', 'POST', [
        'question'   => 'Pengeluarannya digunakan untuk apa aja?',
        'csrf_token' => $csrf,
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json',
    ]);

    if ($res['code'] !== 200) {
        throw new \Exception("Status HTTP bukan 200: " . $res['code'] . " - Body: " . substr($res['body'], 0, 150));
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || !isset($json['success']) || !$json['success']) {
        throw new \Exception("Respons bukan JSON sukses valid: " . substr($res['body'], 0, 200));
    }

    if (str_contains($json['response'], 'di luar tugas utama')) {
        throw new \Exception("Chatbot menolak pertanyaan sebagai di luar tugas utama!");
    }

    if (!str_contains($json['response'], $dbExpenses[0]['keterangan'])) {
        throw new \Exception("Jawaban JSON tidak memuat keterangan transaksi aktual: " . $json['response']);
    }
});

runTest("8. Live HTTP: AJAX POST /ai/ask off-topic ('Siapa presiden Amerika Serikat?')", function() use ($client) {
    $csrf = $client->getCsrfToken();
    $res = $client->request('ai/ask', 'POST', [
        'question'   => 'Siapa presiden Amerika Serikat?',
        'csrf_token' => $csrf,
    ], [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json',
    ]);

    if ($res['code'] !== 200) {
        throw new \Exception("Status HTTP bukan 200: " . $res['code']);
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || !$json['success']) {
        throw new \Exception("Respons bukan JSON sukses valid: " . $res['body']);
    }

    if (!str_contains($json['response'], 'di luar tugas utama')) {
        throw new \Exception("Pertanyaan off-topic harus ditolak dengan penjelasan tugas utama. Got: " . $json['response']);
    }
});

echo "\n=================================================================\n";
echo " RESULTS: {$testsPassed} PASSED | {$testsFailed} FAILED\n";
echo "=================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
