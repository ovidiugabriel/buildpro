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
 * @return void
 */
function error($n_tabs, $text) {
    echo tab($n_tabs) . "#error \"Fatal error: Uncaught exception 'Exception' with message '{$text}'\"";
}

//
// MAIN CODE
//

if (isset($argv[1]) && file_exists($argv[1])) {
    $INPUT = $argv[1];
    $OUTPUT = 'output.php';

    $fp = fopen($INPUT, 'r');
    if ($fp) {
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

            if (preg_match('/#define\s+([^\s]+)\s*(.*)/', $line, $matches)) {
                out($outfd,  count($stack), "define('$matches[1]', '$matches[2]')");

            } elseif (preg_match('/#if\s+(.*)/', $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out($outfd, count($stack)-1, "if ($matches[1]):");

            } elseif (preg_match('/#elif\s+(.*)/', $line, $matches)) {
                $matches[1] = preg_replace('/defined\((.*)\)/', "defined('$1')", $matches[1]);
                out($outfd, count($stack)-1, "elseif ($matches[1]):");

            } elseif (preg_match('/#endif/', $line)) {
                out($outfd, count($stack)-1, "endif;");
                array_pop($stack);

            } elseif (preg_match('/#else/', $line)) {
                out($outfd, count($stack)-1, "else:");

            } elseif (preg_match('/#ifdef\s+(.*)/', $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out($outfd, count($stack)-1, "if (defined('$matches[1]')):");

            } elseif (preg_match('/#ifndef\s+(.*)/', $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out($outfd, count($stack)-1, "if (!defined('$matches[1]')):");

            } elseif (preg_match('/#include\s+(.*)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require '$file'");

            } elseif (preg_match('/@require\s+([^;]+)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require '$file'");

            } elseif (preg_match('/#import\s+(.*)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require_once '$file'");

            } elseif (preg_match('/@require_once\s+([^;]+)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out($outfd, count($stack), "require_once '$file'");

            } elseif (preg_match('/#undef\s+(.*)/', $line, $matches)) {
                error(count($stack), 'undef');

            } elseif (preg_match('/#pragma\s+(.*)/', $line, $matches)) {
                error(count($stack), 'pragma');

            } elseif (preg_match('/@using\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_using('$matches[1]')");

            } elseif (preg_match('/@config_load\s+([^;]+)/', $line, $matches)) {
                out($outfd, count($stack), "_config_load('$matches[1]')");

            } elseif (preg_match_all('/\{\{([^\}]+)\}\}/', $line, $matches)) {
                // Expand to a PHP expression
                fwrite($outfd, preg_replace('/\{\{([^\}]+)\}\}/', '<?php echo $1 ?>', $line));

            } else {
                fwrite($outfd, "$line\n");
            }
        }

        out ($outfd, 0, "ob_end_flush()");

        fclose($outfd);
        fclose($fp);

        //
        // BEGIN TESTCODE
        //

        echo shell_exec("php {$OUTPUT} > {$INPUT}.out; echo \$?");

        echo trim(file_get_contents("{$INPUT}.out")) . "\n";
        echo "\n";
die;
        echo "Compiler output: \n";
        echo "-----------------\n";
        echo shell_exec("dmc {$INPUT}.out");


        if (file_exists("{$INPUT}.exe")) {
            echo "Program output: \n";
            echo "----------------\n";

            echo shell_exec("{$INPUT}.exe");

            unlink("{$INPUT}.exe");
            unlink("{$INPUT}.map");
            unlink("{$INPUT}.obj");
            unlink("{$INPUT}.out");
        }

        //
        // END TESTCODE
        //
    }
}
