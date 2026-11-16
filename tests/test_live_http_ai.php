<?php
/**
 * Test script for HTTP request to AI Assistant
 */
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
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\User;
use App\Core\Auth;
use App\Services\FinancialDataService;
use App\Services\AiService;

$userModel = new User();
$admin = $userModel->authenticate('admin', 'admin123');
Auth::login($admin);

$financialService = new FinancialDataService();
$aiService = new AiService($financialService);

$questions = [
    'Pertanyaan 1: "Siapa kamu?"' => 'Siapa kamu?',
    'Pertanyaan 2: "Apa tugas kamu?"' => 'Apa tugas kamu?',
    'Pertanyaan 3: "Apa yang bisa kamu bantu di sistem ini?"' => 'Apa yang bisa kamu bantu di sistem ini?',
    'Pertanyaan 4: "Apakah kamu bendahara?"' => 'Apakah kamu bendahara?',
    'Pertanyaan 5: "Perkenalkan dirimu."' => 'Perkenalkan dirimu.',
];

echo "=================================================================\n";
echo " PENGUJIAN 5 PERTANYAAN IDENTITAS ASISTEN AI\n";
echo "=================================================================\n\n";

foreach ($questions as $label => $q) {
    echo "-----------------------------------------------------------------\n";
    echo "{$label}\n";
    echo "-----------------------------------------------------------------\n";
    $result = $aiService->ask($q, Auth::id());
    echo "Jawaban Asisten AI:\n\"" . $result['response'] . "\"\n\n";
}
