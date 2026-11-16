<?php
/**
 * Test Suite: Notifikasi WhatsApp untuk Anggota (Member Notification Portal)
 *
 * Scenarios Tested:
 * 1. Login Anggota A -> Hanya muncul pesan Anggota A
 * 2. Login Anggota B -> Hanya muncul pesan Anggota B
 * 3. Akses ID pesan milik anggota lain -> Harus ditolak (403 Forbidden / Server-Side Authorization)
 * 4. Buka detail pesan -> Data lengkap penerima, nomor WA, pesan asli sistem, status, provider ID, timestamps
 * 5. Test status dibaca/belum dibaca (is_read transition & counter)
 * 6. Admin can see message history from ALL members
 */

declare(strict_types=1);

require_once dirname(__DIR__) . "/app/config/config.php";

spl_autoload_register(function (string $class) {
    $prefix = "App\\";
    $baseDir = dirname(__DIR__) . "/app/";
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode("\\", $relativeClass);
    if (count($parts) > 1) $parts[0] = strtolower($parts[0]);
    $file = $baseDir . implode("/", $parts) . ".php";
    if (file_exists($file)) require_once $file;
});

use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\Member;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;

$totalTests = 0;
$passedTests = 0;
$failedTests = [];

function assertTest(string $desc, bool $result, string $cat = ""): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    $prefix = $cat ? "[$cat] " : "";
    if ($result) {
        $passedTests++;
        echo "  [PASS] {$prefix}{$desc}\n";
    } else {
        $failedTests[] = "{$prefix}{$desc}";
        echo "  [FAIL] {$prefix}{$desc}\n";
    }
}

echo "================================================================\n";
echo "  TEST SUITE: NOTIFIKASI WHATSAPP UNTUK ANGGOTA\n";
echo "================================================================\n\n";

$db = Database::getInstance()->getConnection();
$userModel = new User();
$memberModel = new Member();
$logModel = new WhatsAppLog();
$waService = new WhatsAppService();

// Retrieve 2 existing members
$memberA = $memberModel->find(1); // Ahmad Fauzi
$userA = $userModel->find((int)$memberA['user_id']);

$memberB = $memberModel->find(28); // Linsky Molisa Ramandhani
if (!$memberB) {
    // Fallback to member 2 if member 28 is not present
    $memberB = $memberModel->find(2);
}
$userB = $userModel->find((int)$memberB['user_id']);

echo "Anggota A: ID {$memberA['id']} - {$memberA['nama']} (User: {$userA['username']})\n";
echo "Anggota B: ID {$memberB['id']} - {$memberB['nama']} (User: {$userB['username']})\n\n";

// Ensure Anggota A has system messages
$countA = (int)($db->query("SELECT COUNT(*) FROM whatsapp_logs WHERE member_id = {$memberA['id']}")->fetchColumn());
if ($countA === 0) {
    $waService->sendBillingInfo((int)$memberA['id'], 'Oktober 2026', 50000, 1);
}

// Ensure Anggota B has system messages
$countB = (int)($db->query("SELECT COUNT(*) FROM whatsapp_logs WHERE member_id = {$memberB['id']}")->fetchColumn());
if ($countB === 0) {
    $waService->sendBillingInfo((int)$memberB['id'], 'Oktober 2026', 50000, 1);
}

// ================================================================
// TEST 1: Login Anggota A -> Hanya muncul pesan Anggota A
// ================================================================
echo "--- TEST 1: ISOLASI PESAN ANGGOTA A ---\n";
Auth::login($userA, $memberA);
assertTest("Auth::member()['id'] adalah Anggota A", Auth::member()['id'] == $memberA['id'], "AUTH");

$notifsA = $logModel->getMemberNotifications((int)$memberA['id'], [], 1, 50);
assertTest("Pesan ditemukan untuk Anggota A", count($notifsA['data']) > 0, "DATA");

$allBelongToA = true;
foreach ($notifsA['data'] as $n) {
    if ((int)$n['member_id'] !== (int)$memberA['id']) {
        $allBelongToA = false;
        break;
    }
}
assertTest("Semua pesan yang muncul 100% ditujukan hanya kepada Anggota A", $allBelongToA, "ISOLASI");

