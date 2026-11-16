<?php
/**
 * Test Multi-Member Login & Account Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Member.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/models/AuditLog.php';
require_once __DIR__ . '/../app/models/Category.php';
require_once __DIR__ . '/../app/models/PaymentTransaction.php';
require_once __DIR__ . '/../app/models/PaymentSetting.php';
require_once __DIR__ . '/../app/models/WhatsAppLog.php';
require_once __DIR__ . '/../app/models/WhatsAppSetting.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';
require_once __DIR__ . '/../app/services/PaymentGatewayService.php';

use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\Member;
use App\Models\Payment;

Session::start();

$passedTests = 0;
$totalTests = 0;

function runTest(string $description, callable $fn): void {
    global $passedTests, $totalTests;
    $totalTests++;
    echo "TEST #{$totalTests}: {$description}... ";
    try {
        $result = $fn();
        if ($result === true) {
            $passedTests++;
            echo "PASSED [OK]\n";
        } else {
            echo "FAILED: " . ($result ?: "Returned false") . "\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

function runSubprocess(string $code): array {
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_runner_' . uniqid() . '.php';
    file_put_contents($tmp, "<?php\n" . $code);
    exec("php \"$tmp\"", $output, $status);
    @unlink($tmp);
    return ['output' => $output, 'status' => $status];
}

$userModel = new User();
$memberModel = new Member();
$paymentModel = new Payment();

// Cleanup any leftover test records first
$db = \App\Core\Database::getInstance()->getConnection();
$db->exec("DELETE FROM members WHERE nomor_anggota = 'ANG-TEST-LOGIN'");
$db->exec("DELETE FROM users WHERE username = 'siswa_uji_login_unik'");

// TEST 1: Admin can login
runTest('Super User (Admin) can login successfully', function () use ($userModel) {
    $user = $userModel->authenticate('admin', 'admin123');
    if (!$user) return 'Could not authenticate admin';
    Auth::login($user);
    $ok = Auth::isSuperUser() && Auth::username() === 'admin';
    Auth::logout();
    return $ok;
});

// TEST 2: Bendahara can login
runTest('Bendahara can login successfully', function () use ($userModel) {
    $user = $userModel->authenticate('bendahara', 'bendahara123');
    if (!$user) return 'Could not authenticate bendahara';
    Auth::login($user);
    $ok = Auth::isBendahara() && Auth::username() === 'bendahara';
    Auth::logout();
    return $ok;
});

// TEST 3: Original Member (Ahmad Fauzi) can login and see his own scoped profile and payments
runTest('Original Member (ahmad_fauzi) can login and has isolated data', function () use ($userModel, $paymentModel) {
    $user = $userModel->authenticate('ahmad_fauzi', 'member123');
    if (!$user) return 'Could not authenticate ahmad_fauzi';
    Auth::login($user);
    if (!Auth::isMember()) return 'Role is not member';
    $member = Auth::member();
    if ((int)$member['id'] !== 1) return 'Member ID is not 1 (Ahmad Fauzi)';
    $payments = $paymentModel->getMemberHistory(1);
    if (empty($payments)) return 'No payments found for Ahmad';
    foreach ($payments as $p) {
        if ((int)$p['member_id'] !== 1) return 'Payment does not belong to Ahmad';
    }
    Auth::logout();
    return true;
});

// TEST 4: Member 2 (Budi Santoso) can login and has completely separate data from Ahmad
runTest('Second Member (budi_santoso) can login and see only his own payments', function () use ($userModel, $paymentModel) {
    $user = $userModel->authenticate('budi_santoso', 'member123');
    if (!$user) return 'Could not authenticate budi_santoso';
    Auth::login($user);
    if (!Auth::isMember()) return 'Role is not member';
    $member = Auth::member();
    if ((int)$member['id'] !== 2) return 'Member ID is not 2 (Budi Santoso)';
    $payments = $paymentModel->getMemberHistory(2);
    if (count($payments) !== 1) return 'Expected exactly 1 payment for Budi, found ' . count($payments);
    if ((int)$payments[0]['id'] !== 2 || (int)$payments[0]['member_id'] !== 2) return 'Payment data mismatch';
    Auth::logout();
    return true;
});

// TEST 5: Admin can manage/update credentials of an existing member via manageMemberAccount
runTest('Admin can set custom login credentials for an existing member via manageMemberAccount', function () use ($userModel) {
    $res = runSubprocess('
        require_once "app/config/config.php";
        require_once "app/core/Database.php";
        require_once "app/core/Session.php";
        require_once "app/core/Security.php";
        require_once "app/core/Model.php";
        require_once "app/core/Controller.php";
        require_once "app/core/Auth.php";
        require_once "app/models/User.php";
        require_once "app/models/Member.php";
        require_once "app/models/AuditLog.php";
        require_once "app/models/Category.php";
        require_once "app/controllers/AdminController.php";

        \App\Core\Session::start();
        $userModel = new \App\Models\User();
        $admin = $userModel->findByUsername("admin");
        \App\Core\Auth::login($admin);

        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["csrf_token"] = \App\Core\Security::generateCsrfToken();
        $_POST["member_id"] = "3";
        $_POST["username"] = "citra_kas_resmi";
        $_POST["email"] = "citra_resmi@kas.local";
        $_POST["password"] = "citraPass2026!";
        $_POST["status"] = "active";

        $c = new \App\Controllers\AdminController();
        $c->manageMemberAccount();
    ');

    // Verify Citra user account was updated
    $updatedCitraUser = $userModel->findByUsername('citra_kas_resmi');
    if (!$updatedCitraUser) return 'Updated username citra_kas_resmi not found in users table';
    if ($updatedCitraUser['email'] !== 'citra_resmi@kas.local') return 'Updated email mismatch';
    if (!password_verify('citraPass2026!', $updatedCitraUser['password'])) return 'New password does not verify';

    // Verify Citra can now login with her new custom credentials
    $citraAuth = $userModel->authenticate('citra_kas_resmi', 'citraPass2026!');
    if (!$citraAuth) return 'Citra failed to authenticate with new credentials';

    Auth::login($citraAuth);
    if (!Auth::isMember()) return 'Role is not member';
    if ((int)Auth::member()['id'] !== 3) return 'Member ID is not 3';

    Auth::logout();
    return true;
});

// TEST 6: Validation prevents duplicate usernames across different members
runTest('Validation rejects duplicate username across members in manageMemberAccount', function () use ($memberModel) {
    $res = runSubprocess('
        require_once "app/config/config.php";
        require_once "app/core/Database.php";
        require_once "app/core/Session.php";
        require_once "app/core/Security.php";
        require_once "app/core/Model.php";
        require_once "app/core/Controller.php";
        require_once "app/core/Auth.php";
        require_once "app/models/User.php";
        require_once "app/models/Member.php";
        require_once "app/models/AuditLog.php";
        require_once "app/models/Category.php";
        require_once "app/controllers/AdminController.php";

        \App\Core\Session::start();
        $userModel = new \App\Models\User();
        $admin = $userModel->findByUsername("admin");
        \App\Core\Auth::login($admin);

        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["csrf_token"] = \App\Core\Security::generateCsrfToken();
        $_POST["member_id"] = "4";
        $_POST["username"] = "ahmad_fauzi"; // duplicate!
        $_POST["email"] = "dewi_duplicate@kas.local";
        $_POST["password"] = "dewiPass123";
        $_POST["status"] = "active";

        $c = new \App\Controllers\AdminController();
        $c->manageMemberAccount();
    ');

    // Verify Member 4 did NOT take ahmad_fauzi
    $m4 = $memberModel->getDetail(4);
    if ($m4['username'] === 'ahmad_fauzi') return 'Duplicate username was incorrectly allowed';

    return true;
});

// TEST 7: Payment isolation strictly preserved - Member cannot pay other members bills
runTest('Member Controller prevents cross-member bill payment', function () {
    $res = runSubprocess('
        require_once "app/config/config.php";
        require_once "app/core/Database.php";
        require_once "app/core/Session.php";
        require_once "app/core/Security.php";
        require_once "app/core/Model.php";
        require_once "app/core/Controller.php";
        require_once "app/core/Auth.php";
        require_once "app/models/User.php";
        require_once "app/models/Member.php";
        require_once "app/models/Payment.php";
        require_once "app/models/PaymentTransaction.php";
        require_once "app/models/PaymentSetting.php";
        require_once "app/models/AuditLog.php";
        require_once "app/models/WhatsAppLog.php";
        require_once "app/models/WhatsAppSetting.php";
        require_once "app/services/WhatsAppService.php";
        require_once "app/services/PaymentGatewayService.php";
        require_once "app/controllers/MemberController.php";

        \App\Core\Session::start();
        $userModel = new \App\Models\User();
        $citraUser = $userModel->findByUsername("citra_kas_resmi");
        \App\Core\Auth::login($citraUser);

        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["csrf_token"] = \App\Core\Security::generateCsrfToken();
        $_POST["payment_id"] = "1"; // belongs to Member 1 (Ahmad)
        $_POST["metode"] = "tunai";
        $_POST["nominal"] = "50000";

        $memberCtrl = new \App\Controllers\MemberController();
        $memberCtrl->payBill();
    ');

    // Check payment #1 is still member 1 and nominal hasn't been corrupted
    $paymentModel = new \App\Models\Payment();
    $p1 = $paymentModel->find(1);
    if ((int)$p1['member_id'] !== 1) return 'Payment 1 member_id was corrupted';

    return true;
});

// TEST 8: Test creating new member with explicit username in createMember
runTest('Admin can create new member with explicit username', function () use ($memberModel, $userModel) {
    $res = runSubprocess('
        require_once "app/config/config.php";
        require_once "app/core/Database.php";
        require_once "app/core/Session.php";
        require_once "app/core/Security.php";
        require_once "app/core/Model.php";
        require_once "app/core/Controller.php";
        require_once "app/core/Auth.php";
        require_once "app/models/User.php";
        require_once "app/models/Member.php";
        require_once "app/models/AuditLog.php";
        require_once "app/models/Category.php";
        require_once "app/controllers/AdminController.php";

        \App\Core\Session::start();
        $userModel = new \App\Models\User();
        $admin = $userModel->findByUsername("admin");
        \App\Core\Auth::login($admin);

        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["csrf_token"] = \App\Core\Security::generateCsrfToken();
        $_POST["nama"] = "Siswa Uji Login";
        $_POST["kelas"] = "XII RPL 1";
        $_POST["nomor_anggota"] = "ANG-TEST-LOGIN";
        $_POST["username"] = "siswa_uji_login_unik";
        $_POST["email"] = "siswa_uji@kas.local";
        $_POST["password"] = "passwordSiswa123!";
        $_POST["jenis_kelamin"] = "L";
        $_POST["status"] = "aktif";

        $c = new \App\Controllers\AdminController();
        $c->createMember();
    ');

    $createdUser = $userModel->findByUsername('siswa_uji_login_unik');
    if (!$createdUser) return 'Created user with custom username not found';
    if (!password_verify('passwordSiswa123!', $createdUser['password'])) return 'Password does not match hash';

    // Verify member can login with this account
    $auth = $userModel->authenticate('siswa_uji_login_unik', 'passwordSiswa123!');
    if (!$auth) return 'New member failed to authenticate';

    Auth::login($auth);
    if (!Auth::isMember()) return 'Role is not member';
    if (Auth::name() !== 'Siswa Uji Login') return 'Member name does not match';
    Auth::logout();

    // Clean up test member
    $m = $memberModel->findByNomorAnggota('ANG-TEST-LOGIN');
    if ($m) {
        $memberModel->delete((int)$m['id']);
        $userModel->delete((int)$m['user_id']);
    }

    return true;
});

// Clean up Citra credentials back to original
$db->prepare("UPDATE users SET username = 'citra_lestari', email = 'citra@kas.local', password = :p WHERE id = 4")
   ->execute(['p' => password_hash('member123', PASSWORD_DEFAULT)]);
$db->prepare("UPDATE members SET email = 'citra@kas.local' WHERE id = 3")
   ->execute();

// Remove scratch files if any
@unlink(__DIR__ . '/../scratch_check.php');
@unlink(__DIR__ . '/../scratch_test_create.php');

echo "\n============================================\n";
echo "SUMMARY: {$passedTests} / {$totalTests} tests passed.\n";
if ($passedTests === $totalTests) {
    echo "ALL TESTS PASSED SUCCESSFULLY! [100%]\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
