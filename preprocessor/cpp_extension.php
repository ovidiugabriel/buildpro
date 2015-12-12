<?php

/* ************************************************************************* */
/*                                                                           */
/*  Title:       cpp_extension.php.php                                       */
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
/* 13.12.2015           Added digraph prefix                                 */
/* 12.12.2015           Added output buffering and error reporintg line sync */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* History (END).                                                            */
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */

/*                                                                           */

// C++ preprocessor replacement

/*
 * #define
 * #if
 * #elif
 * #else
 * #endif
 * #ifdef
 * #ifndef
 * #include     @require
 * #import      @require_once
 *
 * @require
 * @require_once
 * -----------------------------------------------------------------------------
 * Not supported.
 * -----------------------------------------------------------------------------
 * #undef
 * #pragma
 */


/*                                                                           */
/* USER DEFINED CONSTANTS                                                    */
/*                                                                           */

define ('DIRECTIVE_PREFIX', '(#|\%:)');

define ('DEFAULT_PHP_LIBRARY', 'default.lib.php');

/*                                                                           */
/* --- PUBLIC OPERATIONS (GLOBAL FUNCTIONS) ---                              */
/*                                                                           */

/**
 * @param resource $fp
 * @param integer $n_tabs
 * @param string $text
 * @return void
 */
function out($fp, $n_tabs, $text) {
    fwrite($fp, tab($n_tabs) . '<?php ' . $text . " ?>\n");
}

/**
 * @param integer $size
 * @return string
 */
function tab($size) {
    $result = '';
    for ($i = 0; $i < $size; $i++) {
        $result .= "    ";
    }
    return $result;
}

/**
 * @param integer $n_tabs
 * @param string $text
 * @param string $detail
 * @return void
 */
function error($n_tabs, $text, $detail = null) {
    global $INPUT, $LINE_NUMBER;

    echo "|\n";
    echo "| *** Compiler error: \n";
    echo "|\n";
    echo tab($n_tabs) . "#line {$LINE_NUMBER} \"{$INPUT}\" \n";
    if (null == $detail) {
        echo tab($n_tabs) . "#error \"Fatal error: Uncaught exception 'Exception' with message '{$text}'\"\n";
    } else {
        echo tab($n_tabs) . "#error \"{$detail}\"\n";
    }

    die;
}

//
// MAIN CODE
//

if (isset($argv[1]) && file_exists($argv[1])) {
    $INPUT = $argv[1];
    $OUTPUT = 'output.php';

    $fp = fopen($INPUT, 'r');
    if ($fp) {
        $INPUT = trim($INPUT, ".\\/");
        $outfd = fopen($OUTPUT, 'w');

        $stack = array();
        if (getenv('INCLUDE_PATH')) {
            out ($outfd, 0, "ini_set('include_path', ini_get('include_path') . ';'. getenv('INCLUDE_PATH'))");
        }
        if (defined('DEFAULT_PHP_LIBRARY') && DEFAULT_PHP_LIBRARY) {
            out ($outfd, 0, "include '" . DEFAULT_PHP_LIBRARY . "'");
        }

        out ($outfd, 0, '$INPUT = "'.trim($INPUT, '.\\/').'"');
        out ($outfd, 0, "ob_start()");

        $LINE_NUMBER = 0;

        $T_DIR = DIRECTIVE_PREFIX;          // Directive prefix token

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
                error(count($stack), 'undef');

            } elseif (preg_match("/{$T_DIR}pragma\s+(.*)/", $line, $matches)) {
                error(count($stack), 'pragma');

            } elseif (preg_match("/{$T_DIR}error\s*(.*)/", $line, $matches)) {
                error(count($stack), 'error', $matches[2]);

            }

            //
            // Extensions (not provided by the C++ preprocessor)
            //

            elseif (preg_match('/\%:require\s+([^;]+)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require '$file'");

            } elseif (preg_match("/{$T_DIR}import\s+(.*)/", $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require_once '$file'");

            } elseif (preg_match('/\%:require_once\s+([^;]+)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require_once '$file'");


            } elseif (preg_match('/\%:using\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_using('$matches[1]')");

            } elseif (preg_match('/\%:config_load\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_config_load('$matches[1]')");

            } elseif (preg_match_all('/\{\{([^\}]+)\}\}/', $line, $matches)) {
                // Expand to print a PHP expression
                fwrite($outfd, preg_replace('/\{\{([^\}]+)\}\}/', '<?php echo $1 ?>', $line));

            } else {
                fwrite($outfd, "$line\n");
            }
        }

        out ($outfd, 0, "file_put_contents('{$INPUT}.out', ob_get_clean())");

        fclose($outfd);
        fclose($fp);

        //
        // BEGIN TESTCODE
        //

        // TODO: output.php will generate output into a file
        // and print errors to stderr.


        if (file_exists("{$INPUT}.out")) {
            unlink("{$INPUT}.out");
        }
        echo shell_exec("php {$OUTPUT}");

        if (file_exists("{$INPUT}.out")) {
            echo "File contents: \n";
            echo "-----------------\n";
            echo trim(file_get_contents("{$INPUT}.out")) . "\n";
            echo "\n";
        }

        //
        // END TESTCODE
        //
    }
}
