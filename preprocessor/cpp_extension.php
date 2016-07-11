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
/* 10.07.2016           Moved output logic to Preprocessor class             */ 
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

require_once 'Preprocessor.class.php';
require_once 'Cpp.class.php';

/*                                                                           */
/* USER DEFINED CONSTANTS                                                    */
/*                                                                           */

/**
 * Don't remove parenthesis as it will confuse the parser.
 */
const string DIRECTIVE_PREFIX   = '(#|\%:)';
const string PHP_TAG_NAME       = 'hh';
const string PHP_EXE_NAME       = 'hhvm';
const string NO_SEP             = '';  // No separator
const string SEP_SEMICOLON      = ';';

/*                                                                           */
/* --- PUBLIC OPERATIONS (GLOBAL FUNCTIONS) ---                              */
/*                                                                           */


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

        $pp = new Preprocessor($INPUT, fopen($OUTPUT, 'w'));
        $cpp = new Cpp($pp);

        if ($INCLUDE_PATH = getenv('INCLUDE_PATH')) {
            $pp->out ( 0, "ini_set('include_path', ini_get('include_path') . '" . PATH_SEPARATOR . $INCLUDE_PATH . "')");
        }

        $pp->out ( 0, '$INPUT = "'.trim($INPUT, '.\\/').'"');
        $pp->out ( 0, "ob_start()");

        $LINE_NUMBER = 0;

        $T_DIR = '^\s*' . DIRECTIVE_PREFIX;          // Directive prefix token
        $T_EXT = '^\s*@';

        while ($line = fgets($fp)) {
            $LINE_NUMBER++;

            direct_write($pp->outfd, "");
            $pp->out ( 0, '$LINE_NUMBER = ' . $LINE_NUMBER);
            direct_write($pp->outfd, "#line {$LINE_NUMBER} \"{$INPUT}\"");

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

            $f_matched = false;
            foreach ($cpp->getRules() as $rule => $func_name) {
                if (preg_match(str_replace('{T_DIR}',  $T_DIR, $rule), $line, $matches)) {
                    $cpp->$func_name($line, $matches);
                    $f_matched = true;
                    break;
                }
            }

            //
            // Extensions (not provided by the C++ preprocessor)
            //

            if ($f_matched) { /* Nothing to do for now */ }

            elseif (preg_match("/{$T_EXT}require\s+([^;]+);/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                $pp->out(count($cpp->stack), "require '$file'");

            }

            elseif (preg_match("/{$T_EXT}import\s*\[\"(.*)\"\]/", $line, $matches)) {
                $file = str_replace('.', '/', trim($matches[1], '<">'));

                $filepath  = "lib/$file.scrbl";
                $pp->out(count($cpp->stack), sprintf("require_once '%s'", run_preprocessor($filepath)));
            }

            elseif (preg_match("/{$T_EXT}require_once\s+([^;]+);/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                $pp->out(count($cpp->stack), "require_once '$file'");

            }

            elseif (preg_match("/{$T_DIR}lang (.*)/", $line, $matches)) {
                // Ignore pure Racket syntax for lang directive
                $pp->out(0, "/*  $line */");
            }

            //
            // lang directive, with scribble syntax
            //
            elseif (preg_match("/{$T_EXT}lang\s*\[?[\"\']?([A-Za-z_][A-Za-z0-9_]+)[\"\']?\]?/", $line, $matches)) {
                $lang = $matches[1];
                $pp->out ( 0, "require_once '{$lang}.lang.php'");
            }

            elseif (preg_match("/{$T_EXT}header-code\s*\{(.*)\}/", $line, $matches)) {
                direct_write($pp->outfd, trim($matches[1]));

            }

            // Arguments are not needed.
            elseif (preg_match("/{$T_EXT}debug_print_backtrace/", $line, $matches)) {
                // out ($outfd, 0, 'return array("debug_print_backtrace()" . called_at("'.$INPUT.'", $LINE_NUMBER)); ' . "/* $line */");
                $pp->out (count($cpp->stack), 'debug_print_backtrace()');
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
                direct_write($pp->outfd, replace_defines($line, $cpp->defines));
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

            $pp->out ( 0, "file_put_contents('$final_output', ob_get_clean())");
        }

        fclose($pp->outfd);
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
