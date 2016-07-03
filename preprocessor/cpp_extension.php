#!/usr/bin/env hhvm
<?hh

/* ************************************************************************* */
/*                                                                           */
/*  Title:       cpp_extension.php                                           */
/*                                                                           */
/*  Created on:  02.06.2013 at 12:35:09                                      */
/*  Email:       ovidiugabriel@gmail.com                                     */
/*  Copyright:   (C) 2013-2016 ICE Control srl. All Rights Reserved.         */
/*                                                                           */
/*  $Id$                                                                     */
/*                                                                           */
/* ************************************************************************* */

/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* History (Start).                                                          */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/*                                                                           */
/* Date         Name    Reason                                               */
/* ------------------------------------------------------------------------- */
/* 03.07.2016           Created Cpp class for C preprocessor                 */
/* 02.06.2016           Included global code in main() function              */
/* 23.05.2016           Several updates to @import tag                       */
/* 19.03.2016           Added recursive imports                              */
/* 19.03.2016           Added @lang support                                  */
/* 16.03.2016           Added token aware define replacement                 */
/* 13.12.2015           Added digraph prefix                                 */
/* 12.12.2015           Added output buffering and error reporintg line sync */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* History (END).                                                            */
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */

/*                                                                           */

// C++ preprocessor replacement

/*
 * -----------------------------------------------------------------------------
 * Replacements for standard accepted tokens
 * -----------------------------------------------------------------------------
 * #define
 * #if
 * #elif
 * #else
 * #endif
 * #ifdef
 * #ifndef
 * #include - as in C/C++ preprocessor, here implemented via '#require'
 *
 * -----------------------------------------------------------------------------
 * Extensions
 * -----------------------------------------------------------------------------
 * #include_once - here implemented via '#require_once'
 * #import       - similar with '#include' but more sophisticated features
 * #require      - it is the same as '#include' (actually it is '#include' implementation)
 * #require_once - as '#include' with '#pragma once'
 *
 * -----------------------------------------------------------------------------
 * Advanced Extensions
 * -----------------------------------------------------------------------------
 * #debug_print_backtrace
 * @lang - specify the "language" extension to be used for preprocessing
 * @header-code - writes the given string into the output file
 *
 * -----------------------------------------------------------------------------
 * Standard directives that are Not supported.
 * -----------------------------------------------------------------------------
 * #undef
 * #pragma
 */

/*                                                                           */
/* USER DEFINED INCLUDES                                                     */
/*                                                                           */


/*                                                                           */
/* USER DEFINED CONSTANTS                                                    */
/*                                                                           */

/**
 * Don't remove parenthesis as it will confuse the parser.
 */
define ('DIRECTIVE_PREFIX', '(#|\%:)');
define ('PHP_TAG_NAME',     'hh');
define ('PHP_EXE_NAME',     'hhvm');
define ('NO_SEP', '');  // No separator
define ('SEP_SEMICOLON', ';');

/*                                                                           */
/* --- PUBLIC OPERATIONS (GLOBAL FUNCTIONS) ---                              */
/*                                                                           */

/**
 * Writes a new PHP line to the given file.
 *
 * @param resource $fp
 * @param integer $n_tabs
 * @param string $text
 * @param string $sep
 * @return void
 */
function out(resource $fp, int $n_tabs, string $text, ?string $sep = null):void {
    static $file_started = false;

    // {{{ do not remove 
    if (null === $sep) {
        $sep = SEP_SEMICOLON;
    }
    // }}}

    if (!$file_started) {
        //
        // Note that Hack-language allows only one open tag in the entire file
        // and no close tags
        //
        fwrite($fp, tab($n_tabs) . '<?'.PHP_TAG_NAME." \n\n");
        $file_started = true;
    }

    // Note that Hack-language always requires semicolon at the end of the line
    fwrite($fp, tab($n_tabs) . $text . $sep . "\n");
}

/**
 * Returns a given number of tabs.
 *
 * @param integer $size
 * @return string
 */
function tab(int $size):string {
    $result = '';
    for ($i = 0; $i < $size; $i++) {
        $result .= "    ";  // a tab is 4 spaces
    }
    return $result;
}

/**
 * Emits a preprocessing error.
 *
 * @param integer $n_tabs
 * @param string $text
 * @param string $detail
 * @return void
 * @global  $INPUT
 * @global  $LINE_NUMBER
 */