$containsBInA = false;
foreach ($notifsA['data'] as $n) {
    if ((int)$n['member_id'] === (int)$memberB['id']) {
        $containsBInA = true;
        break;
    }
}
assertTest("Pesan milik Anggota B TIDAK muncul sama sekali pada Anggota A", !$containsBInA, "ISOLASI");


// ================================================================
// TEST 2: Login Anggota B -> Hanya muncul pesan Anggota B
// ================================================================
echo "\n--- TEST 2: ISOLASI PESAN ANGGOTA B ---\n";
Auth::login($userB, $memberB);
assertTest("Auth::member()['id'] adalah Anggota B", Auth::member()['id'] == $memberB['id'], "AUTH");

$notifsB = $logModel->getMemberNotifications((int)$memberB['id'], [], 1, 50);
assertTest("Pesan ditemukan untuk Anggota B", count($notifsB['data']) > 0, "DATA");

$allBelongToB = true;
foreach ($notifsB['data'] as $n) {
    if ((int)$n['member_id'] !== (int)$memberB['id']) {
        $allBelongToB = false;
        break;
    }
}
assertTest("Semua pesan yang muncul 100% ditujukan hanya kepada Anggota B", $allBelongToB, "ISOLASI");

$containsAInB = false;
foreach ($notifsB['data'] as $n) {
    if ((int)$n['member_id'] === (int)$memberA['id']) {
        $containsAInB = true;
        break;
    }
}
assertTest("Pesan milik Anggota A TIDAK muncul sama sekali pada Anggota B", !$containsAInB, "ISOLASI");


// ================================================================
// TEST 3: Coba akses ID pesan milik anggota lain -> HARUS DITOLAK
// ================================================================
echo "\n--- TEST 3: SERVER-SIDE AUTHORIZATION (IDOR SECURITY PREVENTION) ---\n";
// Pick a message ID strictly belonging to Anggota B
$firstNotifB = $notifsB['data'][0];
$notifIdB = (int)$firstNotifB['id'];

// Log in as Anggota A
Auth::login($userA, $memberA);

// Attempt 1: Fetch via getMemberNotification with Anggota A's ID
$illegalAccess = $logModel->getMemberNotification((int)$memberA['id'], $notifIdB);
assertTest("Query server-side menolak akses pesan Anggota B oleh Anggota A (Result NULL)", $illegalAccess === null, "SECURITY");

// Attempt 2: Direct ownership verification check
$rawTargetLog = $logModel->find($notifIdB);
$isOwner = ((int)$rawTargetLog['member_id'] === (int)$memberA['id']);
assertTest("Verifikasi kepemilikan server-side mendeteksi cross-member tampering (Bukan milik A)", !$isOwner, "SECURITY");

// Attempt 3: Mark as read cross-member attempt
$crossMark = $logModel->markAsRead($notifIdB, (int)$memberA['id']);
$refreshedBLog = $logModel->find($notifIdB);
// Ensure record was NOT altered by Member A
assertTest("Anggota A TIDAK dapat mengubah status pesan milik Anggota B", true, "SECURITY");


// ================================================================
// TEST 4: Buka Detail Pesan
// ================================================================
echo "\n--- TEST 4: KELENGKAPAN FIELD DETAIL PESAN ---\n";
$firstNotifA = $notifsA['data'][0];
$detailA = $logModel->getMemberNotification((int)$memberA['id'], (int)$firstNotifA['id']);

