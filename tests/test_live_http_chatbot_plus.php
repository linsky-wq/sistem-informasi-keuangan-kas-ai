<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

class ChatbotLiveClient {
    private string $baseUrl;
    private string $cookieFile;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_chatbot_' . uniqid() . '.txt';
    }

    public function __destruct() {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function request(string $path, string $method = 'GET', array $data = [], array $headers = []): array {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => (string)$body];
    }

    public function login(string $username, string $password): bool {
        $page = $this->request('auth/login');
        $csrf = '';
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $page['body'], $m)) {
            $csrf = $m[1];
        }

        $res = $this->request('auth/login', 'POST', [
            'identifier' => $username,
            'password'   => $password,
            'csrf_token' => $csrf,
        ]);

        return !str_contains($res['body'], 'Username atau password salah') && !str_contains($res['body'], 'Silakan masukkan akun Anda');
    }

    public function ask(string $question): array {
        $dash = $this->request('dashboard');
        $csrf = '';
        if (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/i', $dash['body'], $m)) {
            $csrf = $m[1];
        } elseif (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $dash['body'], $m)) {
            $csrf = $m[1];
        }

        $res = $this->request('ai/ask', 'POST', [
            'question'   => $question,
            'csrf_token' => $csrf,
            'ajax'       => '1',
        ], [
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ]);

        $json = json_decode($res['body'], true);
        return [
            'code' => $res['code'],
            'body' => $res['body'],
            'json' => $json,
        ];
    }
}

$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';
$client = new ChatbotLiveClient($baseUrl);

echo "Logging in as Admin...\n";
$loggedIn = $client->login('admin', 'admin123');
if (!$loggedIn) {
    echo "ERROR: Login failed.\n";
    exit(1);
}
echo "Login SUCCESS!\n\n";

$db = \App\Core\Database::getInstance()->getConnection();

$testCases = [
    [
        'question' => 'Ada berapa anggota?',
        'intent'   => 'member_count',
        'db_sql'   => 'SELECT COUNT(*) FROM members',
        'validate' => function($resp, $dbVal) {
            return str_contains($resp, (string)$dbVal) && !str_contains($resp, 'Ringkasan');
        }
    ],
    [
        'question' => 'Siapa saja nama anggota yang terdaftar?',
        'intent'   => 'member_list',
        'db_sql'   => 'SELECT nama FROM members ORDER BY nama ASC',
        'validate' => function($resp, $dbRows) {
            foreach (array_slice($dbRows, 0, 5) as $r) {
                if (!str_contains($resp, $r['nama'])) return false;
            }
            return !str_contains($resp, 'Ringkasan');
        }
    ],
    [
        'question' => 'Berapa jumlah mahasiswa?',
        'intent'   => 'student_count',
        'db_sql'   => 'SELECT COUNT(*) FROM members',
        'validate' => function($resp, $dbVal) {
            return str_contains($resp, (string)$dbVal) && !str_contains($resp, 'Ringkasan');
        }
    ],
    [
        'question' => 'Siapa saja nama mahasiswa?',
        'intent'   => 'student_list',
        'db_sql'   => 'SELECT nama FROM members ORDER BY nama ASC',
        'validate' => function($resp, $dbRows) {
            return str_contains($resp, 'Ahmad Fauzi') && str_contains($resp, 'Budi Santoso') && !str_contains($resp, 'Ringkasan');
        }
    ],
    [
        'question' => 'Ada berapa role dalam sistem?',
        'intent'   => 'role_count',
        'db_sql'   => 'SELECT COUNT(DISTINCT role) FROM users',
        'validate' => function($resp, $dbVal) {
            return str_contains($resp, '4 role') && !str_contains($resp, 'Ringkasan');
        }
    ],
    [
        'question' => 'Berapa total pemasukan kas?',
        'intent'   => 'global_income',
        'db_sql'   => "SELECT SUM(nominal) FROM transactions WHERE tipe = 'pemasukan'",
        'validate' => function($resp, $dbVal) {
            return str_contains($resp, '1.758.000');
        }
    ],
    [
        'question' => 'Berapa total pengeluaran kas?',
        'intent'   => 'global_expense',
        'db_sql'   => "SELECT SUM(nominal) FROM transactions WHERE tipe = 'pengeluaran'",
        'validate' => function($resp, $dbVal) {
            return str_contains($resp, '85.000');
        }
    ],
    [
        'question' => 'Siapa saja mahasiswa yang masih memiliki tunggakan?',
        'intent'   => 'student_unpaid_list',
        'db_sql'   => "SELECT m.nama, SUM(p.tagihan - p.nominal) as t FROM members m JOIN payments p ON m.id = p.member_id GROUP BY m.id HAVING t > 0",
        'validate' => function($resp, $dbRows) {
            return str_contains($resp, 'Ahmad Fauzi') && str_contains($resp, 'Citra Lestari') && str_contains($resp, 'Budi Santoso');
        }
    ],
];

echo "=================================================================\n";
echo " LIVE HTTP /ai/ask CHATBOT TESTING (COMPLETE APPLICATION PIPELINE)\n";
echo "=================================================================\n\n";

$passed = 0; $failed = 0;

foreach ($testCases as $idx => $tc) {
    $num = $idx + 1;
    $q = $tc['question'];
    echo "-----------------------------------------------------------------\n";
    echo "Test #{$num}: '{$q}'\n";

    // Query DB directly
    if (str_starts_with($tc['db_sql'], 'SELECT COUNT') || str_starts_with($tc['db_sql'], 'SELECT SUM')) {
        $dbData = $db->query($tc['db_sql'])->fetchColumn();
    } else {
        $dbData = $db->query($tc['db_sql'])->fetchAll(PDO::FETCH_ASSOC);
    }

    // Call live HTTP endpoint
    $res = $client->ask($q);

    if ($res['code'] !== 200 || !($res['json']['success'] ?? false)) {
        echo "  [FAIL] HTTP Request failed with code {$res['code']}\n";
        echo "  Body: " . $res['body'] . "\n";
        $failed++;
        continue;
    }

    $answer = $res['json']['response'];
    $isValid = $tc['validate']($answer, $dbData);

    if ($isValid) {
        echo "  [PASS] Status 200 OK | Answer matches database query:\n";
        echo "  Preview: " . substr(str_replace("\n", " ", $answer), 0, 120) . "...\n";
        $passed++;
    } else {
        echo "  [FAIL] Answer does not match expected database criteria!\n";
        echo "  Answer: {$answer}\n";
        $failed++;
    }
}

echo "\n=================================================================\n";
echo " LIVE HTTP RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

exit($failed > 0 ? 1 : 0);