function error(int $n_tabs, string $text):void {
    $LINE_NUMBER = (int) $GLOBALS['LINE_NUMBER'];
    $INPUT       = $GLOBALS['INPUT'];

    assert($GLOBALS['outfd']);

    //
    // C++ specific error.
    //
    direct_write($GLOBALS['outfd'], tab($n_tabs) . "#line {$LINE_NUMBER} \"{$INPUT}\"");
    direct_write($GLOBALS['outfd'], tab($n_tabs) . '#error '. $text);
}

/** 
 * This function application results in decorator to be used in the generated code
 * to get the value of the defined constant name.
 *
 * @param string $value
 * @return string
 */
function define_decorator(string $value):string {
    return "' . constant('$value')  . '";
}

/**
 * Replaces in the given line all occurences of defines with their decorators.
 *
 * @param string $line
 * @param array $defines
 */
function replace_defines(string $line, array $defines = array()):string {
    if (!$line) { return $line; }   // Here we have an empty line, nothing to replace

    if (false !== strpos($line, '"')) {
        $tokens = explode('"', $line);
        $n = count($tokens);
        for ($i = 0; $i < $n; $i++) {
            if (0 == ($i % 2)) {
                $tokens[$i] = str_replace(array_keys($defines), array_values($defines), $tokens[$i]);
            }
        }
        return implode('"', $tokens);
    }
    return str_replace(array_keys($defines), array_values($defines), $line);
}

/** 
 * @param resource $fp
 * @param string $tetx
 * @return integer
 */
function direct_write(resource $fp, string $text):int {
    if (strlen($text) > 0) {
        return fwrite($fp, "echo '$text' . \"\\n\";\n");
    }
    return 0;
}


/** 
 * Used to execute generated PHP script.
 *
 * @param string $artifact
 * @return void
 */
function execute_output(string $artifact):void {
    $incl_result = include $artifact;
    if (is_array($incl_result)) {
        handle_backtrace($incl_result, 'Unknown', 0);
    }
}


//
// MAIN CODE
//

/**
 * @param integer $code
 * @param string $message
 * @param string $file
 * @param integer $line
 * @exits - exits the current process
 */
function pp_error_handler(int $code, string $message, string $file, int $line):void {
    echo "#line {$line} \"".basename($file)."\"\n";
    echo "#error \"{$message}\"\n";

    debug_print_backtrace();
    die;
}
set_error_handler('pp_error_handler');

/** 
 * @param string $input
 * @return string
 */
function run_preprocessor(string $input):string {
    $output = "output/{$input}.php";
    $cmd = sprintf('%s %s --php=%s %s', PHP_EXE_NAME, __FILE__, $output, $input);
    echo shell_exec($cmd);
    return $output;
}

class Cpp {

    private resource $outfd;
    public array $stack = array();
    public ?string $last_id = null;
    public array $defines = array();

    public function __construct() {
        $this->outfd = $GLOBALS['outfd'];
    }

    public function getRules() {
        //
        // Rules for the replacement of standard C++ preprocessor
        //
        return array(
            "/{T_DIR}define\s+([^\s]+)\s*(.*)/" =>  array($this, 'std_define'),
            "/{T_DIR}if\s+(.*)/"                =>  array($this, 'std_if'),
            "/{T_DIR}elif\s+(.*)/"              =>  array($this, 'std_elif'),
            "/{T_DIR}endif/"                    =>  array($this, 'std_endif'),
            "/{T_DIR}else/"                     =>  array($this, 'std_else'),
            "/{T_DIR}ifdef\s+(.*)/"             =>  array($this, 'std_ifdef'),
            "/{T_DIR}ifndef\s+(.*)/"            =>  array($this, 'std_ifndef'),
            "/{T_DIR}include\s+(.*)/"           =>  array($this, 'std_include'),
            "/{T_DIR}undef\s+(.*)/"             =>  array($this, 'std_undef'),
            "/{T_DIR}pragma\s+(.*)/"            =>  array($this, 'std_pragma'),
            "/{T_DIR}error\s*(.*)/"             =>  array($this, 'std_error'),
        );
    }

    public function std_define(string $line, array $matches):void {
        out($this->outfd, count($this->stack), "define('$matches[2]', '$matches[3]')");
        $this->defines[$matches[2]] = define_decorator($matches[2]);
    }

    public function std_if(string $line, array $matches):void {
        $this->last_id = uniqid();
        array_push($this->stack, $this->last_id);
        out($this->outfd, count($this->stack)-1, "if ($matches[2]) {", NO_SEP);
    }

    public function std_elif(string $line, array $matches):void {
        $matches[1] = preg_replace('/defined\((.*)\)/', "defined('$1')", $matches[1]);
        out($this->outfd, count($this->stack)-1, "elseif ($matches[2]):");
    }