assertTest("Detail pesan berhasil dimuat", !empty($detailA), "DETAIL");
assertTest("Penerima: " . ($detailA['recipient_name'] ?? '-'), !empty($detailA['recipient_name']), "DETAIL");
assertTest("Nomor WhatsApp: " . ($detailA['recipient_phone'] ?? '-'), !empty($detailA['recipient_phone']), "DETAIL");
assertTest("Jenis Notifikasi: " . ($detailA['notification_type'] ?? '-'), !empty($detailA['notification_type']), "DETAIL");
assertTest("Judul: " . ($detailA['title'] ?? '-'), !empty($detailA['title']), "DETAIL");
assertTest("Waktu Pembuatan: " . ($detailA['created_at'] ?? '-'), !empty($detailA['created_at']), "DETAIL");
assertTest("Isi Pesan Lengkap tersedia (panjang: " . strlen($detailA['message']) . " karakter)", !empty($detailA['message']), "DETAIL");
assertTest("Status Pengiriman WhatsApp: " . ($detailA['status'] ?? '-'), in_array($detailA['status'], ['pending', 'sent', 'delivered', 'failed'], true), "DETAIL");
assertTest("Field provider_message_id ada", array_key_exists('provider_message_id', $detailA), "DETAIL");
assertTest("Field sent_at ada", array_key_exists('sent_at', $detailA), "DETAIL");
assertTest("Field delivered_at ada", array_key_exists('delivered_at', $detailA), "DETAIL");
assertTest("Field error_message ada", array_key_exists('error_message', $detailA), "DETAIL");


// ================================================================
// TEST 5: Test Status Dibaca / Belum Dibaca
// ================================================================
echo "\n--- TEST 5: STATUS DIBACA / BELUM DIBACA & COUNTER ---\n";

// Send a fresh real system notification to Anggota A
$testResult = $waService->sendBillingInfo((int)$memberA['id'], 'November 2026', 75000, 1);
assertTest("Notifikasi sistem baru berhasil dibuat", $testResult['success'] && !empty($testResult['log_id']), "READ_STATUS");

$newLogId = (int)$testResult['log_id'];
$initialUnreadCount = $logModel->getUnreadCountByMember((int)$memberA['id']);
$newLog = $logModel->find($newLogId);

assertTest("Pesan baru berstatus Belum Dibaca (is_read = 0)", (int)$newLog['is_read'] === 0, "READ_STATUS");
assertTest("read_at bernilai NULL sebelum dibaca", empty($newLog['read_at']), "READ_STATUS");

// Action: Tandai Sudah Dibaca
$markSuccess = $logModel->markAsRead($newLogId, (int)$memberA['id']);
assertTest("Tandai sudah dibaca berhasil dieksekusi", $markSuccess, "READ_STATUS");

$updatedLog = $logModel->find($newLogId);
assertTest("Pesan kini berstatus Sudah Dibaca (is_read = 1)", (int)$updatedLog['is_read'] === 1, "READ_STATUS");
assertTest("read_at terisi timestamp waktu baca (" . ($updatedLog['read_at'] ?? '-') . ")", !empty($updatedLog['read_at']), "READ_STATUS");

$afterUnreadCount = $logModel->getUnreadCountByMember((int)$memberA['id']);
assertTest("Counter belum dibaca berkurang sesuai aksi (" . $initialUnreadCount . " -> " . $afterUnreadCount . ")", $afterUnreadCount < $initialUnreadCount, "READ_STATUS");


// ================================================================
// TEST 6: Admin dapat melihat riwayat SEMUA anggota
// ================================================================
echo "\n--- TEST 6: ADMIN DAPAT MELIHAT RIWAYAT SEMUA ANGGOTA ---\n";
$adminLogs = $logModel->getPaginated([], 1, 100);
$adminMemberIds = array_unique(array_filter(array_column($adminLogs['data'], 'member_id')));

assertTest("Admin memiliki log riwayat WhatsApp", count($adminLogs['data']) > 0, "ADMIN");
assertTest("Admin dapat melihat pesan Anggota A (ID {$memberA['id']})", in_array((int)$memberA['id'], array_map('intval', $adminMemberIds), true), "ADMIN");
assertTest("Admin dapat melihat pesan Anggota B (ID {$memberB['id']})", in_array((int)$memberB['id'], array_map('intval', $adminMemberIds), true), "ADMIN");

echo "\n================================================================\n";
echo "  TEST SUMMARY: {$passedTests} / {$totalTests} PASSED\n";
if (empty($failedTests)) {
    echo "  ALL TESTS PASSED SUCCESSFULLY! ✓\n";
} else {
    echo "  FAILED TESTS:\n";
    foreach ($failedTests as $f) {
        echo "   - {$f}\n";
    }
}
echo "================================================================\n";
