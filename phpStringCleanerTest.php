<?php
require_once 'PHPUnit/Framework.php';
require_once 'phpStringCleaner.class.php';

class phpStringCleanerTest extends PHPUnit_Framework_TestCase {
    
    public function setup() {
        $this->cleaner = new phpStringCleaner();
    }
    private function assertMagic($expected, $input) {
        $output = $this->cleaner->magic($input);
        $this->assertEquals($expected, $output);
    }

    public function testCleansIf() {
        $this->assertMagic('<?php if (', '<?php if(');
    }
    public function testCleansWhile() {
        $this->assertMagic('<?php while (', '<?php while(');
    }
    public function testCleansForeach() {
        $input = '<?php foreach(';
        $this->assertMagic('<?php foreach (', $input);
    }
    public function testCleansSwitch() {
        $input = '<?php switch(';
        $this->assertMagic('<?php switch (', $input);
    }
    public function testCleansFor() {
        $input = '<?php for(';
        $this->assertMagic('<?php for (', $input);
    }
    public function testCleanParens() {
        $input = '<?php function foo ( $bar ) {';
        $this->assertMagic('<?php function foo($bar) {', $input);
        $input = '<?php function baz ( ' . "\n" . '   $monkey ) {';
        $this->assertMagic('<?php function baz(' . "\n" . '   $monkey) {', $input);
    }
    public function testCleansElseSpacing() {
        $input = '<?php if (true) { }   else{ }';
        $this->assertMagic('<?php if (true) { } else { }', $input);
        $input = "<?php if (true) { } \n else{ }";
        $this->assertMagic('<?php if (true) { } else { }', $input);
        $input = "<?php if (true) { } \n else if  (false) { }";
        $this->assertMagic('<?php if (true) { } elseif (false) { }', $input);
        $input = "<?php if (true) { } \n elseif (false) { }";
        $this->assertMagic('<?php if (true) { } elseif (false) { }', $input);
    }
    public function testRemoveReferencesToNew() {
        $input = '<?php $foo =& new bar();';
        $this->assertMagic('<?php $foo = new bar();', $input);
        $input = '<?php $foo = & new bar();';
        $this->assertMagic('<?php $foo = new bar();', $input);
        $input = '<?php $foo = &new bar();';
        $this->assertMagic('<?php $foo = new bar();', $input);
    }
    public function testSplitOutSimpleGlobals() {
        $input = '<?php global $foo, $bar, $baz;';
        $this->assertMagic("<?php global \$foo;\nglobal \$bar;\nglobal \$baz;", $input);
    }
    public function testSplitOutVarDeclarations() {
        $input = '<?php var $foo, $bar, $baz;';
        $this->assertMagic("<?php public \$foo;\npublic \$bar;\npublic \$baz;", $input);
    }
    public function testFormatFunctionBraces() {
        $input = "<?php function foo()\t\n \t{";
        $this->assertMagic('<?php function foo() {', $input);
    }
    public function testAssociativeArrays() {
        $input = '<?php $foo = array("foo"=>"bar", "baz"   =>     "monkey");';
        $this->assertMagic('<?php $foo = array("foo" => "bar", "baz" => "monkey");', $input);
    }
}