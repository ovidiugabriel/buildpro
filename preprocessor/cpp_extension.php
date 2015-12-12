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
/*                                                                           */
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
 * #define
 * #include     @require
 * #import      @require_once
 * -----------------------------------------------------------------------------
 * Not supported.
 * -----------------------------------------------------------------------------
 * #undef
 * #pragma
 */

/*                                                                           */
/* --- PUBLIC OPERATIONS (GLOBAL FUNCTIONS) ---                              */
/*                                                                           */

/**
 * @param integer $n_tabs
 * @param string $text
 * @return void
 */
function out($n_tabs, $text) {
    echo tab($n_tabs) . '<?php ' . $text . " ?>\n";
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

    $fp = fopen($INPUT, 'r');
    if ($fp) {
        $stack = array();
        if (getenv('YLANG_INCLUDE_PATH')) {
            out (0, "ini_set('include_path', ini_get('include_path') . ';'. getenv('YLANG_INCLUDE_PATH'))");
        }
        out (0, "include 'ylang.lib.php'");

        while ($line = fgets($fp)) {
            $line       = rtrim($line);
            $last_id    = (count($stack) > 0) ? $stack[count($stack)-1] : '';

            if (preg_match('/#define\s+([^\s]+)\s*(.*)/', $line, $matches)) {
                out(count($stack), "define('$matches[1]', '$matches[2]') /* ".uniqid()." */");

            } elseif (preg_match('/#if\s+(.*)/', $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out(count($stack)-1, "if ($matches[1]): /* $last_id */");

            } elseif (preg_match('/#elif\s+(.*)/', $line, $matches)) {
                out(count($stack)-1, "elseif ($matches[1]): /* $last_id */");

            } elseif (preg_match('/#endif/', $line)) {
                out(count($stack)-1, "endif /* $last_id */");
                array_pop($stack);
            } elseif (preg_match('/#else/', $line)) {
                out(count($stack)-1, "else: /* $last_id */");

            } elseif (preg_match('/#ifdef\s+(.*)/', $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out(count($stack)-1, "if (defined('$matches[1]')): /* $last_id */");

            } elseif (preg_match('/#ifndef\s+(.*)/', $line, $matches)) {
                $last_id = uniqid();
                array_push($stack, $last_id);
                out(count($stack)-1, "if (!defined('$matches[1]')): /* $last_id */");

            } elseif (preg_match('/#include\s+(.*)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out(count($stack), "require '$file' /* ".uniqid()." */");

            } elseif (preg_match('/@require\s+([^;]+)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out(count($stack), "require '$file' /* ".uniqid()." */");

            } elseif (preg_match('/#import\s+(.*)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out(count($stack), "require_once '$file' /* ".uniqid()." */");

            } elseif (preg_match('/@require_once\s+([^;]+)/', $line, $matches)) {
                $file = trim($matches[1], '<">');
                out(count($stack), "require_once '$file' /* ".uniqid()." */");

            } elseif (preg_match('/#undef\s+(.*)/', $line, $matches)) {
                error(count($stack), 'undef');

            } elseif (preg_match('/#pragma\s+(.*)/', $line, $matches)) {
                error(count($stack), 'pragma');

            } elseif (preg_match('/@using\s+([^;]+)/', $line, $matches)) {
                out(count($stack), "_using('$matches[1]')");

            } elseif (preg_match('/@config_load\s+([^;]+)/', $line, $matches)) {
                out(count($stack), "_config_load('$matches[1]')");

            } elseif (preg_match_all('/\{#([^#]+)#\}/', $line, $matches)) {
                out(count($stack), "_config_var(" . var_export($matches[1], true) . ", '$line')");

            } elseif (preg_match('/@assign\s+([^\s]+)\s*([^;])/', $line, $matches)) {
                out(count($stack), "_assign('$matches[1]', '$matches[2]')");

            } elseif (preg_match_all('/\@const\(([^\}]+)\)/', $line, $matches)) {
                out(count($stack), "_constant(" . var_export($matches[1], true) . ", '$line')");

            } elseif (preg_match_all('/\{\$\.const\.([^\}]+)\}/', $line)) {
                out(count($stack), "_constant(" . var_export($matches[1], true) . ", '$line')");

            } elseif (preg_match_all('/\{\$\.now\}/', $line)) {
                // Treat predefined vars first
                echo str_replace('{$.now}', '<?php echo time() ?>', $line) . "\n";

            } elseif (preg_match_all('/\{\$([^\}]+)\}/', $line, $matches)) {
                out(count($stack), "_var(" . var_export($matches[1], true) . ", '$line')");

            } elseif (preg_match('/@const\s+([^\s]+)\s*=\s*([^;]+)/', $line, $matches)) {
                out(count($stack), "_const('$matches[1]', '$matches[2]') /* ".uniqid()." */");

            } else {
                echo "$line\n";
            }
        }

        fclose($fp);
    }
}
