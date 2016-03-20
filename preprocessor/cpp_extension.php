#!/usr/bin/php
<?php

/* ************************************************************************* */
/*                                                                           */
/*  Title:       cpp_extension.php                                           */
/*                                                                           */
/*  Created on:  02.06.2013 at 12:35:09                                      */
/*  Email:       ovidiugabriel@gmail.com                                     */
/*  Copyright:   (C) 2013-2015 ICE Control srl. All Rights Reserved.         */
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
 * #include
 * -----------------------------------------------------------------------------
 * Extensions
 * -----------------------------------------------------------------------------
 * @include_once
 * @import
 * @require_once
 * @require
 * @require_once
 * @lang - specify the "language" extension to be used for preprocessing
 * @headerCode - writes the given string into the output file
 * -----------------------------------------------------------------------------
 * Not supported.
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

/*                                                                           */
/* --- PUBLIC OPERATIONS (GLOBAL FUNCTIONS) ---                              */
/*                                                                           */

/**
 * Writes a new PHP line to the given file.
 *
 * @param resource $fp
 * @param integer $n_tabs
 * @param string $text
 * @return void
 */
function out($fp, $n_tabs, $text) {
    fwrite($fp, tab($n_tabs) . '<?php ' . $text . " ?>\n");
}

/**
 * Returns a given number of tabs.
 *
 * @param integer $size
 * @return string
 */
