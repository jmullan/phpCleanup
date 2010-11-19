<?php
require_once('phpStringCleaner.class.php');
if (empty($argv) || !is_array($argv) || 2 > count($argv)) {
    echo "You must supply a file to clean\n";
    die();
}
$phpStringCleaner = new phpStringCleaner();
foreach (array_slice($argv, 1) as $filename) {
    $input = file_get_contents($filename);
    $output = $phpStringCleaner->magic($input);
    if ($input != $output) {
        $backup = $filename . '.bak';
        echo "Changed $input\n";
        file_put_contents($backup, $input) && file_put_contents($filename, $output);
    }
}