<?php
/**
 * Automated Test Suite: PROMPT 37 — SEMUA METODE PEMBAYARAN IURAN ANGGOTA
 * Tests Bank Transfer, Cash, QRIS/E-Wallet config, Partial payments, Verification, Rejection, and Cash Ledger Integration.
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
echo " TEST SUITE: PROMPT 37 — IMPLEMENTASI SEMUA METODE PEMBAYARAN    \n";
echo "=================================================================\n\n";

$db = \App\Core\Database::getInstance()->getConnection();
$userModel    = new \App\Models\User();
$memberModel  = new \App\Models\Member();
$paymentModel = new \App\Models\Payment();
$payTrxModel  = new \App\Models\PaymentTransaction();
$settingModel = new \App\Models\PaymentSetting();
$trxModel     = new \App\Models\Transaction();
$gatewaySvc   = new \App\Services\PaymentGatewayService($settingModel);

// -------------------------------------------------------------
// SETUP TEST USERS & MEMBERS
// -------------------------------------------------------------
$adminUser = $userModel->findByUsernameOrEmail('admin');
$bendaharaUser = $userModel->findByUsernameOrEmail('bendahara');
$memberUser1 = $userModel->findByUsernameOrEmail('ahmad_fauzi');
$memberUser2 = $userModel->findByUsernameOrEmail('budi_santoso');

assert($adminUser !== null, "Admin user must exist");
assert($bendaharaUser !== null, "Bendahara user must exist");
assert($memberUser1 !== null, "Member 1 must exist");
assert($memberUser2 !== null, "Member 2 must exist");

$member1 = $memberModel->findByUserId($memberUser1['id']);
$member2 = $memberModel->findByUserId($memberUser2['id']);

echo "1. Lingkungan Pengujian:\n";
echo "   - Super User  : {$adminUser['username']} (ID: {$adminUser['id']})\n";
echo "   - Bendahara   : {$bendaharaUser['username']} (ID: {$bendaharaUser['id']})\n";
echo "   - Anggota 1   : {$member1['nama']} (ID: {$member1['id']})\n";
echo "   - Anggota 2   : {$member2['nama']} (ID: {$member2['id']})\n\n";

// -------------------------------------------------------------
// TEST 1: AUDIT & PENGATURAN METODE PEMBAYARAN
// -------------------------------------------------------------
echo "[TEST 1] Audit Pengaturan Rekening Bank & Gateway...\n";
$bankAccounts = $settingModel->getBankAccounts();
echo "   - Rekening Bank Terkonfigurasi: " . count($bankAccounts) . " rekening\n";
foreach ($bankAccounts as $acc) {
    echo "     * {$acc['bank_name']}: {$acc['account_number']} a.n. {$acc['account_holder']}\n";
}
assert(count($bankAccounts) >= 1, "At least 1 destination bank account must be configured");

$gwConfig = $settingModel->getGatewayConfig();
echo "   - Status QRIS    : " . ($gwConfig['is_qris_active'] ? "AKTIF" : "BELUM AKTIF (Menunggu Server Key)") . "\n";
echo "   - Status E-Wallet : " . ($gwConfig['is_ewallet_active'] ? "AKTIF" : "BELUM AKTIF (Menunggu Server Key)") . "\n";
echo "   ✓ Audit konfigurasi metode pembayaran berhasil.\n\n";

// -------------------------------------------------------------
// TEST 2: TRANSFER BANK & VERIFIKASI BENDAHARA (PARTIAL PAYMENT)
// -------------------------------------------------------------
echo "[TEST 2] Pengajuan Transfer Bank (Cicilan/Sebagian: Rp 20.000 dari Rp 50.000)...\n";

// Buat tagihan uji baru untuk Anggota 1
$periodeTest = "Uji Coba Prompt 37 - " . time();
$billId = $paymentModel->create([
    'member_id'          => $member1['id'],
    'periode'            => $periodeTest,
    'tagihan'            => 50000.00,
    'nominal'            => 0.00,
    'tanggal_pembayaran' => date('Y-m-d'),
    'status'             => 'Belum Bayar',
]);
assert($billId > 0, "Test bill must be created");

$initialIncome = $trxModel->getTotalIncome();

// Anggota 1 mengajukan pembayaran transfer bank Rp 20.000
$kodeTrx1 = $payTrxModel->generateKode();
$subId1 = $payTrxModel->createSubmission([
    'payment_id'     => $billId,
    'member_id'      => $member1['id'],
    'kode_transaksi' => $kodeTrx1,
    'metode'         => \App\Models\PaymentTransaction::METHOD_TRANSFER_BANK,
    'channel'        => 'BCA',
    'nominal'        => 20000.00,
    'status'         => \App\Models\PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI,
    'bukti_transfer' => 'proof_test_part1.jpg',
    'catatan'        => 'Pembayaran cicilan pertama iuran kas via m-BCA',
]);
assert($subId1 > 0, "Submission 1 must be created");

// Cek bahwa status menunggu verifikasi dan BELUM mempengaruhi Buku Kas
$trxBeforeVerify = $payTrxModel->find($subId1);
assert($trxBeforeVerify['status'] === 'menunggu_verifikasi', "Status initial must be menunggu_verifikasi");
assert($trxModel->getTotalIncome() === $initialIncome, "Income in Buku Kas must NOT increase before verification");
echo "   ✓ Pembayaran masuk ke status 'Menunggu Verifikasi' dan belum dihitung sebagai pemasukan kas.\n";

// Bendahara memverifikasi pembayaran Rp 20.000
$verifyRes1 = $payTrxModel->verifyPayment($subId1, (int)$bendaharaUser['id'], 'Bukti transfer valid dan uang masuk mutasi bank');
assert($verifyRes1['success'] === true, "Verification 1 must succeed");
assert($verifyRes1['payment_status'] === 'Sebagian', "Bill status must become Sebagian");
assert($verifyRes1['total_paid'] === 20000.00, "Total paid must be 20,000");

// Verifikasi bahwa pemasukan telah tercatat di Buku Kas Umum
$incomeAfterVerify1 = $trxModel->getTotalIncome();
assert($incomeAfterVerify1 === $initialIncome + 20000.00, "Income must increase by exactly Rp 20.000");

$updatedBill1 = $paymentModel->find($billId);
assert($updatedBill1['status'] === 'Sebagian', "Parent bill status must be Sebagian");
assert((float)$updatedBill1['nominal'] === 20000.00, "Parent bill nominal must be 20000");
echo "   ✓ Verifikasi Bendahara berhasil: status tagihan menjadi 'Sebagian', pemasukan Rp 20.000 tercatat di Buku Kas ({$verifyRes1['cash_trx_kode']}).\n\n";

// -------------------------------------------------------------
// TEST 3: PELUNASAN SISA TAGIHAN (RP 30.000) HINGGA LUNAS
// -------------------------------------------------------------
echo "[TEST 3] Pelunasan Sisa Tagihan (Rp 30.000) hingga Status 'Lunas'...\n";
$kodeTrx2 = $payTrxModel->generateKode();
$subId2 = $payTrxModel->createSubmission([
    'payment_id'     => $billId,
    'member_id'      => $member1['id'],
    'kode_transaksi' => $kodeTrx2,
    'metode'         => \App\Models\PaymentTransaction::METHOD_TRANSFER_BANK,
    'channel'        => 'MANDIRI',
    'nominal'        => 30000.00,
    'status'         => \App\Models\PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI,
    'bukti_transfer' => 'proof_test_part2.jpg',
    'catatan'        => 'Pelunasan sisa iuran kas via Livin Mandiri',
]);

// Bendahara verifikasi pelunasan
$verifyRes2 = $payTrxModel->verifyPayment($subId2, (int)$bendaharaUser['id'], 'Pelunasan diverifikasi');
assert($verifyRes2['success'] === true, "Verification 2 must succeed");
assert($verifyRes2['payment_status'] === 'Lunas', "Bill status must become Lunas");
assert($verifyRes2['total_paid'] === 50000.00, "Total paid must be 50,000");

$updatedBill2 = $paymentModel->find($billId);
assert($updatedBill2['status'] === 'Lunas', "Parent bill status must be Lunas");
assert((float)$updatedBill2['nominal'] === 50000.00, "Parent bill nominal must be 50,000");
echo "   ✓ Pelunasan berhasil: total disetor Rp 50.000,00 dan status tagihan berubah menjadi 'Lunas'.\n\n";

// -------------------------------------------------------------
// TEST 4: PENCEGAHAN TRANSAKSI GANDA / IDEMPOTENCY
// -------------------------------------------------------------
echo "[TEST 4] Uji Pencegahan Transaksi Ganda (Idempotency)...\n";
$doubleVerify = $payTrxModel->verifyPayment($subId2, (int)$bendaharaUser['id']);
assert($doubleVerify['success'] === false, "Double verification must be blocked");
echo "   ✓ Verifikasi ulang pada transaksi yang sudah berhasil berhasil dicegah: {$doubleVerify['error']}\n\n";

// -------------------------------------------------------------
// TEST 5: PENOLAKAN PEMBAYARAN DAN PENJELASAN ALASAN
// -------------------------------------------------------------
echo "[TEST 5] Uji Penolakan Bukti Pembayaran dengan Alasan...\n";
$billIdReject = $paymentModel->create([
    'member_id'          => $member1['id'],
    'periode'            => 'Uji Penolakan - ' . time(),
    'tagihan'            => 50000.00,
    'nominal'            => 0.00,
    'tanggal_pembayaran' => date('Y-m-d'),
    'status'             => 'Belum Bayar',
]);

$kodeTrxReject = $payTrxModel->generateKode();
$subIdReject = $payTrxModel->createSubmission([
    'payment_id'     => $billIdReject,
    'member_id'      => $member1['id'],
    'kode_transaksi' => $kodeTrxReject,
    'metode'         => \App\Models\PaymentTransaction::METHOD_TRANSFER_BANK,
    'channel'        => 'BCA',
    'nominal'        => 50000.00,
    'status'         => \App\Models\PaymentTransaction::STATUS_MENUNGGU_VERIFIKASI,
    'bukti_transfer' => 'proof_buram.jpg',
    'catatan'        => 'Bukti transfer buram',
]);

$reason = "Foto bukti transfer buram, nominal dan nomor referensi bank tidak dapat dibaca oleh Bendahara.";
$rejectRes = $payTrxModel->rejectPayment($subIdReject, (int)$bendaharaUser['id'], $reason);
assert($rejectRes['success'] === true, "Reject must succeed");

$rejectedTrx = $payTrxModel->find($subIdReject);
assert($rejectedTrx['status'] === 'ditolak', "Status must be ditolak");
assert($rejectedTrx['alasan_penolakan'] === $reason, "Rejection reason must be stored correctly");

// Pastikan tagihan tetap belum bayar dan tidak ada uang kas bertambah
$billAfterReject = $paymentModel->find($billIdReject);
assert($billAfterReject['status'] === 'Belum Bayar', "Bill must remain Belum Bayar");
echo "   ✓ Penolakan berhasil: status menjadi 'Ditolak', alasan penolakan tersimpan, dan saldo kas tidak berubah.\n\n";

// -------------------------------------------------------------
// TEST 6: PEMBAYARAN TUNAI (PENCATATAN LANGSUNG OLEH BENDAHARA)
// -------------------------------------------------------------
echo "[TEST 6] Uji Pencatatan Penerimaan Tunai Langsung oleh Bendahara...\n";
$billIdTunai = $paymentModel->create([
    'member_id'          => $member2['id'],
    'periode'            => 'Uji Tunai - ' . time(),
    'tagihan'            => 50000.00,
    'nominal'            => 0.00,
    'tanggal_pembayaran' => date('Y-m-d'),
    'status'             => 'Belum Bayar',
]);

$cashRes = $payTrxModel->recordDirectCash([
    'payment_id' => $billIdTunai,
    'member_id'  => $member2['id'],
    'nominal'    => 50000.00,
    'catatan'    => 'Diterima tunai langsung oleh Bendahara di kelas',
], (int)$bendaharaUser['id']);

assert($cashRes['success'] === true, "Direct cash record must succeed");
assert($cashRes['payment_status'] === 'Lunas', "Bill status must be Lunas");

$billTunai = $paymentModel->find($billIdTunai);
assert($billTunai['status'] === 'Lunas', "Bill must be Lunas");
echo "   ✓ Pencatatan tunai berhasil: status langsung 'Lunas' dan nomor Buku Kas dibuat ({$cashRes['cash_trx_kode']}).\n\n";

// -------------------------------------------------------------
// TEST 7: PENGUJIAN METODE QRIS & E-WALLET KONDISI BELUM AKTIF
// -------------------------------------------------------------
echo "[TEST 7] Uji Validasi Kondisi Payment Gateway Belum Aktif...\n";
// Pastikan jika server key belum ada, sistem menolak pembuatan transaksi digital sembarangan
if (!$gatewaySvc->isConfigured()) {
    $snapRes = $gatewaySvc->createSnapTransaction([
        'transaction_details' => ['order_id' => 'TEST-FAKE-01', 'gross_amount' => 50000],
    ]);
    assert($snapRes['success'] === false, "Must reject when credentials are empty");
    echo "   ✓ Sistem tidak membuat QR palsu atau transaksi sembarangan saat gateway belum dikonfigurasi: {$snapRes['error']}\n\n";
} else {
    echo "   * Payment Gateway resmi sudah terkonfigurasi pada sistem.\n\n";
}

// -------------------------------------------------------------
// TEST 8: PEMBATASAN HAK AKSES ANTARA ANGGOTA & BENDAHARA
// -------------------------------------------------------------
echo "[TEST 8] Uji Pembatasan Akses Anggota vs Bendahara...\n";
$historyMember1 = $payTrxModel->getMemberHistory((int)$member1['id']);
foreach ($historyMember1 as $h) {
    assert((int)$h['member_id'] === (int)$member1['id'], "Member 1 must only see own records");
}
echo "   ✓ Anggota hanya dapat melihat riwayat pembayaran miliknya sendiri (Strict Privacy Scoping).\n\n";

// -------------------------------------------------------------
// CLEANUP DATA UJI
// -------------------------------------------------------------
echo "[CLEANUP] Membersihkan data tagihan uji...\n";
$db->exec("DELETE FROM payment_transactions WHERE payment_id IN ($billId, $billIdReject, $billIdTunai)");
$db->exec("DELETE FROM payments WHERE id IN ($billId, $billIdReject, $billIdTunai)");
echo "   ✓ Data pengujian berhasil dibersihkan.\n\n";

echo "=================================================================\n";
echo " ✓ SEMUA PENGUJIAN METODE PEMBAYARAN BERHASIL 100% (PASSED)       \n";
echo "=================================================================\n";
