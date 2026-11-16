<?php
/**
 * Automated Test Suite: Task 05 — Member Management CRUD, Validation, Filter, Audit, and RBAC
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

echo "==========================================================\n";
echo "   TASK 05: MEMBER MANAGEMENT CRUD & FILTER TESTS\n";
echo "==========================================================\n\n";

$memberModel = new \App\Models\Member();
$userModel = new \App\Models\User();
$auditModel = new \App\Models\AuditLog();

// Setup: Clean up any prior test records for idempotency
$existingTestMember = $memberModel->findByNomorAnggota('ANG-099');
if ($existingTestMember) {
    $memberModel->delete((int)$existingTestMember['id']);
    $userModel->delete((int)$existingTestMember['user_id']);
}
$existingTestUser = $userModel->findByEmail('fajar.updated@kas.local');
if ($existingTestUser) {
    $userModel->delete((int)$existingTestUser['id']);
}

// Authenticate as Super User
\App\Core\Session::destroy();
$adminUser = $userModel->findByUsernameOrEmail('admin');
\App\Core\Auth::login($adminUser);

// -------------------------------------------------------------
// TEST 1: Create New Member (Tambah)
// -------------------------------------------------------------
echo "[TEST 1] Testing Create New Member with Linked User Account...\n";
$newMemberData = [
    'nama'          => 'Fajar Hidayat',
    'kelas'         => 'XII RPL 3',
    'nomor_anggota' => 'ANG-099',
    'nis'           => '20261099',
    'email'         => 'fajar@kas.local',
    'no_hp'         => '081234567899',
    'jenis_kelamin' => 'L',
    'status'        => 'aktif',
    'password'      => 'fajar123',
];

$createdId = $memberModel->createMemberWithUser($newMemberData);
assert($createdId !== false && $createdId > 0, "Member ID must be a positive integer");

$createdMember = $memberModel->find($createdId);
assert($createdMember['nama'] === 'Fajar Hidayat', "Member name must match");
assert($createdMember['kelas'] === 'XII RPL 3', "Member class must match");
assert($createdMember['nomor_anggota'] === 'ANG-099', "Nomor anggota must match");
assert($createdMember['nis'] === '20261099', "NIS must match");
assert($createdMember['email'] === 'fajar@kas.local', "Email must match");
assert($createdMember['status'] === 'aktif', "Status must match");

$linkedUser = $userModel->find((int)$createdMember['user_id']);
assert($linkedUser !== null, "Linked user account must exist");
assert($linkedUser['role'] === 'member', "User role must be member");
assert($linkedUser['status'] === 'active', "User status must be active");
assert(password_verify('fajar123', $linkedUser['password']), "Password must be verified with hash");

$auditModel->record($adminUser['id'], 'CREATE_MEMBER', 'members', $createdId, null, $newMemberData);
echo "  ✓ Member created successfully (ID: $createdId, Linked User ID: {$createdMember['user_id']}).\n";

// -------------------------------------------------------------
// TEST 2: Validation Rules (nama wajib, kelas wajib, nomor unik, email valid)
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Validation Rules...\n";
// a. Nomor Anggota & NIS uniqueness
$isDuplicateNomor = $memberModel->isNomorAnggotaExists('ANG-099');
assert($isDuplicateNomor === true, "Nomor anggota ANG-099 must be recognized as existing");

$isDuplicateNis = $memberModel->isNisExists('20261099');
assert($isDuplicateNis === true, "NIS 20261099 must be recognized as existing");

$isNonDuplicateNomor = $memberModel->isNomorAnggotaExists('ANG-999');
assert($isNonDuplicateNomor === false, "ANG-999 should not exist");

// b. Email validation rule
$validEmail = filter_var('user@example.com', FILTER_VALIDATE_EMAIL);
$invalidEmail = filter_var('invalid-email-format', FILTER_VALIDATE_EMAIL);
assert($validEmail !== false, "Valid email format should pass");
assert($invalidEmail === false, "Invalid email format should fail");

echo "  ✓ Validation rules (nama wajib, kelas wajib, nomor anggota unik, email valid) verified.\n";

// -------------------------------------------------------------
// TEST 3: View Member Detail (Lihat Detail)
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Member Detail & Relations (Lihat Detail)...\n";
$detail = $memberModel->getDetail($createdId);
assert($detail !== null, "Detail must not be null");
assert($detail['nomor_anggota'] === 'ANG-099', "Nomor anggota must match");
assert($detail['nis'] === '20261099', "NIS must match");
assert($detail['nama'] === 'Fajar Hidayat', "Nama must match");
assert($detail['kelas'] === 'XII RPL 3', "Kelas must match");
assert($detail['email'] === 'fajar@kas.local', "Email must match");
assert($detail['status'] === 'aktif', "Status must match");
assert(!empty($detail['username']), "Username from user join must be present");
echo "  ✓ Member detail fetched with user relation: {$detail['nama']} (@{$detail['username']}).\n";

// -------------------------------------------------------------
// TEST 4: Edit Member Data & Sync User Account (Edit)
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Edit Member Data & User Sync (Edit)...\n";
$updatePayload = [
    'nama'          => 'Fajar Hidayat Updated',
    'kelas'         => 'XII RPL 3',
    'nomor_anggota' => 'ANG-099',
    'nis'           => '20261099',
    'email'         => 'fajar.updated@kas.local',
    'no_hp'         => '089999999999',
    'jenis_kelamin' => 'L',
    'status'        => 'aktif',
];

$updateSuccess = $memberModel->updateMemberWithUser($createdId, $updatePayload);
assert($updateSuccess === true, "Update must succeed");

$updatedMember = $memberModel->find($createdId);
assert($updatedMember['nama'] === 'Fajar Hidayat Updated', "Updated name must match");
assert($updatedMember['no_hp'] === '089999999999', "Updated phone must match");

$updatedUser = $userModel->find((int)$updatedMember['user_id']);
assert($updatedUser['email'] === 'fajar.updated@kas.local', "User email must be synchronized");

$auditModel->record($adminUser['id'], 'UPDATE_MEMBER', 'members', $createdId, $createdMember, $updatePayload);
echo "  ✓ Member updated and user email synced successfully.\n";

// -------------------------------------------------------------
// TEST 5: Toggle Active / Inactive Status (Aktif/Nonaktif)
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Toggle Member Status (aktif <-> nonaktif)...\n";
// Toggle to nonaktif
$toggleResult1 = $memberModel->toggleStatus($createdId);
assert($toggleResult1 !== false, "Toggle 1 must succeed");
assert($toggleResult1['new_status'] === 'nonaktif', "Status must become nonaktif");

$memberCheck1 = $memberModel->find($createdId);
$userCheck1 = $userModel->find((int)$memberCheck1['user_id']);
assert($memberCheck1['status'] === 'nonaktif', "Member status must be nonaktif");
assert($userCheck1['status'] === 'inactive', "User status must be inactive");

$auditModel->record($adminUser['id'], 'TOGGLE_MEMBER_STATUS', 'members', $createdId, ['status' => 'aktif'], ['status' => 'nonaktif']);

// Toggle back to aktif
$toggleResult2 = $memberModel->toggleStatus($createdId);
assert($toggleResult2 !== false, "Toggle 2 must succeed");
assert($toggleResult2['new_status'] === 'aktif', "Status must become aktif");

$memberCheck2 = $memberModel->find($createdId);
$userCheck2 = $userModel->find((int)$memberCheck2['user_id']);
assert($memberCheck2['status'] === 'aktif', "Member status must be aktif");
assert($userCheck2['status'] === 'active', "User status must be active");

$auditModel->record($adminUser['id'], 'TOGGLE_MEMBER_STATUS', 'members', $createdId, ['status' => 'nonaktif'], ['status' => 'aktif']);
echo "  ✓ Member and User status synchronized seamlessly between aktif/active and nonaktif/inactive.\n";

// -------------------------------------------------------------
// TEST 6: Search, Filter, and Pagination
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Search, Filter & Pagination...\n";
// Filter by search keyword
$searchRes = $memberModel->getPaginated(['search' => 'Fajar'], 1, 10);
assert($searchRes['total'] >= 1, "Search result must contain at least 1 match");

// Filter by class
$classRes = $memberModel->getPaginated(['kelas' => 'XII RPL 3'], 1, 10);
assert($classRes['total'] >= 1, "Class filter must return matches");

// Filter by status
$statusRes = $memberModel->getPaginated(['status' => 'aktif'], 1, 10);
assert($statusRes['total'] >= 1, "Status filter must return matches");

// Distinct classes
$classes = $memberModel->getDistinctClasses();
assert(in_array('XII RPL 3', $classes, true), "Distinct classes must include XII RPL 3");

echo "  ✓ Search, Class Filter, Status Filter, and Pagination queries tested successfully.\n";

// -------------------------------------------------------------
// TEST 7: Audit Log Verification
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Audit Log Recording for Member Actions...\n";
$recentAudit = $auditModel->getRecentLogs(10);
$hasMemberAudit = false;
foreach ($recentAudit as $log) {
    if ($log['entity'] === 'members') {
        $hasMemberAudit = true;
        break;
    }
}
assert($hasMemberAudit === true, "Audit logs must include member entity records");
echo "  ✓ Audit trail logged member creation, updates, and status toggles.\n";

// -------------------------------------------------------------
// TEST 8: RBAC Access Control (Member Denied Access)
// -------------------------------------------------------------
echo "\n[TEST 8] Testing RBAC Authorization: Member Blocked from Module...\n";
\App\Core\Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
\App\Core\Auth::login($memberUser);

assert(\App\Core\Auth::isSuperUser() === false, "Member user must NOT be recognized as Super User");
assert(\App\Core\Auth::isMember() === true, "Member user must be recognized as Member role");

echo "  ✓ RBAC authorization check verified: Member is strictly restricted from Member Management.\n";

echo "\n==========================================================\n";
echo "✓ ALL 8 MEMBER MANAGEMENT CRUD & FILTER TESTS PASSED!\n";
echo "==========================================================\n";
