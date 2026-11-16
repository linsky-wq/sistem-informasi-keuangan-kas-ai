<?php
/**
 * Live HTTP Authentication & RBAC Integration Test Suite
 */

declare(strict_types=1);

$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

echo "=================================================================\n";
echo " LIVE HTTP AUTHENTICATION & RBAC TEST SUITE                      \n";
echo "=================================================================\n\n";

class HttpClient {
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
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
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
            'code'         => $httpCode,
            'headers'      => $headers,
            'body'         => $body,
            'location'     => $location,
            'effectiveUrl' => $effectiveUrl,
        ];
    }

    public function extractCsrfToken(string $html): ?string {
        if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}

$client = new HttpClient($baseUrl);

// TEST 1: Guest accessing protected dashboard
echo "[TEST 1] Guest access to /dashboard ... ";
$res = $client->request('dashboard');
if ($res['code'] === 302 && str_contains($res['location'] ?? '', 'auth/login')) {
    echo "✓ REDIRECTED to login (302)\n";
} else {
    echo "✗ FAILED. Code: {$res['code']}, Location: {$res['location']}\n";
    exit(1);
}

// TEST 2: Bendahara Login
echo "[TEST 2] Bendahara Login (bendahara / bendahara123) ... ";
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);
if (!$token) {
    echo "✗ Failed to extract CSRF token\n";
    exit(1);
}

$loginRes = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'bendahara',
    'password'   => 'bendahara123',
]);

if ($loginRes['code'] === 302 && (str_ends_with($loginRes['location'] ?? '', 'dashboard') || str_contains($loginRes['location'] ?? '', '/dashboard'))) {
    echo "✓ Successfully redirected to dashboard (302)\n";
} else {
    echo "✗ Login failed. Code: {$loginRes['code']}, Location: {$loginRes['location']}\n";
    exit(1);
}

// TEST 3: Bendahara accesses dashboard & features
echo "[TEST 3] Bendahara accessing /dashboard & /transactions ... ";
$dashRes = $client->request('dashboard');
if ($dashRes['code'] === 200 && (str_contains($dashRes['body'], 'Mode Bendahara') || str_contains($dashRes['body'], 'Dashboard Bendahara'))) {
    echo "✓ Dashboard 200 OK (Mode Bendahara confirmed)\n";
} else {
    echo "✗ Dashboard failed. Code: {$dashRes['code']}\n";
    exit(1);
}

$trxRes = $client->request('transactions');
if ($trxRes['code'] === 200 && str_contains($trxRes['body'], 'Buku Kas')) {
    echo "  ✓ Transactions 200 OK\n";
} else {
    echo "  ✗ Transactions failed. Code: {$trxRes['code']}\n";
    exit(1);
}

// TEST 4: Bendahara Logout
echo "[TEST 4] Bendahara Logout ... ";
$logoutRes = $client->request('auth/logout');
if ($logoutRes['code'] === 302) {
    echo "✓ Successfully logged out (302)\n";
} else {
    echo "✗ Logout failed. Code: {$logoutRes['code']}\n";
    exit(1);
}

// TEST 5: Member Login
echo "[TEST 5] Member Login (ahmad_fauzi / member123) ... ";
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);

$loginMember = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'ahmad_fauzi',
    'password'   => 'member123',
]);

if ($loginMember['code'] === 302 && str_contains($loginMember['location'] ?? '', 'member/dashboard')) {
    echo "✓ Successfully redirected to member/dashboard (302)\n";
} else {
    echo "✗ Member login redirect failed. Code: {$loginMember['code']}, Location: {$loginMember['location']}\n";
    exit(1);
}

// TEST 6: Member accesses member/dashboard
echo "[TEST 6] Member accessing /member/dashboard ... ";
$memberDash = $client->request('member/dashboard');
if ($memberDash['code'] === 200 && str_contains($memberDash['body'], 'Ahmad Fauzi')) {
    echo "✓ Member Dashboard 200 OK (Ahmad Fauzi confirmed)\n";
} else {
    echo "✗ Member dashboard failed. Code: {$memberDash['code']}\n";
    exit(1);
}

// TEST 7: Member blocked from admin modules (transactions)
echo "[TEST 7] Member accessing /transactions (Super User/Bendahara module) ... ";
$memberTrx = $client->request('transactions');
if ($memberTrx['code'] === 403 && str_contains($memberTrx['body'], 'Akses Ditolak')) {
    echo "✓ Strictly REJECTED with 403 Forbidden\n";
} else {
    echo "✗ Member was not blocked! Code: {$memberTrx['code']}\n";
    exit(1);
}

// TEST 8: Member accessing /dashboard is redirected to member/dashboard
echo "[TEST 8] Member accessing /dashboard ... ";
$memberSuperDash = $client->request('dashboard');
if ($memberSuperDash['code'] === 302 && str_contains($memberSuperDash['location'] ?? '', 'member/dashboard')) {
    echo "✓ Gracefully redirected to member/dashboard\n";
} else {
    echo "✗ Redirection failed. Code: {$memberSuperDash['code']}\n";
    exit(1);
}

// TEST 9: Member accessing AI Assistant
echo "[TEST 9] Member accessing /ai ... ";
$memberAi = $client->request('ai');
if ($memberAi['code'] === 200 && str_contains($memberAi['body'], 'AI')) {
    echo "✓ AI Assistant 200 OK for member\n";
} else {
    echo "✗ Member AI access failed. Code: {$memberAi['code']}\n";
    exit(1);
}

// TEST 10: Wrong password rejected
echo "[TEST 10] Wrong password rejected ... ";
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->extractCsrfToken($loginPage['body']);

$wrongLogin = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'bendahara',
    'password'   => 'passwordsalah123',
]);

if ($wrongLogin['code'] === 302 && str_contains($wrongLogin['location'] ?? '', 'auth/login')) {
    echo "✓ Login rejected and redirected back to login\n";
} else {
    echo "✗ Wrong login response unexpected. Code: {$wrongLogin['code']}\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "✓ ALL LIVE HTTP AUTH & RBAC TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
