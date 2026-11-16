<?php
/**
 * Verification script for Chatbot++ enhancements.
 * Executes the 29 query‑based checks described in the specification
 * and compares the chatbot response with a direct SQL result.
 *
 * No code modifications are performed; only read‑only queries are used.
 */

require_once __DIR__.'/../app/config/config.php';

spl_autoload_register(function(string $class){
    $prefix = 'App\\';
    $baseDir = __DIR__.'/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative = substr($class, $len);
    $parts = explode('\\', $relative);
    foreach ($parts as $i=>$p){ if ($i < count($parts)-1) $parts[$i] = strtolower($p); }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require $file;
});

use App\Services\AiService;
use App\Core\Database;

$passed = 0; $failed = 0;
function assertTest(string $desc, bool $cond, string $detail=''){
    global $passed,$failed;
    if($cond){ echo "  [PASS] $desc\n"; $passed++; }
    else{ echo "  [FAIL] $desc -> $detail\n"; $failed++; }
}

$db = Database::getInstance()->getConnection();
$ai = new AiService();

// Helper to run direct SQL and return scalar / list
function fetchScalar(string $sql, array $p=[]){
    global $db; $stmt=$db->prepare($sql); $stmt->execute($p); return $stmt->fetchColumn();
}
function fetchColumnArray(string $sql, array $p=[]){
    global $db; $stmt=$db->prepare($sql); $stmt->execute($p); return array_column($stmt->fetchAll(PDO::FETCH_ASSOC),0);
}

/** 1. Role information **/
// a) count roles defined in users table (distinct role values)
$roleCount = fetchScalar('SELECT COUNT(DISTINCT role) FROM users');
$res = $ai->ask('Ada berapa role dalam sistem?',1,'super_user');
assertTest('Ada berapa role dalam sistem?', strpos($res['response'], (string)$roleCount)!==false, "Expected $roleCount, got {$res['response']}");

// b) list role names
$roleNames = fetchColumnArray('SELECT DISTINCT role FROM users');
$res = $ai->ask('Apa saja nama role yang tersedia?',1,'super_user');
foreach($roleNames as $r){
    assertTest("Role name $r present", strpos(strtolower($res['response']), strtolower($r))!==false, "Missing $r");
}

// c) number of users per role
foreach($roleNames as $r){
    $cnt = fetchScalar('SELECT COUNT(*) FROM users WHERE role = ?',[$r]);
    $res = $ai->ask("Berapa jumlah pengguna pada role $r?",1,'super_user');
    assertTest("User count for $r", strpos($res['response'], (string)$cnt)!==false, "Expected $cnt, got {$res['response']}");
}

/** 2. Member information **/
$memberCount = fetchScalar('SELECT COUNT(*) FROM members');
$res = $ai->ask('Ada berapa anggota dalam sistem?',1,'super_user');
assertTest('Member count', strpos($res['response'], (string)$memberCount)!==false);

// list all member names (limit first 20 for brevity)
$memberNames = fetchColumnArray('SELECT nama FROM members ORDER BY nama LIMIT 20');
$res = $ai->ask('Tampilkan seluruh nama anggota.',1,'super_user');
foreach($memberNames as $n){
    assertTest("Member name $n present", strpos(strtolower($res['response']), strtolower($n))!==false);
}

// active vs inactive members (status column)
$activeCnt = fetchScalar('SELECT COUNT(*) FROM members WHERE status = "aktif"');
$res = $ai->ask('Berapa anggota aktif?',1,'super_user');
assertTest('Active member count', strpos($res['response'], (string)$activeCnt)!==false);

/** 3. Mahasiswa (students) – identified by non‑null nis **/
$studentCnt = fetchScalar('SELECT COUNT(*) FROM members WHERE nis IS NOT NULL AND nis != ""');
$res = $ai->ask('Ada berapa mahasiswa yang terdaftar?',1,'super_user');
assertTest('Student count', strpos($res['response'], (string)$studentCnt)!==false);

/** 4. Tagihan & payment calculations **/
// total tagihan all payments
$totalTagihan = fetchScalar('SELECT COALESCE(SUM(tagihan),0) FROM payments');
$res = $ai->ask('Berapa total tagihan seluruh mahasiswa?',1,'super_user');
assertTest('Total tagihan', strpos($res['response'], number_format($totalTagihan,0,',','.'))!==false);

// total dibayar
$totalPaid = fetchScalar('SELECT COALESCE(SUM(nominal),0) FROM payments');
$res = $ai->ask('Berapa total pembayaran yang sudah diterima?',1,'super_user');
assertTest('Total dibayar', strpos($res['response'], number_format($totalPaid,0,',','.'))!==false);

// total tunggakan = sum(tagihan - nominal) where positive
$totalTunggakan = fetchScalar('SELECT COALESCE(SUM(GREATEST(0, tagihan - nominal)),0) FROM payments');
$res = $ai->ask('Berapa total tunggakan seluruh mahasiswa?',1,'super_user');
assertTest('Total tunggakan', strpos($res['response'], number_format($totalTunggakan,0,',','.'))!==false);

/** 5. Permission check – member role cannot see others **/
// Simulate a member user (id=2 assumed member) – we use role member
$res = $ai->ask('Berapa anggota aktif?',2,'member');
assertTest('Member cannot see global count', strpos(strtolower($res['response']),'maaf')!==false, 'Should be denied');

/** 6. Multi‑turn context example **/
$turn1 = $ai->ask('Ada berapa mahasiswa yang memiliki tunggakan?',1,'super_user');
$cntTunggakan = fetchScalar('SELECT COUNT(*) FROM members WHERE nis IS NOT NULL AND nis != "" AND id IN (SELECT member_id FROM payments WHERE (tagihan - nominal) > 0)');
assertTest('Multi‑turn count', strpos($turn1['response'], (string)$cntTunggakan)!==false);
$turn2 = $ai->ask('Siapa saja namanya?',1,'super_user',$turn1['metrics']['current_context']??null);
$names = fetchColumnArray('SELECT m.nama FROM members m JOIN payments p ON m.id=p.member_id WHERE (p.tagihan - p.nominal) > 0 GROUP BY m.id LIMIT 10');
foreach($names as $n){ assertTest("Student name $n in context", strpos(strtolower($turn2['response']), strtolower($n))!==false); }

// Summary of verification run
echo "\nRESULTS: $passed PASSED | $failed FAILED\n";
?>
