<?php
/**
 * @TODO backticks
 * @TODO space after semicolon in for arguments
 * @TODO negative sign
 * @TODO increment / decrement
 * @TODO logical operators
 * @TODO array brackets
 * @TODO dot (concatenation)
 * @TODO short tags
 * @TODO casting (int) etc.
 * @TODO comma followed by close parenthesis
 * @TODO array brackets
 * @TODO ternary operators
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

    private static $phpKeywords = array(
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'case',
        'catch',
        'cfunction',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'do',
        'else',
        'elseif',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'extends',
        'final',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'interface',
        'instanceof',
        'namespace',
        'new',
        'old_function',
        'or',
        'private',
        'protected',
        'public',
        'static',
        'switch',
        'throw',
        'try',
        'use',
        'var',
        'while',
        'xor'
    );

    private static $phpLanguageConstructs = array(
        'die',
        'echo',
        'empty',
        'exit',
        'eval',
        'include',
        'include_once',
        'isset',
        'list',
        'require',
        'require_once',
        'return',
        'print',
        'unsetpub'
    );

    private static $phpControlStructures = array(
        'if',
        'else',
        'elseif',
        'else if',
        'while',
        'do while',
        'for',
        'foreach',
        'break',
        'continue',
        'switch',
        'declare',
        'return',
        'require',
        'include',
        'require_once',
        'include_once',
        'goto'
    );

    private static $phpOpenTags = array(
        '<?php',
        '<?=',
        '<?'
    );

    private static $phpComparison = array(
        '===',
        '!==',
        '!=',
        '==',
        '<=',
        '>=',
        '<>',
        '&&',
        '||'
    );

    private static $phpAssignment = array(
        '<<=',
        '>>=',
        '=>',
        '+=',
        '-=',
        '*=',
        '/=',
        '%=',
        '.=',
        '&=',
        '|=',
        '^=',
        '=&',
        '='
    );

    private static $phpBitwise = array(
        '<<', '>>', '&', '|', '^', '~'
    );

    private static $phpArithmetic = array(
        '+', '-', '*', '/', '%'
    );

    private static $phpErrorControl = array('@');

    private static $replaces = array(
        // "\r\n" => "\n",
        ' array (' => ' array(',
        ' Array(' => ' array(',
        ' GLOBAL ' => ' global ',
        '){' => ') {',
        ' var $' => ' public $',
        '( ' => '( ',
        'else if' => 'elseif',
        ' stdclass' => ' stdClass'
    );

    private static $regexReplaces = array(
        "/([a-zA-Z0-9]+)\s+\(/" => '$1(',        
        "/([;\s\r\n])()\(/" => '$1$2 (',
        "/}[ \t\r\n]*else/" => '} else',
        "/else[ \t\r\n]*{/" => 'else {',
        "/\)[ \t]+(\r?\n)/" => ")$1",
        "/([ \t\r\n]*)([\r\n])([ \t]*)([,;])([ \t]*)/" => '$4$1$2$3$5',
        "/[ \t]*([,;])[ \t]*/" => '$1 ',
        "/[ \t]*([,;])[ \t]*(\r?\n)/" => '$1$2',
        "/\([ \t]+([\S|\n])/" => '($1',
        "/(\S)[ \t]+\)/" => '$1)',
        "/=\s*&\s*new/" => '= new',
        "/function([^(]+)(\([^)]*\))[ \t\r\n]+{/" => 'function$1$2 {',
        "/!\s+/" => '!',
        "/<\?[ \t]*=[ \t]*/" => '<?= '
    );

    private static $repeatUntilUnchangedRegexes = array(
        "/ (global|var|public)[ \t\r\n]*\\$([^;]+),[ \t\r\n]*/" => " \$1 \$\$2;\n\$1 ",
    );

    private static $possibleRegexReplaces = array(
        "/<\\?php([^\n])/" => "<?php\n"
    );

    private static $unconvertedRegexReplaces = array(
        'class\([^{]+\)[ \t\r\n]+{' => 'class\\1\n{',
        '\\([{};][ \t\r\n]*\\)\\(public\\|private\\|static\\|function\\|var\\|class\\|interface\\|abstract\\)'
        => "\\1\r\n?/**\n *\n */\n\\2",
        '=>\\([^ \t]\\)' => "=> \\1",
        '\\([^ \t]\\)[ \t][ \t]+=>' => '\\1 =>',
        '\n\\([ \t]\\)*//+[ \t]*\\([^\n]+\\)' => '\n\\1/* \\2 */',
        '\\*\\/\n\\([ \t]\\)*\\/\\*' => '\n*',
        '\?>[ \n\t]*\'' => '\n'
    );

    private static $initialized = false;

    private $originalString;
    private $cleanedString;

    public $strings;
    public $comments;

    public function __construct() {

        if (!self::$initialized) {
            $spaceOkay = array('array');
            $spaceBeforeParens = array_diff(
                array_merge(self::$phpControlStructures, self::$phpKeywords),
                $spaceOkay
            );
            $fixLanguageConstructsRegex
                = '/\b(' . join('|', array_map('self::preg_quote_map', $spaceBeforeParens)) . ")[ \t\r\n]*\(/S";
            self::$regexReplaces[$fixLanguageConstructsRegex] = '$1 (';

            $equalsSymbols = array_merge(self::$phpComparison, self::$phpAssignment);

            $fixEqualsSymbols = '/[ \t]*(' . join('|', array_map('self::preg_quote_map', $equalsSymbols)) . ')[ \t]*/S';
            self::$regexReplaces[$fixEqualsSymbols] = ' $1 ';

            self::$regexReplaces['/<\? =/'] = '<?=';

            self::$initialized = true;
        }

        $this->originalString = '';
        $this->cleanedString = '';

        $this->strings = array();
        $this->comments = array();

    }

    private function preg_quote_map($string) {
        return preg_quote($string, '/');
    }

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
            if (is_string($token)) {
                echo $token;               
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
        foreach (self::$replaces as $search => $replace) {
            $this->replace($search, $replace);
        }
        foreach (self::$regexReplaces as $search => $replace) {
            $this->regexReplace($search, $replace);
        }
        foreach (self::$repeatUntilUnchangedRegexes as $search => $replace) {
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

