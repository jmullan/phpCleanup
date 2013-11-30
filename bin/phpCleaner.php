<?php
require_once __DIR__ . '/../vendor/autoload.php';
if (empty($argv) || !is_array($argv) || 2 > count($argv)) {
    $input = file_get_contents('php://stdin');
} else {
    $input = file_get_contents($argv[1]);
}
$phpStringCleaner = new \PHPCleaner\StringCleaner();
echo $phpStringCleaner->magic($input);
