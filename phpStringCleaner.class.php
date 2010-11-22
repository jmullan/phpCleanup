<?php
/**
 *foo
 */
class phpStringCleaner
{
    private static $stringTypes = array(
        T_INLINE_HTML,
        T_ENCAPSED_AND_WHITESPACE,
        T_CONSTANT_ENCAPSED_STRING
    );
    private static $commentTypes = array(
        T_COMMENT,
        T_DOC_COMMENT
    );

    
    public $replaces = array(
        // "\n\r" => "\n",
        'array (' => ' array(',
        'Array(' => 'array(',
        ' GLOBAL ' => ' global ',
        '){' => ') {',
        ' var $' => ' public $',
        '( ' => '( ',
        'else if' => 'elseif',
        ' stdclass' => ' stdClass',
    );

    public $regexReplaces = array(
        "/([a-zA-Z0-9]+)\s+\(/" => '$1(',        
        "/([;\s\n\r])(if|while|foreach|switch|for|list)\(/" => '$1$2 (',
        "/}[ \t\n\r]*else/" => '} else',
        "/else[ \t\n\r]*{/" => 'else {',
        "/\)[ \t]+\n/" => ")\n",
        "/;[ \t]+\n/" => ";\n",
        "/\([ \t]+/" => '(',
        "/[ \t]+\)/" => ')',
        "/=\s*&\s*new/" => '= new',
        "/function([^(]+)(\([^)]*\))[ \t\n\r]+{/" => 'function$1$2 {',
        "/([^ \t])[ \t]*=>/" => "$1 =>",
        "/=>[ \t]*([^ \t])/" => '=> $1',
    );

    public $repeatUntilUnchangedRegexes = array(
        "/ global[ \t\n\r]*([^;]+),[ \t\n\r]*/" => " global \$1;\nglobal ",
    );

    public $possibleRegexReplaces = array(
        "/<\\?php([^\n])/" => "<?php\n"
    );

    public $unconvertedRegexReplaces = array(
        'class\([^{]+\)[ \t\n\r]+{' => 'class\\1\n{',
        '\\([{};][ \t\n\r]*\\)\\(public\\|private\\|static\\|function\\|var\\|class\\|interface\\|abstract\\)'
        => "\\1\n\r?/**\n *\n */\n\\2",
        '=>\\([^ \t]\\)' => "=> \\1",
        '\\([^ \t]\\)[ \t][ \t]+=>' => '\\1 =>',
        '\n\\([ \t]\\)*//+[ \t]*\\([^\n]+\\)' => '\n\\1/* \\2 */',
        '\\*\\/\n\\([ \t]\\)*\\/\\*' => '\n*',
        '\?>[ \n\t]*\'' => '\n'
    );



    private $originalString;
    private $cleanedString;



    public $strings = array();
    public $comments = array();

    public function setOriginalString($string) {
        $this->originalString = $string;

        $tokens = token_get_all($this->originalString);
        ob_start();
        foreach ($tokens as $token) {
            if (!is_string($token)) {
                $token_value = $token[0];
                $token_text = $token[1];
                $token_line = $token[2];
                $token_name = token_name($token_value);
                
            }        
            /*
              array (
              0 => 367,
              1 => '<?php
              ',
              2 => 2,
              )*/
            if (is_string($token)) {
                echo $token;               
                // var_export($token);
            } elseif (in_array($token_value, self::$stringTypes)) {
                $md5 = md5($token_text);
                $this->strings[$md5] = $token_text;
                echo "'$md5'";
            } elseif (in_array($token_value, self::$commentTypes)) {
                $md5 = md5($token_text);
                $this->comments[$md5] = $token_text;
                echo "/*$md5*/";
            } else {
                echo $token_text;
            }
        }
        $output = ob_get_clean();
        $this->cleanedString = $output;
    }
    public function getCurrentString() {
        return $this->cleanedString;
    }
    public function replace($search, $replace) {
        $this->cleanedString = str_replace($search, $replace, $this->cleanedString);
    }
    public function regexReplace($search, $replace) {
        $this->cleanedString = preg_replace($search, $replace, $this->cleanedString);
    }
    private function reindent() {
        $tokens = token_get_all($this->cleanedString);
        foreach ($tokens as $token) {
            if (!is_string($token)) {
                $token_value = $token[0];
                $token_text = $token[1];
                $token_line = $token[2];
                $token_name = token_name($token_value);
                $token[3] = $token_name;
            }
            var_export($token);
        }
    }
    public function getCleanedString() {
        $cleanCode = $this->cleanedString;
        foreach ($this->strings as $search => $replace) {
            $cleanCode = str_replace("'$search'", $replace, $cleanCode);
        }
        foreach ($this->comments as $search => $replace) {
            $cleanCode = str_replace("/*$search*/", $replace, $cleanCode);
        }
        return $cleanCode;
    }
    public function magic($string) {
        $this->setOriginalString($string);
        foreach ($this->replaces as $search => $replace) {
            $this->replace($search, $replace);
        }
        foreach ($this->regexReplaces as $search => $replace) {
            $this->regexReplace($search, $replace);
        }
        foreach ($this->repeatUntilUnchangedRegexes as $search => $replace) {
            $limit = 10;
            $was = false;
            while (($was != $this->cleanedString) && $limit--) {
                $was = $this->cleanedString;
                $this->regexReplace($search, $replace);
            }
        }

        return $this->getCleanedString();
    }
}