    public function std_endif(string $line, array $matches):void {
        out($this->outfd, count($this->stack)-1, "}", NO_SEP);
        array_pop($this->stack);
    }

    public function std_else(string $line, array $matches):void {
        out($this->outfd, count($this->stack)-1, "else:");
    }

    public function std_ifdef(string $line, array $matches):void {
        $this->last_id = uniqid();
        array_push($this->stack, $this->last_id);
        out($this->outfd, count($this->stack)-1, "if (defined('$matches[2]')) {", NO_SEP);
    }

    public function std_ifndef(string $line, array $matches):void {
        $this->last_id = uniqid();
        array_push($this->stack, $this->last_id);
        out($this->outfd, count($this->stack)-1, "if (!defined('$matches[2]')) {", NO_SEP);
    }

    public function std_include(string $line, array $matches):void {
        $file = trim($matches[1], '<">');
        out($this->outfd, count($this->stack), "require '$file'");
    }

    public function std_undef(string $line, array $matches):void {
        error(count($this->stack), 'undef is not allowed here');
    }

    public function std_pragma(string $line, array $matches):void {
        error(count($this->stack), 'pragma is not allowed here');
    }

    public function std_error(string $line, array $matches):void {
        error(count($this->stack), trim($matches[2], '"'));
    }
}

/** 
 * @param integer $argc
 * @param array $argv
 * @return integer
 */
