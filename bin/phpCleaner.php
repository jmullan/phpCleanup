<?php
require_once('phpStringCleaner.class.php');
if (empty($argv) || !is_array($argv) || 2 > count($argv)) {
    $input = file_get_contents('php://stdin');
} else {
    $input = file_get_contents($argv[1]);
}
$phpStringCleaner = new phpStringCleaner();
echo $phpStringCleaner->magic($input);
