<?php
require_once('phpStringCleaner.class.php');
$string = file_get_contents(realpath(__FILE__));
$phpStringCleaner = new phpStringCleaner();
$codeOnly = $phpStringCleaner->setOriginalString($string);

$a = array('a' => 'foo');
$b = "bar";
$testString = "foo $a[a] bar" . "baz ${b} monkey" . 'pirate "ninja" lion' . "zombie 'samurai' wolf";
$testString .= <<<EOF
    wibble unicorn bigfoot
EOF;
ob_start();
?>
WTF SON? IS THE BROOKLYN ZOO IN THE MF HOUSE?
<?php
ob_get_clean();
$phpStringCleaner->reindent();
echo $phpStringCleaner->getCleanedString();