function main(int $argc, array $argv):int {

    if (1 == $argc) {
        echo "Usage: \n";
        echo "    ".PHP_EXE_NAME." $argv[0] [options] <file> \n";
        return 1;
    }

    //
    // Parameters:
    //
    //  --php   generates only the intermediate scripts, do not execute them
    //
    $opts = getopt('o:', array('php::'));

    $final_output = null;
    if (isset($opts['o'])) {
        $final_output = $opts['o'];
        $INPUT = $argv[3];
    } elseif (isset($opts['php'])) {
        $INPUT = $argv[2];
    } else {
        $INPUT = $argv[1];
    }

    if (!file_exists($INPUT)) {
        echo "$INPUT - No such file. \n";
        return 1;
    }

    $input_type = 'php';
    $matches = array();
    if (preg_match('/\.(.*)$/', $INPUT, $matches)) {
        $input_type = $matches[1];
    }

    $OUTPUT = dirname(__FILE__) . '/output/'. $INPUT .'.php';

    $fp = fopen($INPUT, 'r');
    if ($fp) {
        $INPUT = trim($INPUT, ".\\/");

        $dir = dirname($OUTPUT);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!is_writable($dir)) {
            echo "$OUTPUT is not writable\n";
            return 1;
        }

        $outfd = fopen($OUTPUT, 'w');

        if ((false === $outfd) || !is_resource($outfd)) {
            echo "Failed to open '$OUTPUT'\n";

            var_dump( error_get_last() );
            die;
        }
        $GLOBALS['outfd'] = $outfd;

        $cpp = new Cpp();

        if ($INCLUDE_PATH = getenv('INCLUDE_PATH')) {
            out ($outfd, 0, "ini_set('include_path', ini_get('include_path') . '" . PATH_SEPARATOR . $INCLUDE_PATH . "')");
        }

        out ($outfd, 0, '$INPUT = "'.trim($INPUT, '.\\/').'"');
        out ($outfd, 0, "ob_start()");

        $LINE_NUMBER = 0;

        $T_DIR = '^\s*' . DIRECTIVE_PREFIX;          // Directive prefix token
        $T_EXT = '^\s*@';

        while ($line = fgets($fp)) {
            $LINE_NUMBER++;

            direct_write($outfd, "");
            out ($outfd, 0, '$LINE_NUMBER = ' . $LINE_NUMBER);
            direct_write($outfd, "#line {$LINE_NUMBER} \"{$INPUT}\"");

            $line       = rtrim($line);
            $cpp->last_id    = (count($cpp->stack) > 0) ? $cpp->stack[count($cpp->stack)-1] : '';

            if ('scrbl' == $input_type) {
                $comment_delim = '@;';
            } else {
                $comment_delim = '//';
            }

            $comment = explode($comment_delim, $line);
            $has_comment = count($comment)-1;
            if ($has_comment) {
                $line = array_shift($comment);
            }
            //
            // Replacement of C++ preprocessor
            //

            if (preg_match("/{$T_DIR}define\s+([^\s]+)\s*(.*)/", $line, $matches)) {
                $cpp->std_define($line, $matches);

            } elseif (preg_match("/{$T_DIR}if\s+(.*)/", $line, $matches)) {
                $cpp->std_if($line, $matches);

            } elseif (preg_match("/{$T_DIR}elif\s+(.*)/", $line, $matches)) {
                $cpp->std_elif($line, $matches);

            } elseif (preg_match("/{$T_DIR}endif/", $line, $matches)) {
                $cpp->std_endif($line, $matches);

            } elseif (preg_match("/{$T_DIR}else/", $line, $matches)) {
                $cpp->std_else($line, $matches);

            } elseif (preg_match("/{$T_DIR}ifdef\s+(.*)/", $line, $matches)) {
                $cpp->std_ifdef($line, $matches);

            } elseif (preg_match("/{$T_DIR}ifndef\s+(.*)/", $line, $matches)) {
                $cpp->std_ifndef($line, $matches);

            } elseif (preg_match("/{$T_DIR}include\s+(.*)/", $line, $matches)) {
                $cpp->std_include($line, $matches);

            } elseif (preg_match("/{$T_DIR}undef\s+(.*)/", $line, $matches)) {
                $cpp->std_undef($line, $matches);

            } elseif (preg_match("/{$T_DIR}pragma\s+(.*)/", $line, $matches)) {
                $cpp->std_pragma($line, $matches);

            } elseif (preg_match("/{$T_DIR}error\s*(.*)/", $line, $matches)) {
                $cpp->std_error($line, $matches);

            }

            //
            // Extensions (not provided by the C++ preprocessor)
            //

            elseif (preg_match("/{$T_EXT}require\s+([^;]+);/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($cpp->stack), "require '$file'");

            }

            elseif (preg_match("/{$T_EXT}import\s*\[\"(.*)\"\]/", $line, $matches)) {
                $file = str_replace('.', '/', trim($matches[1], '<">'));

                $filepath  = "lib/$file.scrbl";
                out($outfd, count($cpp->stack), sprintf("require_once '%s'", run_preprocessor($filepath)));
            }

            elseif (preg_match("/{$T_EXT}require_once\s+([^;]+);/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($cpp->stack), "require_once '$file'");

            }

            elseif (preg_match("/{$T_DIR}lang (.*)/", $line, $matches)) {
                // Ignore pure Racket syntax for lang directive
                out ($outfd, 0, "/*  $line */");
            }

            //
            // lang directive, with scribble syntax
            //
            elseif (preg_match("/{$T_EXT}lang\s*\[?[\"\']?([A-Za-z_][A-Za-z0-9_]+)[\"\']?\]?/", $line, $matches)) {
                $lang = $matches[1];
                out ($outfd, 0, "require_once '{$lang}.lang.php'");
            }

            elseif (preg_match("/{$T_EXT}header-code\s*\{(.*)\}/", $line, $matches)) {
                direct_write($outfd, trim($matches[1]));

            }

            // Arguments are not needed.
            elseif (preg_match("/{$T_EXT}debug_print_backtrace/", $line, $matches)) {
                // out ($outfd, 0, 'return array("debug_print_backtrace()" . called_at("'.$INPUT.'", $LINE_NUMBER)); ' . "/* $line */");
                out ($outfd, count($cpp->stack), 'debug_print_backtrace()');
            }

    /*
            elseif (preg_match("/{$T_EXT}\(print\s*(.*)\)/", $line, $matches)) {
                direct_write($outfd, trim($matches[1], '"'));

            }
    */
             /* elseif (preg_match('/@using\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_using('$matches[1]')");

            } */
            /* elseif (preg_match('/\%:config_load\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_config_load('$matches[1]')");

            } */

            /*
            elseif (preg_match_all('/\{\{([^\}]+)\}\}/', $line, $matches)) {
                // Expand to print a PHP expression

            }
            */

            else {
                direct_write($outfd, replace_defines($line, $cpp->defines));
            }

            //
            // End of simple syntactical replacements
            //
        }

        if ($final_output && (!isset($opts['php']) || ('' === $opts['php'])) ) {

            $dir = dirname($final_output);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            out ($outfd, 0, "file_put_contents('$final_output', ob_get_clean())");
        }

        fclose($outfd);
        fclose($fp);

        if (isset($opts['php'])) {

            if ($opts['php']) {
                copy($OUTPUT, $opts['php']);
            } else {
                echo file_get_contents($OUTPUT);
            }
        } else {
            execute_output($OUTPUT);
        }
        // unlink($OUTPUT);
    }
    return 0;
}

exit((int) main($argc, $argv));
