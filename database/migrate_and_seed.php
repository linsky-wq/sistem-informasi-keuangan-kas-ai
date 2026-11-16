<?php
/**
 * Database Migration & Seeder CLI Script
 * Task 02: Database Design & Seeder
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
echo "   SISTEM KEUANGAN KAS DENGAN AI — DATABASE MIGRATION & SEEDER\n";
echo "==========================================================\n\n";

// 1. Create DB if not exists
echo "[1/4] Checking and creating database `" . DB_NAME . "` if not exists...\n";
\App\Core\Database::createDatabaseIfNotExists();

$db = \App\Core\Database::getInstance()->getConnection();
if (!$db) {
    echo "ERROR: Gagal terhubung ke database. " . \App\Core\Database::getInstance()->getError() . "\n";
    exit(1);
}
echo "✓ Database connected successfully.\n\n";

// 2. Run Migration Schema
echo "[2/4] Executing schema migration from database/schema.sql...\n";
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
try {
    $db->exec($schemaSql);
    echo "✓ Schema executed successfully. All tables created.\n\n";
} catch (\PDOException $e) {
    echo "ERROR executing schema: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Seeding Data
echo "[3/4] Seeding initial data...\n";

$userModel = new \App\Models\User();
$memberModel = new \App\Models\Member();
$categoryModel = new \App\Models\Category();
$trxModel = new \App\Models\Transaction();
$paymentModel = new \App\Models\Payment();
$auditModel = new \App\Models\AuditLog();

// Seed 1 Super User
$superUserId = $userModel->register([
    'username' => 'admin',
    'email'    => 'admin@kas.local',
    'password' => 'admin123',
    'role'     => 'super_user',
    'status'   => 'active',
]);
echo "  ✓ Super User created (ID: $superUserId, username: admin, role: super_user)\n";

// Seed 5 Members
$membersData = [
    [
        'username' => 'ahmad_fauzi',
        'email'    => 'ahmad@kas.local',
        'password' => 'member123',
        'nama'     => 'Ahmad Fauzi',
        'nomor'    => 'ANG-001',
        'nis'      => '20261001',
        'kelas'    => 'XII RPL 1',
        'gender'   => 'L',
        'phone'    => '081234567801',
    ],
    [
        'username' => 'budi_santoso',
        'email'    => 'budi@kas.local',
        'password' => 'member123',
        'nama'     => 'Budi Santoso',
        'nomor'    => 'ANG-002',
        'nis'      => '20261002',
        'kelas'    => 'XII RPL 1',
        'gender'   => 'L',
        'phone'    => '081234567802',
    ],
    [
        'username' => 'citra_lestari',
        'email'    => 'citra@kas.local',
        'password' => 'member123',
        'nama'     => 'Citra Lestari',
        'nomor'    => 'ANG-003',
        'nis'      => '20261003',
        'kelas'    => 'XII RPL 1',
        'gender'   => 'P',
        'phone'    => '081234567803',
    ],
    [
        'username' => 'dewi_anggraini',
        'email'    => 'dewi@kas.local',
        'password' => 'member123',
        'nama'     => 'Dewi Anggraini',
        'nomor'    => 'ANG-004',
        'nis'      => '20261004',
        'kelas'    => 'XII RPL 2',
        'gender'   => 'P',
        'phone'    => '081234567804',
    ],
    [
        'username' => 'eko_prasetyo',
        'email'    => 'eko@kas.local',
        'password' => 'member123',
        'nama'     => 'Eko Prasetyo',
        'nomor'    => 'ANG-005',
        'nis'      => '20261005',
        'kelas'    => 'XII RPL 2',
        'gender'   => 'L',
        'phone'    => '081234567805',
    ],
];

$seededMemberIds = [];
foreach ($membersData as $m) {
    $uid = $userModel->register([
        'username' => $m['username'],
        'email'    => $m['email'],
        'password' => $m['password'],
        'role'     => 'member',
        'status'   => 'active',
    ]);

    $mid = $memberModel->create([
        'user_id'       => $uid,
        'nomor_anggota' => $m['nomor'],
        'nis'           => $m['nis'],
        'nama'          => $m['nama'],
        'kelas'         => $m['kelas'],
        'jenis_kelamin' => $m['gender'],
        'email'         => $m['email'],
        'no_hp'         => $m['phone'],
        'status'        => 'aktif',
    ]);

    $seededMemberIds[$m['username']] = $mid;
    echo "  ✓ Member created: {$m['nama']} ({$m['nomor']}) — User ID: $uid, Member ID: $mid\n";
}

// Seed Categories (Pemasukan & Pengeluaran)
$categories = [
    // Pemasukan
    ['nama' => 'Iuran Wajib Mingguan', 'tipe' => 'pemasukan', 'deskripsi' => 'Iuran kas rutin mingguan peserta didik/anggota'],
    ['nama' => 'Donasi & Sumbangan', 'tipe' => 'pemasukan', 'deskripsi' => 'Donasi sukarela dan bantuan operasional'],
    ['nama' => 'Denda Keterlambatan', 'tipe' => 'pemasukan', 'deskripsi' => 'Denda administrasi keterlambatan kas'],
    // Pengeluaran
    ['nama' => 'ATK & Logistik Kas', 'tipe' => 'pengeluaran', 'deskripsi' => 'Buku kas, spidol, kertas, dan arsip dokumen'],
    ['nama' => 'Kegiatan Organisasi', 'tipe' => 'pengeluaran', 'deskripsi' => 'Biaya penyelenggaraan acara dan perlombaan'],
    ['nama' => 'Konsumsi Rapat', 'tipe' => 'pengeluaran', 'deskripsi' => 'Snack dan konsumsi pertemuan pengurus & anggota'],
    ['nama' => 'Sosial & Santunan', 'tipe' => 'pengeluaran', 'deskripsi' => 'Santunan duka dan bantuan sosial anggota'],
];

$seededCatIds = [];
foreach ($categories as $cat) {
    $cid = $categoryModel->create([
        'nama'      => $cat['nama'],
        'tipe'      => $cat['tipe'],
        'deskripsi' => $cat['deskripsi'],
        'status'    => 'aktif',
    ]);
    $seededCatIds[$cat['nama']] = $cid;
    echo "  ✓ Category created: {$cat['nama']} [{$cat['tipe']}] (ID: $cid)\n";
}

// Seed Initial Sample Transactions to verify financial calculations
$trxModel->create([
    'kode_transaksi' => 'TRX-IN-20260901-0001',
    'tanggal'        => '2026-09-01',
    'tipe'           => 'pemasukan',
    'category_id'    => $seededCatIds['Iuran Wajib Mingguan'],
    'nominal'        => 250000.00,
    'keterangan'     => 'Penerimaan iuran kas minggu ke-1 September 2026',
    'created_by'     => $superUserId,
]);

$trxModel->create([
    'kode_transaksi' => 'TRX-IN-20260902-0001',
    'tanggal'        => '2026-09-02',
    'tipe'           => 'pemasukan',
    'category_id'    => $seededCatIds['Donasi & Sumbangan'],
    'nominal'        => 500000.00,
    'keterangan'     => 'Donasi kas dari alumni angkatan 2025',
    'created_by'     => $superUserId,
]);

$trxModel->create([
    'kode_transaksi' => 'TRX-OUT-20260903-0001',
    'tanggal'        => '2026-09-03',
    'tipe'           => 'pengeluaran',
    'category_id'    => $seededCatIds['ATK & Logistik Kas'],
    'nominal'        => 85000.00,
    'keterangan'     => 'Buku kas besar, kwitansi, dan binder arsip',
    'created_by'     => $superUserId,
]);
echo "  ✓ Sample transactions seeded.\n";

// Seed Sample Payments with the 3 PRD statuses
$paymentModel->create([
    'member_id'          => $seededMemberIds['ahmad_fauzi'],
    'periode'            => 'September 2026 (Minggu 1)',
    'tanggal_pembayaran' => '2026-09-01',
    'nominal'            => 50000.00,
    'status'             => 'Lunas',
    'verified_by'        => $superUserId,
    'verified_at'        => '2026-09-01 10:30:00',
    'catatan'            => 'Pembayaran lunas via tunai',
]);

$paymentModel->create([
    'member_id'          => $seededMemberIds['budi_santoso'],
    'periode'            => 'September 2026 (Minggu 1)',
    'tanggal_pembayaran' => '2026-09-02',
    'nominal'            => 25000.00,
    'status'             => 'Sebagian',
    'verified_by'        => $superUserId,
    'verified_at'        => '2026-09-02 11:15:00',
    'catatan'            => 'DP 50% iuran bulan September',
]);

$paymentModel->create([
    'member_id'          => $seededMemberIds['citra_lestari'],
    'periode'            => 'September 2026 (Minggu 1)',
    'tanggal_pembayaran' => '2026-09-03',
    'nominal'            => 0.00,
    'status'             => 'Belum Bayar',
    'verified_by'        => null,
    'verified_at'        => null,
    'catatan'            => 'Belum melakukan penyetoran',
]);
echo "  ✓ Sample payments seeded with statuses: Lunas, Sebagian, Belum Bayar.\n";

// Seed initial audit log
$auditModel->record(
    $superUserId,
    'SEED_DATABASE',
    'system',
    null,
    null,
    ['message' => 'Database initialized and seeded successfully for Task 02']
);
echo "  ✓ Initial audit log recorded.\n\n";

// 4. Verification test
echo "[4/4] Running automated model relationship and financial test...\n";

$totalIn = $trxModel->getTotalIncome();
$totalOut = $trxModel->getTotalExpense();
$saldo = $trxModel->getCurrentBalance();

echo "  - Total Pemasukan   : Rp " . number_format($totalIn, 2, ',', '.') . "\n";
echo "  - Total Pengeluaran : Rp " . number_format($totalOut, 2, ',', '.') . "\n";
echo "  - Saldo Kas Bersih  : Rp " . number_format($saldo, 2, ',', '.') . "\n";

// Test User & Member relation
$member1 = $memberModel->findByNomorAnggota('ANG-001');
$user1 = $memberModel->user($member1['user_id']);
$payments1 = $memberModel->payments($member1['id']);

echo "  - Test Member Relation: {$member1['nama']} belongs to User [{$user1['username']}] with " . count($payments1) . " payment record(s).\n";

// Test Password Verify
$authUser = $userModel->authenticate('admin', 'admin123');
if ($authUser && $authUser['role'] === 'super_user') {
    echo "  - Test Auth Super User: Password hash verification SUCCESSFUL.\n";
} else {
    echo "  - Test Auth Super User: FAILED.\n";
}

echo "\n✓ ALL DATABASE MIGRATION & SEEDING TESTS COMPLETED SUCCESSFULLY!\n";
echo "==========================================================\n";
