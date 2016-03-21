<?php

/* ************************************************************************* */
/*                                                                           */
/*  Title:       default.lib.php                                             */
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
function error_handler($code, $message, $file, $line) {
    global $INPUT, $LINE_NUMBER;

    ob_end_clean();

    // This is for good use only with C/C++ compiler
    // that's why is won't be a standard behavior to write this to the output file
    // but to the error console
    //
    echo "/* Compiler error: */\n";
    echo "#line {$LINE_NUMBER} \"{$INPUT}\" \n";

    //
    // Show the include_path for efficient debug in case of require_* or include_* fails
    //
    if ( (2 == $code) && preg_match('/^(require|include)/', $message) ) {
        $message .= "; include_path=" . ini_get('include_path');
    }
    echo "#error \"{$message}\"\n";
    die;
}

set_error_handler('error_handler');

/**
 * Camelize dash or underscore.
 * Credit to: JP Richardson (string.js) <jprichardson@gmail.com>
 */
function camelize($text) {
    $parts = preg_split('/-|_/', $text);
    $st = array_shift($parts);
    return $st. implode('', array_map('ucfirst', $parts));
}

/**
 * Dasherize camel-case or studly caps.
 * Credit to: JP Richardson (string.js) <jprichardson@gmail.com>
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
 */
function underscore($text) {
    $st = ctype_upper(substr($text, 0, 1)) ? '_' : '';

    $text = preg_replace('/([a-z\d])([A-Z]+)/', '$1_$2', $text);
    $text = preg_replace('/([A-Z\d]+)([A-Z][a-z])/', '$1_$2', $text);
    $text = preg_replace('/[-\s]+/', '_', $text);

    return $st . strtolower($text);
}

function is_unittest() {
    $opts = getopt('', array('unittest'));
    return isset($opts['unittest']);
}

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