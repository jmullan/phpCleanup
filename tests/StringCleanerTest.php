<?php
namespace tests;

class StringCleanerTest extends \PHPUnit_Framework_TestCase
{

    public function setup()
    {
    }

    private function assertMagic($expected, $input)
    {
        $this->cleaner = new \PHPCleanup\StringCleaner();
        $output = $this->cleaner->magic($input);
        $this->assertEquals($expected, $output);
    }
    public function testSingleQuotes()
    {
        $this->assertMagic('<?php echo \'foo\'; ?>', '<?php echo "foo"; ?>');
        $this->assertMagic('<?php echo "$bar"; ?>', '<?php echo "$bar"; ?>');
        $this->assertMagic('<?php echo "baz\n"; ?>', '<?php echo "baz\n"; ?>');
        $this->assertMagic('<?php echo "\'monkey\'"; ?>', '<?php echo "\'monkey\'"; ?>');
        $this->assertMagic('<?php echo \'"monkey"\'; ?>', '<?php echo "\"monkey\""; ?>');
        $this->assertMagic('<?php echo "\"$monkey\""; ?>', '<?php echo "\"$monkey\""; ?>');
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
    public function testCleanParens()
    {
        $input = '<?php function foo ( $bar ) {';
        $this->assertMagic('<?php function foo($bar)' . "\n" . '{', $input);
        $input = '<?php function baz ( ' . "\n" . '   $monkey ) {';
        $this->assertMagic('<?php function baz(' . "\n" . '   $monkey)' . "\n" .'{', $input);
        $input = '<?php $foo = (1 + 2); ';
        $this->assertMagic('<?php $foo = (1 + 2); ', $input);
    }
    public function testCleansElseSpacing()
    {
        $input = '<?php if (true) { }   else{ }';
        $this->assertMagic('<?php if (true) { } else { }', $input);
        $input = "<?php if (true) { } \n else{ }";
        $this->assertMagic('<?php if (true) { } else { }', $input);
        $input = "<?php if (true) { } \n else if  (false) { }";
        $this->assertMagic('<?php if (true) { } elseif (false) { }', $input);
        $input = "<?php if (true) { } \n elseif (false) { }";
        $this->assertMagic('<?php if (true) { } elseif (false) { }', $input);
    }
    public function testRemoveReferencesToNew()
    {
        $input = '<?php $foo =& new bar();';
        $this->assertMagic('<?php $foo = new bar(); ', $input);
        $input = '<?php $foo = & new bar();';
        $this->assertMagic('<?php $foo = new bar(); ', $input);
        $input = '<?php $foo = &new bar();';
        $this->assertMagic('<?php $foo = new bar(); ', $input);
    }
    public function testSplitOutSimpleGlobals()
    {
        $input = '<?php global $foo, $bar, $baz;';
        $this->assertMagic("<?php global \$foo;\nglobal \$bar;\nglobal \$baz; ", $input);
    }
    public function testSplitOutVarDeclarations()
    {
        $input = '<?php var $foo, $bar, $baz;';
        $this->assertMagic("<?php public \$foo;\npublic \$bar;\npublic \$baz; ", $input);

        $input = '<?php var $foo = array(1, 2, 3); ';
        $this->assertMagic('<?php public $foo = array(1, 2, 3); ', $input);
    }
    public function testFunctionBraces()
    {
        $input = "<?php function foo()\t\n \t{";
        $this->assertMagic('<?php function foo()' . "\n" . '{', $input);
    }
    public function testAssociativeArrays()
    {
        $input = '<?php $foo = array("foo"=>"bar", "baz"   =>     "monkey"); ';
        $this->assertMagic('<?php $foo = array(\'foo\' => \'bar\', \'baz\' => \'monkey\'); ', $input);
    }
    public function testCommaSpacing()
    {
        $input = '<?php foo("bar" ,"baz");';
        $this->assertMagic('<?php foo(\'bar\', \'baz\'); ', $input);
        $input = "<?php foo(1\n ,2);";
        $this->assertMagic("<?php foo(1,\n 2); ", $input);
    }
    public function testEqualsSpacing()
    {
        $input = '<?php $foo="bar"; $baz=="monkey";  $pirate = 1 && (2 || 3)';
        $this->assertMagic(
            '<?php $foo = \'bar\'; $baz == \'monkey\'; $pirate = 1 && (2 || 3)',
            $input
        );
    }
    public function testBang()
    {
        $input = '<?php $foo= ! $bar; $baz!="monkey";';
        $this->assertMagic('<?php $foo = !$bar; $baz != \'monkey\'; ', $input);
    }
    public function testSemicolonSpacing()
    {
        $input = '<?php echo 1 ;echo 2;  echo 3;';
        $this->assertMagic('<?php echo 1; echo 2; echo 3; ', $input);
        $input = "<?php echo 4 ;\n echo 5; \n echo 6;\n";
        $this->assertMagic("<?php echo 4;\n echo 5;\n echo 6;\n", $input);
    }
    public function testShortTags()
    {
        if (ini_get('short_open_tag')) {
            $input = '<?="foo" ?>bar<? = "baz"; ?>';
            $this->assertMagic('<?= "foo" ?>bar<?= "baz"; ?>', $input);

            $input = '<?echo 1; ?>';
            $this->assertMagic('<?php echo 1; ?>', $input);

        }
    }
    public function testCasting()
    {
        $input = '<?php $foo = "1"; echo ( int ) $foo; ?>';
        $this->assertMagic('<?php $foo = \'1\'; echo (int) $foo; ?>', $input);
        $input = '<?php $foo = "1"; echo  (int)  $bar; ?>';
        $this->assertMagic('<?php $foo = \'1\'; echo (int) $bar; ?>', $input);
        $input = '<?php $foo = ( (int)4); ?>';
        $this->assertMagic('<?php $foo = ((int) 4); ?>', $input);
    }
    public function testFixTokens()
    {
        $input = '<?php foreach (ARRAY(1, 2) AS $foo) {} ?>';
        $this->assertMagic('<?php foreach (array(1, 2) as $foo) {} ?>', $input);
    }
    public function testClearsSemiColonBrace()
    {
        $input = "<?php function foo {\n    echo 'bar';} ?>";
        $this->assertMagic("<?php function foo {\n    echo 'bar';\n} ?>", $input);
    }
}
