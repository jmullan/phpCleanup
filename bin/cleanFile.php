<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (empty($argv) || !is_array($argv) || 2 > count($argv)) {
    echo "You must supply a file to clean\n";
    die();
}

foreach (array_slice($argv, 1) as $filename) {
    $phpStringCleaner = new \PHPCleanup\StringCleaner();
    $input = file_get_contents($filename);
    $output = $phpStringCleaner->magic($input);
    if ($input != $output) {
        $backup = $filename . '.bak';
        echo "Changed $filename\n";
        file_put_contents($backup, $input) && file_put_contents($filename, $output);
    }
}
