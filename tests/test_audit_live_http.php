<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

class ChatbotLiveClient {
    private string $baseUrl;
    private string $cookieFile;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_audit_' . uniqid() . '.txt';
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

$client = new ChatbotLiveClient('http://localhost/Project%20RPL/Project%20Kas');
echo "Logging in as Super User (Admin)...\n";
$loggedIn = $client->login('admin', 'admin123');
if (!$loggedIn) {
    echo "ERROR: Login failed.\n";
    exit(1);
}
echo "Login SUCCESS!\n\n";

$tests = [
    ['cat' => '1. Jumlah anggota', 'q' => 'Ada berapa anggota?', 'check' => '12 orang'],
    ['cat' => '2. Daftar nama anggota', 'q' => 'Siapa saja nama anggota yang terdaftar?', 'check' => 'Ahmad Fauzi'],
    ['cat' => '3. Anggota berdasarkan status', 'q' => 'Siapa anggota yang nonaktif?', 'check' => 'Updated Siswa 1788877732682'],
    ['cat' => '4. Jumlah role', 'q' => 'Ada berapa role dalam sistem?', 'check' => '4 role'],
    ['cat' => '5. Pengguna per role', 'q' => 'Berapa pengguna setiap role dan siapa saja?', 'check' => 'Super User'],
    ['cat' => '6. Jumlah mahasiswa', 'q' => 'Berapa jumlah mahasiswa?', 'check' => '12 orang'],
    ['cat' => '7. Daftar mahasiswa', 'q' => 'Siapa saja nama mahasiswa?', 'check' => 'Ahmad Fauzi'],
    ['cat' => '8. Tagihan', 'q' => 'Berapa total tagihan seluruh mahasiswa?', 'check' => '1.300.000'],
    ['cat' => '9. Pelunasan', 'q' => 'Siapa saja mahasiswa yang sudah lunas?', 'check' => 'Putri Hima'],
    ['cat' => '9b. Verifikasi Pembayaran', 'q' => 'Berapa pembayaran yang sudah diverifikasi?', 'check' => '17 transaksi'],
    ['cat' => '10. Tunggakan', 'q' => 'Siapa saja mahasiswa yang masih memiliki tunggakan?', 'check' => 'Ahmad Fauzi'],
    ['cat' => '11. Pemasukan', 'q' => 'Berapa total pemasukan kas?', 'check' => '1.758.000'],
    ['cat' => '12. Pengeluaran', 'q' => 'Berapa total pengeluaran kas?', 'check' => '85.000'],
    ['cat' => '13. Saldo', 'q' => 'Berapa saldo kas berdasarkan data terbaru?', 'check' => '1.673.000'],
    ['cat' => '14. Berdasarkan nama', 'q' => 'Pemasukan Ahmad Fauzi ada berapa?', 'check' => '803.000'],
    ['cat' => '15. Berdasarkan periode', 'q' => 'Berapa pemasukan tanggal 21 Oktober 2026?', 'check' => '158.000'],
    ['cat' => '16. Percakapan lanjutan', 'q' => 'Kalau pengeluarannya?', 'check' => '0,00'],
];

$passed = 0;
foreach ($tests as $i => $t) {
    $num = $i + 1;
    echo "Test #{$num} ({$t['cat']}): \"{$t['q']}\"\n";
    $res = $client->ask($t['q']);
    $ans = $res['json']['response'] ?? ($res['json']['answer'] ?? '');
    if ($res['code'] === 200 && str_contains($ans, $t['check'])) {
        echo "  [PASS] Status 200 | Preview: " . str_replace("\n", " ", substr($ans, 0, 100)) . "...\n";
        $passed++;
    } else {
        echo "  [FAIL] Expected containing '{$t['check']}' | Body: " . substr($ans ?: $res['body'], 0, 200) . "\n";
    }
}

echo "\nLIVE HTTP RESULTS: {$passed} / " . count($tests) . " PASSED\n";
if ($passed < count($tests)) exit(1);
