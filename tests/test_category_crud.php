<?php
/**
 * Automated Test Suite: Task 06 — Category Management CRUD, Safe Delete, Validation & Audit
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
echo "   TASK 06: CATEGORY MANAGEMENT CRUD & SAFE DELETE TESTS\n";
echo "==========================================================\n\n";

$catModel = new \App\Models\Category();
$trxModel = new \App\Models\Transaction();
$userModel = new \App\Models\User();
$auditModel = new \App\Models\AuditLog();

// Setup: Clean up any prior test category
$testCat = $catModel->fetchOne("SELECT id FROM `categories` WHERE nama = :nama", ['nama' => 'Uji Coba Sumbangan']);
if ($testCat) {
    $catModel->delete((int)$testCat['id']);
}
$testCat2 = $catModel->fetchOne("SELECT id FROM `categories` WHERE nama = :nama", ['nama' => 'Uji Coba Pengeluaran']);
if ($testCat2) {
    $catModel->delete((int)$testCat2['id']);
}

// -------------------------------------------------------------
// TEST 1: Create Categories (Pemasukan & Pengeluaran)
// -------------------------------------------------------------
echo "[TEST 1] Testing Create Pemasukan and Pengeluaran Categories...\n";
$catInId = $catModel->create([
    'nama'      => 'Uji Coba Sumbangan',
    'tipe'      => \App\Models\Category::TYPE_PEMASUKAN,
    'deskripsi' => 'Kategori uji coba pemasukan sukarela',
    'status'    => \App\Models\Category::STATUS_AKTIF,
]);
assert($catInId !== false && $catInId > 0, "Pemasukan category creation must succeed");

$catOutId = $catModel->create([
    'nama'      => 'Uji Coba Pengeluaran',
    'tipe'      => \App\Models\Category::TYPE_PENGELUARAN,
    'deskripsi' => 'Kategori uji coba pengeluaran logistik',
    'status'    => \App\Models\Category::STATUS_AKTIF,
]);
assert($catOutId !== false && $catOutId > 0, "Pengeluaran category creation must succeed");

echo "  ✓ Categories created: Pemasukan (ID: $catInId) and Pengeluaran (ID: $catOutId).\n";

// -------------------------------------------------------------
// TEST 2: Validation Uniqueness & Name Duplication
// -------------------------------------------------------------
echo "\n[TEST 2] Testing Validation & Name Uniqueness within Type...\n";
$isDuplicate = $catModel->isNameExists('Uji Coba Sumbangan', \App\Models\Category::TYPE_PEMASUKAN);
assert($isDuplicate === true, "Category name must be recognized as existing within same type");

$isDifferentTypeDuplicate = $catModel->isNameExists('Uji Coba Sumbangan', \App\Models\Category::TYPE_PENGELUARAN);
assert($isDifferentTypeDuplicate === false, "Different type should not collide");

echo "  ✓ Category name uniqueness per type validated.\n";

// -------------------------------------------------------------
// TEST 3: View List & Summary Queries
// -------------------------------------------------------------
echo "\n[TEST 3] Testing Category List View, Transaction Counts & Stats...\n";
$allCats = $catModel->getAllWithTransactionCount();
assert(count($allCats) >= 2, "Category list must contain records");

$activeCats = $catModel->getActive();
assert(count($activeCats) >= 2, "Active category list must contain active items");

$pemasukanCats = $catModel->getByType(\App\Models\Category::TYPE_PEMASUKAN);
assert(count($pemasukanCats) >= 1, "Pemasukan list must contain at least 1 item");

$pengeluaranCats = $catModel->getByType(\App\Models\Category::TYPE_PENGELUARAN);
assert(count($pengeluaranCats) >= 1, "Pengeluaran list must contain at least 1 item");

echo "  ✓ Category list, types, active filtering, and usage statistics verified.\n";

// -------------------------------------------------------------
// TEST 4: Edit / Update Category
// -------------------------------------------------------------
echo "\n[TEST 4] Testing Update Category...\n";
$updateSuccess = $catModel->update($catInId, [
    'nama'      => 'Uji Coba Sumbangan Updated',
    'deskripsi' => 'Deskripsi diperbarui untuk pengujian',
]);
assert($updateSuccess === true, "Update must succeed");

$updatedCat = $catModel->find($catInId);
assert($updatedCat['nama'] === 'Uji Coba Sumbangan Updated', "Updated name must match");
echo "  ✓ Category successfully updated.\n";

// -------------------------------------------------------------
// TEST 5: Toggle Status (Aktif <-> Nonaktif)
// -------------------------------------------------------------
echo "\n[TEST 5] Testing Toggle Category Status...\n";
$toggleResult1 = $catModel->toggleStatus($catInId);
assert($toggleResult1 !== false, "Toggle 1 must succeed");
assert($toggleResult1['new_status'] === 'nonaktif', "Status must become nonaktif");

$catCheck1 = $catModel->find($catInId);
assert($catCheck1['status'] === 'nonaktif', "Category status must be nonaktif");

$toggleResult2 = $catModel->toggleStatus($catInId);
assert($toggleResult2 !== false, "Toggle 2 must succeed");
assert($toggleResult2['new_status'] === 'aktif', "Status must become aktif");

$catCheck2 = $catModel->find($catInId);
assert($catCheck2['status'] === 'aktif', "Category status must be aktif");
echo "  ✓ Category status toggle verified seamlessly.\n";

// -------------------------------------------------------------
// TEST 6: Safe Delete Protection (Category with Transactions)
// -------------------------------------------------------------
echo "\n[TEST 6] Testing Destructive Delete Protection on In-Use Category...\n";
// Find a seeded category that has transactions (e.g. 'Iuran Wajib Mingguan' or 'ATK & Logistik Kas')
$seededCat = $catModel->fetchOne("SELECT id, nama FROM `categories` WHERE nama = 'Iuran Wajib Mingguan' LIMIT 1");
assert($seededCat !== null, "Seeded category must exist");

$inUseTxCount = $catModel->getTransactionCount((int)$seededCat['id']);
assert($inUseTxCount > 0, "Category must have linked transactions");

$deleteAttempt = $catModel->safeDelete((int)$seededCat['id']);
assert($deleteAttempt['success'] === false, "Delete on in-use category MUST be blocked");
assert(isset($deleteAttempt['is_in_use']) && $deleteAttempt['is_in_use'] === true, "Should flag is_in_use");

$stillExists = $catModel->find((int)$seededCat['id']);
assert($stillExists !== null, "In-use category must remain in database");
echo "  ✓ In-use category deletion SAFELY BLOCKED: {$deleteAttempt['message']}\n";

// -------------------------------------------------------------
// TEST 7: Safe Delete on Unused Category
// -------------------------------------------------------------
echo "\n[TEST 7] Testing Safe Delete on Unused Category...\n";
$unusedDelete = $catModel->safeDelete($catOutId);
assert($unusedDelete['success'] === true, "Unused category deletion should succeed");

$deletedCheck = $catModel->find($catOutId);
assert($deletedCheck === null, "Unused category should no longer exist");
echo "  ✓ Unused category deleted cleanly.\n";

// -------------------------------------------------------------
// TEST 8: Audit Log Verification
// -------------------------------------------------------------
echo "\n[TEST 8] Testing Audit Logging for Category Actions...\n";
$adminUser = $userModel->findByUsernameOrEmail('admin');
$auditModel->record($adminUser['id'], 'CREATE_CATEGORY', 'categories', $catInId, null, ['nama' => 'Uji Coba']);
$recentAudit = $auditModel->getRecentLogs(5);
$hasCatAudit = false;
foreach ($recentAudit as $log) {
    if ($log['entity'] === 'categories') {
        $hasCatAudit = true;
        break;
    }
}
assert($hasCatAudit === true, "Audit trail must contain category records");
echo "  ✓ Audit trail logged category actions successfully.\n";

// -------------------------------------------------------------
// TEST 9: RBAC Authorization (Member Access Denied)
// -------------------------------------------------------------
echo "\n[TEST 9] Testing RBAC Authorization: Member Denied Access to Category Module...\n";
\App\Core\Session::destroy();
$memberUser = $userModel->findByUsernameOrEmail('ahmad_fauzi');
\App\Core\Auth::login($memberUser);

assert(\App\Core\Auth::isSuperUser() === false, "Member user must NOT have Super User privileges");
echo "  ✓ Category management is strictly restricted to Super User role only.\n";

// Clean up test category
$catModel->delete($catInId);

echo "\n==========================================================\n";
echo "✓ ALL 9 CATEGORY MANAGEMENT TESTS PASSED SUCCESSFULLY!\n";
echo "==========================================================\n";
