<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\AiService;

$questions = [
    "Ada berapa anggota?",
    "Siapa saja nama anggota yang terdaftar?",
    "Berapa jumlah mahasiswa?",
    "Siapa saja nama mahasiswa?",
    "Ada berapa role dalam sistem?",
    "Berapa total pemasukan kas?",
    "Berapa total pengeluaran kas?",
    "Siapa saja mahasiswa yang masih memiliki tunggakan?",
];

$ai = new AiService();

foreach ($questions as $q) {
    echo "========================================\n";
    echo "Q: $q\n";
    $res = $ai->ask($q, 1, 'super_user');
    echo "INTENT DETECTED: " . ($res['metrics']['query_results']['intent'] ?? 'N/A') . "\n";
    echo "RESPONSE:\n" . $res['response'] . "\n\n";
}

