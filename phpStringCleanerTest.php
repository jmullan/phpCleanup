<?php
require_once 'PHPUnit/Framework.php';
require_once 'phpStringCleaner.class.php';

class phpStringCleanerTest extends PHPUnit_Framework_TestCase
{
    
    public function setup() {
        $this->cleaner = new phpStringCleaner();
    }
    private function assertMagic($expected, $input)
    {
        $output = $this->cleaner->magic($input);
        $this->assertEquals($expected, $output);
    }

    public function testCleansIf()
    {
        $this->assertMagic('<?php if (', '<?php if(');
    }
    public function testCleansWhile()
    {
        $this->assertMagic('<?php while (', '<?php while(');
    }
    public function testCleansForeach()
    {
        $input = '<?php foreach(';
        $this->assertMagic('<?php foreach (', $input);
    }
    public function testCleansSwitch()
    {
        $input = '<?php switch(';
        $this->assertMagic('<?php switch (', $input);
    }
    public function testCleansFor()
    {
        $input = '<?php for(';
        $this->assertMagic('<?php for (', $input);
    }
    public function testCleansElseSpacing()
    {
        $input = '<?php if (true) { }   else{ }';
        $this->assertMagic('<?php if (true) { } else { }', $input);
    }
}