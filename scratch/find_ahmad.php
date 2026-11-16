<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app'));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'ahmad') !== false) {
            echo $file->getPathname() . PHP_EOL;
            $lines = explode("\n", $content);
            foreach ($lines as $num => $line) {
                if (stripos($line, 'ahmad') !== false) {
                    echo '  Line ' . ($num + 1) . ': ' . trim($line) . PHP_EOL;
                }
            }
        }
    }
}