function tab($size) {
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
function error($n_tabs, $text) {
    global $INPUT, $LINE_NUMBER;
    global $outfd;

    //
    // C++ specific error.
    //
    direct_write($outfd, tab($n_tabs) . "#line {$LINE_NUMBER} \"{$INPUT}\"");
    direct_write($outfd, tab($n_tabs) . '#error '. $text);
}

/**
 * @param string $line
 * @param array $defines
 */
function replace_defines($line = '', array $defines) {
    if (!$line) { return $line; }

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

function direct_write($fp, $text) {
    return fwrite($fp, $text . "\n");
}

//
// MAIN CODE
//


function pp_error_handler($code, $message, $file, $line) {
    echo "#line {$line} \"".basename($file)."\"\n";
    echo "#error \"{$message}\"\n";
    die;
}
set_error_handler('pp_error_handler');


if (1 == $argc) {
    echo "Usage: \n";
    echo "    php $argv[0] [options] <file> \n";
    die;
}

$opts = getopt('o:');

$final_output = null;
if (isset($opts['o'])) {
    $final_output = $opts['o'];
    $INPUT = $argv[3];
} else {
    $INPUT = $argv[1];
}

if (file_exists($INPUT)) {
    $OUTPUT = 'output/'.sha1(uniqid()).'.php';

    $fp = fopen($INPUT, 'r');
    if ($fp) {
        $INPUT = trim($INPUT, ".\\/");
        $outfd = fopen($OUTPUT, 'w');

        $stack = array();
        if ($INCLUDE_PATH = getenv('INCLUDE_PATH')) {
            out ($outfd, 0, "ini_set('include_path', ini_get('include_path') . '" . PATH_SEPARATOR . $INCLUDE_PATH . "')");
        }

        out ($outfd, 0, '$INPUT = "'.trim($INPUT, '.\\/').'"');
        out ($outfd, 0, "ob_start()");

        $LINE_NUMBER = 0;

        $T_DIR = '^\s*' . DIRECTIVE_PREFIX;          // Directive prefix token
        $T_EXT = '^\s*@';

        $defines = array();

        while ($line = fgets($fp)) {
            $LINE_NUMBER++;

            out ($outfd, 0, '$LINE_NUMBER = ' . $LINE_NUMBER);

            $line       = rtrim($line);
            $last_id    = (count($stack) > 0) ? $stack[count($stack)-1] : '';

            $comment = explode('//', $line);
            $has_comment = count($comment)-1;

            if ($has_comment) {
                $line = array_shift($comment);
            }

            //
            // Replacement of C++ preprocessor
            //

            if (preg_match("/{$T_DIR}define\s+([^\s]+)\s*(.*)/", $line, $matches)) {
                out($outfd,  count($stack), "define('$matches[2]', '$matches[3]')");
                $defines[$matches[2]] = "<?php echo {$matches[2]} ?>";

            } elseif (preg_match("/{$T_DIR}if\s+(.*)/", $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out($outfd, count($stack)-1, "if ($matches[2]):");

            } elseif (preg_match("/{$T_DIR}elif\s+(.*)/", $line, $matches)) {
                $matches[1] = preg_replace('/defined\((.*)\)/', "defined('$1')", $matches[1]);
                out($outfd, count($stack)-1, "elseif ($matches[2]):");

            } elseif (preg_match("/{$T_DIR}endif/", $line)) {
                out($outfd, count($stack)-1, "endif;");
                array_pop($stack);

            } elseif (preg_match("/{$T_DIR}else/", $line)) {
                out($outfd, count($stack)-1, "else:");

            } elseif (preg_match("/{$T_DIR}ifdef\s+(.*)/", $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out($outfd, count($stack)-1, "if (defined('$matches[2]')):");

            } elseif (preg_match("/{$T_DIR}ifndef\s+(.*)/", $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out($outfd, count($stack)-1, "if (!defined('$matches[2]')):");

            } elseif (preg_match("/{$T_DIR}include\s+(.*)/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require '$file'");

            } elseif (preg_match("/{$T_DIR}undef\s+(.*)/", $line, $matches)) {
                error(count($stack), 'undef is not allowed here');

            } elseif (preg_match("/{$T_DIR}pragma\s+(.*)/", $line, $matches)) {
                error(count($stack), 'pragma is not allowed here');

            } elseif (preg_match("/{$T_DIR}error\s*(.*)/", $line, $matches)) {
                error(count($stack), trim($matches[2], '"'));

            }

            //
            // Extensions (not provided by the C++ preprocessor)
            //

            elseif (preg_match("/{$T_EXT}require\s+([^;]+);/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require '$file'");

            } elseif (preg_match("/{$T_EXT}import\s+([^;]+);/", $line, $matches)) {
                $file = str_replace('.', '/', trim($matches[1], '<">'));
                $child = shell_exec(sprintf("php %s lib/$file", basename(__FILE__)));

                direct_write($outfd, $child);

            } elseif (preg_match("/{$T_EXT}require_once\s+([^;]+);/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require_once '$file'");

            } elseif (preg_match("/{$T_EXT}lang\s*\(?[\"\']?([A-Za-z_][A-Za-z0-9_]+)[\"\']?\)?;?/", $line, $matches)) {
                $lang = $matches[1];
                out ($outfd, 0, "include '{$lang}.lang.php'");

            } elseif (preg_match("/{$T_EXT}headerCode\(\"(.*)\"\)/", $line, $matches)) {
                direct_write($outfd, $matches[1]);

            } elseif (preg_match("/{$T_EXT}helloworld/", $line, $matches)) {
                //
                // Making a good joke: https://dzone.com/articles/predictions-for-java-20
                //
                $text = preg_replace("/{$T_EXT}helloworld/", 'fprintf(stdout, "%s\n", "Hello world!")', $line);
                direct_write($outfd, $text);

            }

             /* elseif (preg_match('/@using\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_using('$matches[1]')");

            } */
            /* elseif (preg_match('/\%:config_load\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_config_load('$matches[1]')");

            } */
            elseif (preg_match_all('/\{\{([^\}]+)\}\}/', $line, $matches)) {
                // Expand to print a PHP expression
                direct_write($outfd, preg_replace('/\{\{([^\}]+)\}\}/', '<?php echo $1 ?>', $line));

            } else {
                direct_write($outfd, "#line {$LINE_NUMBER} \"{$INPUT}\"");
                direct_write($outfd, replace_defines($line, $defines));
            }
        }

        if ($final_output) {
            $dir = dirname($final_output);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            out ($outfd, 0, "file_put_contents('$final_output', ob_get_clean())");
        }

        fclose($outfd);
        fclose($fp);

        echo shell_exec("php {$OUTPUT}");
        unlink($OUTPUT);
    }
}
