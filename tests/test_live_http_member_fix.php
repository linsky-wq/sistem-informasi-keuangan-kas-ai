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

echo "=================================================================\n";
echo " LIVE HTTP /ai/ask TESTING: 6 MANDATORY MEMBER STATUS/NAME QUESTIONS\n";
echo "=================================================================\n\n";

$mandatoryQuestions = [
    [
        'q' => 'Ada berapa anggota?',
        'must_contain' => ['12 orang'],
        'must_not_contain' => ['Updated Siswa 1788877732682'],
        'type' => 'COUNT'
    ],
    [
        'q' => 'Siapa anggota yang nonaktif?',
        'must_contain' => ['Updated Siswa 1788877732682'],
        'must_not_contain' => ['Jumlah anggota terdaftar adalah 12 orang', 'Kondisi keuangan kas'],
        'type' => 'NAME / IDENTITAS'
    ],
    [
        'q' => 'Berapa anggota yang nonaktif?',
        'must_contain' => ['1 orang'],
        'must_not_contain' => ['Updated Siswa 1788877732682'],
        'type' => 'COUNT'
    ],
    [
        'q' => 'Siapa saja anggota yang aktif?',
        'must_contain' => ['Ahmad Fauzi', 'Budi Santoso', '11 orang'],
        'must_not_contain' => ['Updated Siswa 1788877732682'],
        'type' => 'DAFTAR NAMA'
    ],
    [
        'q' => 'Sebutkan nama seluruh anggota.',
        'must_contain' => ['Ahmad Fauzi', 'Updated Siswa 1788877732682', '12 orang'],
        'must_not_contain' => ['Kondisi keuangan kas'],
        'type' => 'DAFTAR NAMA'
    ],
    [
        'q' => 'Berapa jumlah anggota aktif?',
        'must_contain' => ['11 orang'],
        'must_not_contain' => ['Ahmad Fauzi', 'Budi Santoso'],
        'type' => 'COUNT'
    ],
];

$passed = 0;
$failed = 0;

foreach ($mandatoryQuestions as $idx => $t) {
    $num = $idx + 1;
    $q = $t['q'];
    echo "-----------------------------------------------------------------\n";
    echo "Test #{$num}: '{$q}' [Tipe: {$t['type']}]\n";

    $res = $client->ask($q);
    $json = $res['json'];
    $reply = $json['response'] ?? ($json['answer'] ?? '');

    $ok = true;
    $errors = [];

    if ($res['code'] !== 200 || empty($reply)) {
        $ok = false;
        $errors[] = "HTTP status " . $res['code'] . " or empty reply";
    }

    foreach ($t['must_contain'] as $mc) {
        if (!str_contains($reply, $mc)) {
            $ok = false;
            $errors[] = "Missing '{$mc}'";
        }
    }

    foreach ($t['must_not_contain'] as $mnc) {
        if (str_contains($reply, $mnc)) {
            $ok = false;
            $errors[] = "Forbidden text found: '{$mnc}'";
        }
    }

    if ($ok) {
        echo "  [PASS] Status 200 OK | Jawaban tepat sasaran:\n";
        echo "  Preview: " . str_replace("\n", " ", substr($reply, 0, 130)) . "...\n";
        $passed++;
    } else {
        echo "  [FAIL] Errors: " . implode(', ', $errors) . "\n";
        echo "  Body: " . substr($reply ?: $res['body'], 0, 250) . "\n";
        $failed++;
    }
}

echo "\n=================================================================\n";
echo " LIVE HTTP RESULTS: {$passed} PASSED | {$failed} FAILED\n";
echo "=================================================================\n";

if ($failed > 0) {
    exit(1);
}

