<?php
/**
 * Test & Verification for Member Account Creation & Authentication
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

$userModel = new \App\Models\User();
$memberModel = new \App\Models\Member();

echo "=================================================================\n";
echo " VERIFIKASI AKUN ANGGOTA & PENGUJIAN LOGIN                       \n";
echo "=================================================================\n\n";

// 1. Cek akun anggota uji 'anggota_uji'
$testUsername = 'anggota_uji';
$testEmail = 'anggota.uji@kas.local';
$testPassword = 'AnggotaUji2026!';

$existingUser = $userModel->findByUsernameOrEmail($testUsername);

if (!$existingUser) {
    echo "[INFO] Membuat akun uji resmi '{$testUsername}' melalui Member::createMemberWithUser()...\n";
    $memberData = [
        'username'       => $testUsername,
        'email'          => $testEmail,
        'password'       => $testPassword,
        'nama'           => 'Anggota Uji Sistem',
        'nis'            => '109999',
        'kelas'          => 'XII RPL Uji',
        'jenis_kelamin'  => 'L',
        'no_hp'          => '081234567890',
        'status'         => 'aktif',
    ];
    $memberId = $memberModel->createMemberWithUser($memberData);
    if (!$memberId) {
        echo "[ERROR] Gagal membuat akun anggota uji.\n";
        exit(1);
    }
    echo "  ✓ Berhasil membuat member ID #{$memberId} dengan user '{$testUsername}'.\n";
} else {
    echo "[INFO] Akun uji '{$testUsername}' sudah tersedia (User ID #{$existingUser['id']}).\n";
}

// 2. Test Authenticate user uji 'anggota_uji'
echo "\n[TEST 1] Autentikasi akun '{$testUsername}' dengan password '{$testPassword}'...\n";
$authUser = $userModel->authenticate($testUsername, $testPassword);
if ($authUser && $authUser['status'] === 'active' && ($authUser['role'] === 'member' || $authUser['role'] === 'anggota')) {
    echo "  ✓ Autentikasi BERHASIL. Role: {$authUser['role']}, Status: {$authUser['status']}.\n";
} else {
    echo "  ✗ Autentikasi GAGAL.\n";
    exit(1);
}

// 3. Test Login Sesi & Load Profil Member
echo "\n[TEST 2] Simulasi Login Sesi & Load Profil Member...\n";
\App\Core\Session::destroy();
\App\Core\Auth::login($authUser);

assert(\App\Core\Auth::check() === true, "Auth::check() harus true");
assert(\App\Core\Auth::isMember() === true, "Auth::isMember() harus true");
assert(\App\Core\Auth::isSuperUser() === false, "Auth::isSuperUser() harus false");
assert(\App\Core\Auth::isBendahara() === false, "Auth::isBendahara() harus false");

$loadedMember = \App\Core\Auth::member();
assert($loadedMember !== null, "Profil member harus otomatis termuat");
echo "  ✓ Sesi aktif sebagai: {$loadedMember['nama']} ({$loadedMember['nomor_anggota']}, {$loadedMember['kelas']}).\n";

// 4. Test Live HTTP Login & Redirection ke Dashboard Anggota
echo "\n[TEST 3] Pengujian Live HTTP Login ke Endpoint...\n";
$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$raw = curl_exec($ch);
curl_close($ch);

preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', (string)$raw, $m);
$csrf = $m[1] ?? '';

preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', (string)$raw, $cookieMatches);
$cookieJar = implode('; ', $cookieMatches[1] ?? []);

$postCh = curl_init();
curl_setopt($postCh, CURLOPT_URL, $baseUrl . '/auth/login');
curl_setopt($postCh, CURLOPT_POST, true);
curl_setopt($postCh, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'identifier' => $testUsername,
    'password'   => $testPassword,
]));
curl_setopt($postCh, CURLOPT_COOKIE, $cookieJar);
curl_setopt($postCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($postCh, CURLOPT_HEADER, true);
$postRaw = curl_exec($postCh);
$httpCode = curl_getinfo($postCh, CURLINFO_HTTP_CODE);
curl_close($postCh);

preg_match('/^Location:\s*(.+)$/mi', (string)$postRaw, $locMatch);
$loc = trim($locMatch[1] ?? '');

if ($httpCode === 302 && str_contains($loc, 'member/dashboard')) {
    echo "  ✓ HTTP Login sukses (302 Redirect -> {$loc}).\n";
} else {
    echo "  ✗ HTTP Login gagal. Code: {$httpCode}, Location: {$loc}\n";
    exit(1);
}

// 5. Test Live HTTP Dashboard Access
echo "\n[TEST 4] Akses Halaman Dashboard Anggota...\n";
preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', (string)$postRaw, $newCookies);
if (!empty($newCookies[1])) {
    $cookieJar = implode('; ', $newCookies[1]);
}

$dashCh = curl_init();
curl_setopt($dashCh, CURLOPT_URL, $baseUrl . '/member/dashboard');
curl_setopt($dashCh, CURLOPT_COOKIE, $cookieJar);
curl_setopt($dashCh, CURLOPT_RETURNTRANSFER, true);
$dashBody = curl_exec($dashCh);
$dashCode = curl_getinfo($dashCh, CURLINFO_HTTP_CODE);
curl_close($dashCh);

if ($dashCode === 200 && str_contains($dashBody, 'Anggota Uji Sistem')) {
    echo "  ✓ Dashboard Anggota berhasil terbuka (HTTP 200 OK, terverifikasi atas nama 'Anggota Uji Sistem').\n";
} else {
    echo "  ✗ Dashboard Anggota gagal dimuat. Code: {$dashCode}\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "✓ SELURUH VERIFIKASI AKUN ANGGOTA BERHASIL!\n";
echo "=================================================================\n";
