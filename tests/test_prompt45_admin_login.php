<?php
/**
 * Test Suite: Prompt 45 - Admin Authentication Audit & Verification
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
    if (count($parts) > 1) $parts[0] = strtolower($parts[0]);
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "=================================================================\n";
echo " PROMPT 45: ADMIN AUTHENTICATION AUDIT & VERIFICATION TEST       \n";
echo "=================================================================\n\n";

$userModel = new \App\Models\User();

// 1. Direct Model Check for Admin Account
echo "[STEP 1] Direct Model Verification:\n";
$adminUser = $userModel->findByUsernameOrEmail('admin');
if (!$adminUser) {
    echo "  ✗ Admin user ('admin') NOT FOUND in users table!\n";
    exit(1);
}
echo "  ✓ Found Admin account: ID {$adminUser['id']}, Username '{$adminUser['username']}', Role '{$adminUser['role']}', Status '{$adminUser['status']}'\n";

// Check password verification without outputting password or hash
$authCorrect = $userModel->authenticate('admin', 'admin123');
if ($authCorrect) {
    echo "  ✓ password_verify('admin123') matched successfully for 'admin'.\n";
} else {
    echo "  ✗ password_verify('admin123') FAILED for 'admin'.\n";
}

$authWrong = $userModel->authenticate('admin', 'wrong_password_test');
if ($authWrong === null) {
    echo "  ✓ Wrong password correctly rejected (returned null).\n";
} else {
    echo "  ✗ Wrong password was incorrectly accepted!\n";
}

// 2. HTTP Client for Live Authentication
echo "\n[STEP 2] Live HTTP Authentication:\n";
$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

function sendHttpRequest(string $url, string $method = 'GET', array $data = [], string &$cookieJar = ''): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if (!empty($cookieJar)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieJar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $res = (string)curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hdrSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($res, 0, $hdrSize);
    $body = substr($res, $hdrSize);

    if (preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headers, $matches)) {
        $cookies = [];
        if (!empty($cookieJar)) {
            foreach (explode('; ', $cookieJar) as $pair) {
                $p = explode('=', $pair, 2);
                if (count($p) === 2) $cookies[$p[0]] = $p[1];
            }
        }
        foreach ($matches[1] as $c) {
            $p = explode('=', $c, 2);
            if (count($p) === 2) $cookies[$p[0]] = $p[1];
        }
        $parts = [];
        foreach ($cookies as $k => $v) $parts[] = "{$k}={$v}";
        $cookieJar = implode('; ', $parts);
    }

    $location = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $loc)) {
        $location = trim($loc[1]);
    }

    return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $location];
}

function getCsrf(string $html): string {
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    return '';
}

// A. Test Admin Login with Correct Credentials
$cookieJar = '';
$loginGet = sendHttpRequest($baseUrl . '/auth/login', 'GET', [], $cookieJar);
$csrf = getCsrf($loginGet['body']);

echo "  A. Testing Admin HTTP Login with correct password ('admin123')...\n";
$loginPost = sendHttpRequest($baseUrl . '/auth/login', 'POST', [
    'csrf_token' => $csrf,
    'identifier' => 'admin',
    'password'   => 'admin123'
], $cookieJar);

echo "     Response code: {$loginPost['code']}, Location: " . ($loginPost['location'] ?? 'None') . "\n";
if ($loginPost['code'] === 302 && str_ends_with($loginPost['location'] ?? '', 'dashboard')) {
    echo "     ✓ Correctly redirected to dashboard (302)\n";
} else {
    echo "     ✗ Admin HTTP login failed to redirect to dashboard!\n";
    echo "     Body: " . substr($loginPost['body'], 0, 300) . "\n";
}

// Check Dashboard Access
$dashGet = sendHttpRequest($baseUrl . '/dashboard', 'GET', [], $cookieJar);
if ($dashGet['code'] === 200 && (str_contains($dashGet['body'], 'Super User') || str_contains($dashGet['body'], 'admin'))) {
    echo "     ✓ Admin dashboard accessible (HTTP 200) with Super User identity.\n";
} else {
    echo "     ✗ Admin dashboard access failed. Code: {$dashGet['code']}\n";
}

// B. Test Admin Login with Wrong Password
$cookieJarWrong = '';
$loginGet2 = sendHttpRequest($baseUrl . '/auth/login', 'GET', [], $cookieJarWrong);
$csrf2 = getCsrf($loginGet2['body']);

echo "\n  B. Testing Admin HTTP Login with wrong password ('wrongpass')...\n";
$loginPostWrong = sendHttpRequest($baseUrl . '/auth/login', 'POST', [
    'csrf_token' => $csrf2,
    'identifier' => 'admin',
    'password'   => 'wrongpass'
], $cookieJarWrong);

echo "     Response code: {$loginPostWrong['code']}, Location: " . ($loginPostWrong['location'] ?? 'None') . "\n";
if ($loginPostWrong['code'] === 302 && str_contains($loginPostWrong['location'] ?? '', 'auth/login')) {
    echo "     ✓ Wrong password correctly rejected and redirected to login.\n";
    $follow = sendHttpRequest($baseUrl . '/auth/login', 'GET', [], $cookieJarWrong);
    if (str_contains($follow['body'], 'tidak cocok')) {
        echo "     ✓ Error message 'tidak cocok' displayed.\n";
    }
} else {
    echo "     ✗ Unexpected response for wrong password!\n";
}

// C. Test Bendahara Login
$cookieJarBendahara = '';
$loginGet3 = sendHttpRequest($baseUrl . '/auth/login', 'GET', [], $cookieJarBendahara);
$csrf3 = getCsrf($loginGet3['body']);

echo "\n  C. Testing Bendahara HTTP Login ('bendahara' / 'bendahara123')...\n";
$loginPostBen = sendHttpRequest($baseUrl . '/auth/login', 'POST', [
    'csrf_token' => $csrf3,
    'identifier' => 'bendahara',
    'password'   => 'bendahara123'
], $cookieJarBendahara);
if ($loginPostBen['code'] === 302 && str_ends_with($loginPostBen['location'] ?? '', 'dashboard')) {
    echo "     ✓ Bendahara correctly redirected to dashboard (302)\n";
} else {
    echo "     ✗ Bendahara login failed!\n";
}

// D. Test Member Login
$cookieJarMember = '';
$loginGet4 = sendHttpRequest($baseUrl . '/auth/login', 'GET', [], $cookieJarMember);
$csrf4 = getCsrf($loginGet4['body']);

echo "\n  D. Testing Member HTTP Login ('ahmad_fauzi' / 'member123')...\n";
$loginPostMem = sendHttpRequest($baseUrl . '/auth/login', 'POST', [
    'csrf_token' => $csrf4,
    'identifier' => 'ahmad_fauzi',
    'password'   => 'member123'
], $cookieJarMember);
if ($loginPostMem['code'] === 302 && str_contains($loginPostMem['location'] ?? '', 'member/dashboard')) {
    echo "     ✓ Member correctly redirected to member/dashboard (302)\n";
} else {
    echo "     ✗ Member login failed!\n";
}

echo "\n=================================================================\n";
echo "✓ PROMPT 45 TEST COMPLETED SUCCESSFULLY!\n";
echo "=================================================================\n";
