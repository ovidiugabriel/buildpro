<?php

/* ************************************************************************* */
/*                                                                           */
/*  Title:       default.lang.php                                            */
/*                                                                           */
/*  Created on:  06.03.2016 at 08:45:19                                      */
/*  Email:       ovidiugabriel@gmail.com                                     */
/*  Copyright:   (C) 2016 ICE Control srl. All Rights Reserved.              */
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
/* 16.03.2016           Added include_path hint in error message             */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* History (END).                                                            */
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */

/**
 *
 * @param  integer $code
 * @param  string $message
 * @param  string $file
 * @param  integer $line
 * @return void
 */
function error_handler($code, $message, $file, $line, $overwrite = false) {
    global $INPUT, $LINE_NUMBER;

    // echo "error_handler($code, $message, $file, $line)";

    if (!$LINE_NUMBER || $overwrite) {
        $LINE_NUMBER = $line;
    }

    if (!$INPUT || $overwrite) {
        $INPUT = $file;
    }

    if (ob_get_contents()) {
        ob_end_clean();
    }

    // This is for good use only with C/C++ compiler
    // that's why is won't be a standard behavior to write this to the output file
    // but to the error console
    //
    echo "/* Preprocessor error: */\n";
    echo "#line {$LINE_NUMBER} \"{$INPUT}\" \n";

    //
    // Show the include_path for efficient debug in case of require_* or include_* fails
    //
    if ( (2 == $code) && preg_match('/^(require|include)/', $message) ) {
        $message .= "; include_path=" . ini_get('include_path');
    }
    echo "#error \"{$message}\"\n";

    echo "/* \n";
    echo "[Backtrace] {\n";
    debug_print_backtrace();
    echo "} */\n";

    exit(1);
}

set_error_handler('error_handler');

//
// Utility functions
//

/** 
 * @param string $file
 * @param intger $line
 * @return string
 */
function called_at($file, $line) {
    return sprintf(' called at [%s:%d]', $file, (int) $line);
}

/**
 * On intermediate files:
 *
 *     return track_include('...filename...', __FILE__, __LINE__);
 *
 * On the file requesting the backtraces:
 *
 *      return array("debug_print_backtrace()" . called_at(__FILE__, __LINE__));
 *
 * @param  string $incl [description]
 * @param  string $file [description]
 * @param  integer $line [description]
 * @return array
 */
function track_include($incl, $file, $line) {
    $incl_result = include $incl;
    assert(is_array($incl_result), "is_array(include '$incl')");
    return array_merge($incl_result, array('include('.$incl.')' . called_at($file, $line) ));
}

/**
 * @param array $incl_result
 * @param string $file
 * @param integer $line
 * @return void
 */
function handle_backtrace(array $incl_result, $file, $line) {
    $backtrace = get_debug_print_backtrace($incl_result);

    if ($backtrace) {
        $error = "";
        foreach ($backtrace as $key => $text) {
            $error .= "#{$key}  {$text}\n";
        }
        error_handler(0, trim($error), $file, $line, true /* overwrite (file, line) */);
    }
}

/** 
 * Function to be called from the root of any includes, after calling track_include().
 *
 * @param array $incl_result
 * @return array
 */
function get_debug_print_backtrace(array $incl_result) {
    if (strpos($incl_result[0], 'debug_print_backtrace') === 0) {
        // array_shift($incl_result);
        return $incl_result;
    }
    return null;
}

/**
 * Camelize dash or underscore.
 * Credit to: JP Richardson (string.js) <jprichardson@gmail.com>
 * 
 * @param string $text
 * @return string
 */
function camelize($text) {
    $parts = preg_split('/-|_/', $text);
    $st = array_shift($parts);
    return $st. implode('', array_map('ucfirst', $parts));
}

/**
 * Dasherize camel-case or studly caps.
 * Credit to: JP Richardson (string.js) <jprichardson@gmail.com>
 * 
 * @param string $text
 * @return string
 */
function dasherize($text) {
    $text = preg_replace('/[_\s]+/', '-', $text);
    $text = preg_replace('/([A-Z])/', '-$1', $text);
    $text = preg_replace('/-+/', '-', $text);

    return strtolower($text);
}

/**
 * Underscore camel-case or studly caps.
 * Credit to: JP Richardson (string.js) <jprichardson@gmail.com>
 * 
 * @param string $text
 * @return string
 */
function underscore($text) {
    $st = ctype_upper(substr($text, 0, 1)) ? '_' : '';

    $text = preg_replace('/([a-z\d])([A-Z]+)/', '$1_$2', $text);
    $text = preg_replace('/([A-Z\d]+)([A-Z][a-z])/', '$1_$2', $text);
    $text = preg_replace('/[-\s]+/', '_', $text);

    return $st . strtolower($text);
}

/** 
 * @return boolean
 */
function is_unittest() {
    $opts = getopt('', array('unittest'));
    return isset($opts['unittest']);
}

//
// Unit-test:
//

if (is_unittest()) {
    assert(camelize('data_rate') == 'dataRate');
    assert(camelize('background-color') == 'backgroundColor');
    assert(camelize('-moz-something') == 'MozSomething');
    assert(camelize('_car_speed_') == 'CarSpeed');
    assert(camelize('yes_we_can') == 'yesWeCan');

    assert(dasherize('dataRate') == 'data-rate');
    assert(dasherize('CarSpeed') == '-car-speed');
    assert(dasherize('yesWeCan') == 'yes-we-can');
    assert(dasherize('backgroundColor') == 'background-color');

    assert(underscore('dataRate') == 'data_rate');
    assert(underscore('CarSpeed') == '_car_speed');
    assert(underscore('yesWeCan') == 'yes_we_can');
}

// EOF
