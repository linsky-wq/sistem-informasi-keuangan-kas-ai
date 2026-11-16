<?php
/**
 * Test Suite: Prompt 46 — Admin Profile & Name Customization Verification
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
echo " PROMPT 46: ADMIN PROFILE & NAME CUSTOMIZATION TEST SUITE        \n";
echo "=================================================================\n\n";

$db = \App\Core\Database::getInstance()->getConnection();
$userModel = new \App\Models\User();
$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

// Helper HTTP Client
class HttpTester {
    private string $cookieJar = '';
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function clearCookies(): void {
        $this->cookieJar = '';
    }

    public function request(string $path, string $method = 'GET', array $data = []): array {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if (!empty($this->cookieJar)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookieJar);
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $rawResponse = (string)curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);

        if (preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headers, $matches)) {
            $cookies = [];
            if (!empty($this->cookieJar)) {
                foreach (explode('; ', $this->cookieJar) as $pair) {
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
            $this->cookieJar = implode('; ', $parts);
        }

        $location = null;
        if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
            $location = trim($m[1]);
        }

        return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $location];
    }

    public function getCsrf(string $html): ?string {
        if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}

$client = new HttpTester($baseUrl);

// -------------------------------------------------------------
// TEST 1: Database Column Check
// -------------------------------------------------------------
echo "[TEST 1] Verifying `nama` column in `users` table...\n";
$colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'nama'")->fetch(PDO::FETCH_ASSOC);
if (!$colCheck) {
    echo "  ✗ Column `nama` missing in `users` table!\n";
    exit(1);
}
echo "  ✓ Column `nama` exists ({$colCheck['Type']}).\n";

// -------------------------------------------------------------
// TEST 2: RBAC Protection for /admin/profile
// -------------------------------------------------------------
echo "\n[TEST 2] Testing RBAC Protection on /admin/profile...\n";
// A. Guest Access
$guestRes = $client->request('admin/profile');
if ($guestRes['code'] === 302 && str_contains($guestRes['location'] ?? '', 'auth/login')) {
    echo "  ✓ Guest access redirected to login (302).\n";
} else {
    echo "  ✗ Guest access allowed unexpectedly! Code: {$guestRes['code']}\n";
    exit(1);
}

// B. Member Access (ahmad_fauzi)
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->getCsrf($loginPage['body']);
$client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'ahmad_fauzi',
    'password'   => 'member123'
]);
$memberProfileAccess = $client->request('admin/profile');
if ($memberProfileAccess['code'] === 403) {
    echo "  ✓ Member access to /admin/profile strictly blocked (403 Forbidden).\n";
} else {
    echo "  ✗ Member was NOT blocked! Code: {$memberProfileAccess['code']}\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 3: Admin Updates Full Name
// -------------------------------------------------------------
echo "\n[TEST 3] Admin Logs in and Updates Display Name to 'Muhammad Pigih'...\n";
$client->clearCookies();
$loginPage = $client->request('auth/login');
$token = $client->getCsrf($loginPage['body']);
$loginRes = $client->request('auth/login', 'POST', [
    'csrf_token' => $token,
    'identifier' => 'admin',
    'password'   => 'admin123'
]);

if ($loginRes['code'] !== 302 || !str_ends_with($loginRes['location'] ?? '', 'dashboard')) {
    echo "  ✗ Admin login failed!\n";
    exit(1);
}
echo "  ✓ Admin logged in successfully.\n";

// Access admin profile page
$profPage = $client->request('admin/profile');
if ($profPage['code'] !== 200) {
    echo "  ✗ Access to /admin/profile failed! Code: {$profPage['code']}\n";
    exit(1);
}
$profCsrf = $client->getCsrf($profPage['body']);
if (!$profCsrf) {
    echo "  ✗ CSRF token missing on admin profile page!\n";
    exit(1);
}
echo "  ✓ /admin/profile loaded with valid CSRF token.\n";

// Save initial password hash to verify it is NOT modified
$adminBefore = $db->query("SELECT id, username, email, password, role, status FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$testNewName = 'Muhammad Pigih';
echo "  • Submitting update: nama = '{$testNewName}' ...\n";
$updateRes = $client->request('admin/profile', 'POST', [
    'csrf_token' => $profCsrf,
    'nama'       => $testNewName
]);

if ($updateRes['code'] === 302 && str_contains($updateRes['location'] ?? '', 'admin/profile')) {
    echo "  ✓ Form submitted and redirected back to admin/profile (302).\n";
} else {
    echo "  ✗ Form submission failed! Code: {$updateRes['code']}, Location: {$updateRes['location']}\n";
    exit(1);
}

// Verify Database State
$adminAfter = $db->query("SELECT id, username, nama, email, password, role, status FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if ($adminAfter['nama'] !== $testNewName) {
    echo "  ✗ Database `nama` mismatch! Found: '{$adminAfter['nama']}', Expected: '{$testNewName}'\n";
    exit(1);
}
echo "  ✓ Database `nama` successfully updated to '{$adminAfter['nama']}'.\n";

// Verify account properties preserved
assert($adminAfter['id'] === $adminBefore['id'], "User ID must not change");
assert($adminAfter['username'] === $adminBefore['username'], "Username must not change");
assert($adminAfter['email'] === $adminBefore['email'], "Email must not change");
assert($adminAfter['role'] === $adminBefore['role'], "Role must not change");
assert($adminAfter['status'] === $adminBefore['status'], "Status must not change");
assert($adminAfter['password'] === $adminBefore['password'], "Password hash must not change");
echo "  ✓ Integrity check passed: ID, username, email, password, role, and status remain UNTOUCHED.\n";

// -------------------------------------------------------------
// TEST 4: Consistent Display Across Navbar, Dashboard, Settings, Profile
// -------------------------------------------------------------
echo "\n[TEST 4] Verifying Consistent Name Display Across Application...\n";

// A. Navbar & Dashboard
$dashRes = $client->request('dashboard');
if (str_contains($dashRes['body'], $testNewName)) {
    echo "  ✓ Dashboard welcome banner & Navbar display '{$testNewName}'.\n";
} else {
    echo "  ✗ Dashboard did not display '{$testNewName}'!\n";
    exit(1);
}

// B. Settings Page
$settingsRes = $client->request('settings');
if (str_contains($settingsRes['body'], $testNewName) && str_contains($settingsRes['body'], 'Profil Administrator')) {
    echo "  ✓ Settings page displays '{$testNewName}' and link to Profil Administrator.\n";
} else {
    echo "  ✗ Settings page did not display '{$testNewName}'!\n";
    exit(1);
}

// C. Profile Page Form Input
$profileFollow = $client->request('admin/profile');
if (str_contains($profileFollow['body'], 'value="' . htmlspecialchars($testNewName) . '"')) {
    echo "  ✓ Admin profile form input field contains '{$testNewName}'.\n";
} else {
    echo "  ✗ Profile form did not display '{$testNewName}' in input field!\n";
    exit(1);
}

// D. AI Assistant ("Siapa saya?")
echo "\n[TEST 5] Testing AI Assistant Response for 'Siapa saya?' with New Name...\n";
$aiService = new \App\Services\AiService();
$financialService = new \App\Services\FinancialDataService();

$adminUserFresh = $userModel->find(1);
$metrics = $financialService->getFinancialMetrics('super_user', 1, null, $adminUserFresh);
$factualContext = $financialService->getFactualContextString('super_user', 1, null, $adminUserFresh);

$aiResult = $aiService->analyze("Siapa saya?", $metrics, $factualContext, 1, 'super_user');
echo "  • AI Response: \"{$aiResult['response']}\"\n";
if (str_contains($aiResult['response'], $testNewName)) {
    echo "  ✓ AI Assistant answered correctly with the new name '{$testNewName}'.\n";
} else {
    echo "  ✗ AI Assistant failed to mention the new name '{$testNewName}'!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 6: Persistence Across Logout & Re-login
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Persistence Across Logout & Re-login...\n";
$logoutRes = $client->request('auth/logout');
echo "  • Logged out.\n";

$loginPage2 = $client->request('auth/login');
$token2 = $client->getCsrf($loginPage2['body']);
$reloginRes = $client->request('auth/login', 'POST', [
    'csrf_token' => $token2,
    'identifier' => 'admin',
    'password'   => 'admin123'
]);

if ($reloginRes['code'] !== 302 || !str_ends_with($reloginRes['location'] ?? '', 'dashboard')) {
    echo "  ✗ Re-login failed!\n";
    exit(1);
}
echo "  ✓ Re-logged in successfully.\n";

$dashAfterRelogin = $client->request('dashboard');
if (str_contains($dashAfterRelogin['body'], $testNewName)) {
    echo "  ✓ After re-login, Dashboard & Navbar still display '{$testNewName}'.\n";
} else {
    echo "  ✗ Name was lost after re-login!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 7: Input Validation on Profile Form
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Input Validation Rules...\n";
$profPage3 = $client->request('admin/profile');
$csrf3 = $client->getCsrf($profPage3['body']);

// Empty name rejected
$emptySub = $client->request('admin/profile', 'POST', [
    'csrf_token' => $csrf3,
    'nama'       => '   '
]);
$followEmpty = $client->request('admin/profile');
if (str_contains($followEmpty['body'], 'tidak boleh kosong')) {
    echo "  ✓ Empty name submission correctly rejected with validation error.\n";
} else {
    echo "  ✗ Empty name was not rejected!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 8: Non-regression for Bendahara & Member Login
// -------------------------------------------------------------
echo "\n[TEST 8] Non-regression Check: Bendahara & Member Logins...\n";
$client->clearCookies();
$lp = $client->request('auth/login');
$tk = $client->getCsrf($lp['body']);
$benLogin = $client->request('auth/login', 'POST', [
    'csrf_token' => $tk,
    'identifier' => 'bendahara',
    'password'   => 'bendahara123'
]);
if ($benLogin['code'] === 302 && str_ends_with($benLogin['location'] ?? '', 'dashboard')) {
    echo "  ✓ Bendahara login works perfectly.\n";
} else {
    echo "  ✗ Bendahara login failed!\n";
    exit(1);
}

$client->clearCookies();
$lp2 = $client->request('auth/login');
$tk2 = $client->getCsrf($lp2['body']);
$memLogin = $client->request('auth/login', 'POST', [
    'csrf_token' => $tk2,
    'identifier' => 'ahmad_fauzi',
    'password'   => 'member123'
]);
if ($memLogin['code'] === 302 && str_contains($memLogin['location'] ?? '', 'member/dashboard')) {
    echo "  ✓ Member login works perfectly.\n";
} else {
    echo "  ✗ Member login failed!\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "✓ ALL PROMPT 46 ADMIN PROFILE TESTS PASSED SUCCESSFULLY!\n";
echo "=================================================================\n";
